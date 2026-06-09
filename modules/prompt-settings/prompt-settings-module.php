<?php
/**
 * Prompt Settings Module
 *
 * 统一的AI提示词设置管理模块
 * 允许用户自定义文章优化、分类优化、标签优化、网站优化的AI提示词
 *
 * @version 1.0.0
 * @author www.saiita.com.cn
 */

if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_Prompt_Settings
{
    use Saiita_AI_Toolkit_Singleton;

    private $settings;

    /**
     * 初始化钩子
     */
    protected function init($args = array())
    {
        $this->settings = $this->get_default_prompts();

        // 添加管理菜单 - 作为工具箱设置的子菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // 处理表单提交
        add_action('admin_init', array($this, 'handle_form_submission'));

        // 注册AJAX处理
        add_action('wp_ajax_saiita_ai_toolkit_reset_prompt_default', array($this, 'ajax_reset_prompt_default'));

        // 加载管理后台脚本和样式
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    /**
     * 获取默认提示词设置
     */
    private function get_default_prompts()
    {
        return array(
            'article_optimization' => array(
                'label' => '文章优化',
                'description' => '用于生成文章摘要的AI提示词',
                'prompt' => "请为以下文章内容生成一个简洁、准确的摘要。要求：\n" .
                           "1. 摘要长度控制在{length}字符以内\n" .
                           "2. 突出文章的核心观点和重要信息\n" .
                           "3. 保持语义完整，语句通顺\n" .
                           "4. 不要使用\"本文\"、\"这篇文章\"等引导词\n" .
                           "5. 直接输出摘要内容，不要其他说明\n\n" .
                           "文章内容：\n{content}\n\n" .
                           "摘要："
            ),
            'seo_analysis' => array(
                'label' => 'SEO分析',
                'description' => '用于生成文章SEO分析报告的AI提示词',
                'prompt' => "作为专业SEO分析师，请分析以下文章。只返回JSON格式，不要其他解释文字。\n\n" .
                           "文章信息：\n" .
                           "标题：{title}\n" .
                           "摘要：{excerpt}\n" .
                           "字数：{word_count}字\n" .
                           "标题长度：{title_length}字符\n\n" .
                           "内容：\n" .
                           "{content}\n\n" .
                           "请直接返回标准JSON格式：\n" .
                           "```json\n" .
                           "{\n" .
                           "    \"keywords\": [\"关键词1\", \"关键词2\", \"关键词3\", \"关键词4\", \"关键词5\"],\n" .
                           "    \"score\": {\n" .
                           "        \"overall\": 85,\n" .
                           "        \"title\": 80,\n" .
                           "        \"content\": 85,\n" .
                           "        \"readability\": 90,\n" .
                           "        \"technical\": 80\n" .
                           "    },\n" .
                           "    \"analysis\": {\n" .
                           "        \"title_analysis\": \"标题分析\",\n" .
                           "        \"content_analysis\": \"内容分析\",\n" .
                           "        \"keyword_analysis\": \"关键词分析\",\n" .
                           "        \"readability_analysis\": \"可读性分析\"\n" .
                           "    },\n" .
                           "    \"recommendations\": [\n" .
                           "        {\n" .
                           "            \"title\": \"建议标题\",\n" .
                           "            \"description\": \"问题描述\",\n" .
                           "            \"action\": \"具体操作\",\n" .
                           "            \"impact\": \"预期效果\"\n" .
                           "        }\n" .
                           "    ],\n" .
                           "    \"meta_info\": {\n" .
                           "        \"suggested_title\": \"优化后的标题\",\n" .
                           "        \"meta_description\": \"meta描述\",\n" .
                           "        \"focus_keywords\": [\"核心词1\", \"核心词2\"]\n" .
                           "    }\n" .
                           "}\n" .
                           "```\n\n" .
                           "重要：确保JSON语法正确，只返回代码块"
            ),
            'category_optimization' => array(
                'label' => '分类优化',
                'description' => '用于生成分类描述的AI提示词',
                'prompt' => "请为以下分类生成一个简洁准确的描述：\n" .
                           "分类名称：{category_name}\n" .
                           "分类下的文章主题：{category_content}\n\n" .
                           "要求：\n" .
                           "1. 描述长度控制在100-200字符\n" .
                           "2. 突出分类的主要内容和特色\n" .
                           "3. 语言简洁明了，适合SEO\n" .
                           "4. 直接输出描述内容，不要其他说明\n\n" .
                           "描述："
            ),
            'tag_optimization' => array(
                'label' => '标签优化',
                'description' => '用于生成标签描述的AI提示词',
                'prompt' => "请为以下标签生成一个简洁准确的描述：\n" .
                           "标签名称：{tag_name}\n" .
                           "标签下的文章主题：{tag_content}\n\n" .
                           "要求：\n" .
                           "1. 描述长度控制在80-150字符\n" .
                           "2. 突出标签的主要内容和特色\n" .
                           "3. 语言简洁明了，适合SEO\n" .
                           "4. 直接输出描述内容，不要其他说明\n\n" .
                           "描述："
            ),
            'ai_category' => array(
                'label' => 'AI自动分类',
                'description' => '用于AI自动选择文章分类的提示词',
                'prompt' => "你是一个专业的内容分类助手。请根据文章标题和内容，从给定的分类列表中选择最合适的分类。\n\n" .
                           "要求：\n" .
                           "1. 选择 {max_categories} 个以内的分类\n" .
                           "2. 置信度需要达到 {confidence_threshold} 以上\n" .
                           "3. 优先选择最具体、最相关的分类\n" .
                           "4. 返回JSON格式：{\"categories\": [分类ID数组]}\n" .
                           "5. 如果文章内容不属于任何现有分类，请返回空数组\n\n" .
                           "文章标题：{title}\n\n" .
                           "文章内容：{content}\n\n" .
                           "可选分类：\n" .
                           "{category_list}\n\n" .
                           "请返回JSON格式的分类结果。"
            ),
            'website_optimization' => array(
                'label' => '网站优化',
                'description' => '用于网站整体优化的AI提示词',
                'prompt' => "请分析以下网站内容，提供全面的优化建议：\n" .
                           "网站内容：{website_content}\n\n" .
                           "请从以下方面提供建议：\n" .
                           "1. 内容质量和结构优化\n" .
                           "2. SEO关键词策略\n" .
                           "3. 用户体验改进\n" .
                           "4. 技术优化建议\n\n" .
                           "请提供具体可行的优化方案。"
            )
        );
    }

    /**
     * 获取提示词设置
     */
    public function get_prompt_settings()
    {
        $saved_settings = get_option('saiita_ai_content_toolkit_prompt_settings', array());
        $default_prompts = $this->get_default_prompts();

        // 合并保存的设置和默认设置
        foreach ($default_prompts as $key => $default_prompt) {
            if (isset($saved_settings[$key])) {
                $default_prompts[$key]['prompt'] = $saved_settings[$key]['prompt'] ?? $default_prompt['prompt'];
            }
        }

        return $default_prompts;
    }

    /**
     * 获取特定模块的提示词
     */
    public function get_prompt($module, $variables = array())
    {
        $settings = $this->get_prompt_settings();

        if (!isset($settings[$module])) {
            return '';
        }

        $prompt = $settings[$module]['prompt'];

        // 替换变量
        foreach ($variables as $key => $value) {
            $prompt = str_replace('{' . $key . '}', $value, $prompt);
        }

        return $prompt;
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'saiita-ai-content-toolkit-settings',  // 父菜单：工具箱设置
            __('提示词设置', 'saiita-ai-content-toolkit'),
            __('提示词设置', 'saiita-ai-content-toolkit'),
            'manage_options',
            'saiita-ai-content-toolkit-prompt-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * 处理表单提交
     */
    public function handle_form_submission()
    {
        if (!isset($_POST['save_prompt_settings']) || !check_admin_referer('prompt_settings_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->get_prompt_settings();

        foreach ($settings as $key => $prompt_setting) {
            if (isset($_POST[$key . '_prompt'])) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                $settings[$key]['prompt'] = sanitize_textarea_field(isset($_POST[$key . '_prompt']) ? wp_unslash($_POST[$key . '_prompt']) : ''); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            }
        }

        // 只保存提示词内容
        $saved_settings = array();
        foreach ($settings as $key => $prompt_setting) {
            $saved_settings[$key] = array(
                'prompt' => $prompt_setting['prompt']
            );
        }

        update_option('saiita_ai_content_toolkit_prompt_settings', $saved_settings);

        // 设置成功消息
        add_settings_error(
            'saiita_ai_content_toolkit_prompt_settings',
            'settings_saved',
            __('提示词设置已保存！', 'saiita-ai-content-toolkit'),
            'updated'
        );
    }

    /**
     * AJAX重置默认提示词
     */
    public function ajax_reset_prompt_default()
    {
        check_ajax_referer('saiita_reset_prompt_default_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('权限不足', 'saiita-ai-content-toolkit')));
        }

        $module = isset($_POST['module']) ? sanitize_text_field(wp_unslash($_POST['module'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $default_prompts = $this->get_default_prompts();

        if (!isset($default_prompts[$module])) {
            wp_send_json_error(array('message' => __('无效的模块', 'saiita-ai-content-toolkit')));
        }

        wp_send_json_success(array(
            'prompt' => $default_prompts[$module]['prompt'],
            'message' => __('已重置为默认提示词', 'saiita-ai-content-toolkit')
        ));
    }

    /**
     * 加载管理后台脚本和样式
     */
    public function admin_enqueue_scripts($hook)
    {
        // 只在提示词设置页面加载
        if ('settings_page_saiita-ai-content-toolkit-prompt-settings' !== $hook) {
            return;
        }

        // 加载样式
        wp_enqueue_style(
            'saiita-ai-content-toolkit-prompt-settings-admin',
            SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/prompt-settings/assets/css/admin.css',
            array(),
            SAIITA_AI_CONTENT_TOOLKIT_VERSION
        );

        wp_enqueue_style(
            'saiita-settings-form',
            SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/css/settings-form.css',
            array(),
            SAIITA_AI_CONTENT_TOOLKIT_VERSION
        );

        // 加载脚本
        wp_enqueue_script(
            'saiita-ai-content-toolkit-prompt-settings-admin',
            SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/prompt-settings/assets/js/admin.js',
            array('jquery'),
            SAIITA_AI_CONTENT_TOOLKIT_VERSION,
            true
        );

        // 传递变量到脚本
        wp_localize_script('saiita-ai-content-toolkit-prompt-settings-admin', 'SaiitaAIToolkitPromptSettings', array(
            'resetText' => __('重置为默认', 'saiita-ai-content-toolkit'),
            'resettingText' => __('重置中...', 'saiita-ai-content-toolkit'),
            'requestFailedText' => __('请求失败，请重试', 'saiita-ai-content-toolkit')
        ));
    }

    /**
     * 渲染设置页面
     */
    public function render_settings_page()
    {
        $settings = $this->get_prompt_settings();

        // 显示设置消息
        settings_errors('saiita_ai_content_toolkit_prompt_settings');
        ?>

        <div class="wrap">
            <div class="act-settings-section">
                <h2><?php esc_html_e('提示词设置', 'saiita-ai-content-toolkit'); ?></h2>
                <p class="description"><?php esc_html_e('自定义各AI功能模块的提示词，优化AI生成效果。', 'saiita-ai-content-toolkit'); ?></p>

                <form method="post" action="">
                    <?php wp_nonce_field('prompt_settings_nonce'); ?>

                    <?php foreach ($settings as $key => $prompt_setting) : ?>
                    <h3><?php echo esc_html($prompt_setting['label']); ?></h3>
                    <p class="description"><?php echo esc_html($prompt_setting['description']); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_html($key); ?>_prompt"><?php esc_html_e('提示词内容', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <textarea
                                    id="<?php echo esc_html($key); ?>_prompt"
                                    name="<?php echo esc_html($key); ?>_prompt"
                                    rows="8"
                                    class="large-text code"
                                    style="font-family: 'Courier New', monospace; font-size: 13px;"
                                ><?php echo esc_textarea($prompt_setting['prompt']); ?></textarea>
                                <p class="description">
                                    <?php esc_html_e('支持变量替换，如：{length}、{content}、{category_name}等', 'saiita-ai-content-toolkit'); ?>
                                </p>
                                <button type="button"
                                        class="button button-secondary reset-prompt-btn"
                                        data-module="<?php echo esc_html($key); ?>"
                                        data-nonce="<?php echo esc_attr(wp_create_nonce('saiita_reset_prompt_default_nonce')); ?>">
                                    <?php esc_html_e('重置为默认', 'saiita-ai-content-toolkit'); ?>
                                </button>
                            </td>
                        </tr>
                    </table>
                    <?php endforeach; ?>

                    <div class="submit">
                        <input type="submit" name="save_prompt_settings" class="button button-primary" value="<?php esc_html_e('保存设置', 'saiita-ai-content-toolkit'); ?>">
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}

// 初始化提示词设置模块
Saiita_AI_Toolkit_Prompt_Settings::get_instance();

// 全局辅助函数
if (!function_exists('saiita_ai_content_toolkit_get_prompt')) {
    function saiita_ai_content_toolkit_get_prompt($module, $variables = array())
    {
        $prompt_settings = Saiita_AI_Toolkit_Prompt_Settings::get_instance();
        return $prompt_settings->get_prompt($module, $variables);
    }
}

if (!function_exists('saiita_ai_content_toolkit_get_prompt_settings')) {
    function saiita_ai_content_toolkit_get_prompt_settings()
    {
        $prompt_settings = Saiita_AI_Toolkit_Prompt_Settings::get_instance();
        return $prompt_settings->get_prompt_settings();
    }
}