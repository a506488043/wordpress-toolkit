<?php
/**
 * Custom Card 管理页面
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 调试日志
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Custom Card Admin Page: Started');
    error_log('Custom Card Admin Page: Current tab: ' . (isset($_GET['tab']) ? $_GET['tab'] : 'Not set'));
}

// 获取选项
$options = get_option('wordpress_toolkit_custom_card_options');
$cache_expire_hours = isset($options['cache_expire_hours']) ? intval($options['cache_expire_hours']) : 72;
$enable_memcached = isset($options['enable_memcached']) ? $options['enable_memcached'] : false;
$enable_opcache = isset($options['enable_opcache']) ? $options['enable_opcache'] : true;

// 获取当前选项卡
// 如果是通过设置菜单访问，强制显示设置选项卡
// 如果是通过工具箱菜单访问，强制显示卡片列表选项卡
if (isset($_GET['page']) && $_GET['page'] === 'wordpress-toolkit-custom-card-settings') {
    $current_tab = 'settings';
} elseif (isset($_GET['page']) && $_GET['page'] === 'wordpress-toolkit-cards-list') {
    $current_tab = 'cards';
} else {
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
}

if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Custom Card Admin Page: Current tab set to: ' . $current_tab);
}
?>

<div class="wrap">
    <h1>网站卡片设置</h1>
    
    <!-- 选项卡导航 -->
    <nav class="nav-tab-wrapper">
        <a href="<?php echo add_query_arg('tab', 'settings'); ?>" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            基本设置
        </a>
        <a href="<?php echo add_query_arg('tab', 'cards'); ?>" class="nav-tab <?php echo $current_tab === 'cards' ? 'nav-tab-active' : ''; ?>">
            卡片列表
        </a>
    </nav>
    
    <!-- 基本设置选项卡 -->
    <?php if ($current_tab === 'settings'): ?>
    <div class="wordpress-toolkit-admin-section">
        <div class="wordpress-toolkit-admin-section">
            <form method="post" action="options.php">
                <?php settings_fields('wordpress_toolkit_custom_card_options'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cache_expire_hours">缓存时间（小时）</label>
                        </th>
                        <td>
                            <input type="number" id="cache_expire_hours" name="wordpress_toolkit_custom_card_options[cache_expire_hours]" 
                                   value="<?php echo esc_attr($cache_expire_hours); ?>" min="1" max="720" class="small-text">
                            <p class="description">设置卡片数据的缓存时间，默认为72小时。</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_memcached">启用Memcached缓存</label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_memcached" name="wordpress_toolkit_custom_card_options[enable_memcached]" 
                                   value="1" <?php checked($enable_memcached); ?>>
                            <p class="description">如果服务器支持Memcached，可以启用此选项提高性能。</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_opcache">启用OPcache缓存</label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_opcache" name="wordpress_toolkit_custom_card_options[enable_opcache]" 
                                   value="1" <?php checked($enable_opcache); ?>>
                            <p class="description">如果服务器支持OPcache，可以启用此选项提高性能。</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('保存设置'); ?>
            </form>
        </div>
        
        <div class="wordpress-toolkit-admin-section">
            <h2>使用说明</h2>
            <h3>如何添加网站卡片？</h3>
            <p>网站卡片会在用户通过短代码访问时自动创建。请按照以下步骤操作：</p>
            <ol>
                <li>在文章或页面中使用短代码：<br>
                    <code>[custom_card url="https://example.com"]</code> 或 <br>
                    <code>[custom_card_lazy url="https://example.com"]</code>
                </li>
                <li>保存并发布文章/页面</li>
                <li>访问该页面，卡片数据会自动创建并缓存</li>
                <li>在"卡片列表"页面即可看到卡片数据</li>
            </ol>

            <h3>功能说明</h3>
            <ul>
                <li>网站卡片会在用户通过短代码访问时自动创建</li>
                <li>点击统计会自动记录用户点击卡片的行为</li>
                <li>禁用状态的卡片不会在网站上显示</li>
                <li>删除卡片会同时删除相关的点击统计数据</li>
            </ul>

            <p>查看 <a href="<?php echo esc_url(admin_url('admin.php?page=wordpress-toolkit-about')); ?>">功能说明页面</a> 获取更详细的使用方法和示例。</p>
        </div>
        
        <div class="wordpress-toolkit-admin-section">
            <h2>缓存管理</h2>
            <p>当前缓存设置：</p>
            <ul>
                <li>数据库缓存：<?php echo $cache_expire_hours; ?> 小时</li>
                <li>Memcached：<?php echo $enable_memcached ? '已启用' : '已禁用'; ?></li>
                <li>OPcache：<?php echo $enable_opcache ? '已启用' : '已禁用'; ?></li>
            </ul>
            
            <button type="button" class="button button-secondary" id="clear-card-cache">清除所有缓存</button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 卡片列表选项卡 -->
    <?php if ($current_tab === 'cards'): ?>
    <div class="wordpress-toolkit-admin-section">
        <?php
        // 包含卡片列表页面
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Custom Card Admin Page: Including cards-list.php for cards tab');
        }
        include WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/custom-card/admin/cards-list.php';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Custom Card Admin Page: cards-list.php inclusion completed');
        }
        ?>
    </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#clear-card-cache').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('确定要清除所有网站卡片缓存吗？')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'clear_custom_card_cache',
                    nonce: '<?php echo wp_create_nonce('clear_custom_card_cache'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('缓存已清除');
                    } else {
                        alert('清除缓存失败：' + response.data);
                    }
                },
                error: function() {
                    alert('网络错误，请重试');
                }
            });
        }
    });
});
</script>

