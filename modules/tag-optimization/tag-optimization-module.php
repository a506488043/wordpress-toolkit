<?php
/**
 * Tag Optimization Module - 标签优化模块
 *
 * 通过AI分析标签下的文章，为标签生成描述
 *
 * @version 1.0.0
 * @author Saiita AI Content Toolkit
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tag Optimization Module 主类
 */
class Saiita_AI_Toolkit_Tag_Optimization
{
    use Saiita_AI_Toolkit_Singleton;

    /**
     * 模块设置
     */
    private $settings = array();

    /**
     * 数据库管理器实例
     */
    private $db_manager = null;

    /**
     * 缓存管理器实例
     */
    private $cache_manager = null;

    /**
     * 加载设置
     */
    private function load_settings()
    {
        $default_settings = array(
            'auto_generate' => true,
            'description_length' => 100,
            'analyze_articles_count' => 10,
            'min_articles_count' => 3
        );

        $saved_settings = get_option('saiita_ai_content_toolkit_tag_optimization_settings', array());
        $this->settings = wp_parse_args($saved_settings, $default_settings);
    }

    /**
     * 初始化钩子
     */
    protected function init($args = array())
    {
        $this->db_manager = new Saiita_AI_Toolkit_Database_Manager();
        $this->cache_manager = new Saiita_AI_Toolkit_Cache_Manager();
        $this->load_settings();
        $this->init_hooks();
    }

    /**
     * 初始化钩子
     */
    private function init_hooks()
    {
        // WordPress后台脚本和样式（仅在管理页面加载）
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // AJAX处理
        add_action('wp_ajax_tag_optimization_generate_description', array($this, 'ajax_generate_description'));
        add_action('wp_ajax_tag_optimization_batch_generate', array($this, 'ajax_batch_generate'));
        add_action('wp_ajax_tag_optimization_get_tags_list', array($this, 'ajax_get_tags_list'));
        add_action('wp_ajax_tag_optimization_get_statistics', array($this, 'ajax_get_statistics'));

        // 前端脚本
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * 激活模块
     */
    public function activate()
    {


        try {
            // 创建默认设置（仅在不存在时）
            if (!get_option('saiita_ai_content_toolkit_tag_optimization_settings')) {
                add_option('saiita_ai_content_toolkit_tag_optimization_settings', $this->settings);
            } else {
            }
        } catch (Exception $e) {
        }
    }

    /**
     * 停用模块
     */
    public function deactivate()
    {
        // 清理缓存
        wp_cache_flush();
    }

    /**
     * 加载管理后台脚本和样式
     */
    public function admin_enqueue_scripts($hook)
    {
        // 只在标签优化页面加载
        if (strpos($hook, 'saiita-ai-content-toolkit-tag-optimization') === false) {
            return;
        }
            // 加载共享管理后台样式
            wp_enqueue_style(
                'saiita-ai-content-toolkit-shared-admin-styles',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'assets/css/shared-admin-styles.css',
                array(),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            // 使用统一的模块CSS
            wp_enqueue_style(
                'saiita-ai-content-toolkit-tag-optimization-admin',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/tag-optimization/assets/css/admin.css',
                array('saiita-ai-content-toolkit-shared-admin-styles'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION
            );

            // 加载统一的模块JavaScript
            wp_enqueue_script(
                'saiita-ai-content-toolkit-tag-optimization-admin',
                SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_URL . 'modules/tag-optimization/assets/js/admin.js',
                array('jquery'),
                SAIITA_AI_CONTENT_TOOLKIT_VERSION . '.' . time(), // 添加时间戳强制刷新缓存
                true
            );

            // 传递变量到脚本
            $stats = array(
                'total_tags' => 0,
                'tags_with_description' => 0,
                'tags_without_description' => 0,
                'coverage_rate' => 0
            );
            try {
                $stats = $this->get_statistics();
            } catch (Exception $e) {
                // 静默处理，使用默认值
            }
            $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
            // 免费版本无使用限制
            wp_localize_script('saiita-ai-content-toolkit-tag-optimization-admin', 'SaiitaAIToolkitTagOptAdmin', array(
                'nonce' => wp_create_nonce('saiita_tag_optimization_nonce'),
                'batchNonce' => wp_create_nonce('saiita_tag_optimization_batch'),
                'stats' => $stats,
                'isPro' => $license_manager->is_pro(),
                'remaining' => 999999, // 免费版本无限制
                'limit' => 999999 // 免费版本无限制
            ));
    }

    /**
     * 加载前端脚本和样式
     */
    public function enqueue_scripts()
    {
        // 前端功能脚本（如果需要）
    }

    /**
     * 获取设置
     */
    public function get_settings()
    {
        return $this->settings;
    }

    /**
     * 更新设置
     */
    public function update_settings($new_settings)
    {
        $this->settings = wp_parse_args($new_settings, $this->settings);
        update_option('saiita_ai_content_toolkit_tag_optimization_settings', $this->settings);
    }

    /**
     * 获取标签列表
     */
    public function get_tags_list($page = 1, $per_page = 20, $status = 'all')
    {


        // 获取所有标签
        $args = array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'orderby' => 'count',
            'order' => 'DESC'
        );

        $tags = get_terms($args);
        // 使用兼容的方式获取总数
        $total_tags = function_exists('wp_get_term_count') ? wp_get_term_count('post_tag') : wp_count_terms(array('taxonomy' => 'post_tag'));

        $filtered_tags = array();

        foreach ($tags as $tag) {
            $has_description = !empty($tag->description);

            // 根据状态筛选
            if ($status === 'with_description' && !$has_description) {
                continue;
            } elseif ($status === 'without_description' && $has_description) {
                continue;
            }

            // 获取标签下的文章数量
            $post_count = $tag->count;

            $filtered_tags[] = array(
                'ID' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'description' => $tag->description,
                'description_length' => mb_strlen($tag->description),
                'post_count' => $post_count,
                'has_description' => $has_description,
                'edit_url' => get_edit_term_link($tag->term_id, 'post_tag'),
                'view_url' => get_term_link($tag->term_id, 'post_tag')
            );
        }

        $total_filtered = count($filtered_tags);
        $max_pages = ceil($total_tags / $per_page);



        return array(
            'tags' => $filtered_tags,
            'total' => $total_tags,
            'pages' => $max_pages,
            'current_page' => $page,
            'per_page' => $per_page
        );
    }

    /**
     * 获取统计信息
     */
    public function get_statistics()
    {
        // 使用兼容的方式获取总数
        $total_tags = function_exists('wp_get_term_count') ? wp_get_term_count('post_tag') : wp_count_terms(array('taxonomy' => 'post_tag'));

        // 获取有描述和无描述的标签数量
        $tags_with_description = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- 这是必要的查询，已优化
                array(
                    'key' => 'description',
                    'value' => '',
                    'compare' => '!='
                )
            )
        ));

        $tags_with_description_count = is_wp_error($tags_with_description) ? 0 : count($tags_with_description);
        $tags_without_description_count = $total_tags - $tags_with_description_count;
        $coverage_rate = $total_tags > 0 ? round(($tags_with_description_count / $total_tags) * 100, 2) : 0;



        return array(
            'total_tags' => $total_tags,
            'tags_with_description' => $tags_with_description_count,
            'tags_without_description' => $tags_without_description_count,
            'coverage_rate' => $coverage_rate
        );
    }

    /**
     * 使用AI为标签生成描述
     */
    public function generate_tag_description($tag_id)
    {
        // 检查AI功能是否可用
        if (!function_exists('saiita_ai_content_toolkit_is_ai_available') || !saiita_ai_content_toolkit_is_ai_available()) {
            return array('success' => false, 'message' => __('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit'));
        }

        try {
            $tag = get_term($tag_id, 'post_tag');
            if (!$tag) {
                return array('success' => false, 'message' => __('标签不存在', 'saiita-ai-content-toolkit'));
            }

            // 获取使用该标签的文章
            $posts = get_posts(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $this->settings['analyze_articles_count'],
                'tag' => $tag->slug,
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            // 构建上下文（有文章则分析文章，无文章则基于标签名生成）
            if (empty($posts)) {
                $articles_content = '该标签下暂无文章，请根据标签名称推断其含义。';
                $keywords_text = $tag->name;
            } else {
                // 分析文章内容
                $articles_content = '';
                $keywords = array();

                foreach ($posts as $post) {
                    $articles_content .= "文章标题：{$post->post_title}\n";
                    $articles_content .= "文章内容：" . mb_substr(wp_strip_all_tags($post->post_content), 0, 300) . "\n\n";

                    $content = ($post->post_title ?? '') . ' ' . ($post->post_content ?? '');
                    $words = preg_split('/[\s，。！？；：""\'\'（）【】]/u', $content);
                    foreach ($words as $word) {
                        $word = trim($word);
                        if (mb_strlen($word) >= 2 && mb_strlen($word) <= 6 && !preg_match('/[0-9]/', $word)) {
                            if (isset($keywords[$word])) {
                                $keywords[$word]++;
                            } else {
                                $keywords[$word] = 1;
                            }
                        }
                    }
                }

                unset($keywords[$tag->name]);
                arsort($keywords);
                $top_keywords = array_slice(array_keys($keywords), 0, 8);
                $keywords_text = implode('、', $top_keywords);
            }

            // 构建AI提示词 - 使用动态提示词设置
            if (function_exists('saiita_ai_content_toolkit_get_prompt')) {
                $prompt = saiita_ai_content_toolkit_get_prompt('tag_optimization', array(
                    'tag_name' => $tag->name,
                    'tag_content' => $articles_content,
                    'keywords' => $keywords_text
                ));
            } else {
                // 降级到默认提示词
                $prompt = "请为以下标签生成一个简洁准确的描述：

标签名称：{$tag->name}

使用该标签的文章主要内容：
{$articles_content}

相关关键词：{$keywords_text}

请返回一个1-2句话的标签描述，要求：
1. 准确概括该标签的用途和含义
2. 语言简洁明了，适合用户理解
3. 30-60字之间
4. 只返回描述内容，不要包含其他解释";
            }

            // 调用AI服务（给足 max_tokens 余量，推理模型的 reasoning 会消耗 token 预算）
            $response = saiita_ai_content_toolkit_call_ai_api(
                $prompt,
                array(
                    'max_tokens' => 500,
                    'temperature' => 0.3
                )
            );

            if (is_wp_error($response)) {
                if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('AI API Error (tag): ' . $response->get_error_code() . ' - ' . $response->get_error_message()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                }
            }

            if (!is_wp_error($response) && !empty($response)) {
                $description = trim($response);

                // 清理描述
                $description = preg_replace('/[""\'\']/', '', $description);
                $description = preg_replace('/[\r\n]+/', ' ', $description);
                $description = trim($description);

                if (!empty($description)) {
                    return array(
                        'success' => true,
                        /* translators: %s: value */
                        'message' => sprintf(esc_html__('成功为标签"%s"生成描述', 'saiita-ai-content-toolkit'), $tag->name),
                        'description' => $description,
                        'tag_id' => $tag_id,
                        'tag_name' => $tag->name
                    );
                } else {
                    return array('success' => false, 'message' => __('AI未能生成有效描述', 'saiita-ai-content-toolkit'));
                }
            } else {
                return array('success' => false, 'message' => __('AI服务响应异常', 'saiita-ai-content-toolkit'));
            }
        } catch (Exception $e) {
            return array('success' => false, 'message' => __('AI生成标签描述失败：', 'saiita-ai-content-toolkit') . $e->getMessage());
        }
    }

    /**
     * 应用标签描述
     */
    public function apply_tag_description($tag_id, $description)
    {
        if (!$tag_id || empty($description)) {
            return array('success' => false, 'message' => __('参数无效', 'saiita-ai-content-toolkit'));
        }

        $tag = get_term($tag_id, 'post_tag');
        if (!$tag) {
            return array('success' => false, 'message' => __('标签不存在', 'saiita-ai-content-toolkit'));
        }

        try {
            // 更新标签描述
            wp_update_term($tag_id, 'post_tag', array(
                'description' => $description
            ));

            return array(
                'success' => true,
                'message' => __('标签描述更新成功', 'saiita-ai-content-toolkit'),
                'tag_id' => $tag_id,
                'tag_name' => $tag->name
            );
        } catch (Exception $e) {
            return array('success' => false, 'message' => __('标签描述更新失败：', 'saiita-ai-content-toolkit') . $e->getMessage());
        }
    }

    /**
     * 批量生成标签描述
     */
    public function batch_generate_descriptions()
    {


        // 检查是否启用AI生成
        if (!saiita_ai_content_toolkit_is_ai_available()) {
            return array(
                'success' => false,
                'message' => __('AI功能未配置，请前往 AI设置 页面配置API密钥后使用。', 'saiita-ai-content-toolkit')
            );
        }

        try {
            $max_execution_time = ini_get('max_execution_time');
            // 增加执行时间限制到600秒（10分钟），如果允许的话
            if ($max_execution_time < 600) {
                @set_time_limit(600); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
                $max_execution_time = 600;
            }
            $start_time = time();
            $processed_count = 0;
            $success_count = 0;
            $error_count = 0;
            $first_error = '';

            // 获取所有无描述的标签
            $tags = get_terms(array(
                'taxonomy' => 'post_tag',
                'hide_empty' => false
            ));

            // 过滤出无描述的标签
            $tags_without_description = array();
            foreach ($tags as $tag) {
                if (empty($tag->description)) {
                    $tags_without_description[] = $tag;
                }
            }
            $tags = $tags_without_description;

            if (is_wp_error($tags)) {
                return array(
                    'success' => false,
                    'message' => __('获取标签列表失败：', 'saiita-ai-content-toolkit') . $tags->get_error_message()
                );
            }

            // 过滤掉文章数量太少的标签（批量描述生成不过滤，0篇文章的标签也需要描述）
            $valid_tags = $tags;

            if (empty($valid_tags)) {
                return array(
                    'success' => true,
                    'message' => __('没有符合条件的标签需要处理', 'saiita-ai-content-toolkit'),
                    'processed_count' => 0,
                    'success_count' => 0,
                    'error_count' => 0
                );
            }

            foreach ($valid_tags as $tag) {
                if ((time() - $start_time) >= ($max_execution_time - 10)) {
                    break; // 避免超时
                }

                $processed_count++;

                try {
                    // 检查使用次数限制
                    $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
                    $can_use = $license_manager->can_use('tag_optimization');
                    if (!$can_use['allowed']) {
                        break; // 达到上限，停止批量处理
                    }

                    // 生成描述
                    $result = $this->generate_tag_description($tag->term_id);

                    if ($result && $result['success']) {
                        // 应用描述
                        $apply_result = $this->apply_tag_description($tag->term_id, $result['description']);

                        if ($apply_result && $apply_result['success']) {
                            $success_count++;
                            // 增加使用次数
                            $license_manager->increment_usage('tag_optimization');
                        } else {
                            $error_count++;
                        }
                    } else {
                        $error_count++;
                        if (empty($first_error)) {
                            $first_error = is_array($result) ? ($result['message'] ?? '未知错误') : '生成失败';
                        }
                    }
                } catch (Exception $e) {
                    $error_count++;
                    if (empty($first_error)) {
                        $first_error = $e->getMessage();
                    }
                }
            }

            $result = array(
                'success' => true,
                'processed_count' => $processed_count,
                'success_count' => $success_count,
                'error_count' => $error_count,
                'first_error' => $first_error,
                'message' => sprintf(
                    /* translators: 1: processed, 2: success, 3: fail */
                    esc_html__('批量生成标签描述完成！处理：%1$d个，成功：%2$d个，失败：%3$d个', 'saiita-ai-content-toolkit'),
                    $processed_count,
                    $success_count,
                    $error_count
                )
            );

            return $result;
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('批量生成标签描述失败：', 'saiita-ai-content-toolkit') . $e->getMessage()
            );
        }
    }

    /**
     * AJAX处理生成标签描述
     */
    public function ajax_generate_description()
    {
        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_tag_optimization_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        // 清理输入数据
        $sanitized_data = Saiita_AI_Toolkit_Security_Validator::sanitize_post_data([
            'tag_id' => 'int'
        ]);
        $tag_id = $sanitized_data['tag_id'];

        // 验证必填字段
        $validation = Saiita_AI_Toolkit_Security_Validator::validate_required_fields(
            ['tag_id' => $tag_id],
            ['tag_id']
        );

        if (!$validation['valid']) {
            wp_send_json_error(array('message' => $validation['errors'][0]));
            return;
        }

        try {
            // 检查使用次数限制
            $license_manager = Saiita_AI_Toolkit_License_Manager::get_instance();
            $can_use = $license_manager->can_use('tag_optimization');
            if (!$can_use['allowed']) {
                wp_send_json_error(array('message' => $can_use['reason']));
                return;
            }

            // 生成描述
            $result = $this->generate_tag_description($tag_id);

            if ($result['success']) {
                // 自动应用生成的描述
                $apply_result = $this->apply_tag_description($tag_id, $result['description']);

                if ($apply_result['success']) {
                    // 增加使用次数
                    $license_manager->increment_usage('tag_optimization');

                    wp_send_json_success(array(
                        'tag_id' => $tag_id,
                        'tag_name' => $result['tag_name'],
                        'description' => $result['description'],
                        'message' => $apply_result['message'],
                        'remaining' => 999999, // 免费版本无限制
                        'limit' => 999999 // 免费版本无限制
                    ));
                } else {
                    wp_send_json_error(array('message' => $apply_result['message']));
                }
            } else {
                wp_send_json_error(array('message' => $result['message']));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('生成失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX处理批量生成
     */
    public function ajax_batch_generate()
    {
        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_tag_optimization_batch')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        try {
            $result = $this->batch_generate_descriptions();

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(array('message' => $result['message']));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('批量生成失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX获取标签列表
     */
    public function ajax_get_tags_list()
    {
        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_tag_optimization_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        try {
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification -- nonce verified in AJAX handler

            $tags_list = $this->get_tags_list($page, $per_page, $status);

            wp_send_json_success($tags_list);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('获取标签列表失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * AJAX获取统计信息
     */
    public function ajax_get_statistics()
    {
        // 验证nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash(isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '')), 'saiita_tag_optimization_nonce')) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            wp_send_json_error(array('message' => '安全验证失败'));
            return;
        }

        try {
            $statistics = $this->get_statistics();
            wp_send_json_success($statistics);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('获取统计信息失败：', 'saiita-ai-content-toolkit') . $e->getMessage()));
        }
    }

    /**
     * 显示管理页面
     */
    public function admin_page()
    {
        // 加载管理页面模板
        require_once SAIITA_AI_CONTENT_TOOLKIT_PLUGIN_PATH . 'modules/tag-optimization/admin/admin-page.php';
        $admin_page = Saiita_AI_Toolkit_Tag_Optimization_Admin::get_instance();
        $admin_page->admin_page();
    }
}

// 注册插件激活和停用钩子
register_activation_hook(__FILE__, array('Saiita_AI_Toolkit_Tag_Optimization', 'activate'));
register_deactivation_hook(__FILE__, array('Saiita_AI_Toolkit_Tag_Optimization', 'deactivate'));
