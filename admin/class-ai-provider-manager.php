<?php
/**
 * AI Provider Manager Admin Interface
 * AI提供商配置管理界面
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_Provider_Manager_Admin
{
    /**
     * AI管理器实例
     */
    private $ai_manager;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->ai_manager = Saiita_AI_Toolkit_AI_Manager::get_instance();
        $this->init();
    }

    /**
     * 初始化
     */
    private function init()
    {
        // 注册设置
        add_action('admin_init', array($this, 'register_settings'));

        // 注册AJAX handler
        add_action('wp_ajax_saiita_ai_test_provider_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_saiita_ai_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_saiita_ai_reset_stats', array($this, 'ajax_reset_stats'));
        add_action('wp_ajax_saiita_ai_get_provider_models', array($this, 'ajax_get_provider_models'));
        add_action('wp_ajax_saiita_act_get_usage_data', array($this, 'ajax_get_usage_data'));
    }

    /**
     * 注册设置
     */
    public function register_settings()
    {
        // 注册主要设置
        register_setting(
            'saiita_ai_content_toolkit_providers',
            'saiita_ai_content_toolkit_active_provider',
            array(
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        // 为每个提供商注册设置（使用闭包绑定提供商名称）
        $providers = $this->ai_manager->get_providers();
        foreach ($providers as $name => $provider) {
            $option_name = "saiita_ai_content_toolkit_{$name}_config";
            register_setting(
                'saiita_ai_content_toolkit_providers',
                $option_name,
                array(
                    'sanitize_callback' => function ($config) use ($name) {
                        return $this->sanitize_provider_config($config, $name);
                    },
                )
            );
        }
    }

    /**
     * 清理提供商配置（自动加密 api_key）
     *
     * @param array  $config 配置数据
     * @param string $provider_name 提供商名称
     * @return array
     */
    public function sanitize_provider_config($config, $provider_name = '')
    {
        if (!is_array($config)) {
            return array();
        }

        $sanitized = array();
        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = sanitize_text_field($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        // 加密 api_key（空字段 = 未变更，保留旧值）
        if (class_exists('Saiita_AI_Toolkit_Utilities')) {
            if (empty($sanitized['api_key']) && !empty($provider_name)) {
                $old_config = get_option("saiita_ai_content_toolkit_{$provider_name}_config", array());
                if (!empty($old_config['api_key'])) {
                    $sanitized['api_key'] = $old_config['api_key'];
                }
            } elseif (!empty($sanitized['api_key'])) {
                $sanitized['api_key'] = Saiita_AI_Toolkit_Utilities::encrypt_api_key($sanitized['api_key']);
            }
        }

        return $sanitized;
    }

    /**
     * AJAX：测试连接
     */
    public function ajax_test_connection()
    {
        Saiita_AI_Toolkit_Security_Validator::verify_admin_ajax('saiita_ai_provider_manager');

        $provider_name = isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- nonce verified in verify_admin_ajax
        $provider = $this->ai_manager->get_provider($provider_name);

        if (!$provider) {
            wp_send_json_error(esc_html__('无效的提供商', 'saiita-ai-content-toolkit'));
        }

        $result = $provider->test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(esc_html__('连接测试成功', 'saiita-ai-content-toolkit'));
    }

    /**
     * AJAX清除AI缓存
     */
    public function ajax_clear_cache()
    {
        Saiita_AI_Toolkit_Security_Validator::verify_admin_ajax('saiita_ai_provider_manager');
        Saiita_AI_Toolkit_Cache_Manager::clear_all();
        wp_send_json_success(esc_html__('缓存已清除', 'saiita-ai-content-toolkit'));
    }

    /**
     * AJAX重置统计
     * 免费版本无需重置统计
     */
    public function ajax_reset_stats()
    {
        Saiita_AI_Toolkit_Security_Validator::verify_admin_ajax('saiita_ai_provider_manager');
        // 免费版本无使用限制，无需重置
        wp_send_json_success(esc_html__('免费版本无使用限制', 'saiita-ai-content-toolkit'));
    }

    /**
     * AJAX获取提供商模型列表
     */
    public function ajax_get_provider_models()
    {
        Saiita_AI_Toolkit_Security_Validator::verify_admin_ajax('saiita_ai_provider_manager');
        $provider_name = isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- nonce verified in verify_admin_ajax
        if (empty($provider_name)) {
            wp_send_json_error(esc_html__('缺少提供商参数', 'saiita-ai-content-toolkit'));
        }
        $provider = $this->ai_manager->get_provider($provider_name);
        if (!$provider) {
            wp_send_json_error(esc_html__('无效的提供商', 'saiita-ai-content-toolkit'));
        }
        $models = method_exists($provider, 'get_models') ? $provider->get_models() : array();
        wp_send_json_success(array('models' => $models));
    }

    /**
     * AJAX获取使用量数据
     * 免费版本返回无限制数据
     */
    public function ajax_get_usage_data()
    {
        Saiita_AI_Toolkit_Security_Validator::verify_admin_ajax('saiita_ai_provider_manager');
        // 免费版本无使用限制
        wp_send_json_success(array('usage' => array(
            'article_optimization' => array('used' => 0, 'limit' => 999999, 'remaining' => 999999, 'reached' => false),
            'tag_optimization' => array('used' => 0, 'limit' => 999999, 'remaining' => 999999, 'reached' => false),
            'category_optimization' => array('used' => 0, 'limit' => 999999, 'remaining' => 999999, 'reached' => false),
            'website_seo' => array('used' => 0, 'limit' => 999999, 'remaining' => 999999, 'reached' => false),
        )));
    }
}