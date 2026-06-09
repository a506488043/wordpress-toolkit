<?php
/**
 * WordPress 6.9 Abilities API Integration
 * 集成WordPress 6.9的Abilities API功能
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_Abilities_API_Integration
{
    use Saiita_AI_Toolkit_Singleton;

    /**
     * AI管理器
     */
    private $ai_manager;

    /**
     * 注册的能力列表
     */
    private $registered_abilities = array();

    /**
     * 初始化Abilities API集成
     */
    protected function init($args = array())
    {
        $this->ai_manager = Saiita_AI_Toolkit_AI_Manager::get_instance();

        // 检查WordPress版本是否支持Abilities API
        if ($this->is_wordpress_69_or_higher()) {
            // 在正确的钩子上注册能力分类（分类必须先于能力注册）
            add_action('wp_abilities_api_categories_init', array($this, 'register_ability_categories'));
            // 在正确的钩子上注册能力
            add_action('wp_abilities_api_init', array($this, 'register_abilities_on_init'));
        }
    }

    /**
     * 在 wp_abilities_api_categories_init 钩子上注册所有能力分类
     *
     * @since 2.0.23
     */
    public function register_ability_categories()
    {
        if (!function_exists('wp_register_ability_category')) {
            return;
        }

        $categories = array(
            'content'          => array(
                'label'       => __('内容生成', 'saiita-ai-content-toolkit'),
                'description' => __('AI内容生成和摘要相关的能力', 'saiita-ai-content-toolkit'),
            ),
            'optimization'     => array(
                'label'       => __('内容优化', 'saiita-ai-content-toolkit'),
                'description' => __('优化内容可读性和SEO表现的能力', 'saiita-ai-content-toolkit'),
            ),
            'seo'              => array(
                'label'       => __('SEO分析', 'saiita-ai-content-toolkit'),
                'description' => __('SEO分析和改进建议相关的能力', 'saiita-ai-content-toolkit'),
            ),
            'editor'           => array(
                'label'       => __('编辑器', 'saiita-ai-content-toolkit'),
                'description' => __('块编辑器中使用的AI能力', 'saiita-ai-content-toolkit'),
            ),
            'media'            => array(
                'label'       => __('媒体', 'saiita-ai-content-toolkit'),
                'description' => __('媒体和图片相关的AI能力', 'saiita-ai-content-toolkit'),
            ),
            'bulk-operations'  => array(
                'label'       => __('批量操作', 'saiita-ai-content-toolkit'),
                'description' => __('批量处理和操作相关的能力', 'saiita-ai-content-toolkit'),
            ),
            'review'           => array(
                'label'       => __('内容审核', 'saiita-ai-content-toolkit'),
                'description' => __('内容审核和质量检查相关的能力', 'saiita-ai-content-toolkit'),
            ),
            'recommendation'   => array(
                'label'       => __('内容推荐', 'saiita-ai-content-toolkit'),
                'description' => __('内容推荐和发现相关的能力', 'saiita-ai-content-toolkit'),
            ),
            'search'           => array(
                'label'       => __('智能搜索', 'saiita-ai-content-toolkit'),
                'description' => __('AI增强搜索相关的能力', 'saiita-ai-content-toolkit'),
            ),
        );

        foreach ($categories as $slug => $args) {
            if (function_exists('wp_register_ability_category')) {
                call_user_func('wp_register_ability_category', $slug, $args);
            }
        }
    }

    /**
     * 在 wp_abilities_api_init 钩子上注册所有能力
     */
    public function register_abilities_on_init()
    {
        $this->register_core_abilities();
        $this->register_block_editor_abilities();
        $this->register_admin_abilities();
        $this->register_frontend_abilities();
    }

    /**
     * 检查WordPress版本
     */
    private function is_wordpress_69_or_higher()
    {
        return version_compare(get_bloginfo('version'), '6.9', '>=');
    }

    /**
     * 注册核心AI能力
     */
    private function register_core_abilities()
    {
        // 内容生成能力
        $this->register_ability('content_generation', array(
            'label' => __('AI内容生成', 'saiita-ai-content-toolkit'),
            'description' => __('使用AI生成各种类型的优质内容', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-edit',
            'category' => 'content',
            'callback' => array($this, 'ability_content_generation'),
            'schema' => array(
                'type' => 'object',
                'properties' => array(
                    'prompt' => array(
                        'type' => 'string',
                        'description' => '内容生成提示词'
                    ),
                    'content_type' => array(
                        'type' => 'string',
                        'enum' => array('blog_post', 'article', 'page', 'product_description', 'social_media'),
                        'default' => 'blog_post'
                    ),
                    'tone' => array(
                        'type' => 'string',
                        'enum' => array('professional', 'casual', 'formal', 'friendly'),
                        'default' => 'professional'
                    ),
                    'length' => array(
                        'type' => 'integer',
                        'minimum' => 50,
                        'maximum' => 5000,
                        'default' => 500
                    )
                ),
                'required' => array('prompt')
            )
        ));

        // 内容优化能力
        $this->register_ability('content_optimization', array(
            'label' => __('AI内容优化', 'saiita-ai-content-toolkit'),
            'description' => __('优化现有内容的可读性和SEO表现', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-performance',
            'category' => 'optimization',
            'callback' => array($this, 'ability_content_optimization'),
            'schema' => array(
                'type' => 'object',
                'properties' => array(
                    'content' => array(
                        'type' => 'string',
                        'description' => '需要优化的内容'
                    ),
                    'optimization_type' => array(
                        'type' => 'string',
                        'enum' => array('seo', 'readability', 'engagement', 'clarity', 'all'),
                        'default' => 'all'
                    ),
                    'target_audience' => array(
                        'type' => 'string',
                        'description' => '目标受众描述'
                    )
                ),
                'required' => array('content')
            )
        ));

        // SEO分析能力
        $this->register_ability('seo_analysis', array(
            'label' => __('AI SEO分析', 'saiita-ai-content-toolkit'),
            'description' => __('深入分析内容的SEO表现并提供改进建议', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-search',
            'category' => 'seo',
            'callback' => array($this, 'ability_seo_analysis'),
            'schema' => array(
                'type' => 'object',
                'properties' => array(
                    'content' => array(
                        'type' => 'string',
                        'description' => '要分析的内容'
                    ),
                    'focus_keyword' => array(
                        'type' => 'string',
                        'description' => '主要关键词'
                    ),
                    'analysis_depth' => array(
                        'type' => 'string',
                        'enum' => array('basic', 'comprehensive', 'advanced'),
                        'default' => 'comprehensive'
                    )
                ),
                'required' => array('content')
            )
        ));

        // 摘要生成能力
        $this->register_ability('excerpt_generation', array(
            'label' => __('智能摘要生成', 'saiita-ai-content-toolkit'),
            'description' => __('为文章生成吸引人的摘要', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-text-page',
            'category' => 'content',
            'callback' => array($this, 'ability_excerpt_generation'),
            'schema' => array(
                'type' => 'object',
                'properties' => array(
                    'content' => array(
                        'type' => 'string',
                        'description' => '原文内容'
                    ),
                    'excerpt_style' => array(
                        'type' => 'string',
                        'enum' => array('summary', 'highlights', 'questions', 'bullet_points'),
                        'default' => 'summary'
                    ),
                    'length' => array(
                        'type' => 'integer',
                        'minimum' => 50,
                        'maximum' => 500,
                        'default' => 150
                    )
                ),
                'required' => array('content')
            )
        ));
    }

    /**
     * 注册块编辑器能力
     */
    private function register_block_editor_abilities()
    {
        // 块内容建议
        $this->register_ability('block_content_suggestion', array(
            'label' => __('块内容建议', 'saiita-ai-content-toolkit'),
            'description' => __('为编辑器中的块提供AI内容建议', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-lightbulb',
            'category' => 'editor',
            'context' => array('block_editor'),
            'callback' => array($this, 'ability_block_content_suggestion')
        ));

        // 块样式推荐
        $this->register_ability('block_style_recommendation', array(
            'label' => __('块样式推荐', 'saiita-ai-content-toolkit'),
            'description' => __('基于内容推荐最适合的块样式', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-palette',
            'category' => 'editor',
            'context' => array('block_editor'),
            'callback' => array($this, 'ability_block_style_recommendation')
        ));

        // 图片生成建议
        $this->register_ability('image_generation_prompt', array(
            'label' => __('图片生成提示词', 'saiita-ai-content-toolkit'),
            'description' => __('为内容生成匹配的图片提示词', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-format-image',
            'category' => 'media',
            'callback' => array($this, 'ability_image_generation_prompt')
        ));
    }

    /**
     * 注册管理后台能力
     */
    private function register_admin_abilities()
    {
        // 批量内容生成
        $this->register_ability('batch_content_generation', array(
            'label' => __('批量内容生成', 'saiita-ai-content-toolkit'),
            'description' => __('批量生成多篇文章或内容', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-database-add',
            'category' => 'bulk-operations',
            'context' => array('admin'),
            'permission' => 'manage_options',
            'callback' => array($this, 'ability_batch_content_generation')
        ));

        // 内容审核
        $this->register_ability('content_review', array(
            'label' => __('AI内容审核', 'saiita-ai-content-toolkit'),
            'description' => __('使用AI审核和改进内容质量', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-visibility',
            'category' => 'review',
            'context' => array('admin'),
            'permission' => 'edit_posts',
            'callback' => array($this, 'ability_content_review')
        ));
    }

    /**
     * 注册前端能力
     */
    private function register_frontend_abilities()
    {
        // 内容推荐
        $this->register_ability('content_recommendation', array(
            'label' => __('内容推荐', 'saiita-ai-content-toolkit'),
            'description' => __('基于当前内容推荐相关内容', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-list-view',
            'category' => 'recommendation',
            'context' => array('frontend'),
            'callback' => array($this, 'ability_content_recommendation')
        ));

        // 智能搜索
        $this->register_ability('intelligent_search', array(
            'label' => __('智能搜索', 'saiita-ai-content-toolkit'),
            'description' => __('使用AI增强搜索功能', 'saiita-ai-content-toolkit'),
            'icon' => 'dashicons-search',
            'category' => 'search',
            'context' => array('frontend'),
            'callback' => array($this, 'ability_intelligent_search')
        ));
    }

    /**
     * 注册AI能力
     */
    public function register_ability($name, $config)
    {
        if (!function_exists('wp_register_ability')) {
            return;
        }

        $prefixed_name = 'saiita-ai-content-toolkit/' . str_replace('_', '-', $name);

        $this->registered_abilities[$prefixed_name] = $config;

        // 转换配置为Abilities API格式
        $ability_config = array(
            'label'               => $config['label'],
            'description'         => $config['description'],
            'category'            => str_replace('_', '-', $config['category'] ?? 'general'),
            'execute_callback'    => $config['callback'],
            'input_schema'        => $config['schema'] ?? array(),
        );

        // WP 6.9 不支持 icon 参数，移除
        // 保留向后兼容：旧版 WP 可能通过过滤器支持

        // 添加上下文限制
        if (isset($config['context'])) {
            $ability_config['context'] = $config['context'];
        }

        // 添加权限回调 - 默认要求 edit_posts 权限
        if (isset($config['permission_callback'])) {
            $ability_config['permission_callback'] = $config['permission_callback'];
        } elseif (isset($config['permission'])) {
            // 向后兼容：如果使用旧格式的 permission 键
            $permission = $config['permission'];
            $ability_config['permission_callback'] = function () use ($permission) {
                return current_user_can($permission);
            };
        } else {
            // 默认要求编辑文章的权限
            $ability_config['permission_callback'] = function () {
                return current_user_can('edit_posts');
            };
        }

        call_user_func('wp_register_ability', $prefixed_name, $ability_config);
    }

    /**
     * 能力回调：内容生成
     */
    public function ability_content_generation($params)
    {
        $prompt = $params['prompt'] ?? '';
        $content_type = $params['content_type'] ?? 'blog_post';
        $tone = $params['tone'] ?? 'professional';
        $length = $params['length'] ?? 500;

        if (empty($prompt)) {
            return new WP_Error('missing_prompt', '请提供内容生成提示词');
        }

        // 构建专门的系统提示
        $system_prompt = $this->build_content_generation_prompt($content_type, $tone, $length);

        $options = array(
            'system_prompt' => $system_prompt,
            'max_tokens' => $length * 2,
            'temperature' => 0.7,
            'context' => array(
                'task' => 'content_generation',
                'content_type' => $content_type,
                'tone' => $tone,
            ),
        );

        return $this->ai_manager->generate_content($prompt, $options);
    }

    /**
     * 能力回调：内容优化
     */
    public function ability_content_optimization($params)
    {
        $content = $params['content'] ?? '';
        $optimization_type = $params['optimization_type'] ?? 'all';
        $target_audience = $params['target_audience'] ?? '';

        if (empty($content)) {
            return new WP_Error('missing_content', '请提供需要优化的内容');
        }

        $system_prompt = $this->build_optimization_prompt($optimization_type, $target_audience);

        $prompt = "请优化以下内容：\n\n{$content}";

        $options = array(
            'system_prompt' => $system_prompt,
            'max_tokens' => strlen($content) + 500,
            'temperature' => 0.6,
            'context' => array(
                'task' => 'content_optimization',
                'optimization_type' => $optimization_type,
            ),
        );

        return $this->ai_manager->generate_content($prompt, $options);
    }

    /**
     * 能力回调：SEO分析
     */
    public function ability_seo_analysis($params)
    {
        $content = $params['content'] ?? '';
        $focus_keyword = $params['focus_keyword'] ?? '';
        $analysis_depth = $params['analysis_depth'] ?? 'comprehensive';

        if (empty($content)) {
            return new WP_Error('missing_content', '请提供要分析的内容');
        }

        $system_prompt = $this->build_seo_analysis_prompt($analysis_depth);

        $prompt = "请分析以下内容的SEO表现：\n\n{$content}";

        if (!empty($focus_keyword)) {
            $prompt .= "\n\n主要关键词：{$focus_keyword}";
        }

        $options = array(
            'system_prompt' => $system_prompt,
            'max_tokens' => 1000,
            'temperature' => 0.3,
            'context' => array(
                'task' => 'seo_analysis',
                'focus_keyword' => $focus_keyword,
                'analysis_depth' => $analysis_depth,
            ),
        );

        return $this->ai_manager->generate_content($prompt, $options);
    }

    /**
     * 能力回调：摘要生成
     */
    public function ability_excerpt_generation($params)
    {
        $content = $params['content'] ?? '';
        $excerpt_style = $params['excerpt_style'] ?? 'summary';
        $length = $params['length'] ?? 150;

        if (empty($content)) {
            return new WP_Error('missing_content', '请提供原文内容');
        }

        $system_prompt = $this->build_excerpt_prompt($excerpt_style, $length);

        $options = array(
            'system_prompt' => $system_prompt,
            'max_tokens' => $length * 2,
            'temperature' => 0.6,
            'context' => array(
                'task' => 'excerpt_generation',
                'excerpt_style' => $excerpt_style,
            ),
        );

        return $this->ai_manager->generate_content($content, $options);
    }

    /**
     * 其他能力回调实现
     */
    public function ability_block_content_suggestion($params)
    {
        $prompt = $params['prompt'] ?? '';
        $block_type = $params['block_type'] ?? 'paragraph';

        if (empty($prompt)) {
            return new WP_Error('missing_prompt', '请提供内容建议提示词');
        }

        $system_prompt = sprintf(
            '你是一个WordPress块编辑器助手，为"%s"块提供内容建议。',
            $block_type
        );

        $options = array(
            'system_prompt' => $system_prompt,
            'max_tokens' => 500,
            'temperature' => 0.7,
        );

        return $this->ai_manager->generate_content($prompt, $options);
    }

    public function ability_block_style_recommendation($params)
    {
        $content = $params['content'] ?? '';
        $block_type = $params['block_type'] ?? 'paragraph';

        if (empty($content)) {
            return new WP_Error('missing_content', '请提供块内容');
        }

        $prompt = sprintf(
            "分析以下\"%s\"块的内容特征，推荐最适合的视觉样式（如背景色、边距、字体大小等），并说明理由：\n\n%s",
            $block_type,
            mb_substr($content, 0, 500)
        );

        $result = $this->ai_manager->generate_content($prompt, array(
            'system_prompt' => '你是一个Web设计专家，请基于内容类型和长度给出样式建议。回复JSON格式：{"style": "...,"reason": "..."}',
            'max_tokens' => 300,
            'temperature' => 0.5,
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        $response = $result['content'] ?? '';
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return array(
            'recommended_style' => 'default',
            'reason' => $response,
        );
    }

    public function ability_image_generation_prompt($params)
    {
        $content = $params['content'] ?? '';
        $system_prompt = '你是一个专业的图片提示词生成助手，请为内容生成详细、描述性的图片提示词。';

        $prompt = "请为以下内容生成图片提示词：\n\n{$content}";

        $options = array(
            'system_prompt' => $system_prompt,
            'max_tokens' => 200,
            'temperature' => 0.8,
        );

        return $this->ai_manager->generate_content($prompt, $options);
    }

    public function ability_batch_content_generation($params)
    {
        $prompts = $params['prompts'] ?? array();
        $content_type = $params['content_type'] ?? 'blog_post';
        $tone = $params['tone'] ?? 'professional';

        if (empty($prompts) || !is_array($prompts)) {
            return new WP_Error('missing_prompts', '请提供至少一个内容生成提示词');
        }

        if (count($prompts) > 10) {
            return new WP_Error('too_many_prompts', '最多支持同时生成10篇文章');
        }

        $results = array();
        $system_prompt = $this->build_content_generation_prompt($content_type, $tone, 500);

        foreach ($prompts as $index => $prompt) {
            $result = $this->ai_manager->generate_content($prompt, array(
                'system_prompt' => $system_prompt,
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ));
            $results[] = array(
                'index' => $index,
                'content' => is_wp_error($result) ? null : ($result['content'] ?? ''),
                'error' => is_wp_error($result) ? $result->get_error_message() : null,
            );
        }

        return array(
            'status' => 'completed',
            'results' => $results,
            'successful' => count(array_filter($results, function ($r) { return empty($r['error']); })),
            'failed' => count(array_filter($results, function ($r) { return !empty($r['error']); })),
        );
    }

    public function ability_content_review($params)
    {
        $content = $params['content'] ?? '';
        $review_aspects = $params['aspects'] ?? array('grammar', 'clarity', 'engagement');

        if (empty($content)) {
            return new WP_Error('missing_content', '请提供需要审核的内容');
        }

        $prompt = "请审核以下内容，从语法、清晰度、受众吸引力等方面给出评分（0-100）和改进建议。\n\n" . mb_substr($content, 0, 3000);

        $result = $this->ai_manager->generate_content($prompt, array(
            'system_prompt' => '你是一个专业的内容审核编辑。请按JSON格式回复：{"score":85,"issues":["..."],"suggestions":["..."]}。评分标准：90-100优秀，70-89良好，50-69中等，<50需改进。',
            'max_tokens' => 600,
            'temperature' => 0.3,
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        $response = $result['content'] ?? '';
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['score'])) {
            return $decoded;
        }

        return array(
            'score' => 70,
            'issues' => array(),
            'suggestions' => array($response),
        );
    }

    public function ability_content_recommendation($params)
    {
        $post_id = $params['post_id'] ?? 0;
        $content = $params['content'] ?? '';
        $limit = min(intval($params['limit'] ?? 5), 20);

        if (empty($post_id) && empty($content)) {
            return new WP_Error('missing_params', '请提供文章ID或内容');
        }

        if ($post_id > 0) {
            $post = get_post($post_id);
            if (!$post) {
                return new WP_Error('post_not_found', '未找到指定文章');
            }
            $content = $post->post_content;
            $categories = wp_get_post_categories($post_id, array('fields' => 'ids'));
            $tags = wp_get_post_tags($post_id, array('fields' => 'ids'));
        } else {
            $categories = array();
            $tags = array();
        }

        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'rand',
        );

        if ($post_id > 0) {
            $args['post__not_in'] = array($post_id); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- small query, single exclusion
        }

        if (!empty($categories)) {
            $args['category__in'] = $categories;
        }

        $query = new WP_Query($args);
        $recommendations = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $recommendations[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'url' => get_permalink(),
                );
            }
            wp_reset_postdata();
        }

        return array(
            'recommendations' => $recommendations,
            'source' => 'category_based',
        );
    }

    public function ability_intelligent_search($params)
    {
        $query = $params['query'] ?? '';
        $search_type = $params['search_type'] ?? 'posts';
        $limit = min(intval($params['limit'] ?? 10), 50);

        if (empty($query)) {
            return new WP_Error('missing_query', '请提供搜索关键词');
        }

        $search_query = $query;

        if (isset($params['use_ai_expansion']) && $params['use_ai_expansion']) {
            $prompt = "将以下搜索关键词扩展为更详细的搜索短语，便于在WordPress中检索相关内容。只返回扩展后的搜索短语，不要额外解释：\n\n{$query}";

            $result = $this->ai_manager->generate_content($prompt, array(
                'system_prompt' => '你是一个搜索优化助手，将简短关键词扩展为完整的搜索短语。',
                'max_tokens' => 100,
                'temperature' => 0.5,
            ));

            if (!is_wp_error($result) && !empty($result['content'])) {
                $search_query .= ' ' . $result['content'];
            }
        }

        $args = array(
            's' => $search_query,
            'post_type' => $search_type === 'pages' ? 'page' : 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
        );

        $query_obj = new WP_Query($args);
        $results = array();

        if ($query_obj->have_posts()) {
            while ($query_obj->have_posts()) {
                $query_obj->the_post();
                $results[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'url' => get_permalink(),
                    'relevance' => 'standard',
                );
            }
            wp_reset_postdata();
        }

        return array(
            'results' => $results,
            'total' => count($results),
            'original_query' => $query,
            'expanded_query' => $search_query !== $query ? $search_query : null,
        );
    }

    /**
     * 构建内容生成提示
     */
    private function build_content_generation_prompt($content_type, $tone, $length)
    {
        $prompt = "你是一个专业的内容创作者，擅长撰写高质量的{$content_type}。\n\n";

        $prompt .= "写作要求：\n";
        $prompt .= "- 语调：{$tone}\n";
        $prompt .= "- 长度：约{$length}字\n";

        switch ($content_type) {
            case 'blog_post':
                $prompt .= "- 结构清晰，包含引言、正文和结论\n";
                $prompt .= "- 语言流畅，易于理解\n";
                break;
            case 'article':
                $prompt .= "- 逻辑严谨，论据充分\n";
                $prompt .= "- 信息准确，观点明确\n";
                break;
            case 'product_description':
                $prompt .= "- 突出产品特点和优势\n";
                $prompt .= "- 激发购买欲望\n";
                break;
            case 'social_media':
                $prompt .= "- 简洁有力，吸引眼球\n";
                $prompt .= "- 包含相关标签\n";
                break;
        }

        return $prompt;
    }

    /**
     * 构建优化提示
     */
    private function build_optimization_prompt($optimization_type, $target_audience)
    {
        $prompt = "你是一个专业的内容优化专家。\n\n";

        switch ($optimization_type) {
            case 'seo':
                $prompt .= "请从SEO角度优化内容，包括关键词密度、标题优化、元标签等。\n";
                break;
            case 'readability':
                $prompt .= "请优化内容的可读性，使用更清晰的表达和更好的段落结构。\n";
                break;
            case 'engagement':
                $prompt .= "请优化内容以提高用户参与度，添加互动元素和吸引人的表达。\n";
                break;
            case 'clarity':
                $prompt .= "请优化内容的清晰度，确保表达准确、逻辑清楚。\n";
                break;
            case 'all':
                $prompt .= "请全面优化内容，包括SEO、可读性、参与度和清晰度。\n";
                break;
        }

        if (!empty($target_audience)) {
            $prompt .= "\n目标受众：{$target_audience}\n";
        }

        return $prompt;
    }

    /**
     * 构建SEO分析提示
     */
    private function build_seo_analysis_prompt($analysis_depth)
    {
        $prompt = "你是一个专业的SEO分析师。\n\n";

        switch ($analysis_depth) {
            case 'basic':
                $prompt .= "请提供基础SEO分析，包括关键词密度、标题结构和可读性。\n";
                break;
            case 'comprehensive':
                $prompt .= "请提供全面的SEO分析，包括技术SEO、内容质量、用户体验等方面。\n";
                break;
            case 'advanced':
                $prompt .= "请提供高级SEO分析，包括竞争分析、关键词策略、内容优化建议等。\n";
                break;
        }

        return $prompt;
    }

    /**
     * 构建摘要提示
     */
    private function build_excerpt_prompt($excerpt_style, $length)
    {
        $prompt = "你是一个专业的内容摘要助手。\n\n";

        switch ($excerpt_style) {
            case 'summary':
                $prompt .= "请生成连贯的段落式摘要，突出文章的核心观点。\n";
                break;
            case 'highlights':
                $prompt .= "请以要点列表形式总结文章的亮点。\n";
                break;
            case 'questions':
                $prompt .= "请以问答形式总结文章回答的关键问题。\n";
                break;
            case 'bullet_points':
                $prompt .= "请使用项目符号列出文章的主要观点。\n";
                break;
        }

        $prompt .= "\n摘要长度控制在{$length}字左右。\n";

        return $prompt;
    }

    /**
     * 获取已注册的能力列表
     */
    public function get_registered_abilities()
    {
        return $this->registered_abilities;
    }

    /**
     * 获取能力使用统计
     */
    public function get_ability_usage_stats()
    {
        // 从数据库获取能力使用统计
        $stats = get_option('saiita_ai_content_toolkit_ability_stats', array());

        return $stats;
    }

    /**
     * 记录能力使用
     */
    public function log_ability_usage($ability_name, $params, $result)
    {
        $stats = get_option('saiita_ai_content_toolkit_ability_stats', array());

        if (!isset($stats[$ability_name])) {
            $stats[$ability_name] = array(
                'uses' => 0,
                'successes' => 0,
                'failures' => 0,
                'last_used' => null,
            );
        }

        $stats[$ability_name]['uses']++;
        $stats[$ability_name]['last_used'] = current_time('mysql');

        if (!is_wp_error($result)) {
            $stats[$ability_name]['successes']++;
        } else {
            $stats[$ability_name]['failures']++;
        }

        update_option('saiita_ai_content_toolkit_ability_stats', $stats);
    }
}
