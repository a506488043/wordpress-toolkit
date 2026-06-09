<?php
/**
 * Article Optimization Admin Page
 * 文章优化管理页面
 *
 * @version 1.0.0
 * @author Saiita AI Content Toolkit
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Article Optimization Admin Page 类
 */
class Saiita_AI_Toolkit_Auto_Excerpt_Admin_Page
{
    /**
     * 单例实例
     */
    private static $instance = null;

    /**
     * 模块实例
     */
    private $module;

    /**
     * 获取单例实例
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     */
    private function __construct()
    {
        $this->module = Saiita_AI_Toolkit_Auto_Excerpt::get_instance();
        $this->init_hooks();
    }

    /**
     * 初始化钩子
     */
    private function init_hooks()
    {
        // 添加管理菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // 加载SEO分析相关脚本和样式
        add_action('admin_enqueue_scripts', array($this, 'enqueue_seo_scripts'));

        // 处理批量操作
        add_action('admin_init', array($this, 'handle_batch_operations'));
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'saiita-ai-content-toolkit',
            esc_html__('自动摘要管理', 'saiita-ai-content-toolkit'),
            esc_html__('自动摘要', 'saiita-ai-content-toolkit'),
            'manage_options',
            'saiita-ai-content-toolkit-auto-excerpt',
            array($this, 'render_admin_page')
        );
    }

    /**
     * 渲染管理页面
     */
    public function render_admin_page()
    {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }

        // 处理表单提交
        $this->handle_form_submission();

        // 获取统计数据
        $stats = $this->get_statistics();

        // 获取设置
        $settings = $this->module->get_settings();

        // 检查PRO状态
        $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
        $is_pro = $license_manager->is_pro();
        ?>
        <div class="wrap auto-excerpt-admin">
            <!-- 文章列表和SEO分析 -->
            <div class="act-card">
                <h3><?php esc_html_e('文章列表与SEO分析', 'saiita-ai-content-toolkit'); ?></h3>
                <div id="posts-list-container">
                    <?php
                    // 获取文章列表数据 - 这些是只读操作，用于分页和筛选
                    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- 用户已有管理权限，这些是安全的列表操作参数
                    $per_page = 20;
                    $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                    $offset = ($current_page - 1) * $per_page;

                    $args = array(
                        'post_type' => 'post',
                        'post_status' => 'publish',
                        'posts_per_page' => $per_page,
                        'offset' => $offset,
                        'orderby' => 'modified',
                        'order' => 'DESC'
                    );

                    // 处理筛选 - 已经过sanitize_text_field清理
                    if (isset($_GET['status']) && sanitize_text_field(wp_unslash($_GET['status'])) !== 'all') { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                        if (sanitize_text_field(wp_unslash($_GET['status'])) === 'with_excerpt') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                            $args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 这是必要的查询，已优化
                                array(
                                    'key' => 'post_excerpt',
                                    'value' => '',
                                    'compare' => '!='
                                )
                            );
                        } elseif (sanitize_text_field(wp_unslash($_GET['status'])) === 'without_excerpt') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                            $args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 这是必要的查询，已优化
                                array(
                                    'key' => 'post_excerpt',
                                    'value' => '',
                                    'compare' => '='
                                )
                            );
                        }
                    }

                    $posts_query = new WP_Query($args);
                    $total_posts = $posts_query->found_posts;
                    $total_pages = ceil($total_posts / $per_page);

                    if ($posts_query->have_posts()) {
                        ?>
                        <div class="tablenav top">
                            <div class="alignleft actions bulkactions">
                                <button type="button" class="act-btn" id="batch-seo-analyze"<?php echo !$is_pro ? ' disabled' : ''; ?>>
                                    <span class="dashicons dashicons-search"></span>
                                    <?php esc_html_e('批量SEO分析', 'saiita-ai-content-toolkit'); ?>
                                </button>
                            </div>
                            <div class="tablenav-pages">
                                <span class="displaying-num">
                                    <?php /* translators: %d: total posts count */
                                    printf(esc_html__('共 %d 篇文章', 'saiita-ai-content-toolkit'), absint($total_posts)); ?>
                                </span>
                                <?php
                                $current_url = admin_url('admin.php?page=saiita-ai-content-toolkit-auto-excerpt');
                                if (isset($_GET['status'])) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                                    $current_url .= '&status=' . urlencode(isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : ''); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                                }
                                echo wp_kses_post(paginate_links(array(
                                    'base' => $current_url . '&paged=%#%',
                                    'format' => '',
                                    'prev_text' => esc_html__('&laquo;', 'saiita-ai-content-toolkit'),
                                    'next_text' => esc_html__('&raquo;', 'saiita-ai-content-toolkit'),
                                    'total' => $total_pages,
                                    'current' => $current_page
                                )));
                                ?>
                            </div>
                        </div>

                        <table class="act-table wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col" class="manage-column column-cb check-column">
                                        <input type="checkbox" id="cb-select-all-1">
                                    </th>
                                    <th scope="col"><?php esc_html_e('文章标题', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col"><?php esc_html_e('摘要状态', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col"><?php esc_html_e('SEO得分', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col"><?php esc_html_e('修改时间', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col"><?php esc_html_e('操作', 'saiita-ai-content-toolkit'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while ($posts_query->have_posts()) {
                                    $posts_query->the_post();
                                    $post_id = get_the_ID();
                                    $post_title = get_the_title();
                                    $direct_excerpt = get_post_field('post_excerpt', $post_id);
                                    $has_excerpt = !empty($direct_excerpt);
                                    $excerpt_length = mb_strlen($direct_excerpt);

                                    // 获取SEO分数
                                    $seo_db = new Saiita_AI_Toolkit_SEO_Analyzer_Database();
                                    $seo_score = $seo_db->get_seo_score($post_id);

                                    if ($seo_score !== null) {
                                        $score_class = '';
                                        if ($seo_score >= 90) {
                                            $score_class = 'excellent';
                                        } elseif ($seo_score >= 80) {
                                            $score_class = 'good';
                                        } elseif ($seo_score >= 70) {
                                            $score_class = 'average';
                                        } elseif ($seo_score >= 60) {
                                            $score_class = 'poor';
                                        } else {
                                            $score_class = 'bad';
                                        }
                                    } else {
                                        $seo_score = '-';
                                        $score_class = '';
                                    }
                                    ?>
                                    <tr>
                                        <th scope="row" class="check-column">
                                            <input type="checkbox" name="post_ids[]" value="<?php echo esc_attr($post_id); ?>" class="post-checkbox">
                                        </th>
                                        <td class="column-title">
                                            <strong>
                                                <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>" target="_blank">
                                                    <?php echo esc_html($post_title); ?>
                                                </a>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php if ($has_excerpt) : ?>
                                                <span class="status-badge has-excerpt">
                                                    <?php esc_html_e('有摘要', 'saiita-ai-content-toolkit'); ?>
                                                    <small>(<?php echo esc_html($excerpt_length); ?> 字符)</small>
                                                    <?php
                                                    // 检查是否为AI生成的摘要
                                                    $is_ai_generated = get_post_meta($post_id, '_ai_generated_excerpt', true) ||
                                                                     get_post_meta($post_id, '_auto_excerpt_ai_generated', true);
                                                    if ($is_ai_generated) {
                                                        echo ' <span class="ai-badge">AI</span>';
                                                    }
                                                    ?>
                                                </span>
                                            <?php else : ?>
                                                <span class="status-badge no-excerpt">
                                                    <?php esc_html_e('无摘要', 'saiita-ai-content-toolkit'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td> <?php // phpcs:ignore PSR2.Methods.FunctionCallSignature.CloseBracketLine ?>
                                            <?php if ($seo_score !== '-') : ?>
                                                <div class="seo-score-display">
                                                    <span class="seo-score-badge <?php echo esc_attr($score_class); ?>">
                                                        <?php echo esc_html(number_format($seo_score, 1)); ?>
                                                    </span>
                                                    <div class="seo-score-bar">
                                                        <div class="seo-score-fill" style="width: <?php echo esc_attr($seo_score); ?>%; background: <?php
                                                            echo $seo_score >= 90 ? '#22c55e' : (
                                                                $seo_score >= 80 ? '#3b82f6' : (
                                                                    $seo_score >= 70 ? '#f59e0b' : (
                                                                        $seo_score >= 60 ? '#f97316' : '#ef4444'
                                                                    )
                                                                )
                                                                                                  );
                                                                                                    ?>;"></div>
                                                    </div>
                                                </div>
                                            <?php else : ?>
                                                <span class="seo-score-badge none">
                                                    <?php esc_html_e('未分析', 'saiita-ai-content-toolkit'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html(get_the_modified_date('Y-m-d H:i')); ?></td>
                                        <td>
                                            <div class="row-actions">
                                                <span class="generate-excerpt">
                                                    <button type="button" class="act-btn act-btn-sm generate-excerpt-btn" data-post-id="<?php echo esc_attr($post_id); ?>"<?php if (!$is_pro) {
                                                        echo ' disabled style="opacity:0.5 !important;cursor:not-allowed !important;background:#c3c3c3 !important;border-color:#c3c3c3 !important;color:#9ca3af !important;"';
                                                                                                                                        } ?>>
                                                        📝 生成摘要
                                                    </button>
                                                </span>

                                                <span class="generate-tags">
                                                    <button type="button" class="act-btn act-btn-sm generate-tags-btn" data-post-id="<?php echo esc_attr($post_id); ?>"<?php if (!$is_pro) {
                                                        echo ' disabled style="opacity:0.5 !important;cursor:not-allowed !important;background:#c3c3c3 !important;border-color:#c3c3c3 !important;color:#9ca3af !important;"';
                                                                                                                                     } ?>>
                                                        🏷️ 生成标签
                                                    </button>
                                                </span>
                                                <span class="ai-categorize">
                                                    <button type="button" class="button button-small ai-categorize-btn" data-post-id="<?php echo esc_attr($post_id); ?>" style="background: #22c55e; color: white; border: none; padding: 6px 12px; margin: 2px;"<?php echo !$is_pro ? ' disabled style="opacity:0.5;cursor:not-allowed;background:#c3c3c3;border-color:#c3c3c3;"' : ''; ?>>
                                                        📁 AI分类描述
                                                    </button>
                                                </span>
                                                <span class="ai-optimize-tags">
                                                    <button type="button" class="button button-small ai-optimize-tags-btn" data-post-id="<?php echo esc_attr($post_id); ?>" style="background: #8b5cf6; color: white; border: none; padding: 6px 12px; margin: 2px;"<?php echo !$is_pro ? ' disabled style="opacity:0.5;cursor:not-allowed;background:#c3c3c3;border-color:#c3c3c3;"' : ''; ?>>
                                                        ✨ AI标签描述
                                                    </button>
                                                </span>
                                                <span class="seo-analyze">
                                                    <button type="button" class="act-btn act-btn-sm act-btn-info seo-analyze-btn" data-post-id="<?php echo esc_attr($post_id); ?>"<?php echo !$is_pro ? ' disabled style="opacity:0.5;cursor:not-allowed;background:#c3c3c3;border-color:#c3c3c3;"' : ''; ?>>
                                                        📊 SEO分析
                                                    </button>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                wp_reset_postdata();
                                ?>
                            </tbody>
                        </table>

                        <div class="tablenav bottom">
                            <div class="alignleft actions bulkactions">
                                <button type="button" class="act-btn" id="batch-seo-analyze-bottom"<?php echo !$is_pro ? ' disabled' : ''; ?>>
                                    <span class="dashicons dashicons-search"></span>
                                    <?php esc_html_e('批量SEO分析', 'saiita-ai-content-toolkit'); ?>
                                </button>
                            </div>
                            <div class="tablenav-pages">
                                <?php
                                echo wp_kses_post(paginate_links(array(
                                    'base' => $current_url . '&paged=%#%',
                                    'format' => '',
                                    'prev_text' => esc_html__('&laquo;', 'saiita-ai-content-toolkit'),
                                    'next_text' => esc_html__('&raquo;', 'saiita-ai-content-toolkit'),
                                    'total' => $total_pages,
                                    'current' => $current_page
                                )));
                                ?>
                            </div>
                        </div>
                        <?php
                    } else {
                        echo '<p>'  . esc_html__('没有找到文章', 'saiita-ai-content-toolkit') . '</p>';
                    }
                    ?>
                </div>

                <!-- SEO分析结果模态框 -->
                <div id="seo-result-modal" class="seo-modal" style="display: none;">
                    <div class="modal-backdrop"></div>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><?php esc_html_e('SEO分析结果', 'saiita-ai-content-toolkit'); ?></h3>
                            <button type="button" class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body" id="seo-result-content">
                            <div class="loading"><?php esc_html_e('正在分析...', 'saiita-ai-content-toolkit'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 标签页导航 -->
            <div class="act-card">
                <h2 class="nav-tab-wrapper">
                    <a href="#settings" class="nav-tab nav-tab-active"><?php esc_html_e('基本设置', 'saiita-ai-content-toolkit'); ?></a>
                    <a href="#batch" class="nav-tab"><?php esc_html_e('批量操作', 'saiita-ai-content-toolkit'); ?></a>
                    <a href="#analytics" class="nav-tab"><?php esc_html_e('数据分析', 'saiita-ai-content-toolkit'); ?></a>
                    <a href="#advanced" class="nav-tab"><?php esc_html_e('高级选项', 'saiita-ai-content-toolkit'); ?></a>
                </h2>

                <!-- 基本设置标签页 -->
                <div id="settings" class="tab-content active">
                    <?php $this->render_settings_tab(); ?>
                </div>

                <!-- 批量操作标签页 -->
                <div id="batch" class="tab-content">
                    <?php $this->render_batch_tab(); ?>
                </div>

                <!-- 数据分析标签页 -->
                <div id="analytics" class="tab-content">
                    <?php $this->render_analytics_tab($stats); ?>
                </div>

                <!-- 高级选项标签页 -->
                <div id="advanced" class="tab-content">
                    <?php $this->render_advanced_tab(); ?>
                </div>
            </div>
        </div>

        <!-- 页面样式已提取到外部文件 -->
        <?php wp_enqueue_style(
            'auto-excerpt-admin-page-css',
            SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/css/admin-page.css',
            array(),
            SAIITA_AI_CONTENT_TOOLKIT_VERSION
        ); ?>

        <!-- End of externalized styles -->
        <?php
    }

    /**
     * 渲染设置标签页
     */
    private function render_settings_tab()
    {
        $settings = $this->module->get_settings();
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('auto_excerpt_settings'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="auto_generate"><?php esc_html_e('自动生成摘要', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="auto_generate" name="auto_generate" value="1"
                               <?php checked($settings['auto_generate']); ?>>
                        <span class="description"><?php esc_html_e('保存文章时自动为没有摘要的文章生成摘要', 'saiita-ai-content-toolkit'); ?></span>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="excerpt_length"><?php esc_html_e('摘要长度', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="excerpt_length" name="excerpt_length"
                               value="<?php echo esc_attr($settings['excerpt_length']); ?>"
                               min="50" max="500" step="10">
                        <span class="description"><?php esc_html_e('字符（建议100-200字符）', 'saiita-ai-content-toolkit'); ?></span>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="smart_extraction"><?php esc_html_e('智能提取', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="smart_extraction" name="smart_extraction" value="1"
                               <?php checked($settings['smart_extraction']); ?>>
                        <span class="description"><?php esc_html_e('优先提取文章关键句子，保持语义完整', 'saiita-ai-content-toolkit'); ?></span>
                        <?php if (!function_exists('saiita_ai_content_toolkit_is_ai_available') || !saiita_ai_content_toolkit_is_ai_available()) : ?>
                        <p style="margin-top: 10px;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=saiita-ai-content-toolkit-ai-settings')); ?>" class="button button-secondary">
                                <?php esc_html_e('前往AI设置', 'saiita-ai-content-toolkit'); ?>
                            </a>
                            <span class="description" style="margin-left: 10px;"><?php esc_html_e('⚠️ AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit'); ?></span>
                        </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="preserve_formatting"><?php esc_html_e('保留格式', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="preserve_formatting" name="preserve_formatting" value="1"
                               <?php checked($settings['preserve_formatting']); ?>>
                        <span class="description"><?php esc_html_e('在摘要中保留基本的HTML格式标签', 'saiita-ai-content-toolkit'); ?></span>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="min_content_length"><?php esc_html_e('最小内容长度', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="min_content_length" name="min_content_length"
                               value="<?php echo esc_attr($settings['min_content_length']); ?>"
                               min="50" max="1000" step="10">
                        <span class="description"><?php esc_html_e('字符（内容少于此长度时不生成摘要）', 'saiita-ai-content-toolkit'); ?></span>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="save_settings" class="button button-primary"
                       value="<?php esc_html_e('保存设置', 'saiita-ai-content-toolkit'); ?>">
            </p>
        </form>
        <?php
    }

    /**
     * 渲染批量操作标签页
     */
    private function render_batch_tab()
    {
        ?>
        <h3><?php esc_html_e('批量生成摘要', 'saiita-ai-content-toolkit'); ?></h3>
        <p><?php esc_html_e('为现有的文章批量生成摘要。您可以选择文章类型、数量限制，以及是否覆盖已有摘要。', 'saiita-ai-content-toolkit'); ?></p>

        <div class="batch-progress">
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%"></div>
            </div>
            <div class="progress-text"><?php esc_html_e('准备开始...', 'saiita-ai-content-toolkit'); ?></div>
        </div>

        <form id="batch-generate-form" method="post" action="">
            <?php wp_nonce_field('auto_excerpt_batch'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="batch_post_type"><?php esc_html_e('文章类型', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <select id="batch_post_type" name="batch_post_type">
                            <option value="post"><?php esc_html_e('文章', 'saiita-ai-content-toolkit'); ?></option>
                            <option value="page"><?php esc_html_e('页面', 'saiita-ai-content-toolkit'); ?></option>
                            <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            foreach ($post_types as $post_type) {
                                if (!in_array($post_type->name, array('post', 'page', 'attachment'))) {
                                    ?>
                                    <option value="<?php echo esc_attr($post_type->name); ?>">
                                        <?php echo esc_html($post_type->labels->singular_name); ?>
                                    </option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="batch_limit"><?php esc_html_e('处理数量', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="batch_limit" name="batch_limit" value="50" min="1" max="1000" step="10">
                        <span class="description"><?php esc_html_e('一次最多处理的文章数量', 'saiita-ai-content-toolkit'); ?></span>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="batch_overwrite"><?php esc_html_e('覆盖已有摘要', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="batch_overwrite" name="batch_overwrite" value="1">
                        <span class="description"><?php esc_html_e('勾选此项将覆盖已有的摘要内容', 'saiita-ai-content-toolkit'); ?></span>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="batch_generate" class="button button-primary"
                       value="<?php esc_html_e('开始批量生成', 'saiita-ai-content-toolkit'); ?>"<?php echo !$is_pro ? ' disabled style="opacity:0.5 !important;cursor:not-allowed !important;background:#c3c3c3 !important;border-color:#c3c3c3 !important;color:#9ca3af !important;"' : ''; ?>>
            </p>
        </form>

        <div id="batch-results"></div>
        <?php
    }

    /**
     * 渲染数据分析标签页
     */
    private function render_analytics_tab($stats)
    {
        ?>
        <h3><?php esc_html_e('摘要数据统计', 'saiita-ai-content-toolkit'); ?></h3>

        <div class="analytics-chart">
            <h4><?php esc_html_e('摘要长度分布', 'saiita-ai-content-toolkit'); ?></h4>
            <div class="chart-container">
                <?php
                // 生成摘要长度分布图表数据
                $length_distribution = $this->get_excerpt_length_distribution();

                if (!empty($length_distribution)) {
                    echo '<table class="act-table wp-list-table widefat fixed striped">';
                    echo '<thead><tr><th>'  . esc_html__('长度范围', 'saiita-ai-content-toolkit') . '</th><th>'  . esc_html__('文章数量', 'saiita-ai-content-toolkit') . '</th><th>'  . esc_html__('百分比', 'saiita-ai-content-toolkit') . '</th></tr></thead>';
                    echo '<tbody>';

                    foreach ($length_distribution as $range => $count) {
                        $percentage = $stats['total_posts'] > 0 ? round(($count / $stats['total_posts']) * 100, 1) : 0;
                        echo '<tr>';
                        echo '<td>' . esc_html($range) . '</td>';
                        echo '<td>' . esc_html($count) . '</td>';
                        echo '<td>' . esc_html($percentage) . '%</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                } else {
                    echo '<p>'  . esc_html__('暂无数据', 'saiita-ai-content-toolkit') . '</p>';
                }
                ?>
            </div>
        </div>

        <div class="analytics-chart">
            <h4><?php esc_html_e('最近生成的摘要', 'saiita-ai-content-toolkit'); ?></h4>
            <?php
            $recent_excerpts = $this->get_recent_generated_excerpts(10);

            if (!empty($recent_excerpts)) {
                echo '<table class="act-table wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>'  . esc_html__('文章标题', 'saiita-ai-content-toolkit') . '</th><th>'  . esc_html__('摘要长度', 'saiita-ai-content-toolkit') . '</th><th>'  . esc_html__('生成时间', 'saiita-ai-content-toolkit') . '</th></tr></thead>';
                echo '<tbody>';

                foreach ($recent_excerpts as $post) {
                    echo '<tr>';
                    echo '<td><a href="' . esc_url(get_edit_post_link($post->ID)) . '" target="_blank">' . esc_html(get_the_title($post->ID)) . '</a></td>';
                    echo '<td>' . esc_html(mb_strlen($post->post_excerpt)) . ' '  . esc_html__('字符', 'saiita-ai-content-toolkit') . '</td>';
                    echo '<td>' . esc_html(get_the_modified_date('Y-m-d H:i:s', $post->ID)) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
            } else {
                echo '<p>'  . esc_html__('暂无数据', 'saiita-ai-content-toolkit') . '</p>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * 渲染高级选项标签页
     */
    private function render_advanced_tab()
    {
        ?>
        <h3><?php esc_html_e('高级设置', 'saiita-ai-content-toolkit'); ?></h3>

        <form method="post" action="">
            <?php wp_nonce_field('auto_excerpt_advanced'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('排除的短代码', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <textarea name="exclude_shortcodes" rows="4" class="large-text"
                                  placeholder="gallery&#10;video&#10;audio&#10;caption"><?php
                                    echo esc_html(implode("\n", $this->module->get_settings()['exclude_shortcodes'] ?? array()));
                                    ?></textarea>
                        <span class="description"><?php esc_html_e('每行一个短代码名称，这些短代码的内容将在生成摘要时被忽略', 'saiita-ai-content-toolkit'); ?></span>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="excluded_tags"><?php esc_html_e('保留的HTML标签', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="excluded_tags" name="excluded_tags"
                               value="p,br,strong,em" class="regular-text">
                        <span class="description"><?php esc_html_e('逗号分隔的HTML标签列表，这些标签在清理内容时将被保留', 'saiita-ai-content-toolkit'); ?></span>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="custom_prompt"><?php esc_html_e('自定义提示词', 'saiita-ai-content-toolkit'); ?></label>
                    </th>
                    <td>
                        <textarea id="custom_prompt" name="custom_prompt" rows="4" class="large-text"
                                  placeholder="请为以下内容生成一个简洁的摘要，突出重点信息..."></textarea>
                        <span class="description"><?php esc_html_e('用于指导摘要生成的提示词，留空使用默认提示词', 'saiita-ai-content-toolkit'); ?></span>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="save_advanced" class="button button-primary"
                       value="<?php esc_html_e('保存高级设置', 'saiita-ai-content-toolkit'); ?>">
            </p>
        </form>

        <div class="card">
            <h4><?php esc_html_e('危险操作', 'saiita-ai-content-toolkit'); ?></h4>
            <p><strong><?php esc_html_e('清除所有摘要', 'saiita-ai-content-toolkit'); ?></strong></p>
            <p><?php esc_html_e('此操作将删除所有文章的摘要内容，无法撤销。请谨慎操作。', 'saiita-ai-content-toolkit'); ?></p>
            <form method="post" action="" onsubmit="return confirm('<?php esc_html_e('确定要清除所有摘要吗？此操作无法撤销！', 'saiita-ai-content-toolkit'); ?>')">
                <?php wp_nonce_field('auto_excerpt_clear_all'); ?>
                <input type="submit" name="clear_all_excerpts" class="button"
                       value="<?php esc_html_e('清除所有摘要', 'saiita-ai-content-toolkit'); ?>">
            </form>
        </div>
        <?php
    }

    /**
     * 获取统计数据
     */
    private function get_statistics()
    {
        global $wpdb;

        $total_posts = wp_count_posts('post');
        $total_posts = $total_posts->publish;

        $posts_with_excerpt = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status = 'publish'
            AND post_excerpt != ''
        "); // phpcs:ignore PSR2.Methods.FunctionCallSignature.CloseBracketLine

        $posts_without_excerpt = $total_posts - $posts_with_excerpt;
        $coverage_rate = $total_posts > 0 ? round(($posts_with_excerpt / $total_posts) * 100, 1) : 0;

        return array(
            'total_posts' => $total_posts,
            'posts_with_excerpt' => $posts_with_excerpt,
            'posts_without_excerpt' => $posts_without_excerpt,
            'coverage_rate' => $coverage_rate
        );
    }

    /**
     * 获取摘要长度分布
     */
    private function get_excerpt_length_distribution()
    {
        global $wpdb;

        $results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT
                CASE
                    WHEN LENGTH(post_excerpt) <= 50 THEN '0-50字符'
                    WHEN LENGTH(post_excerpt) <= 100 THEN '51-100字符'
                    WHEN LENGTH(post_excerpt) <= 150 THEN '101-150字符'
                    WHEN LENGTH(post_excerpt) <= 200 THEN '151-200字符'
                    WHEN LENGTH(post_excerpt) <= 300 THEN '201-300字符'
                    ELSE '300+字符'
                END as length_range,
                COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status = 'publish'
            AND post_excerpt != ''
            GROUP BY length_range
            ORDER BY LENGTH(post_excerpt)
        "); // phpcs:ignore PSR2.Methods.FunctionCallSignature.CloseBracketLine

        $distribution = array();
        foreach ($results as $result) {
            $distribution[$result->length_range] = (int) $result->count;
        }

        return $distribution;
    }

    /**
     * 获取最近生成的摘要
     */
    private function get_recent_generated_excerpts($limit = 10)
    {
        return get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 这是必要的查询，已优化
                array(
                    'key' => 'post_excerpt',
                    'value' => '',
                    'compare' => '!='
                )
            )
        ));
    }

    /**
     * 处理表单提交
     */
    private function handle_form_submission()
    {
        if (isset($_POST['save_settings'])) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            if (!wp_verify_nonce(isset($_POST['_wpnonce']) ? sanitize_key(wp_unslash($_POST['_wpnonce'])) : '', 'saiita_auto_excerpt_settings')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                wp_die(esc_html__('安全验证失败', 'saiita-ai-content-toolkit'));
            }

            $settings = array(
                'auto_generate' => isset($_POST['auto_generate']), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'excerpt_length' => isset($_POST['excerpt_length']) ? intval($_POST['excerpt_length']) : 100, // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'smart_extraction' => isset($_POST['smart_extraction']), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'preserve_formatting' => isset($_POST['preserve_formatting']), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'min_content_length' => isset($_POST['min_content_length']) ? intval($_POST['min_content_length']) : 50 // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            );

            $this->module->update_settings($settings);
            echo '<div class="notice notice-success is-dismissible"><p>'  . esc_html__('设置保存成功！', 'saiita-ai-content-toolkit') . '</p></div>';
        }

        if (isset($_POST['save_advanced'])) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            if (!wp_verify_nonce(isset($_POST['_wpnonce']) ? sanitize_key(wp_unslash($_POST['_wpnonce'])) : '', 'saiita_auto_excerpt_advanced')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                wp_die(esc_html__('安全验证失败', 'saiita-ai-content-toolkit'));
            }

            $settings = $this->module->get_settings();

            // 处理排除的短代码
            if (!empty($_POST['exclude_shortcodes'])) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                $exclude_shortcodes = array_filter(array_map('trim', explode("\n", sanitize_text_field(wp_unslash($_POST['exclude_shortcodes'] ?? ''))))); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                $settings['exclude_shortcodes'] = $exclude_shortcodes;
            }

            // 处理其他高级设置
            $settings['excluded_tags'] = sanitize_text_field(isset($_POST['excluded_tags']) ? sanitize_textarea_field(wp_unslash($_POST['excluded_tags'])) : ''); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            $settings['custom_prompt'] = isset($_POST['custom_prompt']) ? sanitize_textarea_field(wp_unslash($_POST['custom_prompt'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

            $this->module->update_settings($settings);
            echo '<div class="notice notice-success is-dismissible"><p>'  . esc_html__('高级设置保存成功！', 'saiita-ai-content-toolkit') . '</p></div>';
        }

        if (isset($_POST['clear_all_excerpts'])) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            if (!wp_verify_nonce(isset($_POST['_wpnonce']) ? sanitize_key(wp_unslash($_POST['_wpnonce'])) : '', 'saiita_auto_excerpt_clear_all')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                wp_die(esc_html__('安全验证失败', 'saiita-ai-content-toolkit'));
            }

            global $wpdb;
            $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                "UPDATE {$wpdb->posts}
                SET post_excerpt = ''
                WHERE post_type = 'post'
            "); // phpcs:ignore PSR2.Methods.FunctionCallSignature.CloseBracketLine

            echo '<div class="notice notice-success is-dismissible"><p>'  . esc_html__('所有摘要已清除！', 'saiita-ai-content-toolkit') . '</p></div>';
        }
    }

    /**
     * 处理批量操作
     */
    public function handle_batch_operations()
    {
        if (isset($_POST['action']) && $_POST['action'] === 'auto_excerpt_batch_generate') { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            if (!wp_verify_nonce(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '', 'saiita_auto_excerpt_batch')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                wp_send_json_error(esc_html__('安全验证失败', 'saiita-ai-content-toolkit'));
            }

            if (!current_user_can('manage_options')) {
                wp_send_json_error(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
            }

            $post_type = sanitize_text_field(isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : 'post'); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            $overwrite = isset($_POST['overwrite']); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

            $args = array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'modified',
                'order' => 'DESC'
            );

            if (!$overwrite) {
                $args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 这是必要的查询，已优化
                    array(
                        'key' => 'post_excerpt',
                        'value' => '',
                        'compare' => '='
                    )
                );
            }

            $posts = get_posts($args);
            $processed = 0;

            foreach ($posts as $post) {
                $excerpt = $this->module->generate_excerpt($post->post_content);
                if ($excerpt) {
                    wp_update_post(array(
                        'ID' => $post->ID,
                        'post_excerpt' => $excerpt
                    ));
                    $processed++;
                }
            }

            wp_send_json_success(array(
                'message' => sprintf(/* translators: %d: processed posts count */ esc_html__('成功处理了 %d 篇文章', 'saiita-ai-content-toolkit'), $processed),
                'processed' => $processed
            ));
        }
    }


    /**
     * 加载SEO分析相关脚本和样式
     */
    public function enqueue_seo_scripts($hook)
    {
        // 只在自动摘要管理页面加载
        if (strpos($hook, 'saiita-ai-content-toolkit-auto-excerpt') === false) {
            return;
        }

        // 加载SEO分析器样式
        wp_enqueue_style(
            'seo-analyzer-css',
            SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/css/seo-analyzer.css',
            array(),
            '1.0.0'
        );

        // 加载新的SEO报告显示样式
        wp_enqueue_style(
            'seo-report-display-css',
            SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/css/seo-report-display.css',
            array(),
            '1.0.0'
        );

        // 加载SEO分析器脚本
        wp_enqueue_script(
            'seo-analyzer-js',
            SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/js/seo-analyzer.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // 加载新的SEO报告显示组件
        wp_enqueue_script(
            'seo-report-display-js',
            SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/js/seo-report-display.js',
            array('seo-analyzer-js'),
            '1.0.0',
            true
        );
    }
}

// 初始化管理页面
Saiita_AI_Toolkit_Auto_Excerpt_Admin_Page::get_instance();