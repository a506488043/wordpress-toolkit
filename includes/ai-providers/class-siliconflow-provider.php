<?php
/**
 * SiliconFlow AI Provider
 * 集成硅基流动 AI API
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_SiliconFlow_Provider extends Saiita_AI_Toolkit_Provider_Base
{
    /**
     * 提供商名称
     */
    protected $name = 'SiliconFlow';

    /**
     * API基础URL
     */
    private $api_base = 'https://api.siliconflow.cn';

    /**
     * 获取配置键名
     */
    protected function get_config_key()
    {
        return 'siliconflow';
    }

    /**
     * 获取默认配置
     */
    protected function get_default_config()
    {
        return array(
            'api_key' => '',
            'model' => 'deepseek-ai/DeepSeek-V3.2',
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
            'deepseek-ai/DeepSeek-V3.2' => array(
                'name' => 'DeepSeek V3.2',
                'description' => '最新通用对话模型，适合各种内容生成任务',
                'context_length' => 65536,
                'cost_per_1k_tokens' => 0.001,
            ),
            'deepseek-chat' => array(
                'name' => 'DeepSeek Chat',
                'description' => '通用对话模型，适合各种内容生成任务',
                'context_length' => 32768,
                'cost_per_1k_tokens' => 0.001,
            ),
            'deepseek-coder' => array(
                'name' => 'DeepSeek Coder',
                'description' => '专业代码生成模型',
                'context_length' => 16384,
                'cost_per_1k_tokens' => 0.0015,
            ),
            'Qwen/Qwen2.5-7B-Instruct' => array(
                'name' => 'Qwen2.5 7B',
                'description' => '通义千问7B指令微调模型',
                'context_length' => 32768,
                'cost_per_1k_tokens' => 0.0006,
            ),
            'Qwen/Qwen2.5-14B-Instruct' => array(
                'name' => 'Qwen2.5 14B',
                'description' => '通义千问14B指令微调模型',
                'context_length' => 32768,
                'cost_per_1k_tokens' => 0.0012,
            ),
            'Qwen/Qwen2.5-32B-Instruct' => array(
                'name' => 'Qwen2.5 32B',
                'description' => '通义千问32B指令微调模型',
                'context_length' => 32768,
                'cost_per_1k_tokens' => 0.0024,
            ),
            'Qwen/Qwen2.5-72B-Instruct' => array(
                'name' => 'Qwen2.5 72B',
                'description' => '通义千问72B指令微调模型',
                'context_length' => 32768,
                'cost_per_1k_tokens' => 0.005,
            ),
            'meta-llama/Meta-Llama-3.1-8B-Instruct' => array(
                'name' => 'Llama 3.1 8B',
                'description' => 'Meta Llama 3.1 8B指令微调模型',
                'context_length' => 131072,
                'cost_per_1k_tokens' => 0.0006,
            ),
            'meta-llama/Meta-Llama-3.1-70B-Instruct' => array(
                'name' => 'Llama 3.1 70B',
                'description' => 'Meta Llama 3.1 70B指令微调模型',
                'context_length' => 131072,
                'cost_per_1k_tokens' => 0.005,
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
                'model' => 'deepseek-ai/DeepSeek-V3.2',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => '你好'
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
            'total_requests' => get_option('saiita_ai_content_toolkit_siliconflow_usage_count', 0),
            'last_used' => get_option('saiita_ai_content_toolkit_siliconflow_last_used'),
            'total_tokens' => get_option('saiita_ai_content_toolkit_siliconflow_total_tokens', 0),
            'total_cost' => get_option('saiita_ai_content_toolkit_siliconflow_total_cost', 0),
        );
    }

    /**
     * 更新token统计
     */
    public function update_token_statistics($token_usage)
    {
        $total_tokens = get_option('saiita_ai_content_toolkit_siliconflow_total_tokens', 0);
        $total_tokens += $token_usage['total_tokens'] ?? 0;
        update_option('saiita_ai_content_toolkit_siliconflow_total_tokens', $total_tokens);

        // 计算成本（基于不同模型的不同价格）
        $model = $this->config['model'];
        $cost_per_1k = $this->get_model_cost($model);
        $cost = ($total_tokens / 1000) * $cost_per_1k;
        update_option('saiita_ai_content_toolkit_siliconflow_total_cost', $cost);
    }

    /**
     * 获取模型成本
     */
    private function get_model_cost($model)
    {
        $costs = array(
            'deepseek-ai/DeepSeek-V3.2' => 0.001,
            'deepseek-chat' => 0.001,
            'deepseek-coder' => 0.0015,
            'Qwen/Qwen2.5-7B-Instruct' => 0.0006,
            'Qwen/Qwen2.5-14B-Instruct' => 0.0012,
            'Qwen/Qwen2.5-32B-Instruct' => 0.0024,
            'Qwen/Qwen2.5-72B-Instruct' => 0.005,
            'meta-llama/Meta-Llama-3.1-8B-Instruct' => 0.0006,
            'meta-llama/Meta-Llama-3.1-70B-Instruct' => 0.005,
        );

        return $costs[$model] ?? 0.001;
    }

    /**
     * 获取速率限制信息
     */
    public function get_rate_limits()
    {
        return array(
            'requests_per_minute' => 100,
            'tokens_per_minute' => 100000,
            'requests_per_hour' => 6000,
            'tokens_per_hour' => 6000000,
        );
    }

    /**
     * 检查是否达到速率限制
     */
    public function is_rate_limited()
    {
        $rate_limits = $this->get_rate_limits();
        return Saiita_AI_Toolkit_Rate_Limiter::is_rate_limited('siliconflow', $rate_limits['requests_per_minute']);
    }

    /**
     * 增加请求计数
     */
    private function increment_request_count()
    {
        Saiita_AI_Toolkit_Rate_Limiter::increment('siliconflow');
    }

    /**
     * 获取配置表单字段
     */
    public function get_config_fields()
    {
        $models = $this->get_available_models();
        $model_options = array();

        foreach ($models as $key => $model) {
            $model_options[$key] = $model['name'] . ' - ' . $model['description'];
        }

        return array(
            'api_key' => array(
                'type' => 'password',
                'label' => 'API Key',
                'description' => '从硅基流动控制台获取的API密钥',
                'required' => true,
            ),
            'model' => array(
                'type' => 'select',
                'label' => '模型',
                'description' => '选择要使用的AI模型',
                'options' => $model_options,
                'default' => 'deepseek-ai/DeepSeek-V3.2',
            ),
            'max_tokens' => array(
                'type' => 'number',
                'label' => '最大Token数',
                'description' => '生成内容的最大长度（1-8192）',
                'min' => 1,
                'max' => 8192,
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

    /**
     * 获取模型分组
     */
    public function get_model_groups()
    {
        return array(
            'deepseek' => array(
                'label' => 'DeepSeek 系列',
                'models' => array('deepseek-ai/DeepSeek-V3.2', 'deepseek-chat', 'deepseek-coder'),
            ),
            'qwen' => array(
                'label' => '通义千问系列',
                'models' => array(
                    'Qwen/Qwen2.5-7B-Instruct',
                    'Qwen/Qwen2.5-14B-Instruct',
                    'Qwen/Qwen2.5-32B-Instruct',
                    'Qwen/Qwen2.5-72B-Instruct',
                ),
            ),
            'llama' => array(
                'label' => 'Meta Llama 系列',
                'models' => array(
                    'meta-llama/Meta-Llama-3.1-8B-Instruct',
                    'meta-llama/Meta-Llama-3.1-70B-Instruct',
                ),
            ),
        );
    }

    /**
     * 根据用途推荐模型
     */
    public function recommend_model_for_purpose($purpose)
    {
        $recommendations = array(
            'content_generation' => 'deepseek-ai/DeepSeek-V3.2',
            'code_generation' => 'deepseek-coder',
            'translation' => 'Qwen/Qwen2.5-32B-Instruct',
            'creative_writing' => 'meta-llama/Meta-Llama-3.1-70B-Instruct',
            'quick_response' => 'Qwen/Qwen2.5-7B-Instruct',
        );

        return $recommendations[$purpose] ?? 'deepseek-ai/DeepSeek-V3.2';
    }
}
