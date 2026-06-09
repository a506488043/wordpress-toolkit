<?php
/**
 * WordPress Toolkit 基础模块类
 * 提供通用的安全验证和错误处理功能
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Saiita_AI_Toolkit_Module_Base
{
    /**
     * 验证AJAX请求的安全性和权限
     *
     * @param string $nonce_action Nonce动作名称
     * @param string $capability 所需权限，默认为'manage_options'
     * @return bool 验证是否通过
     */
    protected function verify_ajax_request($nonce_action, $capability = 'manage_options')
    {
        // 使用统一的Security_Validator类
        if ($capability === 'manage_options') {
            return Saiita_AI_Toolkit_Security_Validator::verify_admin_ajax($nonce_action);
        } elseif ($capability === 'edit_posts') {
            return Saiita_AI_Toolkit_Security_Validator::verify_editor_ajax($nonce_action);
        } else {
            return Saiita_AI_Toolkit_Security_Validator::verify_user_ajax($nonce_action, $capability);
        }
    }

    /**
     * 安全地执行数据库查询并处理错误
     *
     * @param string $query SQL查询语句
     * @param string $error_message 自定义错误消息
     * @param string $module_name 模块名称
     * @return mixed|false 查询结果或false
     */
    protected function safe_db_query($query, $error_message = 'Database query failed', $module_name = 'unknown')
    {
        global $wpdb;

        try {
            // Collect any additional arguments for parameterized query
            $args = func_get_args();
            array_shift($args); // remove $query
            array_shift($args); // remove $error_message
            array_shift($args); // remove $module_name

            if (!empty($args)) {
                $prepared = $wpdb->prepare($query, $args); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is a template, $args are parameters
                $result = $wpdb->query($prepared); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- $prepared from $wpdb->prepare()
            } else {
                // Static query only - caller must ensure no user input in $query
                $result = $wpdb->query($query); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared
            }

            if ($result === false) {
                wt_log_error($error_message . ': ' . $wpdb->last_error, $module_name, array(
                    'query' => $query,
                    'last_error' => $wpdb->last_error
                ));
            }
            return $result;
        } catch (Exception $e) {
            wt_log_error($error_message . ': ' . $e->getMessage(), $module_name, array(
                'query' => $query,
                'exception' => $e->getMessage()
            ));
            return false;
        }
    }

    /**
     * 批量处理操作，避免超时和内存问题
     *
     * @param array $items 要处理的项目数组
     * @param callable $callback 处理回调函数
     * @param int $batch_size 批次大小
     * @param string $module_name 模块名称
     * @return bool 处理是否成功
     */
    protected function process_in_batches($items, $callback, $batch_size = 50, $module_name = 'unknown')
    {
        if (!is_callable($callback)) {
            wt_log_error('Invalid callback provided for batch processing', $module_name);
            return false;
        }

        $batches = array_chunk($items, $batch_size);
        $total_batches = count($batches);
        $success = true;

        foreach ($batches as $index => $batch) {
            try {
                $result = call_user_func($callback, $batch, $index + 1, $total_batches);

                if ($result === false) {
                    wt_log_error("Batch {$index} processing failed", $module_name);
                    $success = false;
                }

                // 避免超时，让系统有机会处理其他任务
                if ($index < $total_batches - 1) {
                    if (function_exists('wp_cache_flush')) {
                        wp_cache_flush();
                    }

                    // 在长时间操作中，检查是否接近超时
                    if (
                        function_exists('wp_get_max_execution_time') &&
                        time() - (isset($_SERVER['REQUEST_TIME']) ? intval($_SERVER['REQUEST_TIME']) : time()) > wp_get_max_execution_time() * 0.8
                    ) {
                        wt_log_warning(' approaching execution time limit during batch processing', $module_name);
                    }
                }
            } catch (Exception $e) {
                wt_log_error("Exception in batch {$index}: " . $e->getMessage(), $module_name, array(
                    'exception' => $e->getMessage(),
                    'batch_index' => $index
                ));
                $success = false;
            }
        }

        return $success;
    }

    /**
     * 发送安全的AJAX响应
     *
     * @param bool $success 是否成功
     * @param string $message 响应消息
     * @param array $data 额外数据
     * @param array $debug_data 调试数据（仅在WP_DEBUG模式下显示）
     */
    protected function send_ajax_response($success, $message, $data = array(), $debug_data = array())
    {
        $response = array_merge($data, array(
            'success' => $success,
            'message' => $message
        ));

        // 在调试模式下添加调试信息
        if (defined('WP_DEBUG') && WP_DEBUG && !empty($debug_data)) {
            $response['debug'] = $debug_data;
        }

        if ($success) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response);
        }
    }

    /**
     * 记录模块特定的操作日志
     *
     * @param string $message 日志消息
     * @param string $level 日志级别（info, warning, error）
     * @param array $context 上下文数据
     */
    protected function log_module_action($message, $level = 'info', $context = array())
    {
        $module_name = strtolower(str_replace(array('Saiita_AI_Toolkit_', '_Module'), '', get_class($this)));

        switch ($level) {
            case 'error':
                wt_log_error($message, $module_name, $context);
                break;
            case 'warning':
                wt_log_warning($message, $module_name, $context);
                break;
            case 'info':
            default:
                wt_log_info($message, $module_name, $context);
                break;
        }
    }

    /**
     * 渲染模块管理页面
     *
     * 子类可重写此方法以提供统一的管理页面入口。
     * 若模块使用独立 Admin 类，此方法可返回 false 或委托调用。
     *
     * @since 2.0.23
     * @return void|false
     */
    public function render_admin_page()
    {
        return false;
    }

    /**
     * 渲染模块设置页面
     *
     * 子类可重写此方法以提供统一的设置页面入口。
     *
     * @since 2.0.23
     * @return void|false
     */
    public function render_settings_page()
    {
        return false;
    }

    /**
     * 模块激活回调
     *
     * 子类可重写此方法以执行模块级激活逻辑。
     *
     * @since 2.0.23
     */
    public function on_activate()
    {
    }

    /**
     * 模块停用回调
     *
     * 子类可重写此方法以执行模块级停用逻辑。
     *
     * @since 2.0.23
     */
    public function on_deactivate()
    {
    }

    /**
     * 模块初始化回调
     *
     * 子类可重写此方法以在 init 钩子上执行初始化。
     *
     * @since 2.0.23
     */
    public function on_init()
    {
    }
}
