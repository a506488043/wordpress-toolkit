<?php
/**
 * WordPress AI 插件集成
 *
 * 管理 WordPress 7.0 AI Client 的 DeepSeek Provider 注册、
 * API Key 注入、连接器同步、凭证检查和多站点兼容。
 *
 * @since 2.0.23
 */

if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_WP_AI_Integration
{
    use Saiita_AI_Toolkit_Singleton;

    /**
     * 注册所有 WordPress AI 插件集成钩子
     */
    public function register()
    {
        $this->register_provider_loader();
        $this->register_credential_filters();
        $this->register_connector_filters();
        $this->register_api_key_injection();
    }

    /**
     * 延迟加载 DeepSeek WP Provider
     *
     * 必须在 plugins_loaded(20) 延迟加载，不能直接在文件加载时 class_exists 检查。
     * 原因：WordPress 按 active_plugins 顺序加载插件文件。若本插件比 AI 插件先激活，
     * 此时 AiClient 尚未加载，检查失败后 Provider 文件被跳过，注册钩子永远不会执行。
     */
    private function register_provider_loader()
    {
        add_action('plugins_loaded', function () {
            if (!class_exists('WordPress\AiClient\AiClient')) {
                return;
            }

            $providers = array(
                'class-deepseek-wp-provider.php',
                'class-siliconflow-wp-provider.php',
            );

            foreach ($providers as $file) {
                $path = SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/wp-ai-providers/' . $file;
                if (file_exists($path)) {
                    require_once $path;
                }
            }
        }, 20);
    }

    private function register_credential_filters()
    {
        add_filter('wpai_has_ai_credentials', function ($has_credentials) {
            if (function_exists('saiita_ai_content_toolkit_is_ai_available') && saiita_ai_content_toolkit_is_ai_available()) {
                return true;
            }
            return $has_credentials;
        }, 999);

        add_filter('wpai_pre_has_valid_credentials_check', function ($valid) {
            if (function_exists('saiita_ai_content_toolkit_is_ai_available') && saiita_ai_content_toolkit_is_ai_available()) {
                return true;
            }
            return $valid;
        }, 999);
    }

    private function register_connector_filters()
    {
        add_filter('script_module_data_options-connectors-wp-admin', function ($data) {
            $settings = get_option('saiita_ai_content_toolkit_ai_settings', array());
            $has_key = !empty($settings['deepseek_api_key']);
            if ($has_key && isset($data['connectors']['deepseek']['authentication'])) {
                $data['connectors']['deepseek']['authentication']['isConnected'] = true;
            }
            return $data;
        }, 999);
    }

    private function register_api_key_injection()
    {
        add_action('init', function () {
            if (!class_exists('WordPress\AiClient\AiClient')) {
                return;
            }

            $registry = \WordPress\AiClient\AiClient::defaultRegistry();
            $settings = get_option('saiita_ai_content_toolkit_ai_settings', array());

            if ($registry->hasProvider('deepseek')) {
                $api_key = $this->resolve_api_key('deepseek_api_key', $settings);
                if (!empty($api_key)) {
                    $registry->setProviderRequestAuthentication(
                        'deepseek',
                        new \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication($api_key)
                    );
                }
            }

            if ($registry->hasProvider('siliconflow')) {
                $api_key = $this->resolve_api_key('siliconflow_api_key', $settings);
                if (!empty($api_key)) {
                    $registry->setProviderRequestAuthentication(
                        'siliconflow',
                        new \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication($api_key)
                    );
                }
            }

            $this->ensure_multisite_ai_enabled();
        }, 10);
    }

    private function resolve_api_key($key_field, $settings = null)
    {
        if ($settings === null) {
            $settings = get_option('saiita_ai_content_toolkit_ai_settings', array());
        }

        $encrypted_key = $settings[$key_field] ?? '';
        $api_key = '';

        if (!empty($encrypted_key) && class_exists('Saiita_AI_Toolkit_Utilities')) {
            $api_key = Saiita_AI_Toolkit_Utilities::decrypt_api_key($encrypted_key);
        }

        if (empty($api_key) && is_multisite() && !is_main_site()) {
            switch_to_blog(get_main_site_id());
            $main_settings = get_option('saiita_ai_content_toolkit_ai_settings', array());
            $encrypted_key = $main_settings[$key_field] ?? '';
            restore_current_blog();

            if (!empty($encrypted_key) && class_exists('Saiita_AI_Toolkit_Utilities')) {
                $api_key = Saiita_AI_Toolkit_Utilities::decrypt_api_key($encrypted_key);
            }
        }

        return $api_key;
    }

    private function ensure_multisite_ai_enabled()
    {
        if (!is_multisite()) {
            return;
        }
        $existed_value = get_option('saiita_ai_content_toolkit_wpai_enabled', '___NONEXISTENT___');
        add_option('saiita_ai_content_toolkit_wpai_enabled', true);
        if ('___NONEXISTENT___' === $existed_value && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Saiita AI] Multisite: enabled AI features for blog ' . get_current_blog_id()); // phpcs:ignore
        }
    }
}
