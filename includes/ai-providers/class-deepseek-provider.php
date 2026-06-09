<?php
/**
 * DeepSeek AI Provider
 * 集成DeepSeek AI API
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_DeepSeek_Provider extends Saiita_AI_Toolkit_Provider_Base
{
    /**
     * 提供商名称
     */
    protected $name = 'DeepSeek';

    /**
     * API基础URL
     */
    private $api_base = 'https://api.deepseek.com';

    /**
     * 获取配置键名
     */
    protected function get_config_key()
    {
        return 'deepseek';
    }

    /**
     * 获取默认配置
     */
    protected function get_default_config()
    {
        return array(
            'api_key' => '',
            'model' => 'deepseek-v4-flash',
            'max_tokens' => 2000,
            'temperature' => 0.7,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        );
    }

    /**
     * 获取必需的配置字段
     */
    protected function get_required_config_fields()
    {
        return array('api_key');
    }

    /**
     * 获取可用模型
     */
    public function get_available_models()
    {
        return array(
            'deepseek-v4-flash' => array(
                'name' => 'DeepSeek V4 Flash',
                'description' => '通用对话模型，速度快且免费，适合各种内容生成任务',
                'context_length' => 1048576,
                'cost_per_1k_tokens' => 0,
            ),
            'deepseek-v4-pro' => array(
                'name' => 'DeepSeek V4 Pro',
                'description' => '高性能模型，适合复杂推理和高质量内容生成',
                'context_length' => 1048576,
                'cost_per_1k_tokens' => 0.002,
            ),
            'deepseek-chat' => array(
                'name' => 'DeepSeek Chat（即将弃用）',
                'description' => '旧版通用对话模型，将于2026/07/24弃用，对应 deepseek-v4-flash',
                'context_length' => 32768,
                'cost_per_1k_tokens' => 0,
            ),
            'deepseek-coder' => array(
                'name' => 'DeepSeek Coder',
                'description' => '专业代码生成模型',
                'context_length' => 16384,
                'cost_per_1k_tokens' => 0.002,
            ),
        );
    }

    /**
     * 生成内容
     */
    public function generate($request)
    {
        if ($this->is_rate_limited()) {
            throw new Exception('已达到速率限制，请稍后再试');
        }

        $this->update_usage_stats();
        $this->increment_request_count();

        $data = array(
            'model' => $request['model'] ?? $this->config['model'],
            'messages' => $this->build_messages($request),
            'max_tokens' => $request['max_tokens'] ?? $this->config['max_tokens'],
            'temperature' => $request['temperature'] ?? $this->config['temperature'],
            'top_p' => $request['top_p'] ?? $this->config['top_p'],
            'frequency_penalty' => $request['frequency_penalty'] ?? $this->config['frequency_penalty'],
            'presence_penalty' => $request['presence_penalty'] ?? $this->config['presence_penalty'],
            'stream' => false,
        );

        $args = array(
            'headers' => array_merge(
                array(
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ),
                $this->get_auth_headers()
            ),
            'body' => $data,
        );

        try {
            $response = $this->send_request($this->api_base . '/v1/chat/completions', $args);
            return $response;
        } catch (Exception $e) {
            $this->log_error('生成内容失败', array('error' => $e->getMessage()));
            throw $e;
        }
    }

    /**
     * 流式生成内容
     */
    public function stream_generate($request, $callback)
    {
        $this->update_usage_stats();

        $data = array(
            'model' => $request['model'] ?? $this->config['model'],
            'messages' => $this->build_messages($request),
            'max_tokens' => $request['max_tokens'] ?? $this->config['max_tokens'],
            'temperature' => $request['temperature'] ?? $this->config['temperature'],
            'top_p' => $request['top_p'] ?? $this->config['top_p'],
            'frequency_penalty' => $request['frequency_penalty'] ?? $this->config['frequency_penalty'],
            'presence_penalty' => $request['presence_penalty'] ?? $this->config['presence_penalty'],
            'stream' => true,
        );

        $args = array(
            'headers' => array_merge(
                array(
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ),
                $this->get_auth_headers()
            ),
            'body' => $data,
        );

        try {
            $this->send_stream_request($this->api_base . '/v1/chat/completions', $callback, $args);
        } catch (Exception $e) {
            $this->log_error('流式生成失败', array('error' => $e->getMessage()));
            throw $e;
        }
    }

    /**
     * 是否支持流式生成
     */
    public function supports_streaming()
    {
        return true;
    }

    // build_messages() 已移至基类 Saiita_AI_Toolkit_Provider_Base

    /**
     * 测试连接
     */
    public function test_connection()
    {
        if (empty($this->config['api_key'])) {
            return new WP_Error('no_api_key', 'API密钥未配置');
        }

        try {
            $test_request = array(
                'model' => 'deepseek-v4-flash',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Hello'
                    )
                ),
                'max_tokens' => 10,
            );

            $args = array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ),
                'body' => $test_request,
            );

            $response = $this->send_request($this->api_base . '/v1/chat/completions', $args);

            if (isset($response['choices'][0]['message']['content'])) {
                $this->log_info('连接测试成功');
                return true;
            } else {
                return new WP_Error('invalid_response', 'API响应格式无效');
            }
        } catch (Exception $e) {
            return new WP_Error('connection_failed', $e->getMessage());
        }
    }

    // extract_content() 已移至基类 Saiita_AI_Toolkit_Provider_Base

    // get_token_usage() 已移至基类 Saiita_AI_Toolkit_Provider_Base

    // get_metadata() 已移至基类 Saiita_AI_Toolkit_Provider_Base

    /**
     * 获取使用统计
     */
    public function get_usage_statistics()
    {
        return array(
            'total_requests' => get_option('saiita_ai_content_toolkit_deepseek_usage_count', 0),
            'last_used' => get_option('saiita_ai_content_toolkit_deepseek_last_used'),
            'total_tokens' => get_option('saiita_ai_content_toolkit_deepseek_total_tokens', 0),
            'total_cost' => get_option('saiita_ai_content_toolkit_deepseek_total_cost', 0),
        );
    }

    /**
     * 更新token统计
     */
    public function update_token_statistics($token_usage)
    {
        $total_tokens = get_option('saiita_ai_content_toolkit_deepseek_total_tokens', 0);
        $total_tokens += $token_usage['total_tokens'] ?? 0;
        update_option('saiita_ai_content_toolkit_deepseek_total_tokens', $total_tokens);

        // 计算成本（假设价格为 $0.0014 每1K tokens）
        $cost = ($total_tokens / 1000) * 0.0014;
        update_option('saiita_ai_content_toolkit_deepseek_total_cost', $cost);
    }

    /**
     * 获取速率限制信息
     */
    public function get_rate_limits()
    {
        return array(
            'requests_per_minute' => 60,
            'tokens_per_minute' => 50000,
            'requests_per_hour' => 3600,
            'tokens_per_hour' => 3000000,
        );
    }

    /**
     * 检查是否达到速率限制
     */
    public function is_rate_limited()
    {
        $rate_limits = $this->get_rate_limits();
        return Saiita_AI_Toolkit_Rate_Limiter::is_rate_limited('deepseek', $rate_limits['requests_per_minute']);
    }

    /**
     * 增加请求计数
     */
    private function increment_request_count()
    {
        Saiita_AI_Toolkit_Rate_Limiter::increment('deepseek');
    }

    /**
     * 获取配置表单字段
     */
    public function get_config_fields()
    {
        return array(
            'api_key' => array(
                'type' => 'password',
                'label' => 'API Key',
                'description' => '从DeepSeek控制台获取的API密钥',
                'required' => true,
            ),
            'model' => array(
                'type' => 'select',
                'label' => '模型',
                'description' => '选择要使用的DeepSeek模型',
                'options' => array(
                    'deepseek-v4-flash' => 'DeepSeek V4 Flash（通用对话·免费）',
                    'deepseek-v4-pro' => 'DeepSeek V4 Pro（高性能·复杂推理）',
                    'deepseek-chat' => 'DeepSeek Chat（即将弃用）',
                    'deepseek-coder' => 'DeepSeek Coder（代码生成）',
                ),
                'default' => 'deepseek-v4-flash',
            ),
            'max_tokens' => array(
                'type' => 'number',
                'label' => '最大Token数',
                'description' => '生成内容的最大长度（1-4096）',
                'min' => 1,
                'max' => 4096,
                'default' => 2000,
            ),
            'temperature' => array(
                'type' => 'range',
                'label' => '创造性（Temperature）',
                'description' => '控制生成内容的创造性（0.0-2.0）',
                'min' => 0.0,
                'max' => 2.0,
                'step' => 0.1,
                'default' => 0.7,
            ),
        );
    }
}
