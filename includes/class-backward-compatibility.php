<?php
/**
 * Backward Compatibility Layer
 * 确保原有功能在新的AI架构下正常工作
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_Backward_Compatibility
{
    use Saiita_AI_Toolkit_Singleton;

    /**
     * AI管理器实例
     */
    private $ai_manager;

    /**
     * 旧版配置映射
     */
    private $config_mapping = array(
        'saiita_ai_content_toolkit_deepseek_api_key' => 'deepseek_api_key',
        'saiita_ai_content_toolkit_siliconflow_api_key' => 'siliconflow_api_key',
        'saiita_ai_content_toolkit_default_provider' => 'active_provider',
        'saiita_ai_content_toolkit_model' => 'deepseek_model',
    );

    /**
     * 初始化向后兼容层
     */
    protected function init($args = array())
    {
        $this->ai_manager = Saiita_AI_Toolkit_AI_Manager::get_instance();

        // 仅在首次安装时迁移旧版配置
        $this->migrate_legacy_config();

        // 保持旧版API接口（用于向后兼容）
        $this->register_legacy_functions();

        // 保持旧版短代码（用于向后兼容）
        $this->register_legacy_shortcodes();

        // 保持旧版AJAX处理（用于向后兼容）
        $this->register_legacy_ajax_handlers();
    }

    /**
     * 迁移旧版配置（仅在首次加载时执行）
     */
    private function migrate_legacy_config()
    {
        static $migrated = null;

        // 使用静态变量避免重复检查
        if ($migrated === true) {
            return;
        }

        $migrated_flag = get_option('saiita_ai_content_toolkit_config_migrated', false);
        $migrated = $migrated_flag;

        if ($migrated_flag) {
            return;
        }

        foreach ($this->config_mapping as $old_key => $new_key_info) {
            $old_value = get_option($old_key);

            if ($old_value !== false) {
                if (is_array($new_key_info)) {
                    $provider = $new_key_info['provider'];
                    $field = $new_key_info['field'];
                    $config = get_option("saiita_ai_content_toolkit_{$provider}_config", array());
                    $config[$field] = $old_value;
                    update_option("saiita_ai_content_toolkit_{$provider}_config", $config);
                } else {
                    update_option($new_key_info, $old_value);
                }
            }
        }

        update_option('saiita_ai_content_toolkit_config_migrated', true);
        $migrated = true;
        Saiita_AI_Toolkit_Logger::info('旧版配置迁移完成', 'backward_compatibility');
    }

    /**
     * 注册旧版函数
     */
    private function register_legacy_functions()
    {
        // 如果旧版函数不存在，定义兼容函数
        if (!function_exists('ai_content_toolkit_generate')) {
            function ai_content_toolkit_generate($prompt, $options = array())  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
            {
                return Saiita_AI_Toolkit_Backward_Compatibility::legacy_generate_content($prompt, $options);
            }
        }

        if (!function_exists('ai_content_toolkit_analyze_seo')) {
            function ai_content_toolkit_analyze_seo($content, $options = array())  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
            {
                return Saiita_AI_Toolkit_Backward_Compatibility::legacy_analyze_seo($content, $options);
            }
        }

        if (!function_exists('ai_content_toolkit_generate_excerpt')) {
            function ai_content_toolkit_generate_excerpt($content, $options = array())  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
            {
                return Saiita_AI_Toolkit_Backward_Compatibility::legacy_generate_excerpt($content, $options);
            }
        }
    }

    /**
     * 注册旧版短代码
     */
    private function register_legacy_shortcodes()
    {
        // 保持原有的AI生成短代码
        add_shortcode('saiita_ai_generate', array($this, 'handle_ai_generate_shortcode'));

        // 保持原有的摘要生成短代码
        add_shortcode('auto_excerpt', array($this, 'handle_auto_excerpt_shortcode'));
    }

    /**
     * 注册旧版AJAX处理器
     */
    private function register_legacy_ajax_handlers()
    {
        // 旧版的AI生成请求
        add_action('wp_ajax_saiita_ai_generate_content', array($this, 'handle_legacy_generate_ajax'));

        // 旧版的SEO分析请求
        add_action('wp_ajax_saiita_ai_analyze_seo', array($this, 'handle_legacy_seo_ajax'));

        // 旧版的摘要生成请求
        add_action('wp_ajax_saiita_ai_toolkit_generate_excerpt', array($this, 'handle_legacy_excerpt_ajax'));
    }

    /**
     * 旧版内容生成函数
     */
    public static function legacy_generate_content($prompt, $options = array())
    {
        $instance = self::get_instance();

        // 转换旧版参数格式
        $legacy_options = $instance->convert_legacy_options($options);

        // 使用新的AI管理器生成内容
        $result = $instance->ai_manager->generate_content($prompt, $legacy_options);

        // 转换回旧版响应格式
        if (is_wp_error($result)) {
            return false;
        }

        return $result['content'];
    }

    /**
     * 旧版SEO分析函数
     */
    public static function legacy_analyze_seo($content, $options = array())
    {
        $instance = self::get_instance();

        $prompt = "请分析以下内容的SEO表现，并提供改进建议：\n\n{$content}";
        $seo_options = array_merge($options, array(
            'system_prompt' => '你是一个专业的SEO分析师，请从关键词密度、标题结构、内容质量、可读性等方面分析内容。'
        ));

        $result = $instance->ai_manager->generate_content($prompt, $seo_options);

        if (is_wp_error($result)) {
            return false;
        }

        return $result['content'];
    }

    /**
     * 旧版摘要生成函数
     */
    public static function legacy_generate_excerpt($content, $options = array())
    {
        $instance = self::get_instance();

        $length = $options['length'] ?? 150;
        $prompt = "请为以下内容生成一个{$length}字左右的摘要：\n\n{$content}";

        $excerpt_options = array_merge($options, array(
            'system_prompt' => '你是一个专业的内容摘要助手，能够准确理解文章内容并生成简洁、准确的摘要。',
            'max_tokens' => min($length * 2, 500),
        ));

        $result = $instance->ai_manager->generate_content($prompt, $excerpt_options);

        if (is_wp_error($result)) {
            return false;
        }

        return $result['content'];
    }

    /**
     * 转换旧版选项格式
     */
    private function convert_legacy_options($options)
    {
        $converted = array();

        // 映射旧版参数
        if (isset($options['temperature'])) {
            $converted['temperature'] = $options['temperature'];
        }

        if (isset($options['max_tokens'])) {
            $converted['max_tokens'] = $options['max_tokens'];
        }

        if (isset($options['model'])) {
            $converted['model'] = $options['model'];
        }

        if (isset($options['system_prompt'])) {
            $converted['system_prompt'] = $options['system_prompt'];
        }

        // 处理旧的提供商选择
        if (isset($options['provider'])) {
            // 临时保存提供商，AI管理器会使用它
            update_option('saiita_ai_content_toolkit_temp_provider', $options['provider']);
        }

        return $converted;
    }

    /**
     * 清理 JSON 解码的选项数据
     */
    private function sanitize_json_options($options)
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

        // 清理数值字段
        if (isset($options['temperature'])) {
            $sanitized['temperature'] = floatval($options['temperature']);
        }

        if (isset($options['max_tokens'])) {
            $sanitized['max_tokens'] = intval($options['max_tokens']);
        }

        if (isset($options['length'])) {
            $sanitized['length'] = intval($options['length']);
        }

        // 清理布尔字段
        if (isset($options['fallback_to_simple'])) {
            $sanitized['fallback_to_simple'] = rest_sanitize_boolean($options['fallback_to_simple']);
        }

        // 保留其他字段（但不清理，应由调用者负责）
        foreach ($options as $key => $value) {
            if (!isset($sanitized[$key])) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * 处理旧版AI生成短代码
     */
    public function handle_ai_generate_shortcode($atts, $content = '')
    {
        $atts = shortcode_atts(array(
            'prompt' => '',
            'length' => 200,
            'provider' => 'deepseek',
            'temperature' => 0.7,
        ), $atts);

        if (empty($atts['prompt'])) {
            return esc_html__('请提供prompt参数', 'saiita-ai-content-toolkit');
        }

        $options = array(
            'max_tokens' => $atts['length'] * 2,
            'provider' => $atts['provider'],
            'temperature' => floatval($atts['temperature']),
        );

        $result = self::legacy_generate_content($atts['prompt'], $options);

        if ($result) {
            // 安全地处理 AI 生成的内容，允许 Post 级别的 HTML
            return wp_kses_post(wpautop($result));
        }

        return esc_html__('生成失败', 'saiita-ai-content-toolkit');
    }

    /**
     * 处理旧版摘要短代码
     */
    public function handle_auto_excerpt_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
            'length' => 150,
        ), $atts);

        $post = get_post($atts['id']);
        if (!$post) {
            return '';
        }

        $content = wp_strip_all_tags($post->post_content);
        $options = array('length' => intval($atts['length']));

        $excerpt = self::legacy_generate_excerpt($content, $options);

        return $excerpt ? '<p>' . esc_html($excerpt) . '</p>' : '';
    }


    /**
     * 处理旧版生成AJAX
     */
    public function handle_legacy_generate_ajax()
    {
        // 安全验证
        Saiita_AI_Toolkit_Security_Validator::verify_admin_ajax('ai_generate_content');

        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field(wp_unslash($_POST['prompt'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- nonce verified in verify_admin_ajax
        $raw_options = $this->decode_post_options();

        $options = $this->sanitize_json_options($raw_options);

        if (empty($prompt)) {
            wp_send_json_error('提示词不能为空');
        }

        $result = self::legacy_generate_content($prompt, $options);

        if (!$result) {
            wp_send_json_error('生成失败');
        }
        wp_send_json_success(array(
            'content' => $result,
            'timestamp' => current_time('mysql'),
        ));
    }

    /**
     * 处理旧版SEO分析AJAX
     */
    public function handle_legacy_seo_ajax()
    {
        Saiita_AI_Toolkit_Security_Validator::verify_admin_ajax('ai_analyze_seo');

        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- nonce verified in verify_admin_ajax
        $raw_options = $this->decode_post_options();
        $options = $this->sanitize_json_options($raw_options);

        if (empty($content)) {
            wp_send_json_error('内容不能为空');
        }

        $result = self::legacy_analyze_seo($content, $options);

        if (!$result) {
            wp_send_json_error('分析失败');
        }

        wp_send_json_success(array(
            'analysis' => $result,
            'timestamp' => current_time('mysql'),
        ));
    }

    /**
     * 处理旧版摘要生成AJAX
     */
    public function handle_legacy_excerpt_ajax()
    {
        Saiita_AI_Toolkit_Security_Validator::verify_admin_ajax('generate_excerpt');

        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- nonce verified in verify_admin_ajax
        $raw_options = $this->decode_post_options();
        $options = $this->sanitize_json_options($raw_options);

        if (empty($content)) {
            wp_send_json_error('内容不能为空');
        }

        $result = self::legacy_generate_excerpt($content, $options);

        if (!$result) {
            wp_send_json_error('生成失败');
        }

        wp_send_json_success(array(
            'excerpt' => $result,
            'timestamp' => current_time('mysql'),
        ));
    }

    /**
     * 获取旧版模块的配置
     */
    public function get_legacy_module_config($module_name)
    {
        $legacy_options = get_option("saiita_ai_content_toolkit_{$module_name}_options", array());

        // 转换为新版配置格式
        $new_config = array();

        if ($module_name === 'auto_excerpt') {
            $new_config['auto_generate'] = $legacy_options['auto_generate'] ?? false;
            $new_config['length'] = $legacy_options['length'] ?? 150;
            $new_config['provider'] = $legacy_options['provider'] ?? 'deepseek';
        }

        return $new_config;
    }

    /**
     * 检查模块是否兼容
     */
    public function is_module_compatible($module_name)
    {
        $compatible_modules = array(
            'auto_excerpt',
            'tag_optimization',
            'category_optimization',
        );

        return in_array($module_name, $compatible_modules);
    }

    /**
     * 获取兼容性信息
     */
    public function get_compatibility_info()
    {
        return array(
            'version' => SAIITA_AI_CONTENT_TOOLKIT_VERSION,
            'config_migrated' => get_option('saiita_ai_content_toolkit_config_migrated', false),
            'legacy_functions_available' => array(
                'ai_content_toolkit_generate' => function_exists('ai_content_toolkit_generate'),
                'ai_content_toolkit_analyze_seo' => function_exists('ai_content_toolkit_analyze_seo'),
                'ai_content_toolkit_generate_excerpt' => function_exists('ai_content_toolkit_generate_excerpt'),
            ),
            'shortcodes_active' => array(
                'ai_generate' => shortcode_exists('saiita_ai_generate'),
                'auto_excerpt' => shortcode_exists('auto_excerpt'),
            ),
        );
    }

    private function decode_post_options()
    {
        if (!isset($_POST['options'])) { // phpcs:ignore WordPress.Security.NonceVerification
            return array();
        }
        $decoded = json_decode(wp_unslash($_POST['options']), true); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification
        return is_array($decoded) ? $decoded : array();
    }
}
