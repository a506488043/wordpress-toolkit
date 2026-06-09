<?php
/**
 * AI Manager - WordPress 6.9 AI集成核心管理器
 * 统一管理AI提供商、请求处理和响应格式化
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_AI_Manager
{
    use Saiita_AI_Toolkit_Singleton;

    /**
     * AI提供商实例
     */
    private $providers = array();

    /**
     * 默认配置
     */
    private $default_config = array(
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 1000, // 毫秒
        'cache_enabled' => true,
        'cache_ttl' => 3600, // 1小时
    );

    /**
     * 初始化AI管理器
     */
    protected function init($args = array())
    {
        // 加载AI提供商
        $this->load_providers();

        // 注册WordPress 6.9 Abilities API支持（必须在正确的action中）
        if ($this->is_wordpress_69()) {
            add_action('wp_abilities_api_init', array($this, 'register_abilities_api'));
        }

        // 添加AJAX处理
        add_action('wp_ajax_saiita_ai_toolkit_generate', array($this, 'handle_ajax_request'));
    }

    /**
     * 检查是否为WordPress 6.9+
     */
    private function is_wordpress_69()
    {
        return version_compare(get_bloginfo('version'), '6.9', '>=');
    }

    /**
     * 加载AI提供商
     */
    private function load_providers()
    {
        // Provider类已在主入口加载，此处仅注册实例
        $this->register_provider('deepseek', new Saiita_AI_Toolkit_DeepSeek_Provider());
        $this->register_provider('siliconflow', new Saiita_AI_Toolkit_SiliconFlow_Provider());
        $this->register_provider('openai', new Saiita_AI_Toolkit_OpenAI_Provider());

        // 允许第三方注册自定义提供商
        do_action('saiita_ai_content_toolkit_register_providers', $this);
    }

    /**
     * 注册AI提供商
     */
    public function register_provider($name, Saiita_AI_Toolkit_Provider_Base $provider)
    {
        $this->providers[$name] = $provider;
        return true;
    }

    /**
     * 获取AI提供商
     */
    public function get_provider($name)
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * 获取所有提供商
     */
    public function get_providers()
    {
        return $this->providers;
    }

    /**
     * 清理 AI 选项数据
     */
    private function sanitize_ai_options($options)
    {
        if (!is_array($options)) {
            return array();
        }

        $sanitized = array();

        // 清理字符串字段
        $string_fields = array('provider', 'model', 'system_prompt', 'api_base', 'api_key');
        foreach ($string_fields as $field) {
            if (isset($options[$field])) {
                $sanitized[$field] = sanitize_text_field($options[$field]);
            }
        }

        // 清理文本区域字段
        if (isset($options['prompt'])) {
            $sanitized['prompt'] = sanitize_textarea_field($options['prompt']);
        }

        // 清理数值字段
        $numeric_fields = array('max_tokens', 'temperature', 'top_p', 'frequency_penalty', 'presence_penalty');
        foreach ($numeric_fields as $field) {
            if (isset($options[$field])) {
                if (in_array($field, array('temperature', 'top_p', 'frequency_penalty', 'presence_penalty'), true)) {
                    $sanitized[$field] = floatval($options[$field]);
                } else {
                    $sanitized[$field] = intval($options[$field]);
                }
            }
        }

        // 清理布尔字段
        $boolean_fields = array('stream', 'fallback_to_simple', 'cache_enabled');
        foreach ($boolean_fields as $field) {
            if (isset($options[$field])) {
                $sanitized[$field] = rest_sanitize_boolean($options[$field]);
            }
        }

        // 清理 URL 字段
        if (isset($options['callback_url'])) {
            $sanitized['callback_url'] = esc_url_raw($options['callback_url']);
        }

        // 清理上下文数组
        if (isset($options['context']) && is_array($options['context'])) {
            $sanitized['context'] = array_map('sanitize_text_field', $options['context']);
        }

        // 保留其他未识别的字段（但不清理，应由调用者负责）
        foreach ($options as $key => $value) {
            if (!isset($sanitized[$key])) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * 生成AI内容
     */
    public function generate_content($prompt, $options = array())
    {
        // 获取配置
        $config = wp_parse_args($options, $this->default_config);

        // 获取活动提供商
        $provider_name = $this->get_active_provider();
        $provider = $this->get_provider($provider_name);

        if (!$provider) {
            return new WP_Error('no_provider', '未找到可用的AI提供商');
        }

        // 检查缓存
        $cache_key = 'ai_content_' . md5($prompt . serialize($config));
        if ($config['cache_enabled']) {
            $cached = get_transient($cache_key);
            if ($cached) {
                return $cached;
            }
        }

        try {
            // 准备请求
            $request = $this->prepare_request($prompt, $config);

            // 执行请求（带重试）
            $response = $this->execute_with_retry($provider, $request, $config);

            // 处理响应
            $result = $this->process_response($response, $provider);

            // 缓存结果
            if ($config['cache_enabled'] && !is_wp_error($result)) {
                set_transient($cache_key, $result, $config['cache_ttl']);
            }

            // 记录日志
            Saiita_AI_Toolkit_Logger::info(
                "AI内容生成成功",
                'ai_manager',
                array(
                    'provider' => $provider_name,
                    'prompt_length' => strlen($prompt),
                    'response_length' => strlen($result['content'] ?? '')
                )
            );

            return $result;
        } catch (Exception $e) {
            Saiita_AI_Toolkit_Logger::error(
                "AI内容生成失败: " . $e->getMessage(),
                'ai_manager',
                array(
                    'provider' => $provider_name,
                    'prompt' => substr($prompt, 0, 100) . '...'
                )
            );

            return new WP_Error('generation_failed', $e->getMessage());
        }
    }

    /**
     * 流式生成内容
     */
    public function generate_content_stream($prompt, $callback, $options = array())
    {
        $provider_name = $this->get_active_provider();
        $provider = $this->get_provider($provider_name);

        if (!$provider || !$provider->supports_streaming()) {
            return new WP_Error('streaming_not_supported', '当前提供商不支持流式生成');
        }

        try {
            $request = $this->prepare_request($prompt, $options, true);
            return $provider->stream_generate($request, $callback);
        } catch (Exception $e) {
            return new WP_Error('streaming_failed', $e->getMessage());
        }
    }

    /**
     * 准备AI请求
     */
    private function prepare_request($prompt, $config, $stream = false)
    {
        return array(
            'prompt' => $prompt,
            'model' => $config['model'] ?? $this->get_default_model(),
            'max_tokens' => $config['max_tokens'] ?? 2000,
            'temperature' => $config['temperature'] ?? 0.7,
            'top_p' => $config['top_p'] ?? 1,
            'stream' => $stream,
            'system_prompt' => $config['system_prompt'] ?? $this->get_default_system_prompt(),
            'context' => $config['context'] ?? array(),
        );
    }

    /**
     * 执行带重试的请求
     */
    private function execute_with_retry($provider, $request, $config)
    {
        $attempts = 0;
        $max_attempts = $config['retry_attempts'];
        $delay = $config['retry_delay'];

        while ($attempts < $max_attempts) {
            try {
                return $provider->generate($request);
            } catch (Exception $e) {
                $attempts++;

                if ($attempts >= $max_attempts) {
                    throw $e;
                }

                // 指数退避
                usleep($delay * 1000 * pow(2, $attempts - 1));
            }
        }

        throw new Exception('重试次数已达上限');
    }

    /**
     * 处理AI响应
     */
    private function process_response($response, $provider)
    {
        if (is_wp_error($response)) {
            return $response;
        }

        // 标准化响应格式
        return array(
            'content' => $provider->extract_content($response),
            'tokens_used' => $provider->get_token_usage($response),
            'model' => $provider->get_model_used($response),
            'provider' => get_class($provider),
            'metadata' => $provider->get_metadata($response),
            'generated_at' => current_time('mysql'),
        );
    }

    /**
     * 获取活动提供商
     */
    private function get_active_provider()
    {
        return get_option('saiita_ai_content_toolkit_active_provider', 'deepseek');
    }

    /**
     * 获取默认模型
     */
    private function get_default_model()
    {
        $provider = $this->get_active_provider();
        $config = get_option("saiita_ai_content_toolkit_{$provider}_config", array());
        return $config['model'] ?? 'default';
    }

    /**
     * 获取默认系统提示
     */
    private function get_default_system_prompt()
    {
        return '你是一个专业的内容创作助手，擅长生成高质量、有深度的文章内容。';
    }

    /**
     * 注册WordPress 6.9 Abilities API
     */
    public function register_abilities_api()
    {
        if (!function_exists('wp_register_ability')) {
            return;
        }

        // 注册内容生成能力
        call_user_func('wp_register_ability', 'saiita-ai-content-toolkit/ai-content-generation', array(
            'label'               => __('AI内容生成', 'saiita-ai-content-toolkit'),
            'description'         => __('使用AI生成高质量内容', 'saiita-ai-content-toolkit'),
            'category'            => 'content',
            'execute_callback'    => array($this, 'ability_generate_content'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));

        // 注册内容优化能力
        call_user_func('wp_register_ability', 'saiita-ai-content-toolkit/ai-content-optimization', array(
            'label'               => __('AI内容优化', 'saiita-ai-content-toolkit'),
            'description'         => __('使用AI优化现有内容', 'saiita-ai-content-toolkit'),
            'category'            => 'optimization',
            'execute_callback'    => array($this, 'ability_optimize_content'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));

        // 注册SEO分析能力
        call_user_func('wp_register_ability', 'saiita-ai-content-toolkit/ai-seo-analysis', array(
            'label'               => __('AI SEO分析', 'saiita-ai-content-toolkit'),
            'description'         => __('使用AI进行SEO分析', 'saiita-ai-content-toolkit'),
            'category'            => 'seo',
            'execute_callback'    => array($this, 'ability_analyze_seo'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));
    }

    /**
     * Abilities API回调：内容生成
     */
    public function ability_generate_content($params)
    {
        $prompt = $params['prompt'] ?? '';
        $options = $params['options'] ?? array();

        return $this->generate_content($prompt, $options);
    }

    /**
     * Abilities API回调：内容优化
     */
    public function ability_optimize_content($params)
    {
        $content = $params['content'] ?? '';
        $options = wp_parse_args($params['options'] ?? array(), array(
            'system_prompt' => '请优化以下内容，使其更加清晰、准确和有吸引力：'
        ));

        $prompt = $options['system_prompt'] . "\n\n原始内容：\n" . $content;
        return $this->generate_content($prompt, $options);
    }

    /**
     * Abilities API回调：SEO分析
     */
    public function ability_analyze_seo($params)
    {
        $content = $params['content'] ?? '';
        $options = wp_parse_args($params['options'] ?? array(), array(
            'system_prompt' => '请分析以下内容的SEO表现，并提供改进建议：',
            'temperature' => 0.3
        ));

        $prompt = $options['system_prompt'] . "\n\n要分析的内容：\n" . $content;
        return $this->generate_content($prompt, $options);
    }

    /**
     * 处理AJAX请求
     */
    public function handle_ajax_request()
    {
        Saiita_AI_Toolkit_Security_Validator::verify_admin_ajax('saiita_ai_content_toolkit_generate');

        if (!isset($_POST['prompt'])) { // phpcs:ignore WordPress.Security.NonceVerification
            wp_send_json_error('提示词不能为空');
        }

        $prompt = sanitize_textarea_field(wp_unslash($_POST['prompt'])); // phpcs:ignore WordPress.Security.NonceVerification

        $raw_options = array();
        if (isset($_POST['options'])) { // phpcs:ignore WordPress.Security.NonceVerification
            $decoded = json_decode(wp_unslash($_POST['options']), true); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification
            if (is_array($decoded)) {
                $raw_options = $decoded;
            }
        }

        $options = $this->sanitize_ai_options($raw_options);

        if (empty($prompt)) {
            wp_send_json_error('提示词不能为空');
        }

        // 设置流式响应头
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $callback = function ($data) {
            echo "data: " . wp_json_encode($data) . "\n\n";
            ob_flush();
            flush();
        };

        $result = $this->generate_content_stream($prompt, $callback, $options);

        if (is_wp_error($result)) {
            echo "data: " . wp_json_encode(array('error' => $result->get_error_message())) . "\n\n";
            ob_flush();
            flush();
        }

        exit;
    }

    /**
     * 获取API统计信息
     */
    public function get_api_stats()
    {
        $stats = array();

        foreach ($this->providers as $name => $provider) {
            $stats[$name] = array(
                'name' => $provider->get_name(),
                'is_configured' => $provider->is_configured(),
                'supports_streaming' => $provider->supports_streaming(),
                'models' => $provider->get_available_models(),
                'last_used' => get_option("saiita_ai_content_toolkit_{$name}_last_used"),
                'usage_count' => get_option("saiita_ai_content_toolkit_{$name}_usage_count", 0),
            );
        }

        return $stats;
    }

    /**
     * 测试AI提供商连接
     */
    public function test_provider($provider_name)
    {
        $provider = $this->get_provider($provider_name);

        if (!$provider) {
            return new WP_Error('invalid_provider', '无效的AI提供商');
        }

        return $provider->test_connection();
    }
}
