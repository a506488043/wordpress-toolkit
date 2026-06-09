<?php
/**
 * Article Optimization Module - 文章优化模块
 *
 * 根据文章内容自动生成摘要和标签
 *
 * @version 1.0.0
 * @author Saiita AI Content Toolkit
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Article Optimization Module 主类
 */
class Saiita_AI_Toolkit_Auto_Excerpt
{
    use Saiita_AI_Toolkit_Singleton;

    /**
     * 模块设置
     */
    private $settings = array();

    /**
     * wp_insert_post_data 中生成的 slug 对应的 post ID 标记
     * 用于 save_post 阶段写入 _ai_generated_slug meta
     */
    private $pending_ai_slug = null;

    /**
     * SEO分析器实例
     */
    private $seo_analyzer = null;

    /**
     * SEO分析数据库实例
     */
    private $seo_database = null;

    /**
     * 数据库管理器实例
     */
    private $db_manager = null;

    /**
     * 缓存管理器实例
     */
    private $cache_manager = null;

    /**
     * 加载设置
     */
    private function load_settings()
    {
        $default_settings = array(
            'excerpt_length' => 200,
        'auto_generate' => true,
        'preserve_formatting' => true,
        'min_content_length' => 50,
        'smart_extraction' => true,
        'exclude_shortcodes' => array('gallery', 'video', 'audio', 'caption'),
        'auto_mode' => 'draft_always', // 新增：自动生成模式，与AI分类保持一致
        'auto_generate_tags' => true,   // 新增：是否自动生成标签
        'auto_generate_slug' => true,   // 新增：是否自动生成 slug
        'slug_language' => 'english',   // 新增：slug 语言 (english/chinese)
        'slug_min_words' => 4,          // 新增：slug 最少词数/字数
        'slug_max_words' => 8,          // 新增：slug 最多词数/字数
        );

        $saved_settings = get_option('saiita_ai_content_toolkit_auto_excerpt_settings', array());

        $this->settings = wp_parse_args($saved_settings, $default_settings);
    }

    /**
     * 初始化钩子
     */
    protected function init($args = array())
    {
        $this->db_manager = new Saiita_AI_Toolkit_Database_Manager();
        $this->cache_manager = new Saiita_AI_Toolkit_Cache_Manager();
        $this->load_settings();
        $this->init_hooks();
        $this->init_seo_analyzer();

        // 加载AI设置辅助函数
        if (file_exists(SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/ai-settings/ai-settings-helper.php')) {
            require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/ai-settings/ai-settings-helper.php';
        }
    }

    /**
     * 初始化SEO分析器
     */
    private function init_seo_analyzer()
    {
        // 加载SEO分析类
        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/auto-excerpt/includes/class-seo-analyzer-database.php';
        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/auto-excerpt/includes/class-seo-analyzer.php';

        $this->seo_database = new Saiita_AI_Toolkit_SEO_Analyzer_Database();

        // 不在构造函数中建表，改为 admin_init 延迟检查
        add_action('admin_init', array($this, 'maybe_ensure_database'));

        // 初始化SEO分析器 - 只有在AI功能可用时才初始化
        if (function_exists('saiita_ai_content_toolkit_is_ai_available') && saiita_ai_content_toolkit_is_ai_available()) {
            $config = saiita_ai_content_toolkit_get_ai_config();
            $seo_settings = array(
                'ai_provider' => saiita_ai_content_toolkit_get_ai_settings('ai_provider', 'deepseek'),
                'ai_model' => $config['model'],
                'api_key' => $config['api_key'],
                'api_base' => $config['api_base'],
                'max_tokens' => $config['max_tokens'],
                'temperature' => $config['temperature']
            );
            $this->seo_analyzer = new Saiita_AI_Toolkit_SEO_Analyzer($seo_settings);
        } else {
            // AI功能不可用，不初始化SEO分析器
            $this->seo_analyzer = null;
        }
    }


   /**
     * 确保数据库架构是最新的
     */
    /**
     * 延迟检查并创建/更新数据库表（每天最多执行一次）
     */
    public function maybe_ensure_database()
    {
        $cache_key = 'saiita_ai_toolkit_seo_db_checked';
        if (get_transient($cache_key)) {
            return;
        }

        if ($this->seo_database) {
            try {
                $this->seo_database->create_tables();
            } catch (Exception $e) {
                // 静默处理
            }
        }

        $this->ensure_database_schema();
        set_transient($cache_key, true, 12 * HOUR_IN_SECONDS);
    }

    private function ensure_database_schema()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'saiita_auto_excerpt_seo_analysis';

        try {
            // 检查表是否存在
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            if (!$table_exists) {
                // 表不存在，尝试创建
                if ($this->seo_database) {
                    $this->seo_database->create_tables();
                }
                return;
            }

            // 检查raw_ai_analysis字段是否存在
            $raw_column_exists = $wpdb->get_var($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME,
                $table_name,
                'raw_ai_analysis'
            ));

            if (!$raw_column_exists) {
                $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
                    "ALTER TABLE {$table_name} ADD COLUMN raw_ai_analysis longtext DEFAULT NULL COMMENT 'AI原始完整分析文本'" // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name validated by dbDelta
                );
            }

            // 检查parsed_analysis字段是否存在
            $parsed_column_exists = $wpdb->get_var($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME,
                $table_name,
                'parsed_analysis'
            ));

            if (!$parsed_column_exists) {
                $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
                    "ALTER TABLE {$table_name} ADD COLUMN parsed_analysis longtext DEFAULT NULL COMMENT '解析后的AI分析数据(JSON)'" // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name validated by dbDelta
                );
            }

            // 检查ai_model字段类型是否正确
            $ai_model_type = $wpdb->get_var($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
                "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME,
                $table_name,
                'ai_model'
            ));

            if ($ai_model_type === 'decimal') {
                $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
                    "ALTER TABLE {$table_name} MODIFY COLUMN ai_model varchar(100) DEFAULT NULL COMMENT 'AI模型'" // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name validated by dbDelta
                );
            }
        } catch (Exception $e) {
            // 静默处理，不阻塞插件
        }
    }

    /**
     * 初始化钩子
     */
    private function init_hooks()
    {
        // WordPress后台脚本和样式（仅在管理页面加载）
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // 保存文章时自动生成摘要
        add_action('save_post', array($this, 'auto_generate_excerpt_on_save'), 10, 2);

        // 保存时保护 AI 生成的 slug，防止 WordPress 从标题重新生成
        add_filter('wp_insert_post_data', array($this, 'preserve_ai_slug'), 10, 2);

        // 保留API测试功能（仅在后台管理页面使用）
        add_action('wp_ajax_saiita_ai_toolkit_test_deepseek_api', array($this, 'ajax_test_deepseek_api'));

        // 添加批量生成和单个生成摘要的AJAX处理
        add_action('wp_ajax_saiita_ai_toolkit_batch_generate_excerpts', array($this, 'ajax_batch_generate_excerpts'));
        add_action('wp_ajax_auto_excerpt_generate', array($this, 'ajax_generate_single_excerpt'));
        add_action('wp_ajax_auto_excerpt_batch_generate', array($this, 'ajax_batch_generate_excerpts'));

        // 添加通用AJAX调试钩子
        add_action('admin_init', array($this, 'debug_ajax_requests'));

        // 添加AI生成标签的AJAX处理
        add_action('wp_ajax_saiita_apply_ai_tags', array($this, 'ajax_apply_tags'));
        add_action('wp_ajax_saiita_ai_toolkit_batch_generate_tags', array($this, 'ajax_batch_generate_tags'));
        add_action('wp_ajax_auto_excerpt_generate_tags', array($this, 'ajax_generate_single_tags'));

        // AI分类和标签优化相关AJAX处理
        add_action('wp_ajax_auto_excerpt_ai_categorize', array($this, 'ajax_ai_categorize'));
        add_action('wp_ajax_auto_excerpt_ai_optimize_tags', array($this, 'ajax_ai_optimize_tags'));

        // 前端脚本
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // AI摘要显示功能
        add_filter('the_content', array($this, 'display_ai_summary_in_content'));

        // SEO分析相关AJAX处理
        add_action('wp_ajax_auto_excerpt_seo_analyze', array($this, 'ajax_analyze_post_seo'));
        add_action('wp_ajax_auto_excerpt_get_seo_report', array($this, 'ajax_get_seo_report'));
        add_action('wp_ajax_saiita_ai_toolkit_batch_analyze_seo', array($this, 'ajax_batch_analyze_seo'));
        add_action('wp_ajax_saiita_get_seo_statistics', array($this, 'ajax_get_seo_statistics'));
        add_action('wp_ajax_saiita_get_posts_for_seo', array($this, 'ajax_get_posts_for_seo'));
        add_action('wp_ajax_saiita_get_seo_reports_list', array($this, 'ajax_get_seo_reports_list'));

        // 数据库架构更新AJAX处理
        add_action('wp_ajax_saiita_ai_toolkit_update_seo_schema', array($this, 'ajax_update_seo_analysis_schema'));

        // 添加定时任务功能
        add_action('wp', array($this, 'schedule_daily_excerpt_generation'));

        // 定时任务执行钩子
        add_action('saiita_auto_excerpt_daily_generation', array($this, 'execute_daily_excerpt_generation'));
    }

    /**
     * 激活模块
     */
    public function activate()
    {


        try {
            // 创建默认设置（仅在不存在时）
            if (!get_option('saiita_ai_content_toolkit_auto_excerpt_settings')) {
                add_option('saiita_ai_content_toolkit_auto_excerpt_settings', $this->settings);
            } else {
            }

            // 重置失败计数
            update_option('saiita_ai_content_toolkit_auto_excerpt_consecutive_failures', 0);
            if (get_option('saiita_auto_excerpt_consecutive_failures') !== false) {
                delete_option('saiita_auto_excerpt_consecutive_failures');
            }

            // 注册定时任务
            $this->schedule_daily_excerpt_generation();

            // 为现有文章生成摘要（已禁用，避免超时问题）
            // 如需批量生成，请手动调用 batch_generate_existing_excerpts() 方法
        } catch (Exception $e) {
        }
    }

    /**
     * 停用模块
     */
    public function deactivate()
    {
        // 清理缓存
        wp_cache_flush();

        // 取消定时任务
        $this->unschedule_daily_excerpt_generation();

        // 清理失败计数
        delete_option('saiita_auto_excerpt_consecutive_failures');
        delete_option('saiita_ai_content_toolkit_auto_excerpt_consecutive_failures');
    }

    /**
     * 加载管理后台脚本和样式
     */
    public function admin_enqueue_scripts($hook)
    {
        // 只在相关页面加载统一脚本和样式
        if (strpos($hook, 'saiita-ai-content-toolkit-auto-excerpt') === false && strpos($hook, 'saiita-ai-content-toolkit') === false) {
            return;
        }
            // 使用统一的模块CSS
            wp_enqueue_style(
                'saiita-ai-content-toolkit-modules-admin',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/css/modules-admin.css',
                array('toolkit-common'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            // 加载统一的模块JavaScript
            wp_enqueue_script(
                'saiita-ai-content-toolkit-modules-admin',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/js/modules-admin.js',
                array('jquery', 'toolkit-core'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION . '.' . time(), // 添加时间戳强制刷新缓存
                true
            );

            // 加载管理页面特定样式
            wp_enqueue_style(
                'auto-excerpt-admin-page',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/css/admin-page.css',
                array(),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            // 加载设置页面样式
            wp_enqueue_style(
                'auto-excerpt-settings-page',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/css/settings-page.css',
                array(),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            // 加载现代化表单样式
            wp_enqueue_style(
                'saiita-settings-form',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/css/settings-form.css',
                array(),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            // 加载管理页面脚本
            wp_enqueue_script(
                'auto-excerpt-admin-page',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/js/admin-page.js',
                array('jquery'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION,
                true
            );

            // 本地化脚本
            wp_localize_script('auto-excerpt-admin-page', 'AutoExcerptConfig', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'generateNonce' => wp_create_nonce('saiita_auto_excerpt_generate'),
                'tagsNonce' => wp_create_nonce('saiita_auto_excerpt_generate_tags'),
                'categorizeNonce' => wp_create_nonce('saiita_auto_excerpt_ai_categorize'),
                'optimizeTagsNonce' => wp_create_nonce('saiita_auto_excerpt_ai_optimize_tags'),
                'seoAnalyzeNonce' => wp_create_nonce('saiita_auto_excerpt_seo_analyze'),
                'getSeoReportNonce' => wp_create_nonce('saiita_auto_excerpt_get_seo_report'),
                'batchNonce' => wp_create_nonce('saiita_batch_generate_excerpts_nonce'),
                'batchTagsNonce' => wp_create_nonce('saiita_batch_generate_tags_nonce'),
                'batchAnalyzeText' => __('批量SEO分析', 'saiita-ai-content-toolkit')
            ));
    }

    /**
     * 加载前端脚本和样式
     */
    public function enqueue_scripts()
    {
        // 只在文章页面加载AI摘要显示功能
        if (is_single() && get_post_type() === 'post') {
            // 加载AI摘要CSS
            wp_enqueue_style(
                'ai-summary-display',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/css/ai-summary-display.css',
                array(),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            // 加载AI摘要JavaScript
            wp_enqueue_script(
                'ai-summary-display',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/auto-excerpt/assets/js/ai-summary-display.js',
                array('jquery'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION,
                true
            );
        }
    }

    /**
     * 在文章内容中显示AI摘要
     */
    public function display_ai_summary_in_content($content)
    {
        // 只在文章页面显示
        if (!is_single() || get_post_type() !== 'post' || !in_the_loop()) {
            return $content;
        }

        global $post;
        if (!$post || empty($post->post_excerpt)) {
            return $content;
        }

        // 检查是否为AI生成的摘要
        $is_ai_generated = get_post_meta($post->ID, '_ai_generated_excerpt', true) ||
                          get_post_meta($post->ID, '_auto_excerpt_ai_generated', true);

        if (!$is_ai_generated) {
            // 使用启发式检测
            $is_ai_generated = $this->was_ai_generated($post->post_excerpt, $post->post_content);
        }

        // 只有AI生成的摘要才显示
        if ($is_ai_generated) {
            $ai_summary_html = $this->get_ai_summary_html($post->post_excerpt);
            // 将AI摘要插入到文章内容之前，确保输出安全
            $content = wp_kses_post($ai_summary_html . $content);
        }

        return $content;
    }

    /**
     * 获取AI摘要HTML
     */
    private function get_ai_summary_html($excerpt)
    {
        // 清理摘要，移除开头和结尾的空格
        $cleaned_excerpt = trim($excerpt);

        ob_start();
        ?>
        <div class="ai-summary-container">
            <div class="ai-summary-header">
                <span class="ai-summary-badge">AI摘要</span>
            </div>
            <div class="ai-summary-content">
                <p class="ai-summary-text"><?php echo esc_html($cleaned_excerpt); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 添加元框到文章编辑页面
     */
    public function add_meta_box()
    {
        add_meta_box(
            'auto-excerpt-meta-box',
            esc_html__('智能摘要生成器', 'saiita-ai-content-toolkit'),
            array($this, 'render_meta_box'),
            'post',
            'normal',
            'high'
        );
    }

    /**
     * 渲染元框内容
     */
    public function render_meta_box($post)
    {


        try {
            // 添加nonce验证
            wp_nonce_field('auto_excerpt_meta_box', 'auto_excerpt_nonce');

            $current_excerpt = $post->post_excerpt;
            $content_length = mb_strlen(wp_strip_all_tags($post->post_content));

            // 简化版本 - 确保基本内容显示
            echo '<div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; margin: 10px 0;">';
            echo '<h3 style="margin: 0 0 10px 0; color: #23282d;">🤖 智能摘要生成器</h3>';
            echo '<p><strong>调试信息：</strong></p>';
            echo '<p>文章ID: ' . esc_html($post->ID) . '</p>';
            echo '<p>内容长度: ' . esc_html($content_length) . ' 字符</p>';
            echo '<p>当前摘要: ' . (!empty($current_excerpt) ? '已有摘要' : '暂无摘要') . '</p>';
            echo '<hr>';

            // 测试按钮
            echo '<button type="button" id="generate-excerpt-btn" class="button button-primary">生成智能摘要</button>';
            echo '<div id="excerpt-result" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; min-height: 50px;">';
            echo '摘要内容将显示在这里...';
            echo '</div>';
            echo '</div>';


            return;
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>自动摘要模块加载出错：' . esc_html($e->getMessage()) . '</p></div>';
            return;
        }
        ?>
        <div class="auto-excerpt-container">
            <div class="auto-excerpt-header">
                <h3>
                    <?php esc_html_e('智能摘要生成', 'saiita-ai-content-toolkit'); ?>
                    <?php if (saiita_ai_content_toolkit_is_ai_available()) : ?>
                        <span class="ai-badge">🤖 AI</span>
                    <?php endif; ?>
                </h3>
                <p class="description">
                    <?php
                    if (saiita_ai_content_toolkit_is_ai_available()) {
                        esc_html_e('基于DeepSeek AI智能生成摘要，支持中英文混合内容。', 'saiita-ai-content-toolkit');
                    } else {
                        esc_html_e('基于文章内容智能生成摘要，支持中英文混合内容。', 'saiita-ai-content-toolkit');
                    }
                    ?>
                </p>
            </div>

            <div class="auto-excerpt-controls">
                <button type="button" id="generate-excerpt-btn" class="button button-primary">
                    <span class="dashicons dashicons-magic"></span>
                    <?php esc_html_e('生成智能摘要', 'saiita-ai-content-toolkit'); ?>
                </button>

                <button type="button" id="regenerate-excerpt-btn" class="button">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('重新生成', 'saiita-ai-content-toolkit'); ?>
                </button>

                <div class="auto-excerpt-options">
                    <label>
                        <input type="checkbox" id="append-mode" value="1">
                        <?php esc_html_e('追加模式（不替换现有摘要）', 'saiita-ai-content-toolkit'); ?>
                    </label>
                </div>
            </div>

            <div class="auto-excerpt-status" style="display: none;">
                <div class="spinner"></div>
                <span class="status-text"></span>
            </div>

            <div class="auto-excerpt-preview" style="display: none;">
                <h4><?php esc_html_e('预览生成的摘要：', 'saiita-ai-content-toolkit'); ?></h4>
                <div class="excerpt-preview-content"></div>
                <div class="excerpt-actions">
                    <button type="button" id="apply-excerpt-btn" class="button button-primary">
                        <?php esc_html_e('应用此摘要', 'saiita-ai-content-toolkit'); ?>
                    </button>
                    <button type="button" id="cancel-excerpt-btn" class="button">
                        <?php esc_html_e('取消', 'saiita-ai-content-toolkit'); ?>
                    </button>
                </div>
            </div>

            <div class="auto-excerpt-info">
                <p>
                    <strong><?php esc_html_e('当前状态：', 'saiita-ai-content-toolkit'); ?></strong>
                    <span id="excerpt-status">
                        <?php if (!empty($current_excerpt)) : ?>
                            <span class="status-exists"><?php esc_html_e('已有摘要', 'saiita-ai-content-toolkit'); ?></span>
                        <?php else : ?>
                            <span class="status-empty"><?php esc_html_e('暂无摘要', 'saiita-ai-content-toolkit'); ?></span>
                        <?php endif; ?>
                    </span>
                </p>
                <p>
                    <strong><?php esc_html_e('内容长度：', 'saiita-ai-content-toolkit'); ?></strong>
                    <span id="content-length"><?php echo esc_html($content_length); ?></span> <?php esc_html_e('字符', 'saiita-ai-content-toolkit'); ?>
                </p>
                <p>
                    <strong><?php esc_html_e('建议摘要长度：', 'saiita-ai-content-toolkit'); ?></strong>
                    <span id="suggested-length"><?php echo esc_html($this->settings['excerpt_length']); ?></span> <?php esc_html_e('字符', 'saiita-ai-content-toolkit'); ?>
                </p>
            </div>

            <div class="auto-excerpt-settings">
                <h4><?php esc_html_e('生成选项：', 'saiita-ai-content-toolkit'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="excerpt_length"><?php esc_html_e('摘要长度', 'saiita-ai-content-toolkit'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="excerpt_length"
                                   value="<?php echo esc_attr($this->settings['excerpt_length']); ?>"
                                   min="50" max="500" step="10" class="small-text">
                            <span class="description"><?php esc_html_e('字符', 'saiita-ai-content-toolkit'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="smart_extraction"><?php esc_html_e('智能提取', 'saiita-ai-content-toolkit'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="smart_extraction" value="1"
                                   <?php checked($this->settings['smart_extraction']); ?>>
                            <span class="description"><?php esc_html_e('优先提取文章关键句和段落', 'saiita-ai-content-toolkit'); ?></span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX处理生成摘要
     */
    public function ajax_generate_excerpt()
    {
        // 验证 nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'saiita_auto_excerpt_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $content = wp_kses_post(wp_unslash($_POST['content'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $append_mode = isset($_POST['append_mode']) ? (bool)$_POST['append_mode'] : false; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $length = isset($_POST['length']) ? intval($_POST['length']) : $this->settings['excerpt_length']; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $smart_extraction = isset($_POST['smart_extraction']) ? (bool)$_POST['smart_extraction'] : $this->settings['smart_extraction']; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

        // 生成摘要
        $use_ai = saiita_ai_content_toolkit_is_ai_available();
        $excerpt = $this->generate_excerpt($content, $length, $smart_extraction);

        if ($excerpt) {
            // 检测是否使用了AI生成（基于设置和API状态）
            $is_ai_generated = $use_ai && $this->was_ai_generated($excerpt, $content);

            $message = $is_ai_generated ?
                esc_html__('AI摘要生成成功', 'saiita-ai-content-toolkit') :
                esc_html__('摘要生成成功', 'saiita-ai-content-toolkit');

            wp_send_json_success(array(
                'excerpt' => $excerpt,
                'length' => mb_strlen($excerpt),
                'message' => $message,
                'ai_generated' => $is_ai_generated
            ));
        } else {
            $error_message = $use_ai ?
                esc_html__('AI生成失败且内容太短，无法生成摘要', 'saiita-ai-content-toolkit') :
                esc_html__('无法生成摘要，内容可能太短', 'saiita-ai-content-toolkit');

            wp_send_json_error(array(
                'message' => $error_message
            ));
        }
    }

    /**
     * 生成文章摘要
     */
    public function generate_excerpt($content, $length = null, $smart_extraction = null)
    {

        if (empty($content)) {
            return '';
        }

        $length = $length ?: $this->settings['excerpt_length'];
        $smart_extraction = $smart_extraction ?: $this->settings['smart_extraction'];

        // 检查内容长度
        $content_length = mb_strlen(wp_strip_all_tags($content));

        if ($content_length < $this->settings['min_content_length']) {
            return '';
        }

        // 清理内容
        $clean_content = $this->clean_content($content);

        // AI未配置时直接返回错误
        if (!saiita_ai_content_toolkit_is_ai_available()) {
            return new WP_Error('ai_not_configured', __('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit'));
        }

        // 使用AI生成摘要
        $ai_excerpt = $this->generate_ai_excerpt($clean_content, $length);

        if ($ai_excerpt) {
            return $ai_excerpt;
        }

        // AI调用失败，返回空字符串
        return '';
    }

    /**
     * 使用AI生成标题
     */
    private function generate_ai_title($content)
    {
        try {
            // 检查AI功能是否可用
            if (!saiita_ai_content_toolkit_is_ai_available()) {
                return null;
            }

            // 构建提示词
            $prompt = "请为以下文章内容生成一个简洁、吸引人的标题。要求：\n";
            $prompt .= "1. 标题长度控制在10-20字之间\n";
            $prompt .= "2. 突出文章的核心内容和价值\n";
            $prompt .= "3. 语言简洁明了，有吸引力\n";
            $prompt .= "4. 直接输出标题，不要其他说明\n\n";
            $prompt .= "文章内容：\n" . mb_substr($content, 0, 1500) . "\n\n";
            $prompt .= "标题：";

            // 调用AI API（给足 max_tokens 余量，推理模型的 reasoning 会消耗 token 预算）
            $response = saiita_ai_content_toolkit_call_ai_api(
                $prompt,
                array(
                    'max_tokens' => 200,
                    'temperature' => 0.3
                )
            );

            if (!is_wp_error($response) && !empty($response)) {
                $title = trim($response);

                // 清理AI生成的内容
                $title = trim($title, '"\'');
                $title = preg_replace('/^(标题|Title)[：:]\s*/', '', $title);
                $title = preg_replace('/\s+/', ' ', $title);

                // 确保标题长度合适
                if (mb_strlen($title) > 30) {
                    $title = mb_substr($title, 0, 30);
                }

                return trim($title);
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * 使用AI生成摘要
     */
    private function generate_ai_excerpt($content, $length)
    {
        try {
            // 构建提示词
            $prompt = $this->build_ai_prompt($content, $length);

            // 发送API请求（给足 max_tokens 余量，推理模型的 reasoning 会消耗 token 预算）
            $response = saiita_ai_content_toolkit_call_ai_api(
                $prompt,
                array(
                    'max_tokens' => 500,
                    'temperature' => 0.3
                )
            );

            if (is_wp_error($response)) {
                if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('AI API Error: ' . $response->get_error_code() . ' - ' . $response->get_error_message()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                }
            }
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('Response is WP_Error: ' . (is_wp_error($response) ? 'YES' : 'NO')); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }            if (!is_wp_error($response)) {
            }

            if (!is_wp_error($response) && !empty($response)) {
                $excerpt = trim($response);

                // 清理AI生成的内容
                $excerpt = $this->clean_ai_excerpt($excerpt);

                // 确保摘要长度合适
                if (mb_strlen($excerpt) > $length * 1.5) {
                    $excerpt = mb_substr($excerpt, 0, $length) . '...';
                }

                return $excerpt;
            } else {
                if (is_wp_error($response)) {
                }
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * 构建AI提示词
     */
    private function build_ai_prompt($content, $length)
    {
        // 使用动态提示词设置
        if (function_exists('saiita_ai_content_toolkit_get_prompt')) {
            $prompt = saiita_ai_content_toolkit_get_prompt('article_optimization', array(
                'length' => $length,
                'content' => mb_substr($content, 0, 2000)
            ));
        } else {
            // 降级到默认提示词
            $prompt = "请为以下文章内容生成一个简洁、准确的摘要。要求：\n";
            $prompt .= "1. 摘要长度控制在{$length}字符以内\n";
            $prompt .= "2. 突出文章的核心观点和重要信息\n";
            $prompt .= "3. 保持语义完整，语句通顺\n";
            $prompt .= "4. 不要使用\"本文\"、\"这篇文章\"等引导词\n";
            $prompt .= "5. 直接输出摘要内容，不要其他说明\n\n";
            $prompt .= "文章内容：\n" . mb_substr($content, 0, 2000) . "\n\n";
            $prompt .= "摘要：";
        }

        return $prompt;
    }

    /**
     * 调用DeepSeek API
     */
    private function call_deepseek_api($api_key, $api_base, $model, $prompt, $max_tokens, $temperature)
    {
        $url = rtrim($api_base, '/') . '/chat/completions';

        // 构建符合官方API规范的消息格式
        $data = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => '你是一个专业的文章摘要助手，能够准确理解文章内容并生成简洁、准确的摘要。'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'stream' => false,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        );

        // 根据模型类型添加相应参数
        if (in_array($model, array('deepseek-v4-flash', 'deepseek-v4-pro', 'deepseek-chat', 'deepseek-v3', 'deepseek-coder'), true)) {
            $data['max_tokens'] = $max_tokens;
            $data['temperature'] = $temperature;
        } elseif ($model === 'deepseek-reasoner') {
            // deepseek-reasoner 不支持 max_tokens 和 temperature 参数
            // 模型会自动推理，无需手动设置长度限制
        }

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
                'Accept' => 'application/json'
            ),
            'body' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'timeout' => 30,
            'sslverify' => true,
            'user-agent' => 'WordPress-Toolkit/1.0.5'
        );

        // 记录API请求日志（仅在调试模式下）
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $error_message = 'HTTP请求失败: ' . $response->get_error_message();
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
            throw new Exception(esc_html($error_message));
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if (defined('WP_DEBUG') && WP_DEBUG) {
        }

        // 检查HTTP状态码
        if ($http_code !== 200) {
            throw new Exception('API请求失败，HTTP状态码: ' . esc_html($http_code));
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON解析失败: ' . esc_html(json_last_error_msg()) . ' - 原始响应: ' . esc_html($body));
        }

        // 检查API错误
        if (isset($data['error'])) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : '未知API错误';
            $error_type = isset($data['error']['type']) ? $data['error']['type'] : '';
            throw new Exception('API错误 [' . esc_html($error_type) . ']: ' . esc_html($error_message));
        }

        // 检查响应格式
        if (!isset($data['choices']) || !is_array($data['choices']) || empty($data['choices'])) {
            throw new Exception('API响应格式异常：缺少choices字段');
        }

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('API响应格式异常：缺少message.content字段');
        }

        return $data;
    }

    /**
     * 清理AI生成的摘要
     */
    private function clean_ai_excerpt($excerpt)
    {
        // 移除可能的引号
        $excerpt = trim($excerpt, '"\'');

        // 移除开头的"摘要："等标识
        $excerpt = preg_replace('/^(摘要|简介|概述)[：:]\s*/', '', $excerpt);

        // 移除多余的空白字符
        $excerpt = preg_replace('/\s+/', ' ', $excerpt);

        return trim($excerpt);
    }

    /**
    /**
     * 清理文章内容
     */
    private function clean_content($content)
    {

        // 确保内容不为 null
        if ($content === null) {
            return '';
        }

        // 移除短代码
        foreach ($this->settings['exclude_shortcodes'] as $shortcode) {
            $content = strip_shortcodes($content);
        }

        // 移除HTML标签
        $content = wp_strip_all_tags($content);

        // 清理多余空白，但保留句子结构
        $content = preg_replace('/\s+/', ' ', $content);

        // 解码HTML实体
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

        $cleaned = trim($content);

        return $cleaned;
    }

    /**
     * 保存文章时智能生成内容
     */
    public function auto_generate_excerpt_on_save($post_id, $post)
    {
        // 跳过自动保存和修订版本
        if (wp_is_post_revision($post_id) || defined('DOING_AUTOSAVE')) {
            return;
        }

        // 检查文章类型
        if ($post->post_type !== 'post') {
            return;
        }

        // 检查用户权限
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // 检查内容是否足够进行AI生成
        $content_length = mb_strlen(wp_strip_all_tags($post->post_content));
        $min_content_length = $this->settings['min_content_length'] ?? 50;
        $can_generate_ai = $content_length >= $min_content_length;

        // 避免无限循环
        remove_action('save_post', array($this, 'auto_generate_excerpt_on_save'), 10);

        // 获取自动生成模式
        $auto_mode = $this->settings['auto_mode'] ?? 'draft_always';

        $updates = array('ID' => $post_id);
        $has_updates = false;

        // 处理标题生成（如果为空，草稿状态优先）
        if (empty($post->post_title) && $post->post_status === 'draft' && $can_generate_ai) {
            $generated_title = $this->generate_ai_title($post->post_content);
            if ($generated_title && !empty($generated_title) && is_string($generated_title)) {
                $updates['post_title'] = sanitize_text_field($generated_title);
                $has_updates = true;
                update_post_meta($post_id, '_ai_generated_title', true);
            }
        }

        // 处理摘要生成（新的智能逻辑：内容更新时重新生成）
        if ($this->settings['auto_generate'] && $can_generate_ai) {
            $should_generate = $this->should_generate_excerpt_smart($post_id, $post, $auto_mode);

            if ($should_generate) {
                $excerpt = $this->generate_excerpt($post->post_content);
                if ($excerpt && !empty($excerpt) && is_string($excerpt)) {
                    $updates['post_excerpt'] = sanitize_textarea_field($excerpt);
                    $has_updates = true;
                    update_post_meta($post_id, '_auto_excerpt_ai_generated', true);

                    // 记录内容变化，用于下次比较
                    $this->update_content_hash($post_id, $post);
                }
            }
        }

        // 处理标签生成（根据模式决定）
        if ($this->should_generate_tags($post_id, $post, $auto_mode) && $can_generate_ai) {
            $generated_tags = $this->generate_ai_tags($post->post_content, $post->post_title);
            if ($generated_tags && !empty($generated_tags)) {
                wp_set_post_tags($post_id, $generated_tags, false);
                update_post_meta($post_id, '_ai_generated_tags', true);
            }
        }

        // 如果有更新，保存文章
        if ($has_updates) {
            wp_update_post($updates);
        }

        // 标记由 wp_insert_post_data 生成的 slug
        if ($this->pending_ai_slug && (intval($this->pending_ai_slug) === intval($post_id) || $this->pending_ai_slug === -1)) {
            update_post_meta($post_id, '_ai_generated_slug', true);
        }
        $this->pending_ai_slug = null;

        // 重新添加钩子
        add_action('save_post', array($this, 'auto_generate_excerpt_on_save'), 10, 2);
    }

    /**
     * 检查是否应该生成摘要
     */
    private function should_generate_excerpt($post_id, $post, $auto_mode)
    {
        switch ($auto_mode) {
            case 'draft_always':
                // 草稿总是生成：草稿状态每次保存都AI生成
                return $post->post_status === 'draft';

            case 'smart_first':
                // 智能首次生成：只有没有摘要时才AI生成
                return empty($post->post_excerpt);

            case 'draft_once':
                // 草稿首次生成：只对草稿第一次保存时AI生成
                $is_ai_generated = get_post_meta($post_id, '_auto_excerpt_ai_generated', true);
                return $post->post_status === 'draft' && !$is_ai_generated;

            default:
                // 默认使用草稿总是生成逻辑
                return $post->post_status === 'draft';
        }
    }

    /**
     * 检查是否应该生成标签
     */
    private function should_generate_tags($post_id, $post, $auto_mode)
    {
        // 检查是否启用了自动生成标签
        if (!$this->settings['auto_generate_tags']) {
            return false;
        }

        switch ($auto_mode) {
            case 'draft_always':
                // 草稿总是生成：草稿状态每次保存都AI生成
                return $post->post_status === 'draft';

            case 'smart_first':
                // 智能首次生成：没有用户手动标签时才AI生成
                $existing_tags = wp_get_post_tags($post_id, array('fields' => 'ids'));
                return empty($existing_tags);

            case 'draft_once':
                // 草稿首次生成：只对草稿第一次保存时AI生成
                $is_ai_generated = get_post_meta($post_id, '_ai_generated_tags', true);
                return $post->post_status === 'draft' && !$is_ai_generated;

            default:
                // 默认使用草稿总是生成逻辑
                return $post->post_status === 'draft';
        }
    }

    /**
     * 在 wp_insert_post_data 阶段生成或保护 AI slug
     * 在 WordPress 处理 slug 之前拦截，确保 AI 生成的 slug 不被标题覆盖
     */
    public function preserve_ai_slug($data, $postarr)
    {
        if ($data['post_type'] !== 'post') {
            return $data;
        }

        if (!$this->settings['auto_generate_slug']) {
            return $data;
        }

        $post_id = !empty($postarr['ID']) ? intval($postarr['ID']) : 0;
        $current_slug = $data['post_name'] ?? '';
        $status = $data['post_status'] ?? '';

        // 已有 AI 生成且为英文的 slug：直接保留，阻止 WordPress 从标题重算
        if ($post_id && get_post_meta($post_id, '_ai_generated_slug', true)) {
            $existing = get_post($post_id);
            if ($existing && !empty($existing->post_name) && $this->is_english_slug($existing->post_name)) {
                $data['post_name'] = $existing->post_name;
                return $data;
            }
        }

        // 已有有效英文 slug（用户自定义或已有）：跳过
        if (!empty($current_slug) && $this->is_english_slug($current_slug)) {
            return $data;
        }

        // 需要生成：slug 为空或含中文/编码
        $auto_mode = $this->settings['auto_mode'] ?? 'draft_always';
        $allowed_statuses = array('draft', 'publish');

        if (!in_array($status, $allowed_statuses, true)) {
            return $data;
        }

        if ($auto_mode === 'draft_once' && $post_id && get_post_meta($post_id, '_ai_generated_slug', true)) {
            return $data;
        }

        $content = !empty($data['post_content']) ? $data['post_content'] : '';
        $title = !empty($data['post_title']) ? $data['post_title'] : '';

        $generated_slug = $this->generate_ai_slug($content, $title);
        if ($generated_slug && !empty($generated_slug)) {
            $data['post_name'] = $generated_slug;
            $this->pending_ai_slug = $post_id ? $post_id : -1;
        }

        return $data;
    }

    /**
     * 判断 slug 是否为纯英文（不含 URL 编码的中文等非 ASCII 内容）
     */
    private function is_english_slug($slug)
    {
        if (empty($slug)) {
            return false;
        }

        if (preg_match('/[^\x00-\x7F]/', $slug)) {
            return false;
        }

        if (preg_match('/%[0-9A-Fa-f]{2}/', $slug)) {
            return false;
        }

        return true;
    }

    /**
     * 批量为现有文章生成摘要
     */
    private function batch_generate_existing_excerpts()
    {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 这是必要的查询，已优化
                'relation' => 'OR',
                array(
                    'key' => 'post_excerpt',
                    'value' => '',
                    'compare' => '='
                ),
                array(
                    'key' => 'post_excerpt',
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        $posts = get_posts($args);

        foreach ($posts as $post) {
            $excerpt = $this->generate_excerpt($post->post_content);
            if ($excerpt) {
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_excerpt' => $excerpt
                ));
            }
        }
    }

    /**
     * 检测是否使用了AI生成摘要
     */
    private function was_ai_generated($excerpt, $content)
    {
        // 简单的启发式检测
        // 确保内容不为 null
        if ($content === null || $excerpt === null) {
            return false;
        }

        // 1. 检查摘要是否包含原内容的句子（如果是，可能是传统提取）
        $content_sentences = preg_split('/[。！？.!?]+/', wp_strip_all_tags($content), -1, PREG_SPLIT_NO_EMPTY);
        $excerpt_words = preg_split('/[\s，。！？、；：""\'\'（）【】\.,!?;:()"()\[\]]+/', $excerpt, -1, PREG_SPLIT_NO_EMPTY);

        $found_exact_sentences = 0;
        foreach ($content_sentences as $sentence) {
            $sentence = trim($sentence);
            if (mb_strlen($sentence) > 10 && strpos($excerpt, $sentence) !== false) {
                $found_exact_sentences++;
            }
        }

        // 如果找到多个完全匹配的句子，可能是传统提取
        if ($found_exact_sentences >= 2) {
            return false;
        }

        // 2. 检查摘要是否具有总结性特征
        $summary_keywords = array('总结', '总之', '因此', '所以', '总的来说', '概括', '核心', '关键', '重点');
        $has_summary_features = false;
        foreach ($summary_keywords as $keyword) {
            if (strpos($excerpt, $keyword) !== false) {
                $has_summary_features = true;
                break;
            }
        }

        // 3. 检查摘要长度和内容长度比例
        $content_length = mb_strlen(wp_strip_all_tags($content));
        $excerpt_length = mb_strlen($excerpt);

        // 防止除零错误：如果内容长度为0，直接返回false
        if ($content_length <= 0) {
            return false;
        }

        $ratio = $excerpt_length / $content_length;

        // AI生成的摘要通常比例更合适（5%-20%）
        $is_appropriate_length = $ratio >= 0.05 && $ratio <= 0.20;

        return ($has_summary_features || $found_exact_sentences === 0) && $is_appropriate_length;
    }

    /**
     * 获取设置
     */
    public function get_settings()
    {
        return $this->settings;
    }

    /**
     * 更新设置
     */
    public function update_settings($new_settings)
    {
        $this->settings = wp_parse_args($new_settings, $this->settings);
        update_option('saiita_ai_content_toolkit_auto_excerpt_settings', $this->settings);
    }

    /**
     * 设置页面
     */
    public function settings_page()
    {
        if (isset($_POST['save_settings'])) { // phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce checked in render method
            $settings = array(
                'excerpt_length' => isset($_POST['excerpt_length']) ? intval($_POST['excerpt_length']) : 100, // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'auto_generate' => isset($_POST['auto_generate']), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'preserve_formatting' => isset($_POST['preserve_formatting']), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'min_content_length' => isset($_POST['min_content_length']) ? intval($_POST['min_content_length']) : 50, // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'smart_extraction' => isset($_POST['smart_extraction']), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'auto_mode' => sanitize_text_field(isset($_POST['auto_mode']) ? sanitize_text_field(wp_unslash($_POST['auto_mode'])) : '' ?? 'draft_always'), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'auto_generate_tags' => isset($_POST['auto_generate_tags']), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'auto_generate_slug' => isset($_POST['auto_generate_slug']), // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'slug_language' => isset($_POST['slug_language']) ? sanitize_text_field(wp_unslash($_POST['slug_language'])) : 'english', // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'slug_min_words' => isset($_POST['slug_min_words']) ? intval($_POST['slug_min_words']) : 4, // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                'slug_max_words' => isset($_POST['slug_max_words']) ? intval($_POST['slug_max_words']) : 8, // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            ); // phpcs:enable WordPress.Security.NonceVerification.Missing

            $this->update_settings($settings);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('设置保存成功！', 'saiita-ai-content-toolkit') . '</p></div>';
        }

        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <form method="post" action="">
                <?php wp_nonce_field('saiita_ai_content_toolkit_auto_excerpt'); ?>

            <div class="act-settings-section">
                <h2><?php esc_html_e('基本设置', 'saiita-ai-content-toolkit'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="auto_generate"><?php esc_html_e('自动生成摘要', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="auto_generate" name="auto_generate" value="1" <?php checked($settings['auto_generate']); ?>>
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
                                <label for="auto_mode"><?php esc_html_e('自动生成模式', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <select id="auto_mode" name="auto_mode">
                                    <option value="draft_always" <?php selected($settings['auto_mode'], 'draft_always'); ?>>
                                        <?php esc_html_e('草稿总是生成', 'saiita-ai-content-toolkit'); ?>
                                    </option>
                                    <option value="smart_first" <?php selected($settings['auto_mode'], 'smart_first'); ?>>
                                        <?php esc_html_e('智能首次生成', 'saiita-ai-content-toolkit'); ?>
                                    </option>
                                    <option value="draft_once" <?php selected($settings['auto_mode'], 'draft_once'); ?>>
                                        <?php esc_html_e('草稿首次生成', 'saiita-ai-content-toolkit'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('草稿总是生成：草稿状态每次保存都AI生成；智能首次生成：只在没有摘要/标签时AI生成；草稿首次生成：只对草稿第一次保存时AI生成', 'saiita-ai-content-toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_generate_tags"><?php esc_html_e('自动生成标签', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="auto_generate_tags" name="auto_generate_tags" value="1" <?php checked($settings['auto_generate_tags']); ?>>
                                <span class="description"><?php esc_html_e('保存文章时自动为没有标签的文章生成标签', 'saiita-ai-content-toolkit'); ?></span>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="auto_generate_slug"><?php esc_html_e('自动生成 Slug', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="auto_generate_slug" name="auto_generate_slug" value="1" <?php checked($settings['auto_generate_slug']); ?>>
                                <span class="description"><?php esc_html_e('保存文章时自动根据内容生成 SEO 友好的 URL slug', 'saiita-ai-content-toolkit'); ?></span>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="slug_language"><?php esc_html_e('Slug 语言', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <select id="slug_language" name="slug_language">
                                    <option value="english" <?php selected($settings['slug_language'], 'english'); ?>>
                                        <?php esc_html_e('英文（推荐）', 'saiita-ai-content-toolkit'); ?>
                                    </option>
                                    <option value="chinese" <?php selected($settings['slug_language'], 'chinese'); ?>>
                                        <?php esc_html_e('中文', 'saiita-ai-content-toolkit'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Slug 长度范围', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="slug_min_words" name="slug_min_words"
                                       value="<?php echo esc_attr($settings['slug_min_words']); ?>"
                                       min="2" max="20" step="1" style="width:70px">
                                <span> — </span>
                                <input type="number" id="slug_max_words" name="slug_max_words"
                                       value="<?php echo esc_attr($settings['slug_max_words']); ?>"
                                       min="2" max="30" step="1" style="width:70px">
                                <span class="description">
                                    <?php esc_html_e('英文为单词数，中文为汉字数', 'saiita-ai-content-toolkit'); ?>
                                </span>
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

                        <tr>
                            <th scope="row">
                                <label for="smart_extraction"><?php esc_html_e('智能内容提取', 'saiita-ai-content-toolkit'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="smart_extraction" name="smart_extraction" value="1" <?php checked($settings['smart_extraction']); ?>>
                                <span class="description"><?php esc_html_e('优先提取文章关键句和段落，而非简单截取', 'saiita-ai-content-toolkit'); ?></span>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <?php esc_html_e('AI 功能', 'saiita-ai-content-toolkit'); ?>
                            </th>
                            <td>
                                <?php if (saiita_ai_content_toolkit_is_ai_available()) : ?>
                                    <span style="color: #00a32a;"><?php esc_html_e('AI 功能已启用', 'saiita-ai-content-toolkit'); ?></span>
                                <?php else : ?>
                                    <span style="color: #d63638;"><?php esc_html_e('AI 功能未配置', 'saiita-ai-content-toolkit'); ?></span>
                                <?php endif; ?>
                                <br>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=saiita-ai-content-toolkit-ai-settings')); ?>">
                                    <?php esc_html_e('前往 AI 设置 配置 API 密钥和模型参数', 'saiita-ai-content-toolkit'); ?>
                                </a>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="submit">
                    <input type="submit" name="save_settings" class="button button-primary" value="<?php esc_html_e('保存设置', 'saiita-ai-content-toolkit'); ?>">
                </div>
            </form>
        </div>


        <?php
    }

    /**
     * AJAX处理API测试
     */
    public function ajax_test_deepseek_api()
    {
        // 验证 nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'saiita_auto_excerpt_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        $api_key = sanitize_text_field(wp_unslash($_POST['api_key'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $api_base = esc_url_raw(isset($_POST['api_base']) ? sanitize_text_field(wp_unslash($_POST['api_base'])) : ''); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $model = isset($_POST['model']) ? sanitize_text_field(wp_unslash($_POST['model'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

        if (empty($api_key)) {
            wp_send_json_error(array('message' => esc_html__('请先配置API密钥', 'saiita-ai-content-toolkit')));
        }

        try {
            // 测试API连接
            $test_prompt = "请回复一个简单的问候语，不超过20个字。";
            $response = $this->call_deepseek_api($api_key, $api_base, $model, $test_prompt, 50, 0.1);

            if ($response && isset($response['choices'][0]['message']['content'])) {
                $reply = trim($response['choices'][0]['message']['content']);
                $usage = isset($response['usage']) ? $response['usage'] : array();

                wp_send_json_success(array(
                    'message' => esc_html__('连接成功，AI回复：', 'saiita-ai-content-toolkit') . $reply,
                    'usage' => $usage
                ));
            } else {
                wp_send_json_error(array('message' => esc_html__('API响应格式异常', 'saiita-ai-content-toolkit')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * 获取文章摘要列表
     */
    public function get_excerpt_list($page = 1, $per_page = 20, $status = 'all')
    {


        // 首先获取所有文章（包括已发布和草稿）
        $args = array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'pending', 'future'), // 包含多种状态
            'posts_per_page' => -1, // 获取所有文章
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids' // 只获取ID以提高性能
        );

        $all_posts_query = new WP_Query($args);
        $all_post_ids = $all_posts_query->posts;



        // 处理每篇文章，筛选符合条件的文章
        $filtered_posts = array();

        foreach ($all_post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                continue;
            }

            $has_excerpt = !empty($post->post_excerpt);

            // 根据状态筛选
            if ($status === 'with_excerpt' && !$has_excerpt) {
                continue;
            } elseif ($status === 'without_excerpt' && $has_excerpt) {
                continue;
            }

            // 检测是否为AI生成的摘要
            $is_ai_generated = false;
            if ($has_excerpt && !empty($post->post_excerpt)) {
                // 检查post meta中是否有AI生成标记（支持两种meta key）
                $ai_generated_meta = get_post_meta($post->ID, '_auto_excerpt_ai_generated', true);
                $ai_generated_meta_alt = get_post_meta($post->ID, '_ai_generated_excerpt', true);

                if ($ai_generated_meta || $ai_generated_meta_alt) {
                    $is_ai_generated = true;
                } else {
                    // 使用启发式检测（与生成时的检测逻辑一致）
                    $use_ai = saiita_ai_content_toolkit_is_ai_available();
                    if ($use_ai) {
                        $is_ai_generated = $this->was_ai_generated($post->post_excerpt, $post->post_content);
                    }
                }
            }

            $filtered_posts[] = array(
                'ID' => $post->ID,
                'title' => get_the_title($post),
                'excerpt' => $post->post_excerpt,
                'excerpt_length' => mb_strlen($post->post_excerpt),
                'content_length' => mb_strlen(wp_strip_all_tags($post->post_content)),
                'has_excerpt' => $has_excerpt,
                'is_ai_generated' => $is_ai_generated,
                'edit_url' => get_edit_post_link($post->ID),
                'view_url' => get_permalink($post->ID),
                'date' => get_the_date('Y-m-d H:i:s', $post),
                'status' => get_post_status($post->ID)
            );
        }

        $total_filtered = count($filtered_posts);


        // 计算分页
        $max_pages = ceil($total_filtered / $per_page);
        $offset = ($page - 1) * $per_page;

        // 获取当前页的数据
        $current_page_posts = array_slice($filtered_posts, $offset, $per_page);



        return array(
            'posts' => $current_page_posts,
            'total' => $total_filtered,
            'pages' => $max_pages,
            'current_page' => $page,
            'per_page' => $per_page
        );
    }

    /**
     * 获取摘要统计信息
     */
    public function get_excerpt_stats()
    {


        // 获取所有文章（包括已发布和草稿）
        $all_posts = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'pending', 'future'),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $total_posts_count = $all_posts->found_posts;


        $with_excerpt_count = 0;
        $ai_generated_count = 0;

        if ($all_posts->have_posts()) {
            while ($all_posts->have_posts()) {
                $all_posts->the_post();
                global $post;

                if (!empty($post->post_excerpt)) {
                    $with_excerpt_count++;
                }

                // 检查是否为AI生成的摘要
                $ai_generated = get_post_meta($post->ID, '_ai_generated_excerpt', true);
                if ($ai_generated) {
                    $ai_generated_count++;
                }
            }
        }

        wp_reset_postdata();

        $without_excerpt_count = $total_posts_count - $with_excerpt_count;
        $coverage_rate = $total_posts_count > 0 ? round(($with_excerpt_count / $total_posts_count) * 100, 2) : 0;



        return array(
            'total_posts' => $total_posts_count,
            'with_excerpt' => $with_excerpt_count,
            'without_excerpt' => $without_excerpt_count,
            'ai_generated' => $ai_generated_count,
            'coverage_rate' => $coverage_rate
        );
    }

    /**
     * AJAX处理批量生成摘要
     */
    public function ajax_batch_generate_excerpts()
    {
        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_batch_generate_excerpts_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
        }

        try {
            // 检查AI是否可用
            if (!saiita_ai_content_toolkit_is_ai_available()) {
                wp_send_json_error(array('message' => __('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit')));
                return;
            }

            $success_count = 0;
            $error_count = 0;
            $processed_count = 0;
            $max_execution_time = ini_get('max_execution_time');
            // 增加执行时间限制到600秒（10分钟），如果允许的话
            if ($max_execution_time < 600) {
                @set_time_limit(600); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
                $max_execution_time = 600;
            }
            $start_time = time();

            // 初始化进度信息
            $progress_id = 'batch_excerpt_' . time();
            update_option('saiita_batch_progress_' . $progress_id, array(
                'task_type' => 'excerpts',
                'total' => 0,
                'processed' => 0,
                'success' => 0,
                'errors' => 0,
                'current_post' => '初始化...',
                'status' => 'processing',
                'start_time' => time()
            ));

            // 获取所有无摘要的已发布文章
            $posts_query = new WP_Query(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'ASC' // 从旧到新处理，避免超时
            ));

            if ($posts_query->have_posts()) {
                while ($posts_query->have_posts() && (time() - $start_time) < ($max_execution_time - 10)) {
                    $posts_query->the_post();
                    global $post;

                    // 检查是否已有摘要
                    if (!empty($post->post_excerpt)) {
                        continue; // 跳过已有摘要的文章
                    }



                    try {
                        // 检查使用次数限制
                        $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
                        $can_use = $license_manager->can_use('article_optimization');
                        if (!$can_use['allowed']) {
                            break; // 达到上限，停止批量处理
                        }

                        // 生成摘要
                        $content = $post->post_content;
                        $excerpt = $this->generate_excerpt($content);

                        // 跳过WP_Error（AI未配置或调用失败）
                        if (is_wp_error($excerpt) || empty($excerpt)) {
                            $error_count++;
                            continue;
                        }

                            // 更新文章摘要
                            wp_update_post(array(
                                'ID' => $post->ID,
                                'post_excerpt' => $excerpt
                            ));

                            // 标记为AI生成
                            update_post_meta($post->ID, '_ai_generated_excerpt', true);
                            update_post_meta($post->ID, '_auto_excerpt_ai_generated', true);

                            // 增加使用次数
                            $license_manager->increment_usage('article_optimization');

                            $success_count++;
                    } catch (Exception $e) {
                        $error_count++;
                    }
                }
            }

            wp_reset_postdata();

            wp_send_json_success(array(
                'success_count' => $success_count,
                'error_count' => $error_count,
                /* translators: 1: success count, 2: fail count */
                'message' => sprintf(esc_html__('处理完成：成功 %1$d 篇，失败 %2$d 篇', 'saiita-ai-content-toolkit'), $success_count, $error_count)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('批量生成失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX处理单个文章生成摘要
     */
    public function ajax_generate_single_excerpt()
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('权限不足', 'saiita-ai-content-toolkit')));
            return;
        }

        check_ajax_referer('saiita_auto_excerpt_generate', 'nonce');


        // 清理输入数据
        $sanitized_data = Saiita_AI_Toolkit_Security_Validator::sanitize_post_data([
            'post_id' => 'int'
        ]);
        $post_id = $sanitized_data['post_id'];

        // 验证必填字段
        $validation = Saiita_AI_Toolkit_Security_Validator::validate_required_fields(
            ['post_id' => $post_id],
            ['post_id']
        );

        if (!$validation['valid']) {
            wp_send_json_error(array('message' => $validation['errors'][0]));
            return;
        }

        try {
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(array('message' => esc_html__('文章不存在', 'saiita-ai-content-toolkit')));
            }

            // 检查是否已有摘要

            if (!empty($post->post_excerpt)) {
                // 检查是否为AI生成的摘要
                $ai_generated_meta = get_post_meta($post_id, '_auto_excerpt_ai_generated', true);
                $ai_generated_meta_alt = get_post_meta($post_id, '_ai_generated_excerpt', true);

                if ($ai_generated_meta || $ai_generated_meta_alt) {
                    wp_send_json_error(array('message' => esc_html__('文章已有AI生成的摘要', 'saiita-ai-content-toolkit')));
                }
                // 如果是手动编写的摘要，允许覆盖，继续执行
            }

            // 检查使用次数限制
            $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
            $can_use = $license_manager->can_use('article_optimization');
            if (!$can_use['allowed']) {
                wp_send_json_error(array('message' => $can_use['reason']));
                return;
            }

            // 生成摘要
            $content = $post->post_content;

            $excerpt = $this->generate_excerpt($content);

            // 检查是否为WP_Error（AI未配置）
            if (is_wp_error($excerpt)) {
                wp_send_json_error(array('message' => $excerpt->get_error_message()));
                return;
            }

            if ($excerpt && !empty($excerpt)) {
                // 清理摘要内容
                $excerpt = sanitize_textarea_field($excerpt);
                $excerpt = trim($excerpt);


                // 强制更新，即使内容相同
                $update_result = wp_update_post(array(
                    'ID' => $post_id,
                    'post_excerpt' => $excerpt,
                    'post_modified' => current_time('mysql'),
                    'post_modified_gmt' => current_time('mysql', true)
                ), true);


                // 清除缓存并重新获取文章
                clean_post_cache($post_id);
                $updated_post = get_post($post_id);

                // 标记为AI生成 - 只要调用此函数就是AI生成摘要
                $use_ai = saiita_ai_content_toolkit_is_ai_available();

                // 只要调用到这里就强制标记为AI生成，不管是覆盖手动摘要还是生成新摘要
                $is_ai_generated = true;

                if ($is_ai_generated) {
                    update_post_meta($post_id, '_ai_generated_excerpt', true);
                    update_post_meta($post_id, '_auto_excerpt_ai_generated', true);
                }

                // 增加使用次数
                $license_manager->increment_usage('article_optimization');

                wp_send_json_success(array(
                    'post_id' => $post_id,
                    'post_title' => get_the_title($post_id),
                    'excerpt' => $excerpt,
                    'excerpt_length' => mb_strlen($excerpt),
                    'ai_generated' => $is_ai_generated,
                    'message' => $is_ai_generated ?
                        esc_html__('AI摘要生成成功', 'saiita-ai-content-toolkit') :
                        esc_html__('摘要生成成功', 'saiita-ai-content-toolkit')
                ));
            } else {
                wp_send_json_error(array('message' => esc_html__('摘要生成失败，无法从内容中提取有效摘要', 'saiita-ai-content-toolkit')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('生成失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX处理单个文章生成标签
     */
    public function ajax_generate_single_tags()
    {

        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_auto_excerpt_generate_tags')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

        if (empty($post_id)) {
            wp_send_json_error(array('message' => esc_html__('文章ID无效', 'saiita-ai-content-toolkit')));
        }

        try {
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(array('message' => esc_html__('文章不存在', 'saiita-ai-content-toolkit')));
            }


            // 检查AI是否可用
            if (!saiita_ai_content_toolkit_is_ai_available()) {
                wp_send_json_error(array('message' => __('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit')));
                return;
            }

            // 检查使用次数限制
            $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
            $can_use = $license_manager->can_use('article_optimization');
            if (!$can_use['allowed']) {
                wp_send_json_error(array('message' => $can_use['reason']));
                return;
            }

            // 使用AI生成标签
            $tags = $this->generate_ai_tags($post->post_content, $post->post_title);

            if ($tags && !empty($tags)) {
                // 获取设置前的现有标签
                $existing_tags_before = wp_get_post_tags($post_id);
                $existing_tag_names_before = array_map(function ($tag) {
                    return $tag->name;
                }, $existing_tags_before);

                // 设置文章标签（不替换，而是添加）
                $result = wp_set_post_tags($post_id, $tags, false);

                $existing_tag_names = $existing_tag_names_before; // 使用设置前的标签

                // 增加使用次数
                $license_manager->increment_usage('article_optimization');

                wp_send_json_success(array(
                    'post_id' => $post_id,
                    'post_title' => get_the_title($post_id),
                    'existing_tags' => $existing_tag_names,  // 现有标签
                    'ai_tags' => $tags,                    // AI生成的标签
                    'tags' => $tags,                       // 保持向后兼容
                    'tag_count' => count($tags),
                    'suggested_action' => 'append',      // 建议动作：添加而不是替换
                    'message' => esc_html__('标签生成成功', 'saiita-ai-content-toolkit'),
                    'remaining' => 999999, // 免费版本无限制
                    'limit' => 999999 // 免费版本无限制
                ));
            } else {
                wp_send_json_error(array('message' => esc_html__('标签生成失败，请检查文章内容', 'saiita-ai-content-toolkit')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('生成失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * 注册定时任务 - 在凌晨3点自动生成摘要
     */
    public function schedule_daily_excerpt_generation()
    {
        // 检查是否已经存在定时任务
        if (!wp_next_scheduled('saiita_auto_excerpt_daily_generation')) {
            // 设置每天凌晨3点执行
            $time_string = '03:00:00';
            $timezone = new DateTimeZone(wp_timezone_string());
            $today = new DateTime('now', $timezone);
            $scheduled_time = new DateTime($today->format('Y-m-d') . ' ' . $time_string, $timezone);

            // 如果当前时间已经过了今天的3点，则设置为明天3点
            if ($today > $scheduled_time) {
                $scheduled_time->modify('+1 day');
            }

            // 调度定时任务
            wp_schedule_event($scheduled_time->getTimestamp(), 'daily', 'saiita_auto_excerpt_daily_generation');
        }
    }

    /**
     * 取消定时任务
     */
    public function unschedule_daily_excerpt_generation()
    {
        $timestamp = wp_next_scheduled('saiita_auto_excerpt_daily_generation');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'saiita_auto_excerpt_daily_generation');
        }
    }

    /**
     * 执行定时摘要生成任务
     */
    public function execute_daily_excerpt_generation()
    {


        // 检查是否启用自动生成
        if (!$this->settings['auto_generate']) {
            return;
        }

        // 检查连续失败次数
        $failure_count = get_option('saiita_ai_content_toolkit_auto_excerpt_consecutive_failures', false);
        if ($failure_count === false) {
            $failure_count = get_option('saiita_auto_excerpt_consecutive_failures', 0);
        }
        $failure_count = intval($failure_count);
        if ($failure_count >= 3) {
            return;
        }

        try {
            $start_time = time();
            $max_execution_time = ini_get('max_execution_time');
            $processed_count = 0;
            $success_count = 0;

            // 获取所有无摘要的已发布文章
            $posts_query = new WP_Query(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'ASC', // 从旧到新处理
                'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 这是必要的查询，已优化
                    'relation' => 'OR',
                    array(
                        'key' => 'post_excerpt',
                        'compare' => '=',
                        'value' => ''
                    ),
                    array(
                        'key' => 'post_excerpt',
                        'compare' => 'NOT EXISTS'
                    )
                )
            ));

            if ($posts_query->have_posts()) {
                while ($posts_query->have_posts() && (time() - $start_time) < ($max_execution_time - 10)) {
                    $posts_query->the_post();
                    global $post;

                    $processed_count++;

                    // 检查是否已有摘要
                    if (!empty($post->post_excerpt)) {
                        continue;
                    }

                    try {
                        // 生成摘要
                        $content = $post->post_content;
                        $excerpt = $this->generate_excerpt($content);

                        if ($excerpt && !empty($excerpt)) {
                            // 更新文章摘要
                            wp_update_post(array(
                                'ID' => $post->ID,
                                'post_excerpt' => $excerpt
                            ));

                            // 标记为AI生成（如果使用了AI）
                            if (saiita_ai_content_toolkit_is_ai_available()) {
                                update_post_meta($post->ID, '_ai_generated_excerpt', true);
                                update_post_meta($post->ID, '_auto_excerpt_ai_generated', true);
                            }

                            $success_count++;
                        }
                    } catch (Exception $e) {
                    }
                }
            }

            wp_reset_postdata();

            // 检查是否成功生成了摘要
            if ($success_count > 0) {
                // 重置失败计数
                delete_option('saiita_auto_excerpt_consecutive_failures');
                update_option('saiita_ai_content_toolkit_auto_excerpt_consecutive_failures', 0);
            } else {
                // 增加失败计数
                $failure_count++;
                delete_option('saiita_auto_excerpt_consecutive_failures');
                update_option('saiita_ai_content_toolkit_auto_excerpt_consecutive_failures', $failure_count);


                // 如果连续3天失败，取消定时任务
                if ($failure_count >= 3) {
                    $this->unschedule_daily_excerpt_generation();
                }
            }
        } catch (Exception $e) {
            // 增加失败计数
            $failure_count = get_option('saiita_ai_content_toolkit_auto_excerpt_consecutive_failures', false);
            if ($failure_count === false) {
                $failure_count = get_option('saiita_auto_excerpt_consecutive_failures', 0);
            }
            $failure_count = intval($failure_count) + 1;
            delete_option('saiita_auto_excerpt_consecutive_failures');
            update_option('saiita_ai_content_toolkit_auto_excerpt_consecutive_failures', $failure_count);


            // 如果连续3天失败，取消定时任务
            if ($failure_count >= 3) {
                $this->unschedule_daily_excerpt_generation();
            }
        }
    }

    /**
     * AI生成文章标签
     */
    public function generate_tags_by_ai($post_id = null)
    {
        if (!$post_id) {
            return array('error' => esc_html__('文章ID无效', 'saiita-ai-content-toolkit'));
        }

        // 检查AI设置
        if (!saiita_ai_content_toolkit_is_ai_available()) {
            return array('error' => esc_html__('AI生成功能未启用或未配置API密钥', 'saiita-ai-content-toolkit'));
        }

        $post = get_post($post_id);
        if (!$post) {
            return array('error' => esc_html__('文章不存在', 'saiita-ai-content-toolkit'));
        }

        try {
            // 构建提示词
            $title = get_the_title($post);
            $content = wp_strip_all_tags($post->post_content);
            $excerpt = !empty($post->post_excerpt) ? $post->post_excerpt : '';

            // 限制内容长度以避免API限制
            if (mb_strlen($content) > 3000) {
                $content = mb_substr($content, 0, 3000) . '...';
            }

            $prompt = "请根据以下文章信息生成3-8个相关的标签：

标题：{$title}

摘要：{$excerpt}

内容：{$content}

要求：
1. 标签要准确反映文章主题和内容
2. 使用简洁的关键词，最好是2-4个字
3. 标签要具有代表性，便于搜索和分类
4. 每行一个标签，不要编号
5. 直接输出标签，不要解释

标签：";

            // 调用AI API（给足 max_tokens 余量，推理模型的 reasoning 会消耗 token 预算）
            $response = saiita_ai_content_toolkit_call_ai_api(
                $prompt,
                array(
                    'max_tokens' => 500,
                    'temperature' => 0.3 // 较低的创造性确保标签准确
                )
            );

            if ($response && !empty($response) && !is_wp_error($response)) {
                $ai_tags_text = trim($response);

                // 处理AI生成的标签
                $ai_tags = array();
                $lines = explode("\n", $ai_tags_text);

                foreach ($lines as $line) {
                    $tag = trim($line);
                    $tag = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $tag); // 清理特殊字符
                    $tag = trim($tag);

                    if (!empty($tag) && mb_strlen($tag) >= 2 && mb_strlen($tag) <= 10) {
                        $ai_tags[] = $tag;
                    }
                }

                // 去重并限制数量
                $ai_tags = array_unique($ai_tags);
                $ai_tags = array_slice($ai_tags, 0, 8);

                // 获取原有标签
                $existing_tags = wp_get_post_tags($post_id, array('fields' => 'names'));

                return array(
                    'success' => true,
                    'ai_tags' => $ai_tags,
                    'existing_tags' => $existing_tags,
                    'suggested_action' => empty($existing_tags) ? 'add' : 'replace'
                );
            } else {
                return array('error' => esc_html__('AI服务响应异常', 'saiita-ai-content-toolkit'));
            }
        } catch (Exception $e) {
            return array('error' => esc_html__('标签生成失败：', 'saiita-ai-content-toolkit') . $e->getMessage());
        }
    }

    /**
     * 应用AI生成的标签到文章
     */
    public function apply_ai_tags($post_id, $new_tags, $action = 'replace')
    {
        if (!$post_id || empty($new_tags)) {
            return array('success' => false, 'message' => esc_html__('参数无效', 'saiita-ai-content-toolkit'));
        }

        $post = get_post($post_id);
        if (!$post) {
            return array('success' => false, 'message' => esc_html__('文章不存在', 'saiita-ai-content-toolkit'));
        }

        try {
            // 获取原有标签名称
            $existing_tag_names = wp_get_post_tags($post_id, array('fields' => 'names'));

            // 根据操作类型处理标签
            switch ($action) {
                case 'add':
                    // 添加到现有标签
                    $final_tag_names = array_merge($existing_tag_names, $new_tags);
                    break;

                case 'merge':
                    // 合并标签（去除重复）
                    $final_tag_names = array_unique(array_merge($existing_tag_names, $new_tags));
                    break;

                case 'replace':
                default:
                    // 替换所有标签
                    $final_tag_names = $new_tags;
                    break;
            }

            // 去重并设置标签
            $final_tag_names = array_unique($final_tag_names);
            $result = wp_set_post_tags($post_id, $final_tag_names, false);

            return array(
                'success' => true,
                'message' => esc_html__('标签更新成功', 'saiita-ai-content-toolkit'),
                'applied_tags' => count($final_tag_names),
                'tag_names' => $final_tag_names
            );
        } catch (Exception $e) {
            return array('success' => false, 'message' => esc_html__('标签更新失败：', 'saiita-ai-content-toolkit') . $e->getMessage());
        }
    }

    /**
     * AJAX处理生成标签
     */
    public function ajax_generate_tags()
    {
        // 验证 nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'saiita_auto_excerpt_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $result = $this->generate_tags_by_ai($post_id);

        if (isset($result['error'])) {
            wp_send_json_error(array('message' => $result['error']));
        } else {
            // 免费版本无使用限制
            $result['remaining'] = 999999;
            wp_send_json_success($result);
        }
    }

    /**
     * AJAX处理应用标签
     */
    public function ajax_apply_tags()
    {
        // 验证 nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'saiita_apply_ai_tags')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $new_tags = isset($_POST['new_tags']) ? array_map('sanitize_text_field', wp_unslash($_POST['new_tags'])) : array(); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $action = isset($_POST['action_type']) ? sanitize_text_field(wp_unslash($_POST['action_type'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

        $result = $this->apply_ai_tags($post_id, $new_tags, $action);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /**
     * 批量生成文章标签
     */
    public function batch_generate_tags()
    {


        // 检查是否启用AI生成
        if (!saiita_ai_content_toolkit_is_ai_available()) {
            return array(
                'success' => false,
                'message' => esc_html__('AI生成功能未启用或未配置API密钥', 'saiita-ai-content-toolkit')
            );
        }

        try {
            $max_execution_time = ini_get('max_execution_time');
            // 增加执行时间限制到600秒（10分钟），如果允许的话
            if ($max_execution_time < 600) {
                @set_time_limit(600); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
                $max_execution_time = 600;
            }
            $start_time = time();
            $processed_count = 0;
            $success_count = 0;
            $error_count = 0;
            $total_applied_tags = 0;

            // 获取所有已发布的文章
            $posts_query = new WP_Query(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'ASC' // 从旧到新处理
            ));

            if ($posts_query->have_posts()) {
                while ($posts_query->have_posts() && (time() - $start_time) < ($max_execution_time - 10)) {
                    $posts_query->the_post();
                    global $post;

                    $processed_count++;

                    try {
                        // 检查使用次数限制
                        $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
                        $can_use = $license_manager->can_use('article_optimization');
                        if (!$can_use['allowed']) {
                            break; // 达到上限，停止批量处理
                        }

                        // 生成标签
                        $result = $this->generate_tags_by_ai($post->ID);

                        if ($result && isset($result['ai_tags']) && !empty($result['ai_tags'])) {
                            // 合并去重模式应用标签
                            $apply_result = $this->apply_ai_tags($post->ID, $result['ai_tags'], 'merge');

                            if ($apply_result && $apply_result['success']) {
                                $success_count++;
                                $total_applied_tags += isset($apply_result['applied_tags']) ? $apply_result['applied_tags'] : 0;
                                // 增加使用次数
                                $license_manager->increment_usage('article_optimization');
                            } else {
                                $error_count++;
                            }
                        } else {
                        }
                    } catch (Exception $e) {
                        $error_count++;
                    }
                }
            }

            wp_reset_postdata();

            return array(
                'success' => true,
                'processed_count' => $processed_count,
                'success_count' => $success_count,
                'error_count' => $error_count,
                'total_applied_tags' => $total_applied_tags,
                'message' => sprintf(
                    /* translators: 1: processed, 2: success, 3: fail, 4: tags */
                    esc_html__('批量生成标签完成！处理：%1$d篇，成功：%2$d篇，失败：%3$d篇，应用标签：%4$d个', 'saiita-ai-content-toolkit'),
                    $processed_count,
                    $success_count,
                    $error_count,
                    $total_applied_tags
                )
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => esc_html__('批量生成标签失败：', 'saiita-ai-content-toolkit') . $e->getMessage()
            );
        }
    }

    /**
     * AJAX处理批量生成标签
     */
    public function ajax_batch_generate_tags()
    {
        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_batch_generate_tags_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
        }

        try {
            $result = $this->batch_generate_tags();

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(array('message' => $result['message']));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('批量生成标签失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX处理单篇文章SEO分析
     */
    public function ajax_analyze_post_seo()
    {
        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_auto_excerpt_seo_analyze')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        if ($post_id <= 0) {
            wp_send_json_error(array('message' => esc_html__('无效的文章ID', 'saiita-ai-content-toolkit')));
        }

        try {
            if (!saiita_ai_content_toolkit_is_ai_available()) {
                wp_send_json_error(array('message' => __('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit')));
                return;
            }

            // 检查使用次数限制
            $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
            $can_use = $license_manager->can_use('article_optimization');
            if (!$can_use['allowed']) {
                wp_send_json_error(array('message' => $can_use['reason']));
                return;
            }

            if (!$this->seo_analyzer) {
                wp_send_json_error(array('message' => __('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit')));
                return;
            }

            $result = $this->seo_analyzer->analyze_post($post_id);

            // 检测分析结果中的错误
            if (is_array($result) && isset($result['error']) && $result['error']) {
                $error_message = isset($result['message']) ? $result['message'] : esc_html__('SEO分析失败', 'saiita-ai-content-toolkit');
                wp_send_json_error(array('message' => $error_message));
                return;
            }

            if ($result) {
                // 增加使用次数
                $license_manager->increment_usage('article_optimization');

                // 返回包含完整分析数据的响应，与前端JavaScript预期格式匹配
                wp_send_json_success(array(
                    'message' => esc_html__('SEO分析完成', 'saiita-ai-content-toolkit'),
                    'analysis' => $result,
                    'post_id' => $post_id,
                    'remaining' => 999999, // 免费版本无限制
                    'limit' => 999999 // 免费版本无限制
                ));
            } else {
                wp_send_json_error(array('message' => esc_html__('SEO分析失败', 'saiita-ai-content-toolkit')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('SEO分析失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX处理批量SEO分析
     */
    public function ajax_batch_analyze_seo()
    {
        // 验证 nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'saiita_auto_excerpt_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        try {
            if (!saiita_ai_content_toolkit_is_ai_available()) {
                wp_send_json_error(array('message' => __('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit')));
                return;
            }

            // 检查使用次数限制
            $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
            $can_use = $license_manager->can_use('website_seo');
            if (!$can_use['allowed']) {
                wp_send_json_error(array('message' => $can_use['reason']));
                return;
            }

            if (!$this->seo_analyzer) {
                wp_send_json_error(array('message' => __('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit')));
                return;
            }

            $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 5; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            $result = $this->seo_analyzer->batch_analyze_posts(array(), $batch_size);

            // 增加使用次数
            $license_manager->increment_usage('website_seo');

            wp_send_json_success(array(
                'message' => esc_html__('批量SEO分析完成', 'saiita-ai-content-toolkit'),
                'result' => $result
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('批量SEO分析失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX获取SEO分析报告
     */
    public function ajax_get_seo_report()
    {
        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_auto_excerpt_get_seo_report')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        if ($post_id <= 0) {
            wp_send_json_error(array('message' => esc_html__('无效的文章ID', 'saiita-ai-content-toolkit')));
        }

        try {
            if (!$this->seo_analyzer) {
                wp_send_json_error(array('message' => esc_html__('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit')));
            }

            $report = $this->seo_analyzer->get_seo_report($post_id);

            if ($report) {
                // 转换报告为数组格式，确保包含完整的AI分析数据
                $report_data = json_decode(json_encode($report), true);

                // 直接添加原始AI分析数据到响应中
                $response_data = array(
                    'report' => $report_data,
                    'raw_ai_analysis' => $report->raw_ai_analysis ?? '',
                    'parsed_analysis' => $report->parsed_analysis ?? array(),
                    'ai_full_analysis' => $report->raw_ai_analysis ?? '',
                    'raw_analysis_data' => isset($report_data['analysis_data']) ? $report_data['analysis_data'] : null
                );

                // 确保report中也包含完整数据
                if (!isset($response_data['report']['raw_ai_analysis'])) {
                    $response_data['report']['raw_ai_analysis'] = $report->raw_ai_analysis ?? '';
                }
                if (!isset($response_data['report']['parsed_analysis'])) {
                    $response_data['report']['parsed_analysis'] = $report->parsed_analysis ?? array();
                }

                // 如果有详细分析数据，尝试解析
                if (isset($report_data['detailed_analysis']) && is_string($report_data['detailed_analysis'])) {
                    $detailed_analysis = json_decode($report_data['detailed_analysis'], true);
                    if ($detailed_analysis) {
                        $response_data['ai_full_analysis'] = $detailed_analysis;
                    }
                }

                wp_send_json_success($response_data);
            } else {
                wp_send_json_error(array('message' => esc_html__('未找到SEO分析报告', 'saiita-ai-content-toolkit')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('获取报告失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX更新SEO分析数据库架构
     */
    public function ajax_update_seo_analysis_schema()
    {
        try {
            $this->ensure_database_schema();

            wp_send_json_success(array(
                'message' => '数据库架构更新成功！现在可以重新生成完整的SEO分析了。',
                'success' => true
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => '数据库架构更新失败：' . $e->getMessage(),
                'success' => false
            ));
        }
    }

    /**
     * AJAX获取SEO统计信息
     */
    public function ajax_get_seo_statistics()
    {
        // 验证 nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'saiita_auto_excerpt_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        try {
            if (!$this->seo_analyzer) {
                wp_send_json_error(array('message' => esc_html__('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit')));
            }

            $statistics = $this->seo_analyzer->get_seo_statistics();

            wp_send_json_success(array(
                'message' => esc_html__('获取统计信息成功', 'saiita-ai-content-toolkit'),
                'statistics' => $statistics
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('获取统计信息失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX获取文章列表（用于SEO分析）
     */
    public function ajax_get_posts_for_seo()
    {
        // 验证 nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'saiita_auto_excerpt_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        try {
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 100,
                'orderby' => 'modified',
                'order' => 'DESC'
            );

            $posts = get_posts($args);
            $posts_data = array();

            foreach ($posts as $post) {
                $posts_data[] = array(
                    'ID' => $post->ID,
                    'post_title' => get_the_title($post->ID),
                    'post_modified' => $post->post_modified
                );
            }

            wp_send_json_success(array(
                'message' => esc_html__('获取文章列表成功', 'saiita-ai-content-toolkit'),
                'posts' => $posts_data
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('获取文章列表失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX获取SEO报告列表
     */
    public function ajax_get_seo_reports_list()
    {
        // 验证 nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'saiita_auto_excerpt_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        try {
            if (!$this->seo_analyzer) {
                wp_send_json_error(array('message' => esc_html__('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit')));
            }

            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

            $reports = $this->seo_analyzer->get_all_seo_reports($limit, $offset);

            wp_send_json_success(array(
                'message' => esc_html__('获取报告列表成功', 'saiita-ai-content-toolkit'),
                'reports' => $reports
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('获取报告列表失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX AI分类文章
     */
    public function ajax_ai_categorize()
    {
        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_auto_excerpt_ai_categorize')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

        if (empty($post_id)) {
            wp_send_json_error(array('message' => esc_html__('文章ID无效', 'saiita-ai-content-toolkit')));
        }

        try {
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(array('message' => esc_html__('文章不存在', 'saiita-ai-content-toolkit')));
            }

            // 调试信息：文章ID {$post_id} - 这个函数现在用于生成分类描述，而不是分类文章
            // 重新设计功能：根据文章生成分类描述，而不是将文章分类
            // 调用AI分类描述生成功能
            $categories = get_categories(array('hide_empty' => false));
            if (!empty($categories)) {
                // 选择第一个分类生成描述（这里可以根据需要修改逻辑）
                $category = $categories[0];
                $category_result = $this->ai_generate_category_description($category->term_id);
            } else {
                $category_result = array('success' => false, 'message' => esc_html__('没有可用的分类', 'saiita-ai-content-toolkit'));
            }

            if ($category_result['success']) {
                wp_send_json_success(array(
                    'message' => $category_result['message'],
                    'category' => $category_result['category']
                ));
            } else {
                wp_send_json_error(array('message' => $category_result['message']));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('AI分类失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX AI优化标签
     */
    public function ajax_ai_optimize_tags()
    {
        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_auto_excerpt_ai_optimize_tags')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

        if (empty($post_id)) {
            wp_send_json_error(array('message' => esc_html__('文章ID无效', 'saiita-ai-content-toolkit')));
        }

        try {
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(array('message' => esc_html__('文章不存在', 'saiita-ai-content-toolkit')));
            }

            // 调试信息：文章ID {$post_id} - 这个函数现在用于生成标签描述，而不是优化标签
            // 重新设计功能：根据文章生成标签描述，而不是优化标签
            // 调用AI标签描述生成功能
            $tags = get_tags(array('hide_empty' => false));
            if (!empty($tags)) {
                // 选择第一个标签生成描述（这里可以根据需要修改逻辑）
                $tag = $tags[0];
                $optimize_result = $this->ai_generate_tag_description($tag->term_id);
            } else {
                $optimize_result = array('success' => false, 'message' => esc_html__('没有可用的标签', 'saiita-ai-content-toolkit'));
            }

            if ($optimize_result['success']) {
                wp_send_json_success(array(
                    'message' => $optimize_result['message'],
                    'optimized_tags' => $optimize_result['optimized_tags'],
                    'removed_tags' => $optimize_result['removed_tags']
                ));
            } else {
                wp_send_json_error(array('message' => $optimize_result['message']));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => esc_html__('AI标签优化失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * 使用AI为分类生成描述
     */
    private function ai_generate_category_description($category_id)
    {
        // 检查AI功能是否可用
        if (!function_exists('saiita_ai_content_toolkit_is_ai_available') || !saiita_ai_content_toolkit_is_ai_available()) {
            return array('success' => false, 'message' => esc_html__('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit'));
        }

        try {
            $category = get_category($category_id);
            if (!$category) {
                return array('success' => false, 'message' => esc_html__('分类不存在', 'saiita-ai-content-toolkit'));
            }

            // 获取该分类下的文章
            $posts = get_posts(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 10,
                'category' => $category_id,
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            if (empty($posts)) {
                return array('success' => false, 'message' => esc_html__('该分类下没有文章', 'saiita-ai-content-toolkit'));
            }

            // 分析文章内容
            $articles_content = '';
            $keywords = array();

            foreach ($posts as $post) {
                $articles_content .= "文章标题：{$post->post_title}\n";
                $articles_content .= "文章内容：" . mb_substr(wp_strip_all_tags($post->post_content), 0, 300) . "\n\n";

                // 提取关键词
                $content = ($post->post_title ?? '') . ' ' . ($post->post_content ?? '');
                $words = preg_split('/[\s，。！？；：""\'\'（）【】]/u', $content);
                foreach ($words as $word) {
                    $word = trim($word);
                    if (mb_strlen($word) >= 2 && mb_strlen($word) <= 6 && !preg_match('/[0-9]/', $word)) {
                        if (isset($keywords[$word])) {
                            $keywords[$word]++;
                        } else {
                            $keywords[$word] = 1;
                        }
                    }
                }
            }

            // 获取高频关键词
            arsort($keywords);
            $top_keywords = array_slice(array_keys($keywords), 0, 10);
            $keywords_text = implode('、', $top_keywords);

            // 构建AI提示词
            $prompt = "请为以下分类生成一个简洁准确的描述：

分类名称：{$category->name}

该分类下的主要文章内容：
{$articles_content}

主要关键词：{$keywords_text}

请返回一个1-2句话的分类描述，要求：
1. 准确概括该分类的主要内容
2. 语言简洁明了，适合用户理解
3. 50-80字之间
4. 只返回描述内容，不要包含其他解释";

            // 调用AI服务
            $response = saiita_ai_content_toolkit_call_ai_api(
                $prompt,
                array(
                    'max_tokens' => 500,
                    'temperature' => 0.3
                )
            );

            if ($response && !empty($response)) {
                $description = trim($response);

                // 清理描述
                $description = preg_replace('/[""\'\'"]/', '', $description);
                $description = preg_replace('/[\r\n]+/', ' ', $description);
                $description = trim($description);

                if (!empty($description)) {
                    // 更新分类描述
                    wp_update_term($category_id, 'category', array(
                        'description' => $description
                    ));

                    return array(
                        'success' => true,
                        /* translators: %s: value */
                        'message' => sprintf(esc_html__('成功为分类"%s"生成描述', 'saiita-ai-content-toolkit'), $category->name),
                        'description' => $description
                    );
                } else {
                    return array('success' => false, 'message' => esc_html__('AI未能生成有效描述', 'saiita-ai-content-toolkit'));
                }
            } else {
                return array('success' => false, 'message' => esc_html__('AI服务响应异常', 'saiita-ai-content-toolkit'));
            }
        } catch (Exception $e) {
            return array('error' => esc_html__('AI生成分类描述失败：', 'saiita-ai-content-toolkit') . $e->getMessage());
        }
    }

    /**
     * 使用AI生成文章标签
     */
    private function generate_ai_tags($content, $title = '')
    {
        try {
            // 检查AI功能是否可用
            if (!saiita_ai_content_toolkit_is_ai_available()) {
                return null;
            }

            // 构建提示词
            $prompt = "请根据以下文章信息生成3-8个相关的标签：\n\n";

            if (!empty($title)) {
                $prompt .= "标题：{$title}\n";
            }

            $prompt .= "内容：" . mb_substr($content, 0, 2000) . "\n\n";
            $prompt .= "要求：\n";
            $prompt .= "1. 标签要准确反映文章主题和内容\n";
            $prompt .= "2. 使用简洁的关键词，最好是2-4个字\n";
            $prompt .= "3. 标签要具有代表性，便于搜索和分类\n";
            $prompt .= "4. 每行一个标签，不要编号\n";
            $prompt .= "5. 直接输出标签，不要解释\n\n";
            $prompt .= "标签：";

            // 调用AI API（max_tokens 需给足余量，deepseek-v4-flash 等推理模型会将 reasoning 计入 token 预算）
            $response = saiita_ai_content_toolkit_call_ai_api(
                $prompt,
                array(
                    'max_tokens' => 500,
                    'temperature' => 0.3
                )
            );

            if (!is_wp_error($response) && !empty($response)) {
                $ai_tags_text = trim($response);

                // 处理AI生成的标签
                $ai_tags = array();
                $lines = explode("\n", $ai_tags_text);

                foreach ($lines as $line) {
                    $tag = trim($line);
                    $tag = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $tag); // 清理特殊字符
                    $tag = trim($tag);

                    if (!empty($tag) && mb_strlen($tag) >= 2 && mb_strlen($tag) <= 10) {
                        $ai_tags[] = $tag;
                    }
                }

                // 去重并限制数量
                $ai_tags = array_unique($ai_tags);
                $ai_tags = array_slice($ai_tags, 0, 8);

                return !empty($ai_tags) ? $ai_tags : null;
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * 使用AI生成 SEO 友好的 URL slug
     */
    private function generate_ai_slug($content, $title = '')
    {
        try {
            if (!saiita_ai_content_toolkit_is_ai_available()) {
                return null;
            }

            $language = $this->settings['slug_language'] ?? 'english';
            $min_words = intval($this->settings['slug_min_words'] ?? 4);
            $max_words = intval($this->settings['slug_max_words'] ?? 8);

            if ($language === 'chinese') {
                $prompt = "请为以下文章内容生成一个中文 URL slug（网址别名）。要求：\n";
                $prompt .= "1. 长度控制在{$min_words}-{$max_words}个汉字之间\n";
                $prompt .= "2. 提取文章最核心的关键词\n";
                $prompt .= "3. 只输出 slug 文本，不要其他内容\n\n";
            } else {
                $prompt = "Generate an SEO-friendly English URL slug for the following article. Requirements:\n";
                $prompt .= "1. Use only lowercase English letters, numbers, and hyphens\n";
                $prompt .= "2. Keep it {$min_words}-{$max_words} words\n";
                $prompt .= "3. Include the most important keywords from the content\n";
                $prompt .= "4. Do NOT include stop words like 'the', 'a', 'an', 'and', 'in', 'on', 'to', 'of', 'for'\n";
                $prompt .= "5. Output ONLY the slug text, nothing else\n\n";
            }

            if (!empty($title)) {
                $prompt .= "Title: {$title}\n";
            }

            $prompt .= "Content: " . mb_substr($content, 0, 2000) . "\n\n";
            $prompt .= "Slug:";

            $response = saiita_ai_content_toolkit_call_ai_api(
                $prompt,
                array(
                    'max_tokens' => 200,
                    'temperature' => 0.3
                )
            );

            if (!is_wp_error($response) && !empty($response)) {
                $slug = trim($response);
                $slug = strtolower($slug);
                $slug = sanitize_title($slug, '');
                $slug = trim($slug, '-');

                if (!empty($slug) && strlen($slug) > 1) {
                    return $slug;
                }
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * 使用AI为标签生成描述
     */
    private function ai_generate_tag_description($tag_id)
    {
        // 检查AI功能是否可用
        if (!function_exists('saiita_ai_content_toolkit_is_ai_available') || !saiita_ai_content_toolkit_is_ai_available()) {
            return array('success' => false, 'message' => esc_html__('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit'));
        }

        try {
            $tag = get_term($tag_id, 'post_tag');
            if (!$tag) {
                return array('success' => false, 'message' => esc_html__('标签不存在', 'saiita-ai-content-toolkit'));
            }

            // 获取使用该标签的文章
            $posts = get_posts(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 10,
                'tag' => $tag->slug,
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            if (empty($posts)) {
                return array('success' => false, 'message' => esc_html__('该标签下没有文章', 'saiita-ai-content-toolkit'));
            }

            // 分析文章内容
            $articles_content = '';
            $keywords = array();

            foreach ($posts as $post) {
                $articles_content .= "文章标题：{$post->post_title}\n";
                $articles_content .= "文章内容：" . mb_substr(wp_strip_all_tags($post->post_content), 0, 300) . "\n\n";

                // 提取关键词
                $content = ($post->post_title ?? '') . ' ' . ($post->post_content ?? '');
                $words = preg_split('/[\s，。！？；：""\'\'（）【】]/u', $content);
                foreach ($words as $word) {
                    $word = trim($word);
                    if (mb_strlen($word) >= 2 && mb_strlen($word) <= 6 && !preg_match('/[0-9]/', $word)) {
                        if (isset($keywords[$word])) {
                            $keywords[$word]++;
                        } else {
                            $keywords[$word] = 1;
                        }
                    }
                }
            }

            // 获取高频关键词（排除标签本身）
            unset($keywords[$tag->name]);
            arsort($keywords);
            $top_keywords = array_slice(array_keys($keywords), 0, 8);
            $keywords_text = implode('、', $top_keywords);

            // 构建AI提示词
            $prompt = "请为以下标签生成一个简洁准确的描述：

标签名称：{$tag->name}

使用该标签的文章主要内容：
{$articles_content}

相关关键词：{$keywords_text}

请返回一个1-2句话的标签描述，要求：
1. 准确概括该标签的用途和含义
2. 语言简洁明了，适合用户理解
3. 30-60字之间
4. 只返回描述内容，不要包含其他解释";

            // 调用AI服务
            $response = saiita_ai_content_toolkit_call_ai_api(
                $prompt,
                array(
                    'max_tokens' => 500,
                    'temperature' => 0.3
                )
            );

            if ($response && !empty($response)) {
                $description = trim($response);

                // 清理描述
                $description = preg_replace('/[""\'\'"]/', '', $description);
                $description = preg_replace('/[\r\n]+/', ' ', $description);
                $description = trim($description);

                if (!empty($description)) {
                    // 更新标签描述
                    wp_update_term($tag_id, 'post_tag', array(
                        'description' => $description
                    ));

                    return array(
                        'success' => true,
                        /* translators: %s: value */
                        'message' => sprintf(esc_html__('成功为标签"%s"生成描述', 'saiita-ai-content-toolkit'), $tag->name),
                        'description' => $description
                    );
                } else {
                    return array('success' => false, 'message' => esc_html__('AI未能生成有效描述', 'saiita-ai-content-toolkit'));
                }
            } else {
                return array('success' => false, 'message' => esc_html__('AI服务响应异常', 'saiita-ai-content-toolkit'));
            }
        } catch (Exception $e) {
            return array('error' => esc_html__('AI生成标签描述失败：', 'saiita-ai-content-toolkit') . $e->getMessage());
        }
    }

    /**
     * 调试AJAX请求
     */
    public function debug_ajax_requests()
    {
        if (wp_doing_ajax() && isset($_POST['action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }
    }

    /**
     * 智能判断是否应该生成摘要（新的逻辑）
     *
     * @param int $post_id 文章ID
     * @param WP_Post $post 文章对象
     * @param string $auto_mode 自动生成模式
     * @return bool 是否应该生成摘要
     */
    private function should_generate_excerpt_smart($post_id, $post, $auto_mode)
    {
        // 规则1：发布状态的文章如果摘要为空，应该生成AI摘要
        if ($post->post_status === 'publish' && empty($post->post_excerpt)) {
            return true;
        }

        // 规则2：检查文章内容或标题是否发生变化
        $content_changed = $this->has_content_changed($post_id, $post);

        if ($content_changed) {
            // 内容有变化，根据模式决定是否重新生成
            switch ($auto_mode) {
                case 'draft_always':
                    // 草稿总是生成：草稿状态每次保存都AI生成，发布状态内容变化时也生成
                    return $post->post_status === 'draft' || $post->post_status === 'publish';

                case 'smart_first':
                    // 智能首次生成：内容变化时重新生成，尊重用户手动编辑的摘要
                    // 检查当前摘要是否是用户手动编辑的
                    $is_user_edited = $this->is_user_edited_excerpt($post_id);
                    return !$is_user_edited; // 只有非用户编辑的摘要才重新生成

                case 'draft_once':
                    // 草稿首次生成：内容变化时重新生成，但避免频繁更新
                    $last_generated = get_post_meta($post_id, '_auto_excerpt_last_generated', true);
                    $min_interval = 300; // 5分钟最小间隔
                    $current_time = time();

                    if ($post->post_status === 'draft') {
                        // 草稿状态：内容变化就生成
                        return true;
                    } else {
                        // 发布状态：检查时间间隔
                        return !$last_generated || ($current_time - $last_generated) > $min_interval;
                    }

                default:
                    // 默认：内容变化时生成
                    return true;
            }
        }

        // 规则3：传统模式判断（向后兼容）
        return $this->should_generate_excerpt($post_id, $post, $auto_mode);
    }

    /**
     * 检查文章内容是否发生变化
     *
     * @param int $post_id 文章ID
     * @param WP_Post $post 文章对象
     * @return bool 内容是否发生变化
     */
    private function has_content_changed($post_id, $post)
    {
        // 获取上次保存的内容哈希
        $last_content_hash = get_post_meta($post_id, '_last_content_hash', true);

        // 计算当前内容的哈希
        $current_content = $post->post_title . "\n" . wp_strip_all_tags($post->post_content);
        $current_hash = md5($current_content);

        // 如果哈希不同，说明内容发生了变化
        if ($last_content_hash !== $current_hash) {
            return true;
        }

        return false;
    }

    /**
     * 更新内容哈希记录
     *
     * @param int $post_id 文章ID
     * @param WP_Post $post 文章对象
     */
    private function update_content_hash($post_id, $post)
    {
        // 计算并保存当前内容的哈希
        $current_content = $post->post_title . "\n" . wp_strip_all_tags($post->post_content);
        $current_hash = md5($current_content);

        update_post_meta($post_id, '_last_content_hash', $current_hash);
        update_post_meta($post_id, '_auto_excerpt_last_generated', time());
    }

    /**
     * 检查摘要是否被用户手动编辑过
     *
     * @param int $post_id 文章ID
     * @return bool 是否被用户编辑过
     */
    private function is_user_edited_excerpt($post_id)
    {
        // 检查是否有AI生成标记
        $is_ai_generated = get_post_meta($post_id, '_auto_excerpt_ai_generated', true) ||
                          get_post_meta($post_id, '_ai_generated_excerpt', true);

        if (!$is_ai_generated) {
            // 没有AI生成标记，可能是用户手动编辑的
            return true;
        }

        // 检查摘要是否在AI生成后被修改过
        $last_generated = get_post_meta($post_id, '_auto_excerpt_last_generated', true);
        $last_modified = get_post_modified_time('U', true, $post_id);

        if ($last_generated && $last_modified > $last_generated) {
            // 摘要在AI生成后又被修改过，认为是用户编辑
            return true;
        }

        return false;
    }
}

// 注册插件激活和停用钩子
register_activation_hook(__FILE__, array('Saiita_AI_Toolkit_Auto_Excerpt', 'activate'));
register_deactivation_hook(__FILE__, array('Saiita_AI_Toolkit_Auto_Excerpt', 'deactivate'));