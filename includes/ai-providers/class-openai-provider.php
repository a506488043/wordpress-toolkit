<?php
/**
 * OpenAI Provider
 * 集成OpenAI API
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_OpenAI_Provider extends Saiita_AI_Toolkit_Provider_Base
{
    /**
     * 提供商名称
     */
    protected $name = 'OpenAI';

    /**
     * API基础URL
     */
    private $api_base = 'https://api.openai.com';

    /**
     * 获取配置键名
     */
    protected function get_config_key()
    {
        return 'openai';
    }

    /**
     * 获取默认配置
     */
    protected function get_default_config()
    {
        return array(
            'api_key' => '',
            'model' => 'gpt-3.5-turbo',
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
            'gpt-4' => array(
                'name' => 'GPT-4',
                'description' => '最先进的模型，适合复杂任务',
                'context_length' => 8192,
                'cost_per_1k_tokens' => 0.03,
            ),
            'gpt-4-turbo' => array(
                'name' => 'GPT-4 Turbo',
                'description' => '更快速的GPT-4版本',
                'context_length' => 128000,
                'cost_per_1k_tokens' => 0.01,
            ),
            'gpt-3.5-turbo' => array(
                'name' => 'GPT-3.5 Turbo',
                'description' => '快速且经济的选择',
                'context_length' => 16385,
                'cost_per_1k_tokens' => 0.002,
            ),
        );
    }

    /**
     * 生成内容
     */
    public function generate($request)
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
                'model' => 'gpt-3.5-turbo',
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

    /**
     * 获取配置表单字段
     */
    public function get_config_fields()
    {
        return array(
            'api_key' => array(
                'type' => 'password',
                'label' => 'API Key',
                'description' => '从OpenAI控制台获取的API密钥',
                'required' => true,
            ),
            'model' => array(
                'type' => 'select',
                'label' => '模型',
                'description' => '选择要使用的OpenAI模型',
                'options' => array(
                    'gpt-4' => 'GPT-4（最强大）',
                    'gpt-4-turbo' => 'GPT-4 Turbo（快速版）',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo（经济版）',
                ),
                'default' => 'gpt-3.5-turbo',
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
