<?php
/**
 * Plugin Loader
 * 负责模块加载逻辑，提供统一的模块注册和生命周期管理
 *
 * @since 2.0.0
 * @since 2.0.23 引入统一模块注册模式
 */

if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_Plugin_Loader
{
    /**
     * 已注册的模块实例映射
     *
     * @var array<string, object>
     */
    private $modules = array();

    /**
     * 注册一个模块（用于统一生命周期管理）
     *
     * @param string $name 模块名称
     * @param object $instance 模块实例
     * @return void
     */
    public function register_module($name, $instance)
    {
        $this->modules[$name] = $instance;
    }

    /**
     * 获取模块实例
     *
     * @param string $name 模块名称
     * @return object|null
     */
    public function get_module($name)
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * 获取已注册的所有模块
     *
     * @return array<string, object>
     */
    public function get_modules()
    {
        return $this->modules;
    }

    /**
     * 加载所有子模块
     */
    public function load_modules()
    {
        // 优先加载核心AI设置模块
        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/ai-settings/ai-settings-module.php';
        $this->register_module('ai_settings', Saiita_AI_Toolkit_AI_Settings::get_instance());

        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/prompt-settings/prompt-settings-module.php';
        $this->register_module('prompt_settings', Saiita_AI_Toolkit_Prompt_Settings::get_instance());

        // 加载Auto Excerpt模块
        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/auto-excerpt/auto-excerpt-module.php';
        $this->register_module('auto_excerpt', Saiita_AI_Toolkit_Auto_Excerpt::get_instance());

        // 加载Tag Optimization模块
        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/tag-optimization/tag-optimization-module.php';
        $this->register_module('tag_optimization', Saiita_AI_Toolkit_Tag_Optimization::get_instance());

        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/tag-optimization/admin/admin-page.php';
        $this->register_module('tag_optimization_admin', Saiita_AI_Toolkit_Tag_Optimization_Admin::get_instance());

        // 加载Category Optimization模块
        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/category-optimization/category-optimization-module.php';
        $this->register_module('category_optimization', Saiita_AI_Toolkit_Category_Optimization::get_instance());

        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/category-optimization/admin/admin-page.php';
        $this->register_module('category_optimization_admin', Saiita_AI_Toolkit_Category_Optimization_Admin::get_instance());

        // 加载AI Category模块
        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/ai-category/ai-category-module.php';
        $this->register_module('ai_category', Saiita_AI_Toolkit_AI_Category::get_instance());

        // 加载Website Optimization模块
        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/website-optimization/website-optimization-module.php';
        $this->register_module('website_optimization', Saiita_AI_Toolkit_Website_Optimization::get_instance());

        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/website-optimization/admin/admin-page.php';
        $this->register_module('website_optimization_admin', Saiita_AI_Toolkit_Website_Optimization_Admin::get_instance());

        // v2.0 加载新的AI组件
        $this->load_ai_components();
    }

    /**
     * 加载v2.0 AI组件
     */
    public function load_ai_components()
    {
        if (class_exists('Saiita_AI_Toolkit_AI_Manager')) {
            $this->register_module('ai_manager', Saiita_AI_Toolkit_AI_Manager::get_instance());
        }

        if (class_exists('Saiita_AI_Toolkit_Backward_Compatibility')) {
            $this->register_module('backward_compatibility', Saiita_AI_Toolkit_Backward_Compatibility::get_instance());
        }

        if (
            version_compare(get_bloginfo('version'), '6.9', '>=') &&
            class_exists('Saiita_AI_Toolkit_Abilities_API_Integration')
        ) {
            $this->register_module('abilities_api', Saiita_AI_Toolkit_Abilities_API_Integration::get_instance());
        }

        if (class_exists('Saiita_AI_Toolkit_Provider_Manager_Admin')) {
            $this->register_module('ai_provider_manager', new Saiita_AI_Toolkit_Provider_Manager_Admin());
        }

        if (class_exists('Saiita_AI_Enhanced_Excerpt_Generator')) {
            $this->register_module('ai_enhanced_excerpt', Saiita_AI_Enhanced_Excerpt_Generator::get_instance());
        }
    }

    /**
     * 激活所有子模块
     */
    public function activate()
    {
        foreach ($this->modules as $name => $instance) {
            if (is_callable(array($instance, 'on_activate'))) {
                $instance->on_activate();
            } elseif (is_callable(array($instance, 'activate'))) {
                $instance->activate();
            }
        }
    }

    /**
     * 停用所有子模块
     */
    public function deactivate()
    {
        foreach ($this->modules as $name => $instance) {
            if (is_callable(array($instance, 'on_deactivate'))) {
                $instance->on_deactivate();
            } elseif (is_callable(array($instance, 'deactivate'))) {
                $instance->deactivate();
            }
        }
    }

    /**
     * 初始化所有子模块
     */
    public function init()
    {
        foreach ($this->modules as $name => $instance) {
            if (is_callable(array($instance, 'on_init'))) {
                $instance->on_init();
            } elseif (is_callable(array($instance, 'init'))) {
                $instance->init();
            }
        }
    }

    /**
     * 加载管理后台资源
     */
    public function admin_enqueue_scripts($hook)
    {
        foreach ($this->modules as $name => $instance) {
            if (is_callable(array($instance, 'admin_enqueue_scripts'))) {
                $instance->admin_enqueue_scripts($hook);
            }
        }
    }

    /**
     * 加载前端资源
     */
    public function enqueue_scripts()
    {
        foreach ($this->modules as $name => $instance) {
            if (is_callable(array($instance, 'enqueue_scripts'))) {
                $instance->enqueue_scripts();
            }
        }
    }
}
