<?php
/**
 * AI增强摘要生成器
 * 使用新的AI管理器生成更智能的文章摘要
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Enhanced_Excerpt_Generator
{
    /**
     * 单例实例
     */
    private static $instance = null;

    /**
     * AI管理器
     */
    private $ai_manager;

    /**
     * 构造函数
     */
    private function __construct()
    {
        $this->ai_manager = Saiita_AI_Toolkit_AI_Manager::get_instance();
    }

    /**
     * 获取单例实例
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 生成智能摘要
     */
    public function generate_smart_excerpt($content, $options = array())
    {
        $default_options = array(
            'length' => 150,
            'style' => 'normal', // normal, bullet_points, questions, highlights
            'tone' => 'neutral', // neutral, formal, casual, professional
            'include_keywords' => true,
            'include_call_to_action' => false,
            'language' => 'zh-CN',
        );

        $options = wp_parse_args($options, $default_options);

        // AI未配置时直接返回错误
        if (!function_exists('saiita_ai_content_toolkit_is_ai_available') || !saiita_ai_content_toolkit_is_ai_available()) {
            return new WP_Error('ai_not_configured', __('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit'));
        }

        // 构建系统提示
        $system_prompt = $this->build_system_prompt($options);

        // 构建用户提示
        $user_prompt = $this->build_user_prompt($content, $options);

        // AI生成参数
        $ai_options = array(
            'system_prompt' => $system_prompt,
            'max_tokens' => min($options['length'] * 2, 500),
            'temperature' => $options['tone'] === 'creative' ? 0.8 : 0.6,
            'context' => array(
                'task' => 'excerpt_generation',
                'content_length' => strlen($content),
            ),
        );

        try {
            $result = $this->ai_manager->generate_content($user_prompt, $ai_options);

            if (is_wp_error($result)) {
                return $result;
            }

            $excerpt = $result['content'];

            // 后处理摘要
            $excerpt = $this->post_process_excerpt($excerpt, $options);

            // 记录生成信息
            $this->log_excerpt_generation($content, $excerpt, $options, $result);

            return $excerpt;
        } catch (Exception $e) {
            Saiita_AI_Toolkit_Logger::error(
                "AI摘要生成失败: " . $e->getMessage(),
                'excerpt_generator',
                array('options' => $options)
            );

            return new WP_Error('ai_generation_failed', __('AI摘要生成失败', 'saiita-ai-content-toolkit') . ': ' . $e->getMessage());
        }
    }

    /**
     * 构建系统提示
     */
    private function build_system_prompt($options)
    {
        $prompt = "你是一个专业的内容摘要助手，擅长生成简洁、准确、吸引人的文章摘要。\n\n";

        // 添加风格要求
        switch ($options['style']) {
            case 'bullet_points':
                $prompt .= "请使用项目符号列表的形式生成摘要要点。\n";
                break;
            case 'questions':
                $prompt .= "请以问答形式生成摘要，突出文章回答的核心问题。\n";
                break;
            case 'highlights':
                $prompt .= "请重点突出文章的亮点和关键信息。\n";
                break;
            default:
                $prompt .= "请生成流畅连贯的段落式摘要。\n";
        }

        // 添加语气要求
        switch ($options['tone']) {
            case 'formal':
                $prompt .= "使用正式、专业的语言风格。\n";
                break;
            case 'casual':
                $prompt .= "使用轻松、口语化的语言风格。\n";
                break;
            case 'professional':
                $prompt .= "使用行业专业的术语和表达。\n";
                break;
            default:
                $prompt .= "使用中性、客观的语言风格。\n";
        }

        // 添加长度要求
        $prompt .= "摘要长度控制在{$options['length']}字左右。\n";

        // 添加语言要求
        if ($options['language'] === 'zh-CN') {
            $prompt .= "请使用中文回复。\n";
        }

        return $prompt;
    }

    /**
     * 构建用户提示
     */
    private function build_user_prompt($content, $options)
    {
        $prompt = "请为以下文章生成摘要：\n\n";

        // 预处理内容，提取关键信息
        $processed_content = $this->preprocess_content($content);
        $prompt .= $processed_content;

        if ($options['include_keywords']) {
            $keywords = $this->extract_keywords($content);
            if (!empty($keywords)) {
                $prompt .= "\n\n关键词：" . implode('、', $keywords);
            }
        }

        if ($options['include_call_to_action']) {
            $prompt .= "\n\n请在摘要末尾添加合适的行动号召。";
        }

        return $prompt;
    }

    /**
     * 预处理内容
     */
    private function preprocess_content($content)
    {
        // 移除HTML标签
        $content = wp_strip_all_tags($content);

        // 移除多余的空白
        $content = preg_replace('/\s+/', ' ', $content);

        // 截取前2000字符作为摘要素材
        if (strlen($content) > 2000) {
            $content = substr($content, 0, 2000) . '...';
        }

        return $content;
    }

    /**
     * 提取关键词
     */
    private function extract_keywords($content)
    {
        // 简单的关键词提取
        $content = strtolower(wp_strip_all_tags($content));

        // 移除停用词
        $stop_words = array('的', '了', '和', '是', '在', '我', '有', '个', '不', '这', '他', '她', '它', '们', '就', '也', '说', '要', '会', '能', '对', '还', '把', '被', '从', '到', '与', '为', '给', '让', '向', '往', '去', '来', '用', '着', '过', '呢', '吗', '吧', '啊', '哦', '嗯', '哈');

        $words = str_word_count($content, 1, '一龯');
        $word_freq = array();

        foreach ($words as $word) {
            if (strlen($word) > 1 && !in_array($word, $stop_words)) {
                $word_freq[$word] = ($word_freq[$word] ?? 0) + 1;
            }
        }

        arsort($word_freq);

        return array_keys(array_slice($word_freq, 0, 5, true));
    }

    /**
     * 后处理摘要
     */
    private function post_process_excerpt($excerpt, $options)
    {
        // 移除多余的空白
        $excerpt = preg_replace('/\s+/', ' ', $excerpt);
        $excerpt = trim($excerpt);

        // 确保长度合适
        if (strlen($excerpt) > $options['length'] * 1.2) {
            $excerpt = substr($excerpt, 0, $options['length'] * 1.2) . '...';
        }

        // 特殊样式处理
        switch ($options['style']) {
            case 'bullet_points':
                // 确保项目符号格式正确
                $excerpt = preg_replace('/^(\s*)[-*+•]/m', '$1• ', $excerpt);
                break;
            case 'questions':
                // 确保问号格式正确
                $excerpt = preg_replace('/(\S)\?(\s)/', '$1？$2', $excerpt);
                break;
        }

        return $excerpt;
    }

    /**
     * 批量生成摘要
     */
    public function batch_generate_excerpts($post_ids, $options = array())
    {
        $results = array();
        $batch_size = 5; // 每批处理5篇文章

        foreach (array_chunk($post_ids, $batch_size) as $batch) {
            foreach ($batch as $post_id) {
                $post = get_post($post_id);
                if (!$post) {
                    $results[$post_id] = new WP_Error('invalid_post', '无效的文章ID');
                    continue;
                }

                $content = $post->post_content;
                $excerpt = $this->generate_smart_excerpt($content, $options);

                if (!is_wp_error($excerpt)) {
                    // 保存摘要到文章
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_excerpt' => $excerpt,
                    ));
                }

                $results[$post_id] = $excerpt;

                // 避免API限制，稍作延迟
                usleep(500000); // 0.5秒
            }
        }

        return $results;
    }

    /**
     * 定时任务：自动生成缺失摘要
     */
    public function auto_generate_missing_excerpts()
    {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 这是必要的查询，已优化
                array(
                    'key' => '_auto_excerpt_generated',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        $posts_query = new WP_Query($args);

        if ($posts_query->have_posts()) {
            while ($posts_query->have_posts()) {
                $posts_query->the_post();
                $post_id = get_the_ID();

                // 获取自动摘要配置
                $options = get_option('saiita_ai_content_toolkit_auto_excerpt_options', array());

                $excerpt = $this->generate_smart_excerpt(get_the_content(), $options);

                if (!is_wp_error($excerpt)) {
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_excerpt' => $excerpt,
                    ));

                    // 标记已生成
                    update_post_meta($post_id, '_auto_excerpt_generated', true);
                    update_post_meta($post_id, '_auto_excerpt_generated_at', current_time('mysql'));
                }
            }
            wp_reset_postdata();
        }
    }

    /**
     * 获取摘要生成统计
     */
    public function get_generation_stats()
    {
        global $wpdb;

        // 统计已生成摘要的文章数
        $generated_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
            WHERE meta_key = '_auto_excerpt_generated'
            AND meta_value = '1'
        "); // phpcs:ignore PSR2.Methods.FunctionCallSignature.CloseBracketLine

        // 统计今日生成数量
        $today = current_time('Y-m-d');
        $today_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                WHERE meta_key = '_auto_excerpt_generated_at'
                AND DATE(meta_value) = %s",
                $today
            )
        );

        // 获取最常用的摘要长度
        $length_stats = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT meta_value as excerpt_length, COUNT(*) as count
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_excerpt_length'
            AND p.post_status = 'publish'
            GROUP BY meta_value
            ORDER BY count DESC
            LIMIT 5
        "); // phpcs:ignore PSR2.Methods.FunctionCallSignature.CloseBracketLine

        return array(
            'total_generated' => intval($generated_count),
            'generated_today' => intval($today_count),
            'length_distribution' => $length_stats,
        );
    }

    /**
     * 记录摘要生成日志
     */
    private function log_excerpt_generation($content, $excerpt, $options, $ai_result)
    {
        Saiita_AI_Toolkit_Logger::info(
            'AI摘要生成成功',
            'excerpt_generator',
            array(
                'content_length' => strlen($content),
                'excerpt_length' => strlen($excerpt),
                'style' => $options['style'],
                'tone' => $options['tone'],
                'provider' => $ai_result['metadata']['model'] ?? 'unknown',
                'tokens_used' => $ai_result['tokens_used']['total_tokens'] ?? 0,
            )
        );
    }

    /**
     * 注册定时任务
     */
    public function register_cron_jobs()
    {
        // 每小时检查一次需要生成摘要的文章
        if (!wp_next_scheduled('saiita_auto_excerpt_cron_job')) {
            wp_schedule_event(time(), 'hourly', 'saiita_auto_excerpt_cron_job');
        }

        add_action('saiita_auto_excerpt_cron_job', array($this, 'auto_generate_missing_excerpts'));
    }

    /**
     * 清理定时任务
     */
    public function deactivate()
    {
        wp_clear_scheduled_hook('saiita_auto_excerpt_cron_job');
    }
}
