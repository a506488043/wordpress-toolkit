<?php
/**
 * AI Provider Base Class
 * 所有AI提供商的基类，定义通用接口
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

abstract class Saiita_AI_Toolkit_Provider_Base
{
    /**
     * 提供商名称
     */
    protected $name;

    /**
     * 配置项
     */
    protected $config = array();

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->load_config();
    }

    /**
     * 获取提供商名称
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * 加载配置（自动解密 api_key）
     */
    protected function load_config()
    {
        $config_key = 'saiita_ai_content_toolkit_' . $this->get_config_key() . '_config';
        $this->config = get_option($config_key, $this->get_default_config());

        if (!empty($this->config['api_key']) && class_exists('Saiita_AI_Toolkit_Utilities')) {
            $this->config['api_key'] = Saiita_AI_Toolkit_Utilities::decrypt_api_key($this->config['api_key']);
        }
    }

    /**
     * 获取配置键名
     */
    abstract protected function get_config_key();

    /**
     * 获取默认配置
     */
    abstract protected function get_default_config();

    /**
     * 检查是否已配置
     */
    public function is_configured()
    {
        $required_fields = $this->get_required_config_fields();

        foreach ($required_fields as $field) {
            if (empty($this->config[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取必需的配置字段
     */
    abstract protected function get_required_config_fields();

    /**
     * 生成内容
     */
    abstract public function generate($request);

    /**
     * 流式生成内容
     */
    public function stream_generate($request, $callback)
    {
        // 默认不支持流式生成
        throw new Exception('流式生成未实现');
    }

    /**
     * 是否支持流式生成
     */
    public function supports_streaming()
    {
        return false;
    }

    /**
     * 测试连接
     */
    abstract public function test_connection();

    /**
     * 获取可用模型
     */
    abstract public function get_available_models();

    /**
     * 获取使用的模型
     */
    public function get_model_used($response)
    {
        return $this->config['model'] ?? 'default';
    }

    /**
     * 发送HTTP请求
     */
    protected function send_request($url, $args = array())
    {
        $default_args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'AI-Content-Toolkit/' . SAIITA_AI_CONTENT_TOOLKIT_VERSION,
            ),
        );

        $args = wp_parse_args($args, $default_args);

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            throw new Exception('HTTP请求失败: ' . esc_html($response->get_error_message()));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code < 200 || $status_code >= 300) {
            throw new Exception('API请求失败，状态码: ' . esc_html($status_code) . ', 响应: ' . esc_html($body));
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('API响应解析失败: ' . esc_html(json_last_error_msg()));
        }

        return $data;
    }

    /**
     * 发送流式HTTP请求
     */
    protected function send_stream_request($url, $callback, $args = array())
    {
        $default_args = array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'AI-Content-Toolkit/' . SAIITA_AI_CONTENT_TOOLKIT_VERSION,
                'Accept' => 'text/event-stream',
            ),
        );

        $args = wp_parse_args($args, $default_args);
        $args['method'] = 'POST';

        // 使用 WP HTTP API 发送请求
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new Exception('HTTP请求失败: ' . esc_html($response->get_error_message()));
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            $body = wp_remote_retrieve_body($response);
            throw new Exception('API请求失败，状态码: ' . esc_html($status_code) . ', 响应: ' . esc_html($body));
        }

        // 处理SSE数据
        $body = wp_remote_retrieve_body($response);
        $lines = explode("\n", $body);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'data: ') === 0) {
                $json_data = substr($line, 6);
                if ($json_data !== '[DONE]') {
                    $data_obj = json_decode($json_data, true);
                    if ($data_obj) {
                        call_user_func($callback, $data_obj);
                    }
                }
            }
        }
    }

    /**
     * 更新使用统计
     */
    protected function update_usage_stats()
    {
        $config_key = $this->get_config_key();

        // 更新使用次数
        $count = get_option("saiita_ai_content_toolkit_{$config_key}_usage_count", 0);
        update_option("saiita_ai_content_toolkit_{$config_key}_usage_count", $count + 1);

        // 更新最后使用时间
        update_option("saiita_ai_content_toolkit_{$config_key}_last_used", current_time('mysql'));
    }

    /**
     * 记录错误日志
     */
    protected function log_error($message, $context = array())
    {
        Saiita_AI_Toolkit_Logger::error(
            "[{$this->name}] " . $message,
            'ai_provider',
            array_merge($context, array('provider' => $this->name))
        );
    }

    /**
     * 记录信息日志
     */
    protected function log_info($message, $context = array())
    {
        Saiita_AI_Toolkit_Logger::info(
            "[{$this->name}] " . $message,
            'ai_provider',
            array_merge($context, array('provider' => $this->name))
        );
    }

    /**
     * 构建消息数组（通用实现）
     */
    protected function build_messages($request)
    {
        $messages = array();

        if (!empty($request['system_prompt'])) {
            $messages[] = array(
                'role' => 'system',
                'content' => $request['system_prompt']
            );
        }

        if (!empty($request['context']['messages'])) {
            $messages = array_merge($messages, $request['context']['messages']);
        }

        $messages[] = array(
            'role' => 'user',
            'content' => $request['prompt']
        );

        return $messages;
    }

    /**
     * 提取响应中的内容（通用实现）
     */
    public function extract_content($response)
    {
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }

        return '';
    }

    /**
     * 获取token使用量（通用实现）
     */
    public function get_token_usage($response)
    {
        if (isset($response['usage'])) {
            return array(
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
            );
        }

        return array(
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        );
    }

    /**
     * 获取元数据（通用实现）
     */
    public function get_metadata($response)
    {
        $metadata = array();

        if (isset($response['id'])) {
            $metadata['response_id'] = $response['id'];
        }

        if (isset($response['created'])) {
            $metadata['created_at'] = gmdate('Y-m-d H:i:s', $response['created']);
        }

        if (isset($response['model'])) {
            $metadata['model'] = $response['model'];
        }

        if (isset($response['usage'])) {
            $metadata['token_usage'] = $this->get_token_usage($response);
        }

        return $metadata;
    }

    /**
     * 获取认证头
     */
    protected function get_auth_headers()
    {
        return array();
    }

    /**
     * 验证配置
     */
    public function validate_config($config)
    {
        $errors = array();

        foreach ($this->get_required_config_fields() as $field) {
            if (empty($config[$field])) {
                $errors[] = "字段 {$field} 是必需的";
            }
        }

        return $errors;
    }

    /**
     * 保存配置（自动加密 api_key）
     */
    public function save_config($config)
    {
        $errors = $this->validate_config($config);

        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(', ', $errors));
        }

        if (!empty($config['api_key']) && class_exists('Saiita_AI_Toolkit_Utilities')) {
            $config['api_key'] = Saiita_AI_Toolkit_Utilities::encrypt_api_key($config['api_key']);
        }

        $config_key = $this->get_config_key();
        $option_name = 'saiita_ai_content_toolkit_' . $config_key . '_config';

        update_option($option_name, $config);
        // 加载回来时自动解密，确保 $this->config 里是明文
        $this->load_config();

        return true;
    }

    /**
     * 获取当前配置
     */
    public function get_config()
    {
        return $this->config;
    }
}
