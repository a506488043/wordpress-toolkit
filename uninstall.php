<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.DB.DirectDatabaseQuery.SchemaChange
/**
 * WordPress Toolkit 卸载脚本
 * 清理插件创建的所有数据和选项
 */

// 加载日志管理类
require_once plugin_dir_path(__FILE__) . 'includes/class-logger.php';

// 全局变量
global $wpdb;

// 定义允许删除的表名白名单
$allowed_drop_tables = array(
    $wpdb->prefix . 'saiita_auto_excerpt_seo_analysis',
);

// 删除插件选项
delete_option('saiita_ai_content_toolkit_activated_time');

// 安全地删除SEO分析数据库表
$seo_table = $wpdb->prefix . 'saiita_auto_excerpt_seo_analysis';
if (in_array($seo_table, $allowed_drop_tables)) {
    $table_name = esc_sql($seo_table);
    $result = $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
        wt_log_error('Failed to drop SEO analysis table: ' . $wpdb->last_error, 'uninstall');
    }
}

// 注意：网站卡片、友情链接、GitHub项目、Time Capsule等已拆分为独立插件，
// 相关表和选项由各自独立插件管理，不再在此清理

// 使用prepare语句安全地删除用户元数据中的相关数据
$result = $wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
    'saiita_ai_content_toolkit_%'
));
if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
    wt_log_error('Failed to delete user meta data: ' . $wpdb->last_error, 'uninstall');
}

// 使用prepare语句安全地删除transients缓存
$transient_patterns = array(
    '_transient_saiita_ai_content_toolkit_%',
    '_transient_timeout_saiita_ai_content_toolkit_%',
);

foreach ($transient_patterns as $pattern) {
    $result = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        $pattern
    ));
    if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
        wt_log_error("Failed to delete transients for pattern {$pattern}: " . $wpdb->last_error, 'uninstall');
    }
}

// 安全地清理post meta中的相关数据
$result = $wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
    'saiita_ai_content_toolkit_%'
));
if ($result === false && defined('WP_DEBUG') && WP_DEBUG) {
    wt_log_error('Failed to delete post meta data: ' . $wpdb->last_error, 'uninstall');
}

// 记录卸载日志
if (defined('WP_DEBUG') && WP_DEBUG) {
    wt_log_info('Plugin uninstalled successfully', 'uninstall');
}
