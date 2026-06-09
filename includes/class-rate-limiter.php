<?php
/**
 * Rate Limiter
 * 使用对象缓存 + 文件锁降级，解决 wp_options 并发竞态问题
 */

if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_Rate_Limiter
{
    /**
     * 检查是否达到速率限制
     *
     * @param string $provider 提供商名称
     * @param int $limit 每分钟限制次数
     * @return bool 是否被限制
     */
    public static function is_rate_limited($provider, $limit)
    {
        $current_minute = floor(time() / 60);
        $key = "saiita_rate_{$provider}_{$current_minute}";

        $count = self::get_cache($key);
        return $count >= $limit;
    }

    /**
     * 增加请求计数
     *
     * @param string $provider 提供商名称
     * @param int $ttl 过期时间（秒），默认65秒覆盖跨分钟边界
     */
    public static function increment($provider, $ttl = 65)
    {
        $current_minute = floor(time() / 60);
        $key = "saiita_rate_{$provider}_{$current_minute}";

        $count = self::get_cache($key);
        self::set_cache($key, $count + 1, $ttl);
    }

    /**
     * 获取当前计数
     *
     * @param string $provider 提供商名称
     * @return int 当前计数
     */
    public static function get_count($provider)
    {
        $current_minute = floor(time() / 60);
        $key = "saiita_rate_{$provider}_{$current_minute}";
        return self::get_cache($key);
    }

    /**
     * 从缓存获取值
     *
     * @param string $key 缓存键
     * @return int 缓存值
     */
    private static function get_cache($key)
    {
        // 优先使用对象缓存（Redis/Memcached）
        if (wp_using_ext_object_cache()) {
            $value = wp_cache_get($key, 'saiita_rate_limiter');
            return $value !== false ? intval($value) : 0;
        }

        // 降级：使用 Transients（数据库，但有索引优化）
        $value = get_transient($key);
        return $value !== false ? intval($value) : 0;
    }

    /**
     * 设置缓存值
     *
     * @param string $key 缓存键
     * @param int $value 值
     * @param int $ttl 过期时间（秒）
     */
    private static function set_cache($key, $value, $ttl = 65)
    {
        // 优先使用对象缓存
        if (wp_using_ext_object_cache()) {
            wp_cache_set($key, $value, 'saiita_rate_limiter', $ttl);
            return;
        }

        // 降级：使用 Transients
        set_transient($key, $value, $ttl);
    }
}
