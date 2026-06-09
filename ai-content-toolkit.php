<?php
/**
 * Plugin Name: Saiita AI Content Toolkit
 * Plugin URI: https://www.saiita.com.cn
 * Description: 集成WordPress 6.9 Abilities API的智能AI内容工具包，支持DeepSeek、硅基流动等多个AI提供商，提供AI文章优化、SEO分析等8+功能。
 * Version: 2.0.23
 * Author: chenghoufeng
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: saiita-ai-content-toolkit
 * Domain Path: /languages
 * Requires at least: 5.3
 * Tested up to: 7.0
 * Requires PHP: 7.4
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('SAIITA_AI_CONTENT_TOOLKIT_VERSION', '2.0.23');
define('SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * 记录缺失的文件警告（仅调试模式）
 */
function saiita_ai_toolkit_missing_file_warning($file) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Saiita AI Content Toolkit] Missing required file: ' . $file); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}

// 加载基础设施类
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-logger.php';
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/trait-singleton.php';
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/abstract-class-module-base.php';
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-security-validator.php';
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-cache-manager.php';
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-database-manager.php';
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-utilities.php';
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-rate-limiter.php';
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-license-manager.php';
// 加载AI系统
if (file_exists(SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-ai-manager.php')) {
    require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-ai-manager.php';
} else {
    saiita_ai_toolkit_missing_file_warning('includes/class-ai-manager.php');
}

if (file_exists(SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/ai-providers/class-provider-base.php')) {
    require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/ai-providers/class-provider-base.php';
    require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/ai-providers/class-deepseek-provider.php';
    require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/ai-providers/class-openai-provider.php';
    require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/ai-providers/class-siliconflow-provider.php';
} else {
    saiita_ai_toolkit_missing_file_warning('includes/ai-providers/class-provider-base.php');
}

if (file_exists(SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-backward-compatibility.php')) {
    require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-backward-compatibility.php';
} else {
    saiita_ai_toolkit_missing_file_warning('includes/class-backward-compatibility.php');
}

if (
    file_exists(SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-abilities-api-integration.php') &&
    version_compare(get_bloginfo('version'), '6.9', '>=')
) {
    require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-abilities-api-integration.php';
}

// WordPress AI Client 集成已提取至 includes/class-wp-ai-integration.php
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-wp-ai-integration.php';
Saiita_AI_Toolkit_WP_AI_Integration::get_instance()->register();

if (file_exists(SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'admin/class-ai-provider-manager.php')) {
    require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'admin/class-ai-provider-manager.php';
} else {
    saiita_ai_toolkit_missing_file_warning('admin/class-ai-provider-manager.php');
}

if (file_exists(SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/auto-excerpt/ai-enhanced-excerpt-generator.php')) {
    require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/auto-excerpt/ai-enhanced-excerpt-generator.php';
} else {
    saiita_ai_toolkit_missing_file_warning('modules/auto-excerpt/ai-enhanced-excerpt-generator.php');
}

// 加载拆分后的类
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-plugin-loader.php';
require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'includes/class-admin-menu.php';

/**
 * Saiita AI Content Toolkit 主类
 */
class Saiita_AI_Toolkit
{
    private static $instance = null;
    private $loader = null;
    private $admin_menu = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->loader = new Saiita_AI_Toolkit_Plugin_Loader();
        $this->admin_menu = new Saiita_AI_Toolkit_Admin_Menu($this->loader);
        $this->init_hooks();
        $this->loader->load_modules();
    }

    private function init_hooks()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this->admin_menu, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this->admin_menu, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this->loader, 'enqueue_scripts'));
        add_filter('plugin_action_links_' . SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_BASENAME, array($this->admin_menu, 'add_plugin_links'));
    }

    public function activate()
    {
        $this->loader->load_modules();
        $this->loader->activate();
        add_option('saiita_ai_content_toolkit_activated_time', current_time('U'));
    }

    public function deactivate()
    {
        $this->loader->deactivate();
    }

    public function init()
    {
        load_plugin_textdomain('saiita-ai-content-toolkit', false, dirname(SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_BASENAME) . '/languages'); // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
        $this->loader->init();
    }
}

Saiita_AI_Toolkit::get_instance();

/**
 * v2.0 便捷函数接口
 */
if (!function_exists('ai_content_toolkit_generate_v2')) {
    function ai_content_toolkit_generate_v2($prompt, $options = array())  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    {
        if (class_exists('Saiita_AI_Toolkit_AI_Manager')) {
            $ai_manager = Saiita_AI_Toolkit_AI_Manager::get_instance();
            $result = $ai_manager->generate_content($prompt, $options);
            if (is_wp_error($result)) {
                return $result;
            }
            return $result['content'] ?? '';
        }
        return new WP_Error('ai_not_available', 'AI管理器不可用');
    }
}

if (!function_exists('ai_content_toolkit_generate_excerpt_v2')) {
    function ai_content_toolkit_generate_excerpt_v2($content, $options = array())  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    {
        if (class_exists('Saiita_AI_Enhanced_Excerpt_Generator')) {
            $excerpt_generator = Saiita_AI_Enhanced_Excerpt_Generator::get_instance();
            return $excerpt_generator->generate_smart_excerpt($content, $options);
        }
        return new WP_Error('excerpt_not_available', '摘要生成器不可用');
    }
}

if (!function_exists('ai_content_toolkit_analyze_seo_v2')) {
    function ai_content_toolkit_analyze_seo_v2($content, $options = array())  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    {
        if (class_exists('Saiita_AI_Toolkit_AI_Manager')) {
            $ai_manager = Saiita_AI_Toolkit_AI_Manager::get_instance();
            $prompt = "请分析以下内容的SEO表现，并提供改进建议：\n\n{$content}";
            $seo_options = array_merge($options, array(
                'system_prompt' => '你是一个专业的SEO分析师，请从关键词密度、标题结构、内容质量、可读性等方面分析内容。'
            ));
            $result = $ai_manager->generate_content($prompt, $seo_options);
            if (is_wp_error($result)) {
                return $result;
            }
            return $result['content'] ?? '';
        }
        return new WP_Error('seo_not_available', 'SEO分析功能不可用');
    }
}

if (!function_exists('ai_content_toolkit_get_manager')) {
    function ai_content_toolkit_get_manager()  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    {
        if (class_exists('Saiita_AI_Toolkit_AI_Manager')) {
            return Saiita_AI_Toolkit_AI_Manager::get_instance();
        }
        return null;
    }
}

if (!function_exists('ai_content_toolkit_test_provider')) {
    function ai_content_toolkit_test_provider($provider_name)  // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    {
        $ai_manager = ai_content_toolkit_get_manager();
        if (!$ai_manager) {
            return new WP_Error('manager_not_available', 'AI管理器不可用');
        }
        return $ai_manager->test_provider($provider_name);
    }
}
