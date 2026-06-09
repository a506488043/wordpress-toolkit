<?php
/**
 * Website Optimization Admin Page - 网站优化管理页面
 *
 * 基于文章优化模块的样式和布局，提供网站SEO分析和优化建议
 *
 * @version 1.0.0
 * @author Saiita AI Content Toolkit
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Website Optimization Admin Page 类
 */
class Saiita_AI_Toolkit_Website_Optimization_Admin
{
    /**
     * 单例实例
     */
    private static $instance = null;

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
        // 初始化操作
    }

    /**
     * 渲染管理页面
     */
    public function render_page($site_info, $stats, $settings)
    {
        ?>
        <div class="wrap">
        <?php
        $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
// 免费版本无使用限制
        if (!$license_manager->is_pro()) :
            ?>
            <div id="act-usage-banner" style="background:linear-gradient(135deg,#fef3c7,#fde68a);border:1px solid #f59e0b;border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:18px;line-height:1;">💡</span>
                    <span id="act-seo-banner-text" style="color:#92400e;font-size:14px;line-height:1.4;"><strong>免费版</strong> — 网站SEO优化 <strong>无使用限制</strong></span>
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=saiita-ai-content-toolkit-license')); ?>" class="button button-small" style="background:#f59e0b;border-color:#d97706;color:white;display:inline-flex;align-items:center;gap:4px;">⭐ 升级 PRO</a>
            </div>
        <?php endif; ?>

            <!-- 统计信息面板 -->
            <div class="act-card">
                <div style="display: flex; align-items: center; gap: 30px; flex-wrap: wrap; justify-content: space-between;">
                    <div>
                        <strong><?php esc_html_e('网站标题', 'saiita-ai-content-toolkit'); ?></strong>
                        <div style="margin-top: 5px;">
                            <span class="dashicons dashicons-admin-site" style="color: #0073aa;"></span>
                            <?php echo esc_html($site_info['site_title']); ?>
                        </div>
                    </div>
                    <div>
                        <strong><?php esc_html_e('文章总数', 'saiita-ai-content-toolkit'); ?></strong>
                        <div style="margin-top: 5px;">
                            <span class="dashicons dashicons-post" style="color: #0073aa;"></span>
                            <?php echo esc_html(number_format($site_info['total_posts'])); ?>
                        </div>
                    </div>
                    <div>
                        <strong><?php esc_html_e('页面总数', 'saiita-ai-content-toolkit'); ?></strong>
                        <div style="margin-top: 5px;">
                            <span class="dashicons dashicons-admin-page" style="color: #0073aa;"></span>
                            <?php echo esc_html(number_format($site_info['total_pages'])); ?>
                        </div>
                    </div>
                    <div>
                        <strong><?php esc_html_e('最后分析', 'saiita-ai-content-toolkit'); ?></strong>
                        <div style="margin-top: 5px;">
                            <span class="dashicons dashicons-calendar" style="color: #0073aa;"></span>
                            <?php echo esc_html($site_info['last_analysis_date']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 操作区域 -->
            <div class="act-card">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
                    <button type="button" id="analyze-website-seo" class="act-btn act-btn-primary" data-ai-feature="website_seo">
                        <?php esc_html_e('分析网站SEO', 'saiita-ai-content-toolkit'); ?>
                    </button>
                    <span class="spinner" id="analysis-spinner" style="display: none; margin-left: 5px;"></span>
                </div>

                    <!-- 分析进度 -->
                    <div id="analysis-progress" style="display: none; margin: 15px 0;">
                        <div class="progress-container">
                            <h4 id="progress-title"><?php esc_html_e('分析中...', 'saiita-ai-content-toolkit'); ?></h4>
                            <div class="progress-bar-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" id="progress-fill"></div>
                                </div>
                                <span class="progress-text" id="progress-text">0%</span>
                            </div>
                            <div class="progress-details" id="progress-details">
                                <span><?php esc_html_e('当前分析：', 'saiita-ai-content-toolkit'); ?><span id="current-analysis"><?php esc_html_e('准备中...', 'saiita-ai-content-toolkit'); ?></span></span>
                                <span><?php esc_html_e('已分析：', 'saiita-ai-content-toolkit'); ?><span id="processed-count">0</span> / <span id="total-count">0</span></span>
                                <span><?php esc_html_e('成功：', 'saiita-ai-content-toolkit'); ?><span id="success-count">0</span></span>
                                <span><?php esc_html_e('失败：', 'saiita-ai-content-toolkit'); ?><span id="error-count">0</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- 分析结果 -->
                    <div id="analysis-result" style="display: none; margin: 15px 0;"></div>

                    <!-- SEO分析报告区域 -->
                    <div id="seo-analysis-report" style="margin-top: 20px; display: none;">
                        <h3><?php esc_html_e('SEO分析报告', 'saiita-ai-content-toolkit'); ?></h3>

                        <!-- 标题SEO报告 -->
                        <div class="seo-report-section" id="title-report-section" style="display: none;">
                            <h4><?php esc_html_e('标题SEO分析', 'saiita-ai-content-toolkit'); ?></h4>
                            <div class="report-content">
                                <div class="report-item">
                                    <strong><?php esc_html_e('当前标题：', 'saiita-ai-content-toolkit'); ?></strong>
                                    <span id="current-title"></span>
                                </div>
                                <div class="report-item">
                                    <strong><?php esc_html_e('标题长度：', 'saiita-ai-content-toolkit'); ?></strong>
                                    <span id="title-length"></span> <?php esc_html_e('字符', 'saiita-ai-content-toolkit'); ?>
                                </div>
                                <div class="analysis-results">
                                    <h5><?php esc_html_e('分析结果：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="title-analysis"></ul>
                                </div>
                                <div class="recommendations">
                                    <h5><?php esc_html_e('优化建议：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="title-recommendations"></ul>
                                </div>
                                <div class="suggestions" id="title-suggestions-section" style="display: none;">
                                    <h5><?php esc_html_e('具体标题建议：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="title-suggestions"></ul>
                                </div>
                                <div class="ai-suggestions" id="ai-title-suggestions-section" style="display: none;">
                                    <h5><?php esc_html_e('🤖 AI智能标题建议：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="ai-title-suggestions"></ul>
                                </div>
                            </div>
                        </div>

                        <!-- 描述SEO报告 -->
                        <div class="seo-report-section" id="description-report-section" style="display: none;">
                            <h4><?php esc_html_e('描述SEO分析', 'saiita-ai-content-toolkit'); ?></h4>
                            <div class="report-content">
                                <div class="report-item">
                                    <strong><?php esc_html_e('当前描述：', 'saiita-ai-content-toolkit'); ?></strong>
                                    <span id="current-description"></span>
                                </div>
                                <div class="report-item">
                                    <strong><?php esc_html_e('描述长度：', 'saiita-ai-content-toolkit'); ?></strong>
                                    <span id="description-length"></span> <?php esc_html_e('字符', 'saiita-ai-content-toolkit'); ?>
                                </div>
                                <div class="analysis-results">
                                    <h5><?php esc_html_e('分析结果：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="description-analysis"></ul>
                                </div>
                                <div class="recommendations">
                                    <h5><?php esc_html_e('优化建议：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="description-recommendations"></ul>
                                </div>
                                <div class="suggestions" id="description-suggestions-section" style="display: none;">
                                    <h5><?php esc_html_e('具体描述建议：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="description-suggestions"></ul>
                                </div>
                                <div class="ai-suggestions" id="ai-description-suggestions-section" style="display: none;">
                                    <h5><?php esc_html_e('🤖 AI智能描述建议：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="ai-description-suggestions"></ul>
                                </div>
                            </div>
                        </div>

                        <!-- 关键字SEO报告 -->
                        <div class="seo-report-section" id="keyword-report-section" style="display: none;">
                            <h4><?php esc_html_e('关键字SEO分析', 'saiita-ai-content-toolkit'); ?></h4>
                            <div class="report-content">
                                <div class="report-item">
                                    <strong><?php esc_html_e('当前关键字：', 'saiita-ai-content-toolkit'); ?></strong>
                                    <span id="current-keywords"></span>
                                </div>
                                <div class="report-item">
                                    <strong><?php esc_html_e('关键字数量：', 'saiita-ai-content-toolkit'); ?></strong>
                                    <span id="keyword-count"></span> <?php esc_html_e('个', 'saiita-ai-content-toolkit'); ?>
                                </div>
                                <div class="analysis-results">
                                    <h5><?php esc_html_e('分析结果：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="keyword-analysis"></ul>
                                </div>
                                <div class="recommendations">
                                    <h5><?php esc_html_e('优化建议：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="keyword-recommendations"></ul>
                                </div>
                                <div class="suggestions" id="keyword-suggestions-section" style="display: none;">
                                    <h5><?php esc_html_e('具体关键字建议：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="keyword-suggestions"></ul>
                                </div>
                                <div class="ai-suggestions" id="ai-keyword-suggestions-section" style="display: none;">
                                    <h5><?php esc_html_e('🤖 AI智能关键字建议：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="ai-keyword-suggestions"></ul>
                                </div>
                                <div class="ai-suggestions" id="ai-longtail-suggestions-section" style="display: none;">
                                    <h5><?php esc_html_e('🤖 AI智能长尾关键字建议：', 'saiita-ai-content-toolkit'); ?></h5>
                                    <ul id="ai-longtail-suggestions"></ul>
                                </div>
                            </div>
                        </div>

                        <!-- 总体优化建议 -->
                        <div class="seo-report-section" id="overall-recommendations-section" style="display: none;">
                            <h4><?php esc_html_e('总体优化建议', 'saiita-ai-content-toolkit'); ?></h4>
                            <div class="report-content">
                                <ul id="overall-recommendations"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php /* Inline styles and scripts removed - loaded via admin_enqueue_scripts in website-optimization-module.php */ ?>

        <?php
    }
}

// 初始化管理页面
Saiita_AI_Toolkit_Website_Optimization_Admin::get_instance();