<?php
/**
 * WordPress Toolkit 卸载脚本
 * 清理插件创建的所有数据和选项
 */

// 防止直接访问
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 加载日志管理类
require_once plugin_dir_path(__FILE__) . 'includes/class-logger.php';

// 检查用户权限
if (!current_user_can('activate_plugins')) {
    return;
}

// 全局变量
global $wpdb;

// 删除插件选项
delete_option('wordpress_toolkit_custom_card_options');
delete_option('wordpress_toolkit_age_calculator_options');
delete_option('wordpress_toolkit_time_capsule_options');
delete_option('wordpress_toolkit_cookieguard_options');
delete_option('wordpress_toolkit_activated_time');
delete_option('wordpress_toolkit_custom_card_activated_time');
delete_option('wordpress_toolkit_time_capsule_activated_time');
delete_option('wordpress_toolkit_cookieguard_activated_time');

// 删除Custom Card数据库表
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chf_card_cache");

// 删除Time Capsule数据库表
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}time_capsule_items");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}time_capsule_categories");

// 删除用户元数据中的相关数据
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wordpress_toolkit_%'");

// 删除所有相关的transients缓存
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wordpress_toolkit_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wordpress_toolkit_%'");

// 删除所有相关的地理位置缓存
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wordpress_toolkit_cookieguard_geo_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wordpress_toolkit_cookieguard_geo_%'");

// 删除所有相关的卡片缓存
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_chf_card_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_chf_card_%'");

// 清理post meta中的相关数据
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'wordpress_toolkit_%'");

// 记录卸载日志
if (defined('WP_DEBUG') && WP_DEBUG) {
    wt_log_info('Plugin uninstalled successfully', 'uninstall');
}