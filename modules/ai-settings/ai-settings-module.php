<?php
/**
 * AI Settings Module
 *
 * 统一的AI设置管理模块
 *
 * @version 1.0.0
 * @author www.saiita.com.cn
 */

if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_AI_Settings
{
    use Saiita_AI_Toolkit_Singleton;

    /**
     * 初始化钩子
     */
    protected function init($args = array())
    {
        // 加载辅助函数
        require_once dirname(__FILE__) . '/ai-settings-helper.php';
        
        // AI设置子菜单已移至 includes/class-admin-menu.php 统一管理
        // admin_post action（标准 WordPress 表单提交处理模式）
        add_action('admin_post_saiita_ai_settings_save', array($this, 'handle_save_settings'));
    }

    /**
     * 获取AI默认设置（静态方法，供其他模块使用）
     */
    public static function get_ai_default_settings()
    {
        return array(
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

    /**
     * 获取AI设置
     */
    public function get_ai_settings()
    {
        // 使用辅助函数获取设置，确保数据一致性
        return saiita_ai_content_toolkit_get_ai_settings();
    }


    /**
     * 渲染设置页面
     */
    public function render_settings_page()
    {
        // admin_post 重定向后显示成功通知（读写分离：写操作用 admin_post 已验 nonce，此处仅读参数显示通知）
        $settings_updated = isset($_GET['settings-updated']) ? sanitize_text_field(wp_unslash($_GET['settings-updated'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 已通过 check_admin_referer 在 handle_save_settings 中验证 nonce，此处为重定向后仅读取
        if ($settings_updated === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('设置已保存！', 'saiita-ai-content-toolkit') . '</p></div>';
        }

        $settings = $this->get_ai_settings();
        ?>

        <div class="wrap">
            <div class="act-settings-section">
                <h2>🤖 <?php esc_html_e('AI服务配置', 'saiita-ai-content-toolkit'); ?></h2>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('saiita_ai_settings_save'); ?>
                    <input type="hidden" name="action" value="saiita_ai_settings_save">

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="use_ai_generation"><?php esc_html_e('启用AI功能', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="use_ai_generation" name="use_ai_generation" value="1" <?php checked($settings['use_ai_generation']); ?>>
                                <span class="description"><?php esc_html_e('启用后所有模块的AI功能将可用', 'saiita-ai-content-toolkit'); ?></span>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="ai_provider"><?php esc_html_e('AI提供商', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <select id="ai_provider" name="ai_provider">
                                    <option value="deepseek" <?php selected($settings['ai_provider'], 'deepseek'); ?>><?php esc_html_e('DeepSeek', 'saiita-ai-content-toolkit'); ?></option>
                                    <option value="siliconflow" <?php selected($settings['ai_provider'], 'siliconflow'); ?>><?php esc_html_e('硅基流动', 'saiita-ai-content-toolkit'); ?></option>
                                </select>
                                <span class="description"><?php esc_html_e('选择AI服务提供商', 'saiita-ai-content-toolkit'); ?></span>
                            </td>
                        </tr>

                        <!-- DeepSeek 配置 -->
                        <tr class="provider-config deepseek-config" style="<?php echo ($settings['ai_provider'] !== 'deepseek') ? 'display: none;' : ''; ?>">
                            <th scope="row">
                                <label for="deepseek_api_key"><?php esc_html_e('DeepSeek API密钥', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="deepseek_api_key" name="deepseek_api_key"
                                       value="<?php echo esc_attr($settings['deepseek_api_key']); ?>"
                                       class="regular-text" placeholder="sk-...">
                                <span class="description">
                                    <?php esc_html_e('从DeepSeek平台获取API密钥', 'saiita-ai-content-toolkit'); ?>
                                    <a href="https://platform.deepseek.com/api_keys" target="_blank"><?php esc_html_e('获取密钥', 'saiita-ai-content-toolkit'); ?></a><br>
                                    <?php esc_html_e('格式：sk-xxxxxxxx', 'saiita-ai-content-toolkit'); ?>
                                </span>
                            </td>
                        </tr>

                        <tr class="provider-config deepseek-config" style="<?php echo ($settings['ai_provider'] !== 'deepseek') ? 'display: none;' : ''; ?>">
                            <th scope="row">
                                <label for="deepseek_api_base"><?php esc_html_e('API基础URL', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="deepseek_api_base" name="deepseek_api_base"
                                       value="<?php echo esc_attr($settings['deepseek_api_base']); ?>"
                                       class="regular-text">
                                <span class="description"><?php esc_html_e('DeepSeek API服务地址（无需修改）', 'saiita-ai-content-toolkit'); ?></span>
                            </td>
                        </tr>

                        <tr class="provider-config deepseek-config" style="<?php echo ($settings['ai_provider'] !== 'deepseek') ? 'display: none;' : ''; ?>">
                            <th scope="row">
                                <label for="deepseek_model"><?php esc_html_e('AI模型', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <?php
                                $deepseek_models = function_exists('saiita_ai_content_toolkit_get_deepseek_models')
                                    ? saiita_ai_content_toolkit_get_deepseek_models() : array();
                                $current_model = $settings['deepseek_model'] ?? 'deepseek-v4-flash';
                                ?>
                                <select id="deepseek_model" name="deepseek_model">
                                    <?php foreach ($deepseek_models as $model_id => $model_info) : ?>
                                        <option value="<?php echo esc_attr($model_id); ?>" <?php selected($current_model, $model_id); ?>>
                                            <?php echo esc_html($model_info['name'] . ' — ' . $model_info['desc']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if (!isset($deepseek_models[$current_model]) && !empty($current_model)) : ?>
                                        <option value="<?php echo esc_attr($current_model); ?>" selected>
                                            <?php echo esc_html($current_model); ?> (<?php esc_html_e('自定义', 'saiita-ai-content-toolkit'); ?>)
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <span class="description"><?php esc_html_e('选择 DeepSeek AI 模型', 'saiita-ai-content-toolkit'); ?></span>
                            </td>
                        </tr>

                        <!-- 硅基流动 配置 -->
                        <tr class="provider-config siliconflow-config" style="<?php echo ($settings['ai_provider'] !== 'siliconflow') ? 'display: none;' : ''; ?>">
                            <th scope="row">
                                <label for="siliconflow_api_key"><?php esc_html_e('硅基流动 API密钥', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="siliconflow_api_key" name="siliconflow_api_key"
                                       value="<?php echo esc_attr($settings['siliconflow_api_key'] ?? ''); ?>"
                                       class="regular-text" placeholder="sk-...">
                                <span class="description">
                                    <?php esc_html_e('从硅基流动平台获取API密钥', 'saiita-ai-content-toolkit'); ?>
                                    <a href="https://cloud.siliconflow.cn/i/lZiQhOti" target="_blank"><?php esc_html_e('获取密钥', 'saiita-ai-content-toolkit'); ?></a><br>
                                    <?php esc_html_e('格式：sk-xxxxxxxx', 'saiita-ai-content-toolkit'); ?>
                                </span>
                            </td>
                        </tr>

                        <tr class="provider-config siliconflow-config" style="<?php echo ($settings['ai_provider'] !== 'siliconflow') ? 'display: none;' : ''; ?>">
                            <th scope="row">
                                <label for="siliconflow_api_base"><?php esc_html_e('API基础URL', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="siliconflow_api_base" name="siliconflow_api_base"
                                       value="<?php echo esc_attr($settings['siliconflow_api_base'] ?? 'https://api.siliconflow.cn/v1'); ?>"
                                       class="regular-text">
                                <span class="description"><?php esc_html_e('硅基流动 API服务地址（无需修改）', 'saiita-ai-content-toolkit'); ?></span>
                            </td>
                        </tr>

                        <tr class="provider-config siliconflow-config" style="<?php echo ($settings['ai_provider'] !== 'siliconflow') ? 'display: none;' : ''; ?>">
                            <th scope="row">
                                <label for="siliconflow_model"><?php esc_html_e('AI模型', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <?php
                                $siliconflow_models = function_exists('saiita_ai_content_toolkit_get_siliconflow_models')
                                    ? saiita_ai_content_toolkit_get_siliconflow_models() : array();
                                $current_sf_model = $settings['siliconflow_model'] ?? 'deepseek-ai/DeepSeek-V3.2';
                                ?>
                                <select id="siliconflow_model" name="siliconflow_model">
                                    <?php foreach ($siliconflow_models as $model_id => $model_info) : ?>
                                        <option value="<?php echo esc_attr($model_id); ?>" <?php selected($current_sf_model, $model_id); ?>>
                                            <?php echo esc_html($model_info['name'] . ' — ' . $model_info['desc']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if (!isset($siliconflow_models[$current_sf_model]) && !empty($current_sf_model)) : ?>
                                        <option value="<?php echo esc_attr($current_sf_model); ?>" selected>
                                            <?php echo esc_html($current_sf_model); ?> (<?php esc_html_e('自定义', 'saiita-ai-content-toolkit'); ?>)
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <span class="description"><?php esc_html_e('选择硅基流动 AI 模型', 'saiita-ai-content-toolkit'); ?></span>
                                <p class="description">
                                    <a href="https://cloud.siliconflow.cn/models" target="_blank"><?php esc_html_e('查看所有可用模型', 'saiita-ai-content-toolkit'); ?></a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="ai_max_tokens"><?php esc_html_e('最大Token数', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="ai_max_tokens" name="ai_max_tokens"
                                       value="<?php echo esc_attr($settings['ai_max_tokens']); ?>"
                                       min="50" max="1000" step="10">
                                <span class="description"><?php esc_html_e('AI生成内容的最大长度', 'saiita-ai-content-toolkit'); ?></span>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="ai_temperature"><?php esc_html_e('创造性', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="range" id="ai_temperature" name="ai_temperature"
                                       value="<?php echo esc_attr($settings['ai_temperature']); ?>"
                                       min="0" max="1" step="0.1">
                                <span id="temperature-value"><?php echo esc_html($settings['ai_temperature']); ?></span>
                                <span class="description"><?php esc_html_e('值越高越有创造性，建议0.3-0.7', 'saiita-ai-content-toolkit'); ?></span>
                            </td>
                        </tr>

                        <!-- API连接测试 -->
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('API连接测试', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <button type="button" id="test-api-btn" class="button"><?php esc_html_e('🧪 测试API连接', 'saiita-ai-content-toolkit'); ?></button>
                                <span class="description"><?php esc_html_e('测试API连接是否正常工作', 'saiita-ai-content-toolkit'); ?></span>
                                <div id="api-test-result" style="margin-top: 15px;"></div>
                            </td>
                        </tr>
                    </table>

                    <div class="submit">
                        <input type="submit" name="save_settings" class="button button-primary" value="<?php esc_html_e('保存设置', 'saiita-ai-content-toolkit'); ?>">
                    </div>
                </form>
            </div>
        </div>

        <?php
        wp_enqueue_style(
            'ai-settings-admin-css',
            SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/ai-settings/assets/css/admin.css',
            array(),
            SAIITA_AI_CONTENT_TOOLKIT_VERSION
        );
        wp_enqueue_script(
            'ai-settings-admin-js',
            SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/ai-settings/assets/js/admin.js',
            array('jquery'),
            SAIITA_AI_CONTENT_TOOLKIT_VERSION,
            true
        );
        wp_localize_script('ai-settings-admin-js', 'SaiitaAIToolkitSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('saiita_ai_toolkit_test_ai_api_nonce'),
            'i18n'    => array(
                'testing'     => __('测试中...', 'saiita-ai-content-toolkit'),
                'networkError' => __('请求失败，请检查网络连接', 'saiita-ai-content-toolkit'),
                'testApi'     => __('测试API连接', 'saiita-ai-content-toolkit'),
            ),
        ));
        ?>

        <?php
    }

    /**
     * 保存设置
     */
    public function save_settings()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified before this point
        // 读取旧设置，用于保留未变更的密钥
        $old_settings = get_option('saiita_ai_content_toolkit_ai_settings', array());

        $settings = array(
            'use_ai_generation' => isset($_POST['use_ai_generation']), // phpcs:ignore WordPress.Security.NonceVerification.Missing
            'ai_provider' => isset($_POST['ai_provider']) ? sanitize_key(wp_unslash($_POST['ai_provider'])) : '', // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            'deepseek_api_key' => isset($_POST['deepseek_api_key']) ? sanitize_text_field(wp_unslash($_POST['deepseek_api_key'])) : '', // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            'deepseek_api_base' => isset($_POST['deepseek_api_base']) ? sanitize_text_field(wp_unslash($_POST['deepseek_api_base'])) : '', // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            'deepseek_model' => isset($_POST['deepseek_model']) ? sanitize_text_field(wp_unslash($_POST['deepseek_model'])) : '', // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            'siliconflow_api_key' => isset($_POST['siliconflow_api_key']) ? sanitize_text_field(wp_unslash($_POST['siliconflow_api_key'])) : '', // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            'siliconflow_api_base' => isset($_POST['siliconflow_api_base']) ? esc_url_raw(wp_unslash($_POST['siliconflow_api_base'])) : '', // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            'siliconflow_model' => isset($_POST['siliconflow_model']) ? sanitize_text_field(wp_unslash($_POST['siliconflow_model'])) : '', // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            'ai_max_tokens' => isset($_POST['ai_max_tokens']) ? intval($_POST['ai_max_tokens']) : 4096, // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            'ai_temperature' => isset($_POST['ai_temperature']) ? floatval(wp_unslash($_POST['ai_temperature'])) : 0.5, // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        );

        // 加密 API Key（空字段 = 未变更，保留旧值）
        foreach (array('deepseek_api_key', 'siliconflow_api_key') as $key_field) {
            if (empty($settings[$key_field]) && isset($old_settings[$key_field])) {
                $settings[$key_field] = $old_settings[$key_field];
            } elseif (!empty($settings[$key_field])) {
                if ($key_field === 'deepseek_api_key' && is_multisite()) {
                    update_site_option('saiita_ai_content_toolkit_has_deepseek', true);
                }
                $settings[$key_field] = Saiita_AI_Toolkit_Utilities::encrypt_api_key($settings[$key_field]);
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        update_option('saiita_ai_content_toolkit_ai_settings', $settings);
    }

    /**
     * 通过 admin_post 处理表单提交
     */
    public function handle_save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }

        check_admin_referer('saiita_ai_settings_save');

        $this->save_settings();

        wp_safe_redirect(admin_url('admin.php?page=saiita-ai-content-toolkit-ai-settings&settings-updated=true'));
        exit;
    }
}

// 初始化AI设置模块
Saiita_AI_Toolkit_AI_Settings::get_instance();

// AJAX处理函数
add_action('wp_ajax_saiita_ai_toolkit_test_api', function () {
    check_ajax_referer('saiita_ai_toolkit_test_ai_api_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('权限不足', 'saiita-ai-content-toolkit')));
    }

    $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
    $api_base = isset($_POST['api_base']) ? sanitize_text_field(wp_unslash($_POST['api_base'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
    $model = isset($_POST['model']) ? sanitize_text_field(wp_unslash($_POST['model'])) : 'deepseek-v4-flash'; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
    $provider = isset($_POST['provider']) ? sanitize_key(wp_unslash($_POST['provider'])) : 'deepseek'; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

    if (empty($api_key)) {
        wp_send_json_error(array('message' => __('请先填写API密钥', 'saiita-ai-content-toolkit')));
    }

    // 根据提供商设置合适的测试提示词
    $test_prompt = $provider === 'siliconflow' ? 'test' : __('请回复"测试成功"', 'saiita-ai-content-toolkit');

    // 构建API URL，所有provider的base_url已包含版本前缀（如有）
    $api_url = rtrim($api_base, '/') . '/chat/completions';

    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'AI-Content-Toolkit/' . SAIITA_AI_CONTENT_TOOLKIT_VERSION,
        ),
        'body' => wp_json_encode(array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $test_prompt
                )
            ),
            'max_tokens' => 10,
            'temperature' => 0.1
        )),
        'timeout' => 15
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => __('连接失败: ', 'saiita-ai-content-toolkit') . $response->get_error_message()));
    }

    // 检查HTTP状态码
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($response_code !== 200) {
        // 尝试解析 JSON 错误响应
        $error_data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($error_data['error']['message'])) {
            $error_msg = $error_data['error']['message'];
        } else {
            $error_msg = __('HTTP状态码: ', 'saiita-ai-content-toolkit') . $response_code . ' - ' . mb_substr(wp_strip_all_tags($body), 0, 200);
        }
        wp_send_json_error(array('message' => __('API请求失败: ', 'saiita-ai-content-toolkit') . $error_msg));
    }

    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $raw_preview = mb_substr($body, 0, 300);
        wp_send_json_error(array('message' => __('响应格式错误: ', 'saiita-ai-content-toolkit') . json_last_error_msg() . ' (原始响应: ' . esc_html($raw_preview) . ')'));
    }

    if (isset($data['error'])) {
        wp_send_json_error(array('message' => __('API错误: ', 'saiita-ai-content-toolkit') . $data['error']['message']));
    }

    wp_send_json_success(array('message' => __('✅ API连接测试成功！模型可用，配置正确。', 'saiita-ai-content-toolkit')));
});