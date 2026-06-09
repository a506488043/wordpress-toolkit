<?php
/**
 * Admin Menu
 * 负责管理菜单注册和所有管理页面渲染
 */

if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_Admin_Menu
{
    /**
     * Plugin Loader 实例
     */
    private $loader;

    public function __construct(Saiita_AI_Toolkit_Plugin_Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu()
    {
        // 工具箱主菜单
        add_menu_page(
            'Saiita AI Content Toolkit',
            __('工具箱', 'saiita-ai-content-toolkit'),
            'read',
            'saiita-ai-content-toolkit',
            array($this, 'admin_page'),
            'dashicons-admin-tools',
            30
        );

        add_submenu_page(
            'saiita-ai-content-toolkit',
            __('文章优化', 'saiita-ai-content-toolkit'),
            __('文章优化', 'saiita-ai-content-toolkit'),
            'manage_options',
            'saiita-ai-content-toolkit-auto-excerpt',
            array($this, 'auto_excerpt_admin_page')
        );

        add_submenu_page(
            'saiita-ai-content-toolkit',
            __('标签优化', 'saiita-ai-content-toolkit'),
            __('标签优化', 'saiita-ai-content-toolkit'),
            'manage_options',
            'saiita-ai-content-toolkit-tag-optimization',
            array($this, 'tag_optimization_admin_page')
        );

        add_submenu_page(
            'saiita-ai-content-toolkit',
            __('分类优化', 'saiita-ai-content-toolkit'),
            __('分类优化', 'saiita-ai-content-toolkit'),
            'manage_options',
            'saiita-ai-content-toolkit-category-optimization',
            array($this, 'category_optimization_admin_page')
        );

        add_submenu_page(
            'saiita-ai-content-toolkit',
            __('网站优化', 'saiita-ai-content-toolkit'),
            __('网站优化', 'saiita-ai-content-toolkit'),
            'manage_options',
            'saiita-ai-content-toolkit-website-optimization',
            array($this, 'website_optimization_admin_page')
        );

        // 工具箱设置主菜单
        add_menu_page(
            __('工具箱设置', 'saiita-ai-content-toolkit'),
            __('工具箱设置', 'saiita-ai-content-toolkit'),
            'manage_options',
            'saiita-ai-content-toolkit-settings',
            array($this, 'toolkit_settings_main_page'),
            'dashicons-admin-settings',
            31
        );

        if (class_exists('Saiita_AI_Toolkit_AI_Settings')) {
            add_submenu_page(
                'saiita-ai-content-toolkit-settings',
                __('AI设置', 'saiita-ai-content-toolkit'),
                __('AI设置', 'saiita-ai-content-toolkit'),
                'manage_options',
                'saiita-ai-content-toolkit-ai-settings',
                array(Saiita_AI_Toolkit_AI_Settings::get_instance(), 'render_settings_page')
            );
        }

        add_submenu_page(
            'saiita-ai-content-toolkit-settings',
            __('文章优化设置', 'saiita-ai-content-toolkit'),
            __('文章优化', 'saiita-ai-content-toolkit'),
            'manage_options',
            'saiita-ai-content-toolkit-auto-excerpt-settings',
            array($this, 'auto_excerpt_settings_page')
        );

        add_submenu_page(
            'saiita-ai-content-toolkit-settings',
            __('AI自动分类设置', 'saiita-ai-content-toolkit'),
            __('AI自动分类', 'saiita-ai-content-toolkit'),
            'manage_options',
            'saiita-ai-content-toolkit-ai-category-settings',
            array($this, 'ai_category_settings_page')
        );
    }

    /**
     * 加载管理后台脚本和样式
     */
    public function admin_enqueue_scripts($hook)
    {
        if (strpos($hook, 'saiita-ai-content-toolkit') !== false || strpos($hook, 'options-general') !== false) {
            wp_enqueue_style(
                'toolkit-variables',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/css/variables.css',
                array(),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            wp_enqueue_style(
                'toolkit-common',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/css/common.css',
                array('toolkit-variables'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            wp_enqueue_style(
                'toolkit-seo-report',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/css/seo-report.css',
                array('toolkit-variables', 'toolkit-common'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            wp_enqueue_style(
                'toolkit-admin-list',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/css/admin-list-unified.css',
                array('toolkit-variables', 'toolkit-common'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            wp_enqueue_style(
                'saiita-settings-form',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/css/settings-form.css',
                array('toolkit-common'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            wp_enqueue_style(
                'toolkit-admin-dashboard-css',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/css/admin-dashboard.css',
                array('toolkit-variables', 'toolkit-common'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            wp_enqueue_script(
                'toolkit-core',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/js/toolkit-core.js',
                array('jquery'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION . '.' . time(),
                true
            );

            wp_enqueue_script(
                'toolkit-migration',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/js/migration-helper.js',
                array('jquery', 'toolkit-core'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION . '.' . time(),
                true
            );

            wp_localize_script('toolkit-core', 'SaiitaAIToolkitConfig', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('saiita_ai_toolkit_nonce'),
                'strings' => array(
                    'saveSuccess' => __('保存成功！', 'saiita-ai-content-toolkit'),
                    'saveError' => __('保存失败，请重试。', 'saiita-ai-content-toolkit'),
                    'networkError' => __('网络错误，请重试。', 'saiita-ai-content-toolkit'),
                    'confirmDelete' => __('确定要删除这个项目吗？此操作不可撤销。', 'saiita-ai-content-toolkit'),
                    'deleteSuccess' => __('删除成功！', 'saiita-ai-content-toolkit'),
                    'deleteError' => __('删除失败，请重试。', 'saiita-ai-content-toolkit'),
                    'loading' => __('加载中...', 'saiita-ai-content-toolkit'),
                    'processing' => __('处理中...', 'saiita-ai-content-toolkit'),
                    'confirm' => __('确定', 'saiita-ai-content-toolkit'),
                    'cancel' => __('取消', 'saiita-ai-content-toolkit')
                )
            ));
        }

        // 委托给子模块
        $this->loader->admin_enqueue_scripts($hook);
    }

    /**
     * 添加插件操作链接
     */
    public function add_plugin_links($links)
    {
        $settings_link = '<a href="' . admin_url('admin.php?page=saiita-ai-content-toolkit') . '">设置</a>';
        $about_link = '<a href="' . admin_url('admin.php?page=saiita-ai-content-toolkit-about') . '">功能说明</a>';
        array_unshift($links, $about_link, $settings_link);
        return $links;
    }

    /**
     * 主管理页面
     */
    public function admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }
        $this->toolbox_about_page();
    }

    /**
     * 工具箱关于页面
     */
    public function toolbox_about_page()
    {
        ?>
        <div class="wrap wordpress-toolkit-dashboard">
            <div class="toolkit-welcome toolkit-welcome--purple">
                <h2><?php esc_html_e('欢迎使用 Saiita AI Content Toolkit！', 'saiita-ai-content-toolkit'); ?></h2>
                <p><?php esc_html_e('这是一个功能强大的WordPress综合工具包，集成了多个实用模块。', 'saiita-ai-content-toolkit'); ?></p>
            </div>

            <div class="toolkit-cards">
                <div class="toolkit-card">
                    <h3>
                        <span class="dashicons dashicons-grid-view" style="color:#2271b1;"></span>
                        <?php esc_html_e('可用模块', 'saiita-ai-content-toolkit'); ?>
                    </h3>
                    <ul class="toolkit-feature-list">
                        <li>
                            <span class="dashicons dashicons-edit" style="color:#2271b1;flex-shrink:0;"></span>
                            <div>
                                <strong><?php esc_html_e('文章优化', 'saiita-ai-content-toolkit'); ?></strong>
                                <p><?php esc_html_e('智能文章摘要生成和SEO分析', 'saiita-ai-content-toolkit'); ?></p>
                            </div>
                        </li>
                        <li>
                            <span class="dashicons dashicons-category" style="color:#dc2626;flex-shrink:0;"></span>
                            <div>
                                <strong><?php esc_html_e('分类优化', 'saiita-ai-content-toolkit'); ?></strong>
                                <p><?php esc_html_e('AI智能分类描述生成', 'saiita-ai-content-toolkit'); ?></p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="toolkit-card">
                    <h3>
                        <span class="dashicons dashicons-external" style="color:#2271b1;"></span>
                        <?php esc_html_e('快速链接', 'saiita-ai-content-toolkit'); ?>
                    </h3>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=saiita-ai-content-toolkit-auto-excerpt')); ?>" class="toolkit-link-btn">
                            <span class="dashicons dashicons-edit" style="color:#2271b1;font-size:20px;width:20px;height:20px;"></span>
                            <div>
                                <strong><?php esc_html_e('文章优化', 'saiita-ai-content-toolkit'); ?></strong>
                                <span class="has-desc"><?php esc_html_e('摘要生成、SEO分析', 'saiita-ai-content-toolkit'); ?></span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * 文章优化管理页面
     */
    public function auto_excerpt_admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }

        if (isset($_POST['_wpnonce'])) { // phpcs:ignore WordPress.Security.NonceVerification
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'saiita_ai_content_toolkit_auto_excerpt')) {
                wp_die(esc_html__('安全验证失败', 'saiita-ai-content-toolkit'));
            }
        }


        $auto_excerpt = $this->loader->get_module('auto_excerpt');
        if ($auto_excerpt) {
            ?>
            <div class="wrap">
                <?php $stats = $auto_excerpt->get_excerpt_stats(); ?>

                <div class="postbox" style="margin-top: 10px;">
                    <div class="inside" style="padding: 15px;">
                        <?php
                        $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                        $excerpt_list = $auto_excerpt->get_excerpt_list($current_page, 15, $status);
                        ?>

                        <?php
                        $license_mgr = Saiita_AI_Toolkit_License_Manager::get_instance();
                        // 免费版本无使用限制
                        if (!$license_mgr->is_pro()) :
                            ?>
                        <div id="act-usage-banner" style="background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid #f59e0b;border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                            <span style="font-size:18px;line-height:1;">💡</span>
                            <span style="color:#92400e;font-size:14px;line-height:1.4;">文章优化功能完全可用，无使用次数限制</span>
                        </div>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <form method="get" action="" style="display: flex; align-items: center; gap: 10px; margin: 0;">
                                    <input type="hidden" name="page" value="saiita-ai-content-toolkit-auto-excerpt">
                                    <select name="status" id="excerpt-status-filter">
                                        <option value="all" <?php selected(isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all', 'all'); ?>><?php esc_html_e('全部文章', 'saiita-ai-content-toolkit'); ?></option>
                                        <option value="with_excerpt" <?php selected(isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all', 'with_excerpt'); ?>><?php esc_html_e('有摘要文章', 'saiita-ai-content-toolkit'); ?></option>
                                        <option value="without_excerpt" <?php selected(isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all', 'without_excerpt'); ?>><?php esc_html_e('无摘要文章', 'saiita-ai-content-toolkit'); ?></option>
                                    </select>
                                    <button type="submit" class="button"><?php esc_html_e('筛选', 'saiita-ai-content-toolkit'); ?></button>

                                    <span style="margin: 0 5px; color: #666;">|</span>

                                    <span style="margin: 0 10px; color: #666;">|</span>
                                    <span style="display: inline-block; font-size: 13px; color: #3c434a; line-height: 20px; vertical-align: middle;">
                                        <span class="dashicons dashicons-post" style="color: #0073aa; font-size: 16px; width: 16px; height: 16px; line-height: 16px; vertical-align: text-bottom; display: inline-block;"></span>
                                        文章: <strong><?php echo number_format($stats['total_posts']); ?></strong>
                                    </span>
                                    <span style="display: inline-block; font-size: 13px; color: #3c434a; margin-left: 15px; line-height: 20px; vertical-align: middle;">
                                        <span class="dashicons dashicons-yes-alt" style="color: #00a32a; font-size: 16px; width: 16px; height: 16px; line-height: 16px; vertical-align: text-bottom; display: inline-block;"></span>
                                        有摘要: <strong><?php echo number_format($stats['with_excerpt']); ?></strong>
                                    </span>
                                    <span style="display: inline-block; font-size: 13px; color: #3c434a; margin-left: 15px; line-height: 20px; vertical-align: middle;">
                                        <span class="dashicons dashicons-no-alt" style="color: #d63638; font-size: 16px; width: 16px; height: 16px; line-height: 16px; vertical-align: text-bottom; display: inline-block;"></span>
                                        无摘要: <strong><?php echo number_format($stats['without_excerpt']); ?></strong>
                                    </span>
                                    <span style="display: inline-block; font-size: 13px; color: #3c434a; margin-left: 15px; line-height: 20px; vertical-align: middle;">
                                        <span class="dashicons dashicons-chart-bar" style="color: #0073aa; font-size: 16px; width: 16px; height: 16px; line-height: 16px; vertical-align: text-bottom; display: inline-block;"></span>
                                        覆盖率: <strong><?php echo esc_html($stats['coverage_rate']); ?>%</strong>
                                        <?php if ($stats['ai_generated'] > 0) : ?>
                                            <span class="badge-ai" style="background: #f0f6fc; color: #0073aa; padding: 2px 6px; border-radius: 3px; font-size: 11px; border: 1px solid #c3d9ea; margin-left: 5px; vertical-align: middle;">🤖 <?php echo esc_html($stats['ai_generated']); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </form>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 15px;">

                            <?php if (!empty($excerpt_list) && isset($excerpt_list['pages']) && $excerpt_list['pages'] > 1) : ?>
                            <div class="tablenav-pages" style="margin: 0;">
                                <?php
                                $current_url = admin_url('admin.php?page=saiita-ai-content-toolkit-auto-excerpt');
                                if (isset($_GET['status'])) {
                                    $current_url .= '&status=' . urlencode(isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all');
                                }
                                ?>
                                <span class="displaying-num">
                                    
                                <?php
                                /* translators: %d: total items count */
                                printf(esc_html__('共 %d 个项目', 'saiita-ai-content-toolkit'), absint($excerpt_list['total']));
                                ?>
                                </span>
                                <?php
                                echo wp_kses_post(paginate_links(array(
                                    'base' => $current_url . '&paged=%#%',
                                    'format' => '',
                                    'prev_text' => esc_html__('&laquo; 上一页', 'saiita-ai-content-toolkit'),
                                    'next_text' => esc_html__('下一页 &raquo;', 'saiita-ai-content-toolkit'),
                                    'total' => $excerpt_list['pages'],
                                    'current' => $current_page
                                )));
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div id="batch-generate-progress" style="display: none; margin: 15px 0;">
                            <div class="progress-container">
                                <h4 id="progress-title">处理中...</h4>
                                <div class="progress-bar-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" id="progress-fill"></div>
                                    </div>
                                    <span class="progress-text" id="progress-text">0%</span>
                                </div>
                                <div class="progress-details" id="progress-details">
                                    <span>当前处理：<span id="current-post">准备中...</span></span>
                                    <span>已处理：<span id="processed-count">0</span> / <span id="total-count">0</span></span>
                                    <span>成功：<span id="success-count">0</span></span>
                                    <span>失败：<span id="error-count">0</span></span>
                                </div>
                            </div>
                        </div>

                        <div id="batch-generate-result" style="display: none; margin: 15px 0;"></div>

                        <?php
                        if (empty($excerpt_list) || !isset($excerpt_list['posts'])) {
                            echo '<div class="notice notice-warning"><p>摘要列表数据加载失败</p></div>';
                        } elseif (empty($excerpt_list['posts'])) {
                            ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col" width="35%"><?php esc_html_e('标题', 'saiita-ai-content-toolkit'); ?></th>
                                        <th scope="col" width="10%"><?php esc_html_e('摘要状态', 'saiita-ai-content-toolkit'); ?></th>
                                        <th scope="col" width="10%"><?php esc_html_e('摘要长度', 'saiita-ai-content-toolkit'); ?></th>
                                        <th scope="col" width="10%"><?php esc_html_e('内容长度', 'saiita-ai-content-toolkit'); ?></th>
                                        <th scope="col" width="15%"><?php esc_html_e('发布日期', 'saiita-ai-content-toolkit'); ?></th>
                                        <th scope="col" width="20%"><?php esc_html_e('操作', 'saiita-ai-content-toolkit'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            <div style="font-size: 16px; color: #666; margin-bottom: 20px;">
                                                <span class="dashicons dashicons-edit-page" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></span>
                                                暂无文章数据
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <?php
                        } else {
                            ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col" width="35%"><?php esc_html_e('标题', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col" width="10%"><?php esc_html_e('摘要状态', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col" width="10%"><?php esc_html_e('摘要长度', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col" width="10%"><?php esc_html_e('内容长度', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col" width="15%"><?php esc_html_e('发布日期', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col" width="20%"><?php esc_html_e('操作', 'saiita-ai-content-toolkit'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($excerpt_list['posts'] as $post) : ?>
                                <tr>
                                    <td>
                                        <strong><a href="<?php echo esc_url($post['edit_url']); ?>" target="_blank"><?php echo esc_html($post['title']); ?></a></strong>
                                        <?php if ($post['status'] !== 'publish') : ?>
                                        <span class="status-draft">草稿</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($post['has_excerpt']) : ?>
                                            <span class="status-active"><?php esc_html_e('有摘要', 'saiita-ai-content-toolkit'); ?></span>
                                            <?php if (isset($post['is_ai_generated']) && $post['is_ai_generated']) : ?>
                                            <span class="ai-badge" style="margin-left: 5px; background: #e6f3ff; color: #0073aa; padding: 2px 6px; border-radius: 3px; font-size: 11px; border: 1px solid #b3d9ff; font-weight: 500;">🤖 AI</span>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span class="status-inactive"><?php esc_html_e('无摘要', 'saiita-ai-content-toolkit'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($post['excerpt_length']); ?> <?php esc_html_e('字符', 'saiita-ai-content-toolkit'); ?></td>
                                    <td><?php echo esc_html($post['content_length']); ?> <?php esc_html_e('字符', 'saiita-ai-content-toolkit'); ?></td>
                                    <td><?php echo esc_html($post['date']); ?></td>
                                    <td>
                                        <div class="action-buttons-container">
                                            <a href="<?php echo esc_url($post['edit_url']); ?>" class="button button-small" target="_blank" style="background: #646970; color: white; border-color: #646970; margin: 0; padding: 5px 10px; font-size: 12px; text-decoration: none;"><?php esc_html_e('编辑', 'saiita-ai-content-toolkit'); ?></a>
                                            <a href="<?php echo esc_url($post['view_url']); ?>" class="button button-small" target="_blank" style="background: #646970; color: white; border-color: #646970; margin: 0; padding: 5px 10px; font-size: 12px; text-decoration: none;"><?php esc_html_e('查看', 'saiita-ai-content-toolkit'); ?></a>
                                            <?php if (!$post['has_excerpt'] || !$post['is_ai_generated']) : ?>
                                            <button type="button" class="button button-small generate-excerpt-single" data-post-id="<?php echo esc_attr($post['ID']); ?>" title="为这篇生成智能摘要" style="background: #46b450; color: white; border-color: #46b450; margin: 0; padding: 5px 10px; font-size: 12px;">
                                                生成摘要
                                            </button>
                                            <?php endif; ?>
                                            <button type="button" class="button button-small generate-tags-single" data-post-id="<?php echo esc_attr($post['ID']); ?>" data-title="<?php echo esc_attr($post['title']); ?>" title="AI生成文章标签" style="background: #ff6900; color: white; border-color: #ff6900; margin: 0; padding: 5px 10px; font-size: 12px;">
                                                生成标签
                                            </button>
                                            <button type="button" class="button button-small seo-analyze-single" data-post-id="<?php echo esc_attr($post['ID']); ?>" title="AI SEO分析" style="background: #0073aa; color: white; border-color: #0073aa; margin: 0; padding: 5px 10px; font-size: 12px;">
                                                SEO分析
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <?php
            wp_enqueue_script(
                'toolkit-admin-dashboard-js',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/js/admin-dashboard.js',
                array('jquery'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION,
                true
            );

            // SEO 报告弹框样式
            wp_enqueue_style(
                'toolkit-seo-report-display-css',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/css/seo-report-display.css',
                array('toolkit-admin-dashboard-css'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );
            $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
            // 免费版本无使用限制
            wp_localize_script('toolkit-admin-dashboard-js', 'saiitaAdminData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'stats'   => array(
                    'total_posts'     => $stats['total_posts'],
                    'without_excerpt' => $stats['without_excerpt'],
                ),
                'isPro' => $license_manager->is_pro(),
                'remaining' => 999999, // 免费版本无限制
                'limit' => 999999, // 免费版本无限制
                'nonces'  => array(
                    'batchGenerateExcerpts' => wp_create_nonce('saiita_batch_generate_excerpts_nonce'),
                    'autoExcerptGenerate'   => wp_create_nonce('saiita_auto_excerpt_generate'),
                    'autoExcerptGenerateTags' => wp_create_nonce('saiita_auto_excerpt_generate_tags'),
                    'applyAiTags'           => wp_create_nonce('saiita_apply_ai_tags'),
                    'batchGenerateTags'     => wp_create_nonce('saiita_batch_generate_tags_nonce'),
                    'autoExcerptSeoAnalyze' => wp_create_nonce('saiita_auto_excerpt_seo_analyze'),
                ),
            ));
            ?>
            <?php
        }
    }

    /**
     * 标签优化管理页面
     */
    public function tag_optimization_admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }

        if (isset($_POST['_wpnonce'])) { // phpcs:ignore WordPress.Security.NonceVerification
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'saiita_ai_content_toolkit_tag_optimization')) {
                wp_die(esc_html__('安全验证失败', 'saiita-ai-content-toolkit'));
            }
        }

        $tag_optimization = $this->loader->get_module('tag_optimization');
        if ($tag_optimization) {
            $tag_optimization->admin_page();
        } else {
            echo '<div class="wrap">';
            echo '<div class="error"><p>' . esc_html__('标签优化模块未正确加载', 'saiita-ai-content-toolkit') . '</p></div></div>';
        }
    }

    /**
     * 分类优化管理页面
     */
    public function category_optimization_admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }

        if (isset($_POST['_wpnonce'])) { // phpcs:ignore WordPress.Security.NonceVerification
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'saiita_ai_content_toolkit_category_optimization')) {
                wp_die(esc_html__('安全验证失败', 'saiita-ai-content-toolkit'));
            }
        }

        $category_optimization = $this->loader->get_module('category_optimization');
        if ($category_optimization) {
            $category_optimization->admin_page();
        } else {
            echo '<div class="wrap">';
            echo '<div class="error"><p>' . esc_html__('分类优化模块未正确加载', 'saiita-ai-content-toolkit') . '</p></div></div>';
        }
    }

    /**
     * 网站优化管理页面
     */
    public function website_optimization_admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }

        if (isset($_POST['_wpnonce'])) { // phpcs:ignore WordPress.Security.NonceVerification
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'saiita_ai_content_toolkit_website_optimization')) {
                wp_die(esc_html__('安全验证失败', 'saiita-ai-content-toolkit'));
            }
        }

        $website_optimization = $this->loader->get_module('website_optimization');
        if ($website_optimization) {
            $website_optimization->admin_page();
        } else {
            echo '<div class="wrap">';
            echo '<div class="error"><p>' . esc_html__('网站优化模块未正确加载', 'saiita-ai-content-toolkit') . '</p></div></div>';
        }
    }

    /**
     * 工具箱设置主页面
     */
    public function toolkit_settings_main_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }
        ?>
        <div class="wrap toolkit-settings-dashboard">
            <div class="toolkit-welcome toolkit-welcome--blue">
                <h2><?php esc_html_e('欢迎使用 Saiita AI Content Toolkit 设置！', 'saiita-ai-content-toolkit'); ?></h2>
                <p><?php esc_html_e('请从左侧子菜单选择具体模块进行设置。', 'saiita-ai-content-toolkit'); ?></p>
            </div>

            <div class="toolkit-cards">
                <div class="toolkit-card">
                    <h3>
                        <span class="dashicons dashicons-admin-settings" style="color:#2271b1;"></span>
                        <?php esc_html_e('快速设置', 'saiita-ai-content-toolkit'); ?>
                    </h3>
                    <div class="toolkit-quick-links">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=saiita-ai-content-toolkit-auto-excerpt-settings')); ?>" class="toolkit-link-btn">
                            <span class="dashicons dashicons-edit" style="color:#2271b1;"></span>
                            <span><?php esc_html_e('文章优化', 'saiita-ai-content-toolkit'); ?></span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=saiita-ai-content-toolkit-ai-category-settings')); ?>" class="toolkit-link-btn">
                            <span class="dashicons dashicons-category" style="color:#dc2626;"></span>
                            <span><?php esc_html_e('AI自动分类', 'saiita-ai-content-toolkit'); ?></span>
                        </a>
                    </div>
                </div>

                <div class="toolkit-card">
                    <h3>
                        <span class="dashicons dashicons-info" style="color:#6b7280;"></span>
                        <?php esc_html_e('系统信息', 'saiita-ai-content-toolkit'); ?>
                    </h3>
                    <div class="toolkit-system-info">
                        <div class="toolkit-system-info-item">
                            <span><?php esc_html_e('插件版本', 'saiita-ai-content-toolkit'); ?></span>
                            <strong><?php echo esc_html(SAIITA_AI_CONTENT_TOOLKIT_VERSION); ?></strong>
                        </div>
                        <div class="toolkit-system-info-item">
                            <span><?php esc_html_e('WordPress版本', 'saiita-ai-content-toolkit'); ?></span>
                            <strong><?php echo esc_html(get_bloginfo('version')); ?></strong>
                        </div>
                        <div class="toolkit-system-info-item">
                            <span><?php esc_html_e('PHP版本', 'saiita-ai-content-toolkit'); ?></span>
                            <strong><?php echo esc_html(PHP_VERSION); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * 文章优化设置页面
     */
    public function auto_excerpt_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }

        $auto_excerpt = $this->loader->get_module('auto_excerpt');
        if ($auto_excerpt) {
            $auto_excerpt->settings_page();
        } else {
            echo '<div class="wrap">';
            echo '<div class="error"><p>' . esc_html__('文章优化模块未正确加载', 'saiita-ai-content-toolkit') . '</p></div></div>';
        }
    }

    /**
     * AI自动分类设置页面
     */
    public function ai_category_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }

        $ai_category = $this->loader->get_module('ai_category');
        if ($ai_category) {
            $ai_category->settings_page();
        } else {
            echo '<div class="wrap">';
            echo '<div class="error"><p>' . esc_html__('AI自动分类模块未正确加载', 'saiita-ai-content-toolkit') . '</p></div></div>';
        }
    }
}
