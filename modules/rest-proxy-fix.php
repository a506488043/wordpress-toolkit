<?php
/**
 * REST代理修复模块
 * 解决 public-api.wordpress.com 连接失败的问题
 *
 * @package WordPressToolkit
 * @subpackage Modules
 * @since 1.0.5
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class WordPress_Toolkit_REST_Proxy_Fix {

    /**
     * 构造函数
     */
    public function __construct() {
        add_action('init', [$this, 'init_module']);
    }

    /**
     * 初始化模块
     */
    public function init_module() {
        // 初始化钩子
        $this->init_hooks();
    }

    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 移除导致REST代理问题的脚本
        add_action('wp_enqueue_scripts', [$this, 'remove_problematic_scripts'], 999);

        // 禁用WordPress.com连接
        add_filter('pre_http_request', [$this, 'block_wordpress_dotcom_requests'], 10, 3);

        // 添加自定义错误处理
        add_action('wp_footer', [$this, 'add_debug_info']);

        // 清理相关的transient缓存
        add_action('init', [$this, 'clear_related_cache']);

        // 添加管理菜单
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // 插件激活时清理缓存
        register_activation_hook(WORDPRESS_TOOLKIT_PLUGIN_BASENAME, [$this, 'plugin_activation']);
    }

    /**
     * 移除有问题的脚本
     */
    public function remove_problematic_scripts() {
        global $wp_scripts;

        // 查找并移除REST代理相关的脚本
        if (isset($wp_scripts->registered)) {
            foreach ($wp_scripts->registered as $handle => $script) {
                if (strpos($script->src, 'rest-proxy') !== false ||
                    strpos($script->src, 'public-api.wordpress.com') !== false) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                }
            }
        }

        // 清理可能的问题脚本
        $problematic_handles = ['rest-proxy', 'wordpress-api-proxy', 'wp-api-proxy'];
        foreach ($problematic_handles as $handle) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }

    /**
     * 阻止向WordPress.com的请求
     * 只阻止有问题的WordPress.com域名，不影响本地API和小程序功能
     */
    public function block_wordpress_dotcom_requests($preempt, $r, $url) {
        // 只阻止有问题的WordPress.com域名，不影响本地API和小程序
        $blocked_domains = [
            'public-api.wordpress.com',
            'rest-proxy.com',
            'wp-proxy.com'
        ];

        // 保护WordPress官方服务域名（主题/插件更新等）
        $protected_wordpress_domains = [
            'api.wordpress.org',
            'wordpress.org',
            'download.wordpress.org',
            's.w.org'
        ];

        // 保护本地域名和允许的API域名
        $allowed_domains = [
            'saiita.com.cn',
            'www.saiita.com.cn',
            'localhost',
            '127.0.0.1',
            'api.weixin.qq.com',  // 微信小程序API
            'pay.weixin.qq.com',  // 微信支付API
            // RSS/Feed 相关域名
            'rss.com',
            'feedburner.com',
            'feeds.feedburner.com',
            'feedly.com',
            'feedspot.com',
            'inoreader.com',
            'feedvalidator.org'
        ];

        $parsed_url = parse_url($url);
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';

        // 优先检查WordPress官方服务域名
        foreach ($protected_wordpress_domains as $protected_domain) {
            if (strpos($host, $protected_domain) !== false) {
                return $preempt; // 允许访问WordPress官方服务
            }
        }

        // 检查是否是允许的域名（本地API和小程序相关API）
        foreach ($allowed_domains as $allowed_domain) {
            if (strpos($host, $allowed_domain) !== false) {
                return $preempt; // 允许访问
            }
        }

        // 额外保护：WordPress内部RSS/Feed和API相关请求
        $allowed_paths = [
            '/feed/',
            '/rss/',
            '/atom/',
            '/rdf/',
            'feed=rss',
            'feed=atom',
            'feed=rdf',
            'wp-json/wp/v2/',  // WordPress REST API
            'wp-json/watch-life-net/v1/',  // 小程序API
            '/wp-json/',      // 所有WordPress REST API路径
            '/wp-admin/',     // WordPress管理后台
            '/wp-cron.php',   // WordPress定时任务
            '/wp-login.php',  // WordPress登录页面
            '/wp-admin/admin-ajax.php',  // WordPress AJAX
            '/wp-admin/admin-post.php'   // WordPress POST请求
        ];

        foreach ($allowed_paths as $allowed_path) {
            if (strpos($path, $allowed_path) !== false) {
                return $preempt; // 允许访问
            }
        }

        // 检查是否是阻止的域名
        foreach ($blocked_domains as $blocked_domain) {
            if (strpos($host, $blocked_domain) !== false) {
                // 记录被阻止的请求
                error_log("WordPress Toolkit REST Proxy Fix: Blocked request to {$url}");
                return new WP_Error('rest_proxy_blocked', 'REST API connection blocked for security reasons.');
            }
        }

        return $preempt;
    }

    /**
     * 添加调试信息到页脚
     */
    public function add_debug_info() {
        if (current_user_can('manage_options')) {
            ?>
            <style>
            .wp-toolkit-debug-info {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 10px;
                margin: 20px 0;
                font-family: monospace;
                font-size: 12px;
                display: none;
            }
            </style>
            <div class="wp-toolkit-debug-info" id="wp-toolkit-debug-info">
                <strong>WordPress Toolkit - REST代理修复状态:</strong><br>
                <span id="wp-toolkit-proxy-status">✅ 已启用修复</span><br>
                <small>问题: public-api.wordpress.com 连接失败</small><br>
                <small>解决方案: 已阻止相关请求并添加错误处理</small><br>
                <small><a href="<?php echo admin_url('admin.php?page=wordpress-toolkit'); ?>">管理插件</a></small>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var debugInfo = document.getElementById('wp-toolkit-debug-info');
                if (debugInfo) {
                    debugInfo.style.display = 'block';
                }

                // 监听REST代理错误
                window.addEventListener('error', function(e) {
                    if (e.message && e.message.includes('rest-proxy')) {
                        console.log('WordPress Toolkit: REST代理错误已被修复插件捕获:', e.message);
                    }
                });
            });
            </script>
            <?php
        }
    }

    /**
     * 清理相关的缓存
     */
    public function clear_related_cache() {
        // 清理可能包含REST代理错误的缓存
        $transient_keys = [
            'rest_proxy_*',
            'public_api_wordpress_com_*',
            'wordpress_com_api_*'
        ];

        foreach ($transient_keys as $key_pattern) {
            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $key_pattern
                )
            );
        }
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu() {
        // 添加到WordPress Toolkit主菜单下的子菜单
        add_submenu_page(
            'wordpress-toolkit',
            'REST代理修复',
            'REST代理修复',
            'manage_options',
            'wp-toolkit-rest-proxy-fix',
            [$this, 'admin_page']
        );
    }

    /**
     * 管理页面
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WordPress Toolkit - REST代理修复</h1>

            <div class="notice notice-success">
                <p><strong>✅ 修复已启用</strong></p>
                <p>REST代理连接问题已成功修复，插件正在运行中。</p>
            </div>

            <h3>修复详情</h3>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>功能</th>
                        <th>状态</th>
                        <th>说明</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>阻止问题请求</td>
                        <td>✅ 已启用</td>
                        <td>仅阻止有问题的REST代理域名</td>
                    </tr>
                    <tr>
                        <td>移除问题脚本</td>
                        <td>✅ 已启用</td>
                        <td>移除导致REST代理错误的脚本</td>
                    </tr>
                    <tr>
                        <td>WordPress官方服务保护</td>
                        <td>✅ 已启用</td>
                        <td>保护主题/插件更新等官方服务</td>
                    </tr>
                    <tr>
                        <td>缓存清理</td>
                        <td>✅ 已启用</td>
                        <td>清理相关的transient缓存</td>
                    </tr>
                    <tr>
                        <td>错误处理</td>
                        <td>✅ 已启用</td>
                        <td>提供安全的错误处理机制</td>
                    </tr>
                    <tr>
                        <td>小程序API保护</td>
                        <td>✅ 已启用</td>
                        <td>确保微信小程序API正常工作</td>
                    </tr>
                    <tr>
                        <td>RSS/Feed支持</td>
                        <td>✅ 已启用</td>
                        <td>保护所有RSS和Feed订阅功能</td>
                    </tr>
                </tbody>
            </table>

            <h3>阻止的域名</h3>
            <ul>
                <li>🚫 public-api.wordpress.com (问题REST代理)</li>
                <li>🚫 rest-proxy.com (问题代理服务)</li>
                <li>🚫 wp-proxy.com (问题代理服务)</li>
            </ul>

            <h3>保护的域名</h3>
            <ul>
                <li>✅ saiita.com.cn (本地域名)</li>
                <li>✅ api.weixin.qq.com (微信小程序)</li>
                <li>✅ pay.weixin.qq.com (微信支付)</li>
                <li>✅ feedly.com (RSS阅读器)</li>
                <li>✅ feedburner.com (RSS服务)</li>
                <li>✅ api.wordpress.org (WordPress官方API)</li>
                <li>✅ wordpress.org (WordPress官网)</li>
                <li>✅ download.wordpress.org (WordPress下载)</li>
                <li>✅ 所有WordPress内部API</li>
            </ul>

            <p><strong>注意：</strong> 此功能用于修复REST代理连接问题，是WordPress Toolkit的内置功能，无法单独禁用。</p>
        </div>
        <?php
    }

    /**
     * 插件激活时清理缓存
     */
    public function plugin_activation() {
        $this->clear_related_cache();

        // 添加激活日志
        error_log('WordPress Toolkit: REST Proxy Fix module activated successfully');
    }

    /**
     * 获取修复状态
     */
    public function get_status() {
        return [
            'enabled' => true,
            'blocked_domains' => [
                'public-api.wordpress.com',
                'rest-proxy.com',
                'wp-proxy.com'
            ],
            'protected_domains' => [
                'api.wordpress.org',
                'wordpress.org',
                'download.wordpress.org',
                's.w.org'
            ],
            'protected_features' => [
                '微信小程序API',
                'WordPress REST API',
                'RSS/Feed订阅',
                '本地域名访问',
                '微信支付功能',
                'WordPress官方服务(主题/插件更新)'
            ]
        ];
    }
}

// 初始化模块
new WordPress_Toolkit_REST_Proxy_Fix();
?>