<?php
/**
 * Category Optimization Admin Page
 *
 * 分类优化管理页面 - 使用与文章优化页面相同的样式和布局
 *
 * @version 1.0.0
 * @author Saiita AI Content Toolkit
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Category Optimization Admin Page 类
 */
class Saiita_AI_Toolkit_Category_Optimization_Admin
{
    use Saiita_AI_Toolkit_Singleton;

    /**
     * 分类优化模块实例
     */
    private $category_optimization = null;

    /**
     * 初始化钩子
     */
    protected function init($args = array())
    {
        $this->category_optimization = Saiita_AI_Toolkit_Category_Optimization::get_instance();
    }

    /**
     * 管理页面
     */
    public function admin_page()
    {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('权限不足', 'saiita-ai-content-toolkit'));
        }

        // 验证nonce（防止CSRF攻击）
        if (isset($_POST['_wpnonce'])) { // phpcs:ignore WordPress.Security.NonceVerification
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'saiita_ai_content_toolkit_category_optimization')) {
                wp_die(esc_html__('安全验证失败', 'saiita-ai-content-toolkit'));
            }
        }

        // 显示管理页面
        ?>
        <div class="wrap">
            <div class="act-card">
                    <?php
                    // 获取分页数据
                    $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                    $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput


                    $categories_list = $this->category_optimization->get_categories_list($current_page, 15, $status);
                    $stats = $this->category_optimization->get_statistics();

                    ?>

                    <!-- 筛选器、批量操作和分页放在同一行 -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 15px;">
                        <!-- 左侧：筛选器和批量操作 -->
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <form method="get" action="" class="act-filter-row">
                                <input type="hidden" name="page" value="saiita-ai-content-toolkit-category-optimization">
                                <select name="status" id="category-status-filter">
                                    <option value="all" <?php selected(isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all', 'all'); ?>><?php esc_html_e('全部分类', 'saiita-ai-content-toolkit'); ?></option> // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                                    <option value="with_description" <?php selected(isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all', 'with_description'); ?>><?php esc_html_e('有描述分类', 'saiita-ai-content-toolkit'); ?></option> // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                                    <option value="without_description" <?php selected(isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all', 'without_description'); ?>><?php esc_html_e('无描述分类', 'saiita-ai-content-toolkit'); ?></option> // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                                </select>
                                <button type="submit" class="act-btn"><?php esc_html_e('筛选', 'saiita-ai-content-toolkit'); ?></button>
                            </form>
                        </div>

                        <!-- 右侧：分页 -->
                        <?php if (!empty($categories_list) && isset($categories_list['pages']) && $categories_list['pages'] > 1) : ?>
                        <div class="tablenav-pages" style="margin: 0;">
                            <?php
                            $current_url = admin_url('admin.php?page=saiita-ai-content-toolkit-category-optimization');
                            if (isset($_GET['status'])) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                                $current_url .= '&status=' . urlencode(isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all'); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                            }
                            ?>
                            <span class="displaying-num">
                                <?php
                                printf(
                                    /* translators: %d: count */
                                    esc_html__('共 %d 个项目', 'saiita-ai-content-toolkit'),
                                    absint($categories_list['total'])
                                );
                                ?>
                            </span>
                            <?php
                            // 使用WordPress标准的paginate_links函数
                            echo wp_kses_post(paginate_links(array(
                                'base' => $current_url . '&paged=%#%',
                                'format' => '',
                                'prev_text' => esc_html__('&laquo; 上一页', 'saiita-ai-content-toolkit'),
                                'next_text' => esc_html__('下一页 &raquo;', 'saiita-ai-content-toolkit'),
                                'total' => $categories_list['pages'],
                                'current' => $current_page
                            )));
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- 批量操作进度 -->
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
                                <span>当前处理：<span id="current-category">准备中...</span></span>
                                <span>已处理：<span id="processed-count">0</span> / <span id="total-count">0</span></span>
                                <span>成功：<span id="success-count">0</span></span>
                                <span>失败：<span id="error-count">0</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- 批量操作结果 -->
                    <div id="batch-generate-result" style="display: none; margin: 15px 0;"></div>

                    <!-- 分类列表 -->
                    <?php
                    // 添加调试信息和错误处理
                    if (empty($categories_list) || !isset($categories_list['categories'])) {
                        echo '<div class="notice notice-warning"><p>分类列表数据加载失败，请检查错误日志。</p></div>';
                    } elseif (empty($categories_list['categories'])) {
                        // 显示空状态
                        ?>
                        <table class="act-table wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col" width="30%"><?php esc_html_e('分类名称', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col" width="10%"><?php esc_html_e('描述状态', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col" width="10%"><?php esc_html_e('描述长度', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col" width="10%"><?php esc_html_e('文章数量', 'saiita-ai-content-toolkit'); ?></th>
                                    <th scope="col" width="20%"><?php esc_html_e('操作', 'saiita-ai-content-toolkit'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px;">
                                        <?php
                                        $current_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
                                        if ($current_status !== 'all') :
                                            ?>
                                        <div style="font-size: 16px; color: #666; margin-bottom: 20px;">
                                            <span class="dashicons dashicons-search" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></span>
                                            没有找到匹配的<?php echo $current_status === 'with_description' ? esc_html__('有描述', 'saiita-ai-content-toolkit') : esc_html__('无描述', 'saiita-ai-content-toolkit'); ?>分类
                                        </div>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=saiita-ai-content-toolkit-category-optimization')); ?>" class="button button-primary">
                                            清除筛选条件
                                        </a>
                                        <?php else : ?>
                                        <div style="font-size: 16px; color: #666; margin-bottom: 20px;">
                                            <span class="dashicons dashicons-category" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></span>
                                            暂无分类数据
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <?php
                    } else {
                        ?>

                    <table class="act-table wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" width="30%"><?php esc_html_e('分类名称', 'saiita-ai-content-toolkit'); ?></th>
                                <th scope="col" width="10%"><?php esc_html_e('描述状态', 'saiita-ai-content-toolkit'); ?></th>
                                <th scope="col" width="10%"><?php esc_html_e('描述长度', 'saiita-ai-content-toolkit'); ?></th>
                                <th scope="col" width="10%"><?php esc_html_e('文章数量', 'saiita-ai-content-toolkit'); ?></th>
                                <th scope="col" width="20%"><?php esc_html_e('操作', 'saiita-ai-content-toolkit'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories_list['categories'] as $category) : ?>
                            <tr>
                                <td>
                                    <strong><a href="<?php echo esc_url($category['edit_url']); ?>" target="_blank"><?php echo esc_html($category['name']); ?></a></strong>
                                    <div style="font-size: 12px; color: #666; margin-top: 4px;">
                                        <?php echo esc_html($category['slug']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($category['has_description']) : ?>
                                        <span class="status-active"><?php esc_html_e('有描述', 'saiita-ai-content-toolkit'); ?></span>
                                    <?php else : ?>
                                        <span class="status-inactive"><?php esc_html_e('无描述', 'saiita-ai-content-toolkit'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($category['description_length']); ?> <?php esc_html_e('字符', 'saiita-ai-content-toolkit'); ?></td>
                                <td><?php echo esc_html($category['post_count']); ?> <?php esc_html_e('篇', 'saiita-ai-content-toolkit'); ?></td>
                                <td>
                                    <div class="action-buttons-container">
                                        <a href="<?php echo esc_url($category['edit_url']); ?>" class="act-btn act-btn-sm act-btn-edit" target="_blank"><?php esc_html_e('编辑', 'saiita-ai-content-toolkit'); ?></a>
                                        <a href="<?php echo esc_url($category['view_url']); ?>" class="act-btn act-btn-sm act-btn-view" target="_blank"><?php esc_html_e('查看', 'saiita-ai-content-toolkit'); ?></a>
                                        <?php if (!$category['has_description']) : ?>
                                        <button type="button" class="button button-small generate-description-single" data-category-id="<?php echo esc_attr($category['ID']); ?>" data-category-name="<?php echo esc_attr($category['name']); ?>" title="为这个分类生成AI描述" data-ai-feature="category_optimization" style="background: #46b450; color: white; border-color: #46b450; margin: 0;">
                                            生成描述
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php } // End of else from categories check ?>
                </div>
            </div>

        <?php
    }
}

// 初始化管理页面
Saiita_AI_Toolkit_Category_Optimization_Admin::get_instance();