<?php
/**
 * WordPress Toolkit 国际化支持
 * 提供统一的文本翻译函数
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress Toolkit 国际化类
 */
class WordPress_Toolkit_i18n {
    
    /**
     * 获取翻译文本
     * 
     * @param string $text 要翻译的文本
     * @param string $domain 文本域
     * @return string 翻译后的文本
     */
    public static function __($text, $domain = 'wordpress-toolkit') {
        return __($text, $domain);
    }
    
    /**
     * 输出翻译文本
     * 
     * @param string $text 要翻译的文本
     * @param string $domain 文本域
     */
    public static function _e($text, $domain = 'wordpress-toolkit') {
        _e($text, $domain);
    }
    
    /**
     * 获取复数形式的翻译文本
     * 
     * @param string $single 单数形式
     * @param string $plural 复数形式
     * @param int $number 数量
     * @param string $domain 文本域
     * @return string 翻译后的文本
     */
    public static function _n($single, $plural, $number, $domain = 'wordpress-toolkit') {
        return _n($single, $plural, $number, $domain);
    }
    
    /**
     * 获取带上下文的翻译文本
     * 
     * @param string $text 要翻译的文本
     * @param string $context 上下文
     * @param string $domain 文本域
     * @return string 翻译后的文本
     */
    public static function _x($text, $context, $domain = 'wordpress-toolkit') {
        return _x($text, $context, $domain);
    }
    
    /**
     * 输出带上下文的翻译文本
     * 
     * @param string $text 要翻译的文本
     * @param string $context 上下文
     * @param string $domain 文本域
     */
    public static function _ex($text, $context, $domain = 'wordpress-toolkit') {
        _ex($text, $context, $domain);
    }
    
    /**
     * 常用文本翻译
     */
    public static function common_texts() {
        return array(
            'settings' => __('设置', 'wordpress-toolkit'),
            'save' => __('保存', 'wordpress-toolkit'),
            'cancel' => __('取消', 'wordpress-toolkit'),
            'delete' => __('删除', 'wordpress-toolkit'),
            'edit' => __('编辑', 'wordpress-toolkit'),
            'add' => __('添加', 'wordpress-toolkit'),
            'update' => __('更新', 'wordpress-toolkit'),
            'search' => __('搜索', 'wordpress-toolkit'),
            'loading' => __('加载中...', 'wordpress-toolkit'),
            'error' => __('错误', 'wordpress-toolkit'),
            'success' => __('成功', 'wordpress-toolkit'),
            'warning' => __('警告', 'wordpress-toolkit'),
            'info' => __('信息', 'wordpress-toolkit'),
            'permission_denied' => __('权限不足', 'wordpress-toolkit'),
            'invalid_url' => __('无效的URL格式', 'wordpress-toolkit'),
            'network_error' => __('网络请求失败', 'wordpress-toolkit'),
            'server_error' => __('服务器内部错误', 'wordpress-toolkit'),
            'timeout' => __('请求超时', 'wordpress-toolkit'),
            'not_found' => __('请求的资源不存在', 'wordpress-toolkit'),
        );
    }
    
    /**
     * Custom Card 模块文本
     */
    public static function custom_card_texts() {
        return array(
            'website_card' => __('网站卡片', 'wordpress-toolkit'),
            'custom_card' => __('自定义卡片', 'wordpress-toolkit'),
            'lazy_loading' => __('懒加载', 'wordpress-toolkit'),
            'cache_settings' => __('缓存设置', 'wordpress-toolkit'),
            'cache_expiration' => __('缓存过期时间', 'wordpress-toolkit'),
            'hours' => __('小时', 'wordpress-toolkit'),
            'card_list' => __('卡片列表', 'wordpress-toolkit'),
            'card_settings' => __('卡片设置', 'wordpress-toolkit'),
            'card_cache' => __('卡片缓存', 'wordpress-toolkit'),
            'clear_cache' => __('清空缓存', 'wordpress-toolkit'),
            'cache_cleared' => __('缓存已清空', 'wordpress-toolkit'),
            'cache_status' => __('缓存状态', 'wordpress-toolkit'),
            'memcached_enabled' => __('Memcached已启用', 'wordpress-toolkit'),
            'memcached_disabled' => __('Memcached已禁用', 'wordpress-toolkit'),
            'opcache_enabled' => __('Opcache已启用', 'wordpress-toolkit'),
            'opcache_disabled' => __('Opcache已禁用', 'wordpress-toolkit'),
        );
    }
    
    /**
     * CookieGuard 模块文本
     */
    public static function cookieguard_texts() {
        return array(
            'cookie_consent' => __('Cookie同意', 'wordpress-toolkit'),
            'cookie_notice' => __('Cookie通知', 'wordpress-toolkit'),
            'accept_cookies' => __('接受Cookie', 'wordpress-toolkit'),
            'decline_cookies' => __('拒绝Cookie', 'wordpress-toolkit'),
            'learn_more' => __('了解更多', 'wordpress-toolkit'),
            'cookie_settings' => __('Cookie设置', 'wordpress-toolkit'),
            'cookie_expiry' => __('Cookie过期时间', 'wordpress-toolkit'),
            'days' => __('天', 'wordpress-toolkit'),
            'position' => __('位置', 'wordpress-toolkit'),
            'top' => __('顶部', 'wordpress-toolkit'),
            'bottom' => __('底部', 'wordpress-toolkit'),
            'background_color' => __('背景颜色', 'wordpress-toolkit'),
            'text_color' => __('文本颜色', 'wordpress-toolkit'),
            'button_color' => __('按钮颜色', 'wordpress-toolkit'),
            'button_text_color' => __('按钮文本颜色', 'wordpress-toolkit'),
            'show_decline_button' => __('显示拒绝按钮', 'wordpress-toolkit'),
            'enable_analytics' => __('启用分析', 'wordpress-toolkit'),
            'enable_geo_detection' => __('启用地理位置检测', 'wordpress-toolkit'),
            'local_ip_as_china' => __('本地IP视为中国', 'wordpress-toolkit'),
            'clear_geo_cache' => __('清空地理位置缓存', 'wordpress-toolkit'),
            'geo_cache_cleared' => __('地理位置缓存已清空', 'wordpress-toolkit'),
        );
    }
    
    /**
     * Age Calculator 模块文本
     */
    public static function age_calculator_texts() {
        return array(
            'age_calculator' => __('年龄计算器', 'wordpress-toolkit'),
            'birth_date' => __('出生日期', 'wordpress-toolkit'),
            'calculate' => __('计算', 'wordpress-toolkit'),
            'years' => __('岁', 'wordpress-toolkit'),
            'months' => __('个月', 'wordpress-toolkit'),
            'days' => __('天', 'wordpress-toolkit'),
            'exact_age' => __('精确年龄', 'wordpress-toolkit'),
            'age_result' => __('年龄结果', 'wordpress-toolkit'),
            'invalid_date' => __('无效的日期', 'wordpress-toolkit'),
            'future_date' => __('不能选择未来日期', 'wordpress-toolkit'),
        );
    }
    
    /**
     * Time Capsule 模块文本
     */
    public static function time_capsule_texts() {
        return array(
            'time_capsule' => __('物品管理', 'wordpress-toolkit'),
            'items' => __('物品', 'wordpress-toolkit'),
            'categories' => __('分类', 'wordpress-toolkit'),
            'add_item' => __('添加物品', 'wordpress-toolkit'),
            'edit_item' => __('编辑物品', 'wordpress-toolkit'),
            'item_name' => __('物品名称', 'wordpress-toolkit'),
            'purchase_date' => __('购买日期', 'wordpress-toolkit'),
            'warranty_period' => __('保修期', 'wordpress-toolkit'),
            'months' => __('个月', 'wordpress-toolkit'),
            'warranty_expired' => __('保修已过期', 'wordpress-toolkit'),
            'warranty_active' => __('保修有效', 'wordpress-toolkit'),
            'expiring_soon' => __('即将过期', 'wordpress-toolkit'),
            'category_name' => __('分类名称', 'wordpress-toolkit'),
            'description' => __('描述', 'wordpress-toolkit'),
            'price' => __('价格', 'wordpress-toolkit'),
            'status' => __('状态', 'wordpress-toolkit'),
        );
    }
}

// 简化的翻译函数别名
if (!function_exists('wt__')) {
    function wt__($text, $domain = 'wordpress-toolkit') {
        return WordPress_Toolkit_i18n::__($text, $domain);
    }
}

if (!function_exists('wt_e')) {
    function wt_e($text, $domain = 'wordpress-toolkit') {
        WordPress_Toolkit_i18n::_e($text, $domain);
    }
}

if (!function_exists('wt_n')) {
    function wt_n($single, $plural, $number, $domain = 'wordpress-toolkit') {
        return WordPress_Toolkit_i18n::_n($single, $plural, $number, $domain);
    }
}