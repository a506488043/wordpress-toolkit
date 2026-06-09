<?php
/**
 * AI Settings Helper
 *
 * 提供全局访问AI设置的辅助函数
 *
 * @version 1.0.0
 * @author www.saiita.com.cn
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 获取AI设置
 *
 * @param string|null $key 设置键名，为null时返回所有设置
 * @param mixed $default 默认值
 * @return mixed
 */
function saiita_ai_content_toolkit_get_ai_settings($key = null, $default = null)
{
    // 直接从数据库读取，WordPress get_option 已自带对象缓存
    $ai_settings = get_option('saiita_ai_content_toolkit_ai_settings', array());

    // 合并默认设置 - 使用统一的默认设置
    if (class_exists('Saiita_AI_Toolkit_AI_Settings')) {
        $default_settings = Saiita_AI_Toolkit_AI_Settings::get_ai_default_settings();
    } else {
        // 备用默认设置（如果主类不可用）
        $default_settings = array(
            'use_ai_generation' => true,
            'ai_provider' => 'deepseek',
            'deepseek_api_key' => '',
            'deepseek_api_base' => 'https://api.deepseek.com/v1',
            'deepseek_model' => 'deepseek-v4-flash',
            'siliconflow_api_key' => '',
            'siliconflow_api_base' => 'https://api.siliconflow.cn/v1',
            'siliconflow_model' => 'deepseek-ai/DeepSeek-V3.2',
            'ai_max_tokens' => 150,
            'ai_temperature' => 0.5,
        );
    }

    $ai_settings = wp_parse_args($ai_settings, $default_settings);

    // 解密 API Key（数据库里是加密的，读出来自动解密）
    if (class_exists('Saiita_AI_Toolkit_Utilities')) {
        foreach (array('deepseek_api_key', 'siliconflow_api_key') as $key_field) {
            if (!empty($ai_settings[$key_field])) {
                $ai_settings[$key_field] = Saiita_AI_Toolkit_Utilities::decrypt_api_key($ai_settings[$key_field]);
            }
        }
    }

    // 自动升级已废弃的模型名称
    if (isset($ai_settings['deepseek_model']) && in_array($ai_settings['deepseek_model'], array('deepseek-chat', 'deepseek-v3'), true)) {
        $ai_settings['deepseek_model'] = 'deepseek-v4-flash';
    }

    if ($key === null) {
        return $ai_settings;
    }

    return isset($ai_settings[$key]) ? $ai_settings[$key] : $default;
}

/**
 * 检查AI功能是否可用
 *
 * @return bool
 */
function saiita_ai_content_toolkit_is_ai_available()
{
    $settings = saiita_ai_content_toolkit_get_ai_settings();
    $provider = $settings['ai_provider'] ?? 'deepseek';

    if ($provider === 'siliconflow') {
        return $settings['use_ai_generation'] &&
               !empty($settings['siliconflow_api_key']) &&
               class_exists('WP_Http');
    } else {
        return $settings['use_ai_generation'] &&
               !empty($settings['deepseek_api_key']) &&
               class_exists('WP_Http');
    }
}

/**
 * 获取AI API配置
 *
 * @return array
 */
function saiita_ai_content_toolkit_get_ai_config()
{
    $provider = saiita_ai_content_toolkit_get_ai_settings('ai_provider', 'deepseek');

    if ($provider === 'siliconflow') {
        return array(
            'api_key' => saiita_ai_content_toolkit_get_ai_settings('siliconflow_api_key'),
            'api_base' => saiita_ai_content_toolkit_get_ai_settings('siliconflow_api_base', 'https://api.siliconflow.cn/v1'),
            'model' => saiita_ai_content_toolkit_get_ai_settings('siliconflow_model', 'deepseek-ai/DeepSeek-V3.2'),
            'max_tokens' => saiita_ai_content_toolkit_get_ai_settings('ai_max_tokens', 150),
            'temperature' => saiita_ai_content_toolkit_get_ai_settings('ai_temperature', 0.5)
        );
    } else {
        return array(
            'api_key' => saiita_ai_content_toolkit_get_ai_settings('deepseek_api_key'),
            'api_base' => saiita_ai_content_toolkit_get_ai_settings('deepseek_api_base', 'https://api.deepseek.com/v1'),
            'model' => saiita_ai_content_toolkit_get_ai_settings('deepseek_model', 'deepseek-v4-flash'),
            'max_tokens' => saiita_ai_content_toolkit_get_ai_settings('ai_max_tokens', 150),
            'temperature' => saiita_ai_content_toolkit_get_ai_settings('ai_temperature', 0.5)
        );
    }
}

/**
 * 调用AI API的通用函数
 *
 * @param string $prompt 提示词
 * @param array $options 额外选项
 * @return array|string
 */
function saiita_ai_content_toolkit_call_ai_api($prompt, $options = array())
{
    if (!saiita_ai_content_toolkit_is_ai_available()) {
        return new WP_Error('ai_unavailable', __('AI功能不可用', 'saiita-ai-content-toolkit'));
    }

    $config = saiita_ai_content_toolkit_get_ai_config();
    $options = wp_parse_args($options, array(
        'max_tokens' => $config['max_tokens'],
        'temperature' => $config['temperature'],
        'timeout' => 30
    ));

    $response = wp_remote_post($config['api_base'] . '/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $config['api_key'],
            'Content-Type' => 'application/json',
        ),
        'body' => wp_json_encode(array(
            'model' => $config['model'],
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature']
        )),
        'timeout' => $options['timeout']
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('AI API JSON Error: ' . json_last_error_msg() . ' - Body: ' . substr($body, 0, 500)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }        return new WP_Error('json_error', __('响应格式错误', 'saiita-ai-content-toolkit'));
    }

    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('AI API Response: ' . substr($body, 0, 1000)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
    if (isset($data['error'])) {
        return new WP_Error('api_error', $data['error']['message']);
    }

    if (!isset($data['choices'][0]['message']['content'])) {
        return new WP_Error('invalid_response', __('无效的API响应', 'saiita-ai-content-toolkit'));
    }

    return $data['choices'][0]['message']['content'];
}

/**
 * 获取 DeepSeek 可用模型列表
 *
 * @return array
 */
function saiita_ai_content_toolkit_get_deepseek_models()
{
    return array(
        'deepseek-v4-flash' => array(
            'name' => 'DeepSeek V4 Flash',
            'desc' => '免费 · 通用对话 · 1M上下文',
        ),
        'deepseek-v4-pro'   => array(
            'name' => 'DeepSeek V4 Pro',
            'desc' => '付费 · 高性能推理 · 1M上下文',
        ),
        'deepseek-reasoner' => array(
            'name' => 'DeepSeek Reasoner (R1)',
            'desc' => '付费 · 深度推理 · 复杂分析',
        ),
        'deepseek-coder'    => array(
            'name' => 'DeepSeek Coder',
            'desc' => '付费 · 代码生成 · 16K上下文',
        ),
    );
}

/**
 * 获取硅基流动可用模型列表
 *
 * @return array
 */
function saiita_ai_content_toolkit_get_siliconflow_models()
{
    return array(
        'deepseek-ai/DeepSeek-V3.2'       => array(
            'name' => 'DeepSeek V3.2',
            'desc' => '通用对话 · 65K上下文',
        ),
        'deepseek-ai/DeepSeek-Coder'      => array(
            'name' => 'DeepSeek Coder',
            'desc' => '代码生成 · 16K上下文',
        ),
        'Qwen/Qwen2.5-32B-Instruct'       => array(
            'name' => 'Qwen2.5 32B',
            'desc' => '通义千问 · 32K上下文',
        ),
        'Qwen/Qwen2.5-72B-Instruct'       => array(
            'name' => 'Qwen2.5 72B',
            'desc' => '通义千问 · 32K上下文',
        ),
        'meta-llama/Meta-Llama-3.1-8B-Instruct' => array(
            'name' => 'Llama 3.1 8B',
            'desc' => 'Meta · 131K上下文',
        ),
        'Pro/Llama-4-Maverick'            => array(
            'name' => 'Llama 4 Maverick',
            'desc' => 'Meta · 最新旗舰',
        ),
    );
}
