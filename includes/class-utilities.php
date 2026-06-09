<?php
/**
 * WordPress Toolkit - 工具类库
 * 通用工具函数和辅助方法
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_Utilities
{
    /**
     * 提取URL元数据
     *
     * @param string $url 目标URL
     * @param int $timeout 请求超时时间（秒）
     * @return array|false 元数据数组或false
     */
    public static function extract_url_metadata($url, $timeout = 10)
    {
        // 验证URL格式
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            self::saiita_ai_toolkit_debug_log('URL格式无效: ' . $url, 'utilities');
            return false;
        }

        // 检查缓存
        $cache_key = "url_metadata_{$url}";
        $cached_data = Saiita_AI_Toolkit_Cache_Manager::get($cache_key, 'url_meta');

        if ($cached_data !== false) {
            return $cached_data;
        }

        // 发起HTTP请求
        $response = wp_remote_get($url, [
            'timeout' => $timeout,
            'user-agent' => 'Saiita AI Content Toolkit/2.0 (+https://www.saiita.com.cn)',
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive'
            ],
            'sslverify' => apply_filters('saiita_toolkit_verify_ssl', true)
        ]);

        if (is_wp_error($response)) {
            self::saiita_ai_toolkit_debug_log('URL提取失败: ' . $response->get_error_message(), 'utilities');
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200 || empty($body)) {
            self::saiita_ai_toolkit_debug_log('URL请求失败: HTTP ' . $status_code . ', Body length: ' . strlen($body), 'utilities');
            return false;
        }

        // 提取元数据
        $metadata = [
            'url' => $url,
            'title' => self::extract_title($body),
            'description' => self::extract_description($body),
            'image' => self::extract_image($body),
            'site_name' => self::extract_site_name($body),
            'favicon' => self::extract_favicon($url, $body),
            'type' => self::extract_type($body),
            'author' => self::extract_author($body),
            'published_time' => self::extract_published_time($body),
            'modified_time' => self::extract_modified_time($body),
            'extracted_at' => current_time('mysql')
        ];

        // 缓存结果
        Saiita_AI_Toolkit_Cache_Manager::set($cache_key, $metadata, 3600, 'url_meta');

        return $metadata;
    }

    /**
     * 提取页面标题
     */
    private static function extract_title($html)
    {
        // 优先级：Open Graph > Twitter Card > title标签
        $patterns = [
            '/<meta[^>]*property=["\']og:title["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is',
            '/<meta[^>]*name=["\']twitter:title["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is',
            '/<title[^>]*>(.*?)<\/title>/is'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
            }
        }

        return '';
    }

    /**
     * 提取页面描述
     */
    private static function extract_description($html)
    {
        // 优先级：Open Graph > Twitter Card > meta description
        $patterns = [
            '/<meta[^>]*property=["\']og:description["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is',
            '/<meta[^>]*name=["\']twitter:description["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is',
            '/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
            }
        }

        // 尝试从正文提取
        $content = '';
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $body_matches)) {
            $text_only = wp_strip_all_tags($body_matches[1]);
            $text_only = preg_replace('/\s+/', ' ', $text_only);
            $content = substr(trim($text_only), 0, 200);
        }

        return $content;
    }

    /**
     * 提取页面图片
     */
    private static function extract_image($html)
    {
        // 优先级：Open Graph > Twitter Card > 第一张图片
        $patterns = [
            '/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is',
            '/<meta[^>]*name=["\']twitter:image["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim($matches[1]);
            }
        }

        // 提取第一张图片
        if (preg_match('/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * 提取网站名称
     */
    private static function extract_site_name($html)
    {
        $patterns = [
            '/<meta[^>]*property=["\']og:site_name["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is',
            '/<meta[^>]*name=["\']application-name["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
            }
        }

        return '';
    }

    /**
     * 提取网站图标
     */
    private static function extract_favicon($base_url, $html)
    {
        // 从HTML中提取favicon
        $patterns = [
            '/<link[^>]*rel=["\'](?:shortcut )?icon["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i',
            '/<link[^>]*rel=["\']apple-touch-icon["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return self::resolve_url($base_url, trim($matches[1]));
            }
        }

        // 默认favicon位置
        $parsed_url = wp_parse_url($base_url);
        return $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/favicon.ico';
    }

    /**
     * 提取内容类型
     */
    private static function extract_type($html)
    {
        if (preg_match('/<meta[^>]*property=["\']og:type["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return trim($matches[1]);
        }
        return 'website';
    }

    /**
     * 提取作者
     */
    private static function extract_author($html)
    {
        $patterns = [
            '/<meta[^>]*property=["\']article:author["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]*name=["\']author["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
            }
        }

        return '';
    }

    /**
     * 提取发布时间
     */
    private static function extract_published_time($html)
    {
        $patterns = [
            '/<meta[^>]*property=["\']article:published_time["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]*name=["\']article:published_time["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]*property=["\']published_time["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim($matches[1]);
            }
        }

        return '';
    }

    /**
     * 提取修改时间
     */
    private static function extract_modified_time($html)
    {
        $patterns = [
            '/<meta[^>]*property=["\']article:modified_time["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]*name=["\']article:modified_time["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]*property=["\']modified_time["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim($matches[1]);
            }
        }

        return '';
    }

    /**
     * 解析相对URL为绝对URL
     *
     * @param string $base_url 基础URL
     * @param string $relative_url 相对URL
     * @return string 绝对URL
     */
    public static function resolve_url($base_url, $relative_url)
    {
        if (empty($relative_url)) {
            return $base_url;
        }

        // 如果已经是绝对URL，直接返回
        if (filter_var($relative_url, FILTER_VALIDATE_URL)) {
            return $relative_url;
        }

        // 解析基础URL
        $parsed_base = wp_parse_url($base_url);
        if (!$parsed_base) {
            return $base_url;
        }

        // 构建基础部分
        $scheme = $parsed_base['scheme'] ?? 'https';
        $host = $parsed_base['host'] ?? '';
        $port = isset($parsed_base['port']) ? ':' . $parsed_base['port'] : '';
        $path = $parsed_base['path'] ?? '';

        // 处理相对路径
        if (strpos($relative_url, '/') === 0) {
            // 绝对路径
            return $scheme . '://' . $host . $port . $relative_url;
        } else {
            // 相对路径
            $base_path = dirname($path);
            if ($base_path === '.') {
                $base_path = '';
            }
            return $scheme . '://' . $host . $port . $base_path . '/' . $relative_url;
        }
    }

    /**
     * 生成随机字符串
     *
     * @param int $length 长度
     * @param string $chars 字符集
     * @return string
     */
    public static function random_string($length = 32, $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $result = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $max)];
        }

        return $result;
    }

    /**
     * 格式化文件大小
     *
     * @param int $bytes 字节数
     * @param int $precision 精度
     * @return string
     */
    public static function format_file_size($bytes, $precision = 2)
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * 格式化数字
     *
     * @param int $number 数字
     * @return string
     */
    public static function format_number($number)
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        } else {
            return number_format($number);
        }
    }

    /**
     * 计算两个日期之间的时间差
     *
     * @param string $date1 日期1
     * @param string $date2 日期2
     * @param string $unit 单位 (days, months, years)
     * @return int
     */
    public static function date_diff($date1, $date2, $unit = 'days')
    {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        $interval = $datetime1->diff($datetime2);

        switch ($unit) {
            case 'years':
                return $interval->y;
            case 'months':
                return $interval->y * 12 + $interval->m;
            case 'days':
            default:
                return $interval->days;
        }
    }

    /**
     * 生成颜色
     *
     * @param string $seed 种子字符串
     * @return string 十六进制颜色值
     */
    public static function generate_color($seed = '')
    {
        $hash = md5($seed . microtime());
        return '#' . substr($hash, 0, 6);
    }

    /**
     * 清理HTML内容
     *
     * @param string $content HTML内容
     * @param array $allowed_tags 允许的标签
     * @return string 清理后的内容
     */
    public static function clean_html($content, $allowed_tags = null)
    {
        if ($allowed_tags === null) {
            $allowed_tags = [
                'p', 'br', 'strong', 'em', 'u', 'i', 'b',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'ul', 'ol', 'li',
                'a', 'img',
                'blockquote', 'pre', 'code'
            ];
        }

        return wp_kses($content, array_fill_keys($allowed_tags, []));
    }

    /**
     * 截取文本并保留HTML标签
     *
     * @param string $text 原文本
     * @param int $length 截取长度
     * @param string $suffix 后缀
     * @return string 截取后的文本
     */
    public static function truncate_html($text, $length = 200, $suffix = '...')
    {
        if (strlen(wp_strip_all_tags($text)) <= $length) {
            return $text;
        }

        // 简单的HTML截取（可以进一步优化）
        $plain_text = wp_strip_all_tags($text);
        $truncated = mb_substr($plain_text, 0, $length, 'UTF-8');

        return $truncated . $suffix;
    }

    /**
     * 检测是否为移动设备
     *
     * @return bool
     */
    public static function is_mobile()
    {
        return wp_is_mobile();
    }

    /**
     * 获取客户端信息
     *
     * @return array
     */
    public static function get_client_info()
    {
        return [
            'ip' => Saiita_AI_Toolkit_Security_Validator::get_client_ip(),
            'user_agent' => sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')),
            'referer' => esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'] ?? '')),
            'is_mobile' => self::is_mobile()
        ];
    }

    /**
     * 生成UUID v4
     *
     * @return string
     */
    public static function generate_uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /**
     * 加密 API Key（使用 WordPress salt 做 AES-256-CTR 加密）
     *
     * @param string $value 明文 API Key
     * @return string 加密后的字符串（带标记前缀），空值原样返回
     */
    public static function encrypt_api_key($value)
    {
        if (empty($value)) {
            return $value;
        }

        if (!extension_loaded('openssl')) {
            return $value;
        }

        $key  = defined('LOGGED_IN_KEY') ? LOGGED_IN_KEY : 'saiita-fallback-key';
        $salt = defined('LOGGED_IN_SALT') ? LOGGED_IN_SALT : 'saiita-fallback-salt';
        $method = 'aes-256-ctr';
        $iv_len  = openssl_cipher_iv_length($method);
        $iv      = openssl_random_pseudo_bytes($iv_len);

        $encrypted = openssl_encrypt($value, $method, hash('sha256', $key . $salt, true), OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            return $value;
        }

        return '$S$' . base64_encode($iv . $encrypted);
    }

    /**
     * 解密 API Key
     *
     * @param string $value 加密后的字符串（带 $S$ 标记）或明文
     * @return string 明文 API Key
     */
    public static function decrypt_api_key($value)
    {
        if (empty($value) || strpos($value, '$S$') !== 0) {
            // 不带标记 → 旧版明文，直接返回（向后兼容）
            return $value;
        }

        if (!extension_loaded('openssl')) {
            return $value;
        }

        $key  = defined('LOGGED_IN_KEY') ? LOGGED_IN_KEY : 'saiita-fallback-key';
        $salt = defined('LOGGED_IN_SALT') ? LOGGED_IN_SALT : 'saiita-fallback-salt';
        $method = 'aes-256-ctr';
        $iv_len  = openssl_cipher_iv_length($method);

        $raw   = base64_decode(substr($value, 3), true);
        if ($raw === false || strlen($raw) < $iv_len) {
            return $value;
        }

        $iv         = substr($raw, 0, $iv_len);
        $encrypted  = substr($raw, $iv_len);
        $decrypted  = openssl_decrypt($encrypted, $method, hash('sha256', $key . $salt, true), OPENSSL_RAW_DATA, $iv);

        return $decrypted !== false ? $decrypted : $value;
    }

    /**
     * 统一的调试日志记录函数
     *
     * @param string $message 日志消息
     * @param string $context 上下文
     * @param array $data 附加数据
     */
    public static function saiita_ai_toolkit_debug_log($message, $context = '', $data = array())
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wt_log_debug($message, $context, $data);
        }
    }

    /**
     * 统一的错误日志记录函数
     *
     * @param string $message 错误消息
     * @param string $context 上下文
     * @param array $data 附加数据
     */
    public static function error_log($message, $context = '', $data = array())
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wt_log_error($message, $context, $data);
        }
    }
}
