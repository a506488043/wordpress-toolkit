<?php
/**
 * 时间胶囊物品管理页面模板
 * Template Name: 时间胶囊物品管理
 */

// 定义常量（如果未定义）
if (!defined('TIME_CAPSULE_PLUGIN_URL')) {
    define('TIME_CAPSULE_PLUGIN_URL', WORDPRESS_TOOLKIT_PLUGIN_URL . 'modules/time-capsule/');
}
if (!defined('TIME_CAPSULE_VERSION')) {
    define('TIME_CAPSULE_VERSION', '1.0.3');
}

// 加载前端管理样式
wp_enqueue_style('time-capsule-frontend-manage', TIME_CAPSULE_PLUGIN_URL . 'assets/css/frontend-manage.css', array(), TIME_CAPSULE_VERSION);

// 加载jQuery
wp_enqueue_script('jquery');

// 加载前端管理脚本
wp_enqueue_script('time-capsule-frontend-manage', TIME_CAPSULE_PLUGIN_URL . 'assets/js/frontend-manage.js', array('jquery'), TIME_CAPSULE_VERSION, true);

// 传递AJAX URL给前端脚本
wp_localize_script('time-capsule-frontend-manage', 'tc_frontend_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('time_capsule_frontend_nonce')
));

get_header(); ?>

<div class="time-capsule-page-wrapper">
    <?php while (have_posts()) : the_post(); ?>

        <div class="tc-page-main">
            <div class="container">
                <?php
                // 检查用户权限
                if (!is_user_logged_in()) {
                    echo '<div class="tc-login-notice">';
                    echo '<h3>请登录管理您的时间胶囊</h3>';
                    echo '<p>您需要登录后才能添加、编辑和删除您的个人物品记录。</p>';
                    echo '<a href="' . wp_login_url(get_permalink()) . '" class="tc-login-btn">立即登录</a>';
                    echo '</div>';
                    echo '</div></div>';
                    get_footer();
                    return;
                }

                // 加载必要的类文件
                require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/time-capsule/includes/class-database.php';
                require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/time-capsule/includes/class-item.php';
                require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/time-capsule/includes/class-category.php';

                // 获取用户数据
                $item_manager = new TimeCapsule_Item();
                $category_manager = new TimeCapsule_Category();

                $args = array(
                    'user_id' => get_current_user_id(),
                    'limit' => 50, // 前端管理页面显示更多物品
                    'status' => '' // 显示所有状态的物品
                );

                $items = $item_manager->get_items($args);
                $categories = $category_manager->get_categories();
                $stats = $item_manager->get_stats(get_current_user_id());
                ?>

                <!-- 页面标题和操作按钮 -->
                <div class="tc-manage-header">
                    <h1><?php the_title(); ?></h1>
                    <div class="tc-manage-actions">
                        <button type="button" class="tc-btn tc-btn-primary" id="tc-add-item-btn">
                            <span>+</span>
                            添加物品
                        </button>
                    </div>
                </div>

                
                <!-- 物品列表 -->
                <div class="tc-manage-items">
                    <?php if (empty($items)): ?>
                        <div class="tc-empty-state">
                            <div class="tc-empty-icon">📦</div>
                            <h3>暂无物品记录</h3>
                            <p>您还没有添加任何物品记录。</p>
                            <button type="button" class="tc-btn tc-btn-primary" id="tc-add-first-item">添加第一个物品</button>
                        </div>
                    <?php else: ?>
                        <div class="tc-items-grid">
                            <?php foreach ($items as $item): ?>
                                <div class="tc-item-card" data-item-id="<?php echo esc_attr($item->id); ?>">
                                    <div class="tc-item-header">
                                        <div class="tc-item-category">
                                            <?php
                                            $icons = array(
                                                'electronics' => '📱',
                                                'appliances' => '🏠',
                                                'furniture' => '🪑',
                                                'clothing' => '👔',
                                                'books' => '📚',
                                                'sports' => '⚽',
                                                'tools' => '🔧',
                                                'certificate' => '🏆',
                                                'vehicle' => '🚗',
                                                'other' => '📦'
                                            );
                                            echo $icons[$item->category] ?? '📦';
                                            ?>
                                        </div>
                                        <div class="tc-item-header-info">
                                            <div class="tc-item-name"><?php echo esc_html($item->name); ?></div>
                                            <div class="tc-item-status <?php echo esc_attr($item->status); ?>">
                                                <?php
                                                $status_texts = array(
                                                    'active' => '使用中',
                                                    'inactive' => '闲置',
                                                    'disposed' => '已处置'
                                                );
                                                echo $status_texts[$item->status] ?? $item->status;
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tc-item-content">
                                        <?php if (!empty($item->description)): ?>
                                            <div class="tc-item-description"><?php echo esc_html($item->description); ?></div>
                                        <?php endif; ?>

                                        <div class="tc-item-meta">
                                            <!-- 第一行：购买时间和价格 -->
                                            <div class="tc-meta-row">
                                                <div class="tc-meta-item">
                                                    <span class="tc-meta-label">购买时间：</span>
                                                    <span class="tc-meta-value"><?php echo esc_html(date('Y-m-d', strtotime($item->purchase_date))); ?></span>
                                                </div>
                                                <div class="tc-meta-item">
                                                    <span class="tc-meta-label">价格：</span>
                                                    <span class="tc-meta-value">
                                                        <?php if (!empty($item->price)): ?>
                                                            ¥<?php echo number_format(floatval($item->price)); ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- 第二行：类别 -->
                                            <div class="tc-meta-row">
                                                <div class="tc-meta-item">
                                                    <span class="tc-meta-label">类别：</span>
                                                    <span class="tc-meta-value"><?php echo esc_html($item->category_display_name ?? $item->category); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tc-item-actions">
                                        <button type="button" class="tc-action-btn tc-edit-btn" data-item-id="<?php echo esc_attr($item->id); ?>">
                                            <span>✏️</span>
                                            编辑
                                        </button>
                                        <button type="button" class="tc-action-btn tc-delete-btn" data-item-id="<?php echo esc_attr($item->id); ?>">
                                            <span>🗑️</span>
                                            删除
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    <?php endwhile; ?>
</div>

<!-- 添加/编辑物品模态框 -->
<div id="tc-item-modal" class="tc-modal" style="display: none;">
    <div class="tc-modal-overlay"></div>
    <div class="tc-modal-content">
        <div class="tc-modal-header">
            <h3 id="tc-modal-title">添加物品</h3>
            <button type="button" class="tc-modal-close">&times;</button>
        </div>
        <div class="tc-modal-body">
            <form id="tc-item-form" class="tc-item-form">
                <input type="hidden" name="action" value="time_capsule_frontend_save_item">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('time_capsule_frontend_nonce'); ?>">
                <input type="hidden" name="item_id" id="tc-item-id" value="">

                <div class="tc-form-grid">
                    <!-- 基本信息 -->
                    <div class="tc-form-section">
                        <h3>基本信息</h3>

                        <div class="tc-field-group">
                            <label for="tc_name">物品名称 <span class="required">*</span></label>
                            <input type="text" id="tc_name" name="name" required>
                        </div>

                        <div class="tc-field-group">
                            <label for="tc_category">类别 <span class="required">*</span></label>
                            <select id="tc_category" name="category" required>
                                <option value="">请选择类别</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category->name); ?>">
                                        <?php echo esc_html($category->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="tc-field-group">
                            <label for="tc_description">描述</label>
                            <textarea id="tc_description" name="description" rows="3"></textarea>
                        </div>

                        <div class="tc-field-group">
                            <label for="tc_status">状态</label>
                            <select id="tc_status" name="status">
                                <option value="active" selected>使用中</option>
                                <option value="inactive">闲置</option>
                                <option value="disposed">已处置</option>
                            </select>
                        </div>
                    </div>

                    <!-- 购买信息 -->
                    <div class="tc-form-section">
                        <h3>购买信息</h3>

                        <div class="tc-field-group">
                            <label for="tc_purchase_date">购买日期 <span class="required">*</span></label>
                            <input type="date" id="tc_purchase_date" name="purchase_date" required>
                        </div>

                        <div class="tc-field-group">
                            <label for="tc_purchase_source">购买来源</label>
                            <select id="tc_purchase_source" name="purchase_source">
                                <option value="">请选择购买来源</option>
                                <option value="京东">京东</option>
                                <option value="淘宝">淘宝</option>
                                <option value="小米有品">小米有品</option>
                                <option value="咸鱼">咸鱼</option>
                                <option value="小米商城">小米商城</option>
                                <option value="途虎">途虎</option>
                                <option value="亚马逊购物">亚马逊购物</option>
                                <option value="阿里巴巴">阿里巴巴</option>
                                <option value="IKE 宜家家居">IKE 宜家家居</option>
                                <option value="抖音">抖音</option>
                                <option value="小红书">小红书</option>
                                <option value="实体店">实体店</option>
                                <option value="线下">线下</option>
                            </select>
                        </div>

                        <div class="tc-field-group">
                            <label for="tc_price">购买价格</label>
                            <input type="number" id="tc_price" name="price" step="0.01" min="0" placeholder="0.00">
                            <span class="field-unit">元</span>
                        </div>

                        <div class="tc-field-group">
                            <label for="tc_warranty_period">质保期</label>
                            <input type="number" id="tc_warranty_period" name="warranty_period" min="0" placeholder="0">
                            <span class="field-unit">月</span>
                        </div>

                        <!-- 零食食品特有字段 -->
                        <div class="tc-field-group tc-snacks-only" style="display: none;">
                            <label for="tc_shelf_life">保质期</label>
                            <input type="number" id="tc_shelf_life" name="shelf_life" min="0" placeholder="0">
                            <span class="field-unit">天</span>
                        </div>
                    </div>

                    <!-- 产品信息 -->
                    <div class="tc-form-section">
                        <h3>产品信息</h3>

                        <div class="tc-field-group">
                            <label for="tc_brand">品牌</label>
                            <input type="text" id="tc_brand" name="brand">
                        </div>

                        <div class="tc-field-group">
                            <label for="tc_model">型号</label>
                            <input type="text" id="tc_model" name="model">
                        </div>

                        <div class="tc-field-group">
                            <label for="tc_serial_number">序列号</label>
                            <input type="text" id="tc_serial_number" name="serial_number">
                        </div>
                    </div>

                    <!-- 使用信息 -->
                    <div class="tc-form-section">
                        <h3>使用信息</h3>

                        <div class="tc-field-group">
                            <label for="tc_used_time_hours">已使用时间</label>
                            <input type="number" id="tc_used_time_hours" name="used_time_hours" min="0" placeholder="0">
                            <span class="field-unit">小时</span>
                        </div>

                        <!-- 交通工具特有字段 -->
                        <div class="tc-field-group tc-vehicle-only" style="display: none;">
                            <label for="tc_total_mileage">总里程</label>
                            <input type="number" id="tc_total_mileage" name="total_mileage" step="0.1" min="0" placeholder="0.0">
                            <span class="field-unit">公里</span>
                        </div>

                        <div class="tc-field-group">
                            <label for="tc_notes">备注</label>
                            <textarea id="tc_notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <!-- 表单按钮 -->
                <div class="tc-form-actions">
                    <button type="submit" class="tc-btn tc-btn-primary">
                        <span id="tc-submit-text">添加物品</span>
                    </button>
                    <button type="button" class="tc-btn tc-btn-secondary tc-modal-close">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 删除确认模态框 -->
<div id="tc-delete-modal" class="tc-modal" style="display: none;">
    <div class="tc-modal-overlay"></div>
    <div class="tc-modal-content">
        <div class="tc-modal-header">
            <h3>确认删除</h3>
            <button type="button" class="tc-modal-close">&times;</button>
        </div>
        <div class="tc-modal-body">
            <p>您确定要删除这个物品吗？此操作无法撤销。</p>
            <div class="tc-form-actions">
                <button type="button" class="tc-btn tc-btn-danger" id="tc-confirm-delete">确认删除</button>
                <button type="button" class="tc-btn tc-btn-secondary tc-modal-close">取消</button>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>