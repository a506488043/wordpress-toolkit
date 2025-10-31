<?php
/**
 * Plugin Name: WordPress Toolkit
 * Plugin URI: https://www.saiita.com.cn
 * Description: 一个集成了网站卡片、年龄计算器、物品管理、友情链接、Cookie同意通知和REST代理修复的综合工具包。
 * Version: 1.0.5
 * Author: www.saiita.com.cn
 * Author URI: https://www.saiita.com.cn
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wordpress-toolkit
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('WORDPRESS_TOOLKIT_VERSION', '1.0.5');
define('WORDPRESS_TOOLKIT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WORDPRESS_TOOLKIT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WORDPRESS_TOOLKIT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// 国际化支持已移除 - 直接使用WordPress原生翻译函数

// 加载日志管理
require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'includes/class-logger.php';

// 加载管理页面模板系统
require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'includes/class-admin-page-template.php';

// 加载REST代理修复模块
require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/rest-proxy-fix.php';

/**
 * WordPress Toolkit 主类
 */
class WordPress_Toolkit {
    
    /**
     * 单例实例
     */
    private static $instance = null;
    
    /**
     * 子模块实例
     */
    private $custom_card = null;
    private $age_calculator = null;
    private $time_capsule = null;
    private $cookieguard = null;
    private $simple_friendlink = null;
    private $simple_friendlink_admin = null;
    
    /**
     * 获取单例实例
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 构造函数
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_modules();
    }
    
    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 插件激活和停用钩子
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // WordPress初始化钩子
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // 插件链接
        add_filter('plugin_action_links_' . WORDPRESS_TOOLKIT_PLUGIN_BASENAME, array($this, 'add_plugin_links'));
    }
    
    /**
     * 加载子模块
     */
    private function load_modules() {
        // 加载Custom Card模块
        require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/custom-card/custom-card-module.php';
        $this->custom_card = new Custom_Card_Module();
        
        // 加载Age Calculator模块
        require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/age-calculator/age-calculator-module.php';
        $this->age_calculator = new Age_Calculator_Module();
        
        // 加载Time Capsule模块
        require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/time-capsule/time-capsule-module.php';
        $this->time_capsule = new Time_Capsule_Module();
        
        // 加载CookieGuard模块
        require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/cookieguard/cookieguard-module.php';
        $this->cookieguard = CookieGuard_Module::get_instance();

        // 加载Simple FriendLink模块
        require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/simple-friendlink/simple-friendlink-module.php';
        $this->simple_friendlink = Simple_FriendLink_Module::get_instance();

        // 加载Simple FriendLink管理页面
        require_once WORDPRESS_TOOLKIT_PLUGIN_PATH . 'modules/simple-friendlink/admin.php';
        $this->simple_friendlink_admin = new Simple_FriendLink_Admin();

        // 调试日志
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WordPress Toolkit: Modules loaded - Custom Card: ' . ($this->custom_card ? 'Yes' : 'No'));
        }
    }
    
    /**
     * 插件激活
     */
    public function activate() {
        // 先加载模块
        $this->load_modules();
        
        // 激活所有子模块
        if ($this->custom_card) $this->custom_card->activate();
        if ($this->age_calculator) $this->age_calculator->activate();
        if ($this->time_capsule) $this->time_capsule->activate();
        if ($this->cookieguard) $this->cookieguard->activate();
        if ($this->simple_friendlink) $this->simple_friendlink->activate();
        
        // 设置插件激活时间
        add_option('wordpress_toolkit_activated_time', current_time('timestamp'));
    }
    
    /**
     * 插件停用
     */
    public function deactivate() {
        // 停用所有子模块
        if ($this->custom_card) $this->custom_card->deactivate();
        if ($this->age_calculator) $this->age_calculator->deactivate();
        if ($this->time_capsule) $this->time_capsule->deactivate();
        if ($this->cookieguard) $this->cookieguard->deactivate();
        if ($this->simple_friendlink) $this->simple_friendlink->deactivate();
    }
    
    /**
     * 初始化
     */
    public function init() {
        // 加载文本域
        load_plugin_textdomain('wordpress-toolkit', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // 初始化所有子模块
        if ($this->custom_card) $this->custom_card->init();
        if ($this->age_calculator) $this->age_calculator->init();
        if ($this->time_capsule) $this->time_capsule->init();
        if ($this->cookieguard) $this->cookieguard->init();
        if ($this->simple_friendlink) $this->simple_friendlink->init();
    }
    
    /**
     * 添加管理菜单 - 重新组织结构
     */
    public function add_admin_menu() {
        // ======================
        // 工具箱菜单 - 数据查看和操作
        // ======================

        // 添加主菜单 - 使用较低权限让订阅者也能看到
        add_menu_page(
            'WordPress Toolkit',
            __('工具箱', 'wordpress-toolkit'),
            'read', // 使用基础阅读权限，所有登录用户都有
            'wordpress-toolkit',
            array($this, 'admin_page'),
            'dashicons-admin-tools',
            30
        );

        // 网站卡片（仅管理员可见）
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'wordpress-toolkit',
                __('网站卡片', 'wordpress-toolkit'),
                __('网站卡片', 'wordpress-toolkit'),
                'manage_options',
                'wordpress-toolkit-cards-list',
                array($this, 'custom_cards_list_page')
            );
        }

        // 物品管理（订阅者和管理员都可见）
        add_submenu_page(
            'wordpress-toolkit',
            __('物品管理', 'wordpress-toolkit'),
            __('物品管理', 'wordpress-toolkit'),
            'read', // 使用基础阅读权限
            'wordpress-toolkit-time-capsule',
            array($this, 'time_capsule_admin_page')
        );

        // 友情链接（仅管理员可见）
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'wordpress-toolkit',
                __('友情链接', 'wordpress-toolkit'),
                __('友情链接', 'wordpress-toolkit'),
                'manage_options',
                'wordpress-toolkit-friendlinks',
                array($this, 'friendlinks_admin_page')
            );
        }


        // ======================
        // 设置菜单 - 插件配置
        // ======================

        // 网站卡片设置
        add_options_page(
            __('网站卡片设置', 'wordpress-toolkit'),
            __('网站卡片', 'wordpress-toolkit'),
            'manage_options',
            'wordpress-toolkit-custom-card-settings',
            array($this, 'custom_card_settings_page')
        );

        // 年龄计算器设置
        add_options_page(
            __('年龄计算器设置', 'wordpress-toolkit'),
            __('年龄计算器', 'wordpress-toolkit'),
            'manage_options',
            'wordpress-toolkit-age-calculator-settings',
            array($this, 'age_calculator_settings_page')
        );

        // Cookie同意设置
        add_options_page(
            __('Cookie同意设置', 'wordpress-toolkit'),
            __('Cookie同意', 'wordpress-toolkit'),
            'manage_options',
            'wordpress-toolkit-cookieguard-settings',
            array($this, 'cookieguard_settings_page')
        );

        // 简洁友情链接设置
        add_options_page(
            __('简洁友情链接', 'wordpress-toolkit'),
            __('简洁友情链接', 'wordpress-toolkit'),
            'manage_options',
            'wordpress-toolkit-simple-friendlink-settings',
            array($this, 'simple_friendlink_settings_page')
        );


            }
    
    /**
     * 加载管理后台脚本和样式
     */
    public function admin_enqueue_scripts($hook) {
        // 只在插件相关页面加载统一样式和脚本
        if (strpos($hook, 'wordpress-toolkit') !== false || strpos($hook, 'options-general') !== false) {
            // 加载统一CSS变量
            wp_enqueue_style(
                'toolkit-variables',
                WORDPRESS_TOOLKIT_PLUGIN_URL . 'assets/css/variables.css',
                array(),
                WORDPRESS_TOOLKIT_VERSION
            );

            // 加载通用样式
            wp_enqueue_style(
                'toolkit-common',
                WORDPRESS_TOOLKIT_PLUGIN_URL . 'assets/css/common.css',
                array('toolkit-variables'),
                WORDPRESS_TOOLKIT_VERSION
            );

            // 加载核心JavaScript框架
            wp_enqueue_script(
                'toolkit-core',
                WORDPRESS_TOOLKIT_PLUGIN_URL . 'assets/js/toolkit-core.js',
                array('jquery'),
                WORDPRESS_TOOLKIT_VERSION,
                true
            );

            // 加载迁移助手
            wp_enqueue_script(
                'toolkit-migration',
                WORDPRESS_TOOLKIT_PLUGIN_URL . 'assets/js/migration-helper.js',
                array('jquery', 'toolkit-core'),
                WORDPRESS_TOOLKIT_VERSION,
                true
            );

            // 传递配置到JavaScript
            wp_localize_script('toolkit-core', 'ToolkitConfig', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('toolkit_nonce'),
                'strings' => array(
                    'saveSuccess' => __('保存成功！', 'wordpress-toolkit'),
                    'saveError' => __('保存失败，请重试。', 'wordpress-toolkit'),
                    'networkError' => __('网络错误，请重试。', 'wordpress-toolkit'),
                    'confirmDelete' => __('确定要删除这个项目吗？此操作不可撤销。', 'wordpress-toolkit'),
                    'deleteSuccess' => __('删除成功！', 'wordpress-toolkit'),
                    'deleteError' => __('删除失败，请重试。', 'wordpress-toolkit'),
                    'loading' => __('加载中...', 'wordpress-toolkit'),
                    'processing' => __('处理中...', 'wordpress-toolkit'),
                    'confirm' => __('确定', 'wordpress-toolkit'),
                    'cancel' => __('取消', 'wordpress-toolkit')
                )
            ));
        }

        // 加载子模块的资源（已重构，主要加载模块特定资源）
        if ($this->custom_card) $this->custom_card->admin_enqueue_scripts($hook);
        if ($this->age_calculator) $this->age_calculator->admin_enqueue_scripts($hook);
        if ($this->time_capsule) $this->time_capsule->admin_enqueue_scripts($hook);
        if ($this->cookieguard) $this->cookieguard->admin_enqueue_scripts($hook);
        // Simple_FriendLink_Module 不需要特殊的管理页面资源加载
    }
    
    /**
     * 加载前端脚本和样式
     */
    public function enqueue_scripts() {
        // 加载子模块的前端资源
        if ($this->custom_card) $this->custom_card->enqueue_scripts();
        if ($this->age_calculator) $this->age_calculator->enqueue_scripts();
        if ($this->time_capsule) $this->time_capsule->enqueue_scripts();
        if ($this->cookieguard) $this->cookieguard->enqueue_scripts();
        if ($this->simple_friendlink) $this->simple_friendlink->enqueue_scripts();
    }
    
    /**
     * 主管理页面 - 安全版本（简化版）
     */
    public function admin_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }

        // 显示工具箱主页面，包含功能说明
        $this->toolbox_about_page();
    }
    
    /**
     * 网站卡片设置页面 - 放在设置菜单中
     */
    public function custom_card_settings_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }

        // 验证nonce（防止CSRF攻击）
        if (isset($_POST['action']) && !wp_verify_nonce($_POST['_wpnonce'], 'wordpress_toolkit_custom_card')) {
            wp_die(__('安全验证失败', 'wordpress-toolkit'));
        }

        // 调试日志
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WordPress Toolkit: Custom Card settings page called');
        }

        if ($this->custom_card) {
            // 调用自定义卡片模块的设置页面（只显示设置选项卡）
            $this->custom_card->settings_page();
        } else {
            echo '<div class="wrap"><h1>网站卡片设置</h1><div class="error"><p>Custom Card 模块未正确加载，请检查插件设置。</p></div></div>';
        }
    }

    /**
     * 年龄计算器设置页面 - 放在设置菜单中
     */
    public function age_calculator_settings_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }

        // 验证nonce（防止CSRF攻击）
        if (isset($_POST['action']) && !wp_verify_nonce($_POST['_wpnonce'], 'wordpress_toolkit_age_calculator')) {
            wp_die(__('安全验证失败', 'wordpress-toolkit'));
        }

        if ($this->age_calculator) {
            // 调用年龄计算器模块的设置页面
            $this->age_calculator->settings_page();
        } else {
            echo '<div class="wrap"><h1>年龄计算器设置</h1><div class="error"><p>Age Calculator 模块未正确加载，请检查插件设置。</p></div></div>';
        }
    }

    /**
     * 物品管理设置页面 - 放在设置菜单中
     */
    public function time_capsule_settings_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }

        // 验证nonce（防止CSRF攻击）
        if (isset($_POST['action']) && !wp_verify_nonce($_POST['_wpnonce'], 'wordpress_toolkit_time_capsule')) {
            wp_die(__('安全验证失败', 'wordpress-toolkit'));
        }

        if ($this->time_capsule) {
            // 调用时间胶囊模块的设置页面
            $this->time_capsule->settings_page();
        } else {
            echo '<div class="wrap"><h1>物品管理设置</h1><div class="error"><p>Time Capsule 模块未正确加载，请检查插件设置。</p></div></div>';
        }
    }

    /**
     * Cookie同意设置页面 - 放在设置菜单中
     */
    public function cookieguard_settings_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }

        // 验证nonce（防止CSRF攻击）
        if (isset($_POST['action']) && !wp_verify_nonce($_POST['_wpnonce'], 'wordpress_toolkit_cookieguard')) {
            wp_die(__('安全验证失败', 'wordpress-toolkit'));
        }

        // 处理表单提交
        if (isset($_POST['action']) && $_POST['action'] === 'save_cookieguard_settings') {
            $this->save_cookieguard_settings();
        }

        // 获取设置
        $settings = get_option('wordpress_toolkit_cookieguard_settings', array(
            'cookie_types' => array(),
            'theme' => 'light',
            'position' => 'bottom',
            'learn_more_url' => '',
            'privacy_policy_url' => '',
            'consent_expiry_days' => 365
        ));

        // 添加设置页面样式
        wp_enqueue_style('cookieguard-admin', plugins_url('assets/cookieguard-admin.css', __FILE__));
        ?>
        <div class="wrap">
            <h1><?php _e('Cookie同意设置', 'wordpress-toolkit'); ?></h1>

            <form method="post" action="">
                <input type="hidden" name="action" value="save_cookieguard_settings">
                <?php wp_nonce_field('wordpress_toolkit_cookieguard'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Cookie类型', 'wordpress-toolkit'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookie_types[]" value="necessary" checked disabled>
                                <?php _e('必要Cookie', 'wordpress-toolkit'); ?> (<?php _e('始终启用', 'wordpress-toolkit'); ?>)
                            </label><br>
                            <label>
                                <input type="checkbox" name="cookie_types[]" value="functional" <?php echo in_array('functional', $settings['cookie_types']) ? 'checked' : ''; ?>>
                                <?php _e('功能性Cookie', 'wordpress-toolkit'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="cookie_types[]" value="analytics" <?php echo in_array('analytics', $settings['cookie_types']) ? 'checked' : ''; ?>>
                                <?php _e('分析性Cookie', 'wordpress-toolkit'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="cookie_types[]" value="marketing" <?php echo in_array('marketing', $settings['cookie_types']) ? 'checked' : ''; ?>>
                                <?php _e('营销性Cookie', 'wordpress-toolkit'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('主题', 'wordpress-toolkit'); ?></th>
                        <td>
                            <select name="theme">
                                <option value="light" <?php selected($settings['theme'], 'light'); ?>><?php _e('浅色', 'wordpress-toolkit'); ?></option>
                                <option value="dark" <?php selected($settings['theme'], 'dark'); ?>><?php _e('深色', 'wordpress-toolkit'); ?></option>
                                <option value="auto" <?php selected($settings['theme'], 'auto'); ?>><?php _e('自动', 'wordpress-toolkit'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('位置', 'wordpress-toolkit'); ?></th>
                        <td>
                            <select name="position">
                                <option value="bottom" <?php selected($settings['position'], 'bottom'); ?>><?php _e('底部', 'wordpress-toolkit'); ?></option>
                                <option value="top" <?php selected($settings['position'], 'top'); ?>><?php _e('顶部', 'wordpress-toolkit'); ?></option>
                                <option value="center" <?php selected($settings['position'], 'center'); ?>><?php _e('居中', 'wordpress-toolkit'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('了解更多链接', 'wordpress-toolkit'); ?></th>
                        <td>
                            <input type="url" name="learn_more_url" value="<?php echo esc_url($settings['learn_more_url']); ?>" class="regular-text">
                            <p class="description"><?php _e('Cookie使用说明页面链接', 'wordpress-toolkit'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('隐私政策链接', 'wordpress-toolkit'); ?></th>
                        <td>
                            <input type="url" name="privacy_policy_url" value="<?php echo esc_url($settings['privacy_policy_url']); ?>" class="regular-text">
                            <p class="description"><?php _e('隐私政策页面链接', 'wordpress-toolkit'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('同意有效期', 'wordpress-toolkit'); ?></th>
                        <td>
                            <input type="number" name="consent_expiry_days" value="<?php echo $settings['consent_expiry_days']; ?>" min="1" max="3650" step="1">
                            <p class="description"><?php _e('用户Cookie同意记录的有效天数（默认：365天）', 'wordpress-toolkit'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="save_settings" class="button button-primary" value="<?php _e('保存设置', 'wordpress-toolkit'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * 保存CookieGuard设置
     */
    private function save_cookieguard_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }

        $cookie_types = isset($_POST['cookie_types']) ? array_map('sanitize_text_field', $_POST['cookie_types']) : array();

        $settings = array(
            'cookie_types' => $cookie_types,
            'theme' => sanitize_text_field($_POST['theme']),
            'position' => sanitize_text_field($_POST['position']),
            'learn_more_url' => esc_url_raw($_POST['learn_more_url']),
            'privacy_policy_url' => esc_url_raw($_POST['privacy_policy_url']),
            'consent_expiry_days' => intval($_POST['consent_expiry_days'])
        );

        update_option('wordpress_toolkit_cookieguard_settings', $settings);

        // 显示成功消息
        add_settings_error('wordpress_toolkit_cookieguard_settings', 'settings_saved', __('设置已保存', 'wordpress-toolkit'), 'updated');
        set_transient('settings_errors', get_settings_errors(), 30);
    }

    /**
     * 简洁友情链接设置页面
     */
    public function simple_friendlink_settings_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }

        // 保存设置
        if (isset($_POST['save_settings'])) {
            $settings = array(
                'allow_user_submit' => isset($_POST['allow_user_submit']),
                'require_login' => isset($_POST['require_login']),
                'admin_approval' => isset($_POST['admin_approval']),
                'max_links_per_page' => intval($_POST['max_links_per_page'])
            );

            if (class_exists('Simple_FriendLink_Module')) {
                $friendlink_module = Simple_FriendLink_Module::get_instance();
                $friendlink_module->save_settings($settings);
                echo '<div class="notice notice-success is-dismissible"><p>' . __('设置保存成功！', 'wordpress-toolkit') . '</p></div>';
            }
        }

        // 获取当前设置
        if (class_exists('Simple_FriendLink_Module')) {
            $friendlink_module = Simple_FriendLink_Module::get_instance();
            $settings = $friendlink_module->get_settings();
        } else {
            $settings = array(
                'allow_user_submit' => true,
                'require_login' => true,
                'admin_approval' => false,
                'max_links_per_page' => 30
            );
        }

        // 显示设置表单
        ?>
        <div class="wrap">
            <h1><?php echo __('简洁友情链接设置', 'wordpress-toolkit'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('wordpress_toolkit_simple_friendlink'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('用户提交', 'wordpress-toolkit'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="allow_user_submit" value="1" <?php checked($settings['allow_user_submit']); ?>>
                                <?php _e('允许用户提交友情链接', 'wordpress-toolkit'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('需要登录', 'wordpress-toolkit'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_login" value="1" <?php checked($settings['require_login']); ?>>
                                <?php _e('用户必须登录才能提交', 'wordpress-toolkit'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('管理员审核', 'wordpress-toolkit'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="admin_approval" value="1" <?php checked($settings['admin_approval']); ?>>
                                <?php _e('用户提交的链接需要管理员审核', 'wordpress-toolkit'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('每页显示数量', 'wordpress-toolkit'); ?></th>
                        <td>
                            <input type="number" name="max_links_per_page" value="<?php echo $settings['max_links_per_page']; ?>" min="5" max="50" step="5">
                            <p class="description"><?php _e('友情链接页面每页显示的链接数量（默认：30）', 'wordpress-toolkit'); ?></p>
                        </td>
                    </tr>

                                    </table>

                <p class="submit">
                    <input type="submit" name="save_settings" class="button button-primary" value="<?php _e('保存设置', 'wordpress-toolkit'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

/**
     * 网站卡片页面 - 放在工具箱菜单中
     */
    public function custom_cards_list_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }

        // 验证nonce（防止CSRF攻击）
        if (isset($_POST['action']) && !wp_verify_nonce($_POST['_wpnonce'], 'wordpress_toolkit_custom_card')) {
            wp_die(__('安全验证失败', 'wordpress-toolkit'));
        }

        // 调试日志
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WordPress Toolkit: Custom Cards list page called');
        }

        if ($this->custom_card) {
            // 调用自定义卡片模块的卡片列表页面
            $this->custom_card->cards_list_page();
        } else {
            echo '<div class="wrap"><div class="error"><p>Custom Card 模块未正确加载，请检查插件设置。</p></div></div>';
        }
    }
    
    /**
     * Age Calculator管理页面 - 安全版本
     */
    public function age_calculator_admin_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }
        
        // 验证nonce（防止CSRF攻击）
        if (isset($_POST['action']) && !wp_verify_nonce($_POST['_wpnonce'], 'wordpress_toolkit_age_calculator')) {
            wp_die(__('安全验证失败', 'wordpress-toolkit'));
        }
        
        if ($this->age_calculator) {
            $this->age_calculator->admin_page();
        }
    }
    
    /**
     * Time Capsule管理页面 - 安全版本
     */
    public function time_capsule_admin_page() {
        // 验证用户权限 - 允许管理员和订阅者访问
        if (!current_user_can('manage_options') && !current_user_can('read')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }

        // 验证nonce（防止CSRF攻击）- 只在有POST数据时验证
        if (!empty($_POST) && isset($_POST['action']) && !wp_verify_nonce($_POST['_wpnonce'], 'wordpress_toolkit_time_capsule')) {
            wp_die(__('安全验证失败', 'wordpress-toolkit'));
        }

        if ($this->time_capsule) {
            $this->time_capsule->admin_page();
        }
    }

    /**
     * 友情链接管理页面 - 统一管理页面
     */
    public function friendlinks_admin_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }

        // 简化验证 - 将nonce验证移到具体的操作处理函数中
        // 避免在页面加载时进行验证，防止误报

        if ($this->simple_friendlink_admin) {
            // 调用友情链接管理页面的统一视图
            $this->simple_friendlink_admin->unified_admin_page();
        } else {
            echo '<div class="wrap"><div class="error"><p>' . __('友情链接管理模块未正确加载', 'wordpress-toolkit') . '</p>';
            echo '<br><strong>调试信息:</strong><br>';
            echo 'simple_friendlink_admin: ' . ($this->simple_friendlink_admin ? '已加载' : '未加载') . '<br>';
            echo 'simple_friendlink: ' . ($this->simple_friendlink ? '已加载' : '未加载') . '<br>';
            echo 'WordPress工具包版本: ' . WORDPRESS_TOOLKIT_VERSION . '<br>';
            echo '</p></div></div>';
        }
    }

    /**
     * CookieGuard管理页面 - 安全版本
     */
    public function cookieguard_admin_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }
        
        // 验证nonce（防止CSRF攻击）
        if (isset($_POST['action']) && !wp_verify_nonce($_POST['_wpnonce'], 'wordpress_toolkit_cookieguard')) {
            wp_die(__('安全验证失败', 'wordpress-toolkit'));
        }
        
        if ($this->cookieguard) {
            $this->cookieguard->admin_page();
        }
    }
    
    
    /**
     * 功能说明页面 - 统一的功能说明
     */
    public function toolbox_about_page() {
        // 验证用户权限
        if (!current_user_can('manage_options')) {
            wp_die(__('权限不足', 'wordpress-toolkit'));
        }
        ?>
        <div class="wrap">
            <h1>WordPress Toolkit - 功能说明</h1>
            <div class="wordpress-toolkit-about">

                <div class="quick-links">
                    <h3>快速导航</h3>
                    <div class="quick-links-grid">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wordpress-toolkit-cards-list')); ?>" class="quick-link">
                            <span class="dashicons dashicons-admin-links"></span>
                            <span>网站卡片</span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wordpress-toolkit-time-capsule')); ?>" class="quick-link">
                            <span class="dashicons dashicons-archive"></span>
                            <span>物品管理</span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wordpress-toolkit-friendlinks')); ?>" class="quick-link">
                            <span class="dashicons dashicons-networking"></span>
                            <span>友情链接管理</span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('options-general.php?page=wordpress-toolkit-custom-card-settings')); ?>" class="quick-link">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <span>网站卡片设置</span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('options-general.php?page=wordpress-toolkit-age-calculator-settings')); ?>" class="quick-link">
                            <span class="dashicons dashicons-calendar"></span>
                            <span>年龄计算器设置</span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('options-general.php?page=wordpress-toolkit-cookieguard-settings')); ?>" class="quick-link">
                            <span class="dashicons dashicons-shield"></span>
                            <span>Cookie同意设置</span>
                        </a>
                    </div>
                </div>

                <div class="about-section">
                    <h2>网站卡片模块</h2>
                    <div class="feature-card">
                        <h3>主要功能</h3>
                        <ul>
                            <li>自动抓取网站元数据（标题、描述、图片）</li>
                            <li>生成美观的网站卡片展示</li>
                            <li>支持懒加载和即时加载两种模式</li>
                            <li>多级缓存支持（数据库、Memcached、Opcache）</li>
                            <li>Gutenberg区块编辑器支持</li>
                        </ul>
                        
                        <h3>使用方法</h3>
                        <p>使用短代码 <code>[custom_card url="https://example.com"]</code> 或 <code>[custom_card_lazy url="https://example.com"]</code></p>
                        <p>在Gutenberg编辑器中搜索"Custom Card"区块</p>
                    </div>
                </div>
                
                <div class="about-section">
                    <h2>年龄计算器模块</h2>
                    <div class="feature-card">
                        <h3>主要功能</h3>
                        <ul>
                            <li>精确计算周岁年龄，考虑闰年2月29日</li>
                            <li>支持多种显示格式（年、月、天、详细）</li>
                            <li>自动计算和手动计算两种模式</li>
                            <li>支持自定义页面模板</li>
                            <li>用户生日记忆功能（登录用户）</li>
                        </ul>
                        
                        <h3>使用方法</h3>
                        <p>使用短代码 <code>[manus_age_calculator]</code> 显示计算器表单</p>
                        <p>使用短代码 <code>[manus_age_calculator_form]</code> 仅显示表单</p>
                        <p>使用页面模板"年龄计算器页面"创建专用页面</p>
                    </div>
                </div>
                
                <div class="about-section">
                    <h2>物品管理模块</h2>
                    <div class="feature-card">
                        <h3>主要功能</h3>
                        <ul>
                            <li>记录和管理个人物品购买信息</li>
                            <li>追踪物品使用情况和保修状态</li>
                            <li>分类管理物品（电子产品、家居用品、服装等）</li>
                            <li>保修到期提醒功能</li>
                            <li>数据统计和分析</li>
                        </ul>
                        
                        <h3>使用方法</h3>
                        <p>使用短代码 <code>[time_capsule]</code> 显示物品列表和添加表单</p>
                        <p>使用短代码 <code>[time_capsule_item id="123"]</code> 显示单个物品详情</p>
                        <p>使用页面模板"物品管理页面"创建专用页面</p>
                    </div>
                </div>
                
                <div class="about-section">
                    <h2>Cookie同意模块</h2>
                    <div class="feature-card">
                        <h3>主要功能</h3>
                        <ul>
                            <li>符合GDPR要求的Cookie同意通知</li>
                            <li>苹果风格设计，美观易用</li>
                            <li>多语言支持</li>
                            <li>自定义样式和文案</li>
                            <li>用户偏好记忆</li>
                        </ul>
                        
                        <h3>使用方法</h3>
                        <p>模块自动启用，无需短代码</p>
                        <p>在后台设置中配置Cookie通知样式和内容</p>
                        <p>支持自定义CSS样式覆盖</p>
                    </div>
                </div>

                <div class="about-section">
                    <h2>友情链接模块</h2>
                    <div class="feature-card">
                        <h3>主要功能</h3>
                        <ul>
                            <li>完整的友情链接管理系统</li>
                            <li>支持链接分类和状态管理</li>
                            <li>用户提交友情链接功能</li>
                            <li>管理员审核机制（统一管理界面）</li>
                            <li>响应式网格布局展示</li>
                            <li>支持网站Logo和描述</li>
                            <li>搜索和分页功能</li>
                            <li>专用页面模板</li>
                            <li>AJAX表单提交</li>
                        </ul>

                        <h3>后台管理</h3>
                        <p>管理员可在"工具箱" → "友情链接管理"中统一管理所有友情链接</p>
                        <p>管理页面包含"已发布链接"和"待审核申请"两个标签页</p>
                        <p>支持批量操作、单个审核、删除等功能</p>

                        <h3>前端显示</h3>
                        <p>使用页面模板"友情链接页面"或"简洁友情链接页面"创建专用页面</p>
                        <p>页面将自动包含完整的友情链接展示和提交功能</p>
                    </div>
                </div>

                <div class="about-section">
                    <h2>通用功能</h2>
                    <div class="feature-card">
                        <h3>所有模块共享的功能</h3>
                        <ul>
                            <li>响应式设计，支持移动端</li>
                            <li>多语言支持（国际化）</li>
                            <li>权限控制，确保安全性</li>
                            <li>详细的错误处理和日志记录</li>
                            <li>定期更新和维护</li>
                        </ul>
                    </div>
                </div>
                
            </div>
        </div>
        
        <style>
        .wordpress-toolkit-about {
            max-width: 100%;
            box-sizing: border-box;
            width: 100%;
        }
        
        .about-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            box-sizing: border-box;
        }
        
        .about-section h2 {
            color: #2271b1;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
            margin-top: 0;
        }
        
        .feature-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #2271b1;
            width: 100%;
            box-sizing: border-box;
        }
        
        .feature-card h3 {
            color: #2c3338;
            margin-top: 0;
        }
        
        .feature-card ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .feature-card li {
            margin-bottom: 5px;
            line-height: 1.5;
        }
        
        .feature-card code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        
        /* 响应式设计 */
        @media screen and (max-width: 782px) {
            .about-section {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .feature-card {
                padding: 15px;
            }
            
            .feature-card h3 {
                font-size: 16px;
            }
        }
        </style>
        <?php
    }
    
    /**
     * 添加插件操作链接
     */
    public function add_plugin_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wordpress-toolkit') . '">设置</a>';
        $about_link = '<a href="' . admin_url('admin.php?page=wordpress-toolkit-about') . '">功能说明</a>';
        array_unshift($links, $about_link, $settings_link);
        return $links;
    }
}

// 初始化插件
WordPress_Toolkit::get_instance();
