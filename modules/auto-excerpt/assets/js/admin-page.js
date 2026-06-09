/**
 * Auto Excerpt Module - Admin Page Scripts
 */
(function($) {
    'use strict';

    // 标签页切换
    $('.act-card .nav-tab').on('click', function(e) {
        e.preventDefault();

        var targetId = $(this).attr('href').substring(1);

        // 更新标签状态
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // 显示对应内容
        $('.tab-content').removeClass('active');
        $('#' + targetId).addClass('active');
    });

    // 批量操作AJAX
    $('#batch-generate-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var progressDiv = $('.batch-progress');
        var progressBar = $('.progress-fill');
        var progressText = $('.progress-text');
        var resultsDiv = $('#batch-results');

        progressDiv.show();
        form.find('input[type="submit"]').prop('disabled', true);

        var data = {
            action: 'auto_excerpt_batch_generate',
            nonce: AutoExcerptConfig.batchNonce,
            post_type: $('#batch_post_type').val(),
            limit: parseInt($('#batch_limit').val()),
            overwrite: $('#batch_overwrite').is(':checked')
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        progressBar.css('width', (percentComplete * 100) + '%');
                        progressText.text('处理中... ' + Math.round(percentComplete * 100) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                progressDiv.hide();
                form.find('input[type="submit"]').prop('disabled', false);

                if (response.success) {
                    resultsDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    resultsDiv.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                progressDiv.hide();
                form.find('input[type="submit"]').prop('disabled', false);
                resultsDiv.html('<div class="notice notice-error"><p>操作失败，请重试</p></div>');
            }
        });
    });

    // 生成摘要按钮
    $(document).on('click', '.generate-excerpt-btn', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var originalText = button.text();

        button.prop('disabled', true).text('生成中...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'auto_excerpt_generate',
                nonce: AutoExcerptConfig.generateNonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    button.text('已生成').addClass('success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    button.prop('disabled', false).text(originalText);
                    alert('生成失败：' + response.data.message);
                }
            },
            error: function() {
                button.prop('disabled', false).text(originalText);
                alert('生成失败，请重试');
            }
        });
    });

    // 生成标签按钮
    $(document).on('click', '.generate-tags-btn', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var originalText = button.text();

        button.prop('disabled', true).text('生成中...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'auto_excerpt_generate_tags',
                nonce: AutoExcerptConfig.tagsNonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    button.text('已生成').addClass('success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    button.prop('disabled', false).text(originalText);
                    alert('生成失败：' + response.data.message);
                }
            },
            error: function() {
                button.prop('disabled', false).text(originalText);
                alert('生成失败，请重试');
            }
        });
    });

    // AI分类按钮
    $(document).on('click', '.ai-categorize-btn', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var originalText = button.text();

        button.prop('disabled', true).text('生成分类描述中...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'auto_excerpt_ai_categorize',
                nonce: AutoExcerptConfig.categorizeNonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    button.text('已分类').addClass('success');
                    alert('分类描述生成成功：' + response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    button.prop('disabled', false).text(originalText);
                    alert('分类描述生成失败：' + response.data.message);
                }
            },
            error: function() {
                button.prop('disabled', false).text(originalText);
                alert('分类描述生成失败，请重试');
            }
        });
    });

    // AI优化标签按钮
    $(document).on('click', '.ai-optimize-tags-btn', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var originalText = button.text();

        button.prop('disabled', true).text('生成标签描述中...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'auto_excerpt_ai_optimize_tags',
                nonce: AutoExcerptConfig.optimizeTagsNonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    button.text('已优化').addClass('success');
                    alert('标签描述生成成功：' + response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    button.prop('disabled', false).text(originalText);
                    alert('标签描述生成失败：' + response.data.message);
                }
            },
            error: function() {
                button.prop('disabled', false).text(originalText);
                alert('标签描述生成失败，请重试');
            }
        });
    });

    // SEO分析按钮
    $(document).on('click', '.seo-analyze-btn', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var originalText = button.html();

        button.prop('disabled', true).html('<span class="dashicons dashicons-spinner"></span> 分析中...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'auto_excerpt_seo_analyze',
                nonce: AutoExcerptConfig.seoAnalyzeNonce,
                post_id: postId
            },
            success: function(response) {
                button.prop('disabled', false).html(originalText);
                if (response.success) {
                    showSEOReport(response.data);
                } else {
                    alert('分析失败：' + response.data.message);
                }
            },
            error: function() {
                button.prop('disabled', false).html(originalText);
                alert('分析失败，请重试');
            }
        });
    });

    // 查看SEO报告按钮
    $(document).on('click', '.view-seo-report-btn', function() {
        var postId = $(this).data('post-id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'auto_excerpt_get_seo_report',
                nonce: AutoExcerptConfig.getSeoReportNonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    showSEOReport(response.data);
                } else {
                    alert('获取报告失败：' + response.data.message);
                }
            },
            error: function() {
                alert('获取报告失败，请重试');
            }
        });
    });

    // 批量SEO分析
    $('#batch-seo-analyze, #batch-seo-analyze-bottom').on('click', function() {
        var selectedPosts = $('.post-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedPosts.length === 0) {
            alert('请先选择要分析的文章');
            return;
        }

        if (!confirm('确定要对选中的 ' + selectedPosts.length + ' 篇文章进行SEO分析吗？')) {
            return;
        }

        var button = $(this);
        button.prop('disabled', true).html('<span class="dashicons dashicons-spinner"></span> 批量分析中...');

        var currentIndex = 0;
        var results = [];

        function analyzeNextPost() {
            if (currentIndex >= selectedPosts.length) {
                button.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> ' + AutoExcerptConfig.batchAnalyzeText);
                alert('批量分析完成！共分析了 ' + results.length + ' 篇文章。');
                location.reload();
                return;
            }

            var postId = selectedPosts[currentIndex];

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'auto_excerpt_seo_analyze',
                    nonce: AutoExcerptConfig.seoAnalyzeNonce,
                    post_id: postId
                },
                success: function(response) {
                    results.push({
                        post_id: postId,
                        success: response.success,
                        message: response.success ? '分析成功' : response.data.message
                    });
                },
                complete: function() {
                    currentIndex++;
                    setTimeout(analyzeNextPost, 500); // 延迟500ms避免API限制
                }
            });
        }

        analyzeNextPost();
    });

    // 显示SEO报告 - 使用完整AI分析逻辑
    function showSEOReport(data) {
        // 解包数据：支持多种数据格式
        if (data.analysis && typeof data.analysis === 'object' && data.analysis.overall_score !== undefined) {
            // ajax_analyze_post_seo 路径：所有数据在 analysis 内
            data = data.analysis;
        } else if (data.report && typeof data.report === 'object' && data.report.overall_score !== undefined) {
            // ajax_get_seo_report 路径：report 包含得分数据，raw_ai_analysis 在顶层
            data = $.extend({}, data.report, { raw_ai_analysis: data.raw_ai_analysis || data.report.raw_ai_analysis });
        }

        // 优先使用独立弹框组件（避免模态框嵌套问题）
        try {
            if (typeof window.SEOReportDisplay !== 'undefined') {
                showSEOReportModal(data);
                return;
            }
        } catch (e) {
            console.error('SEOReportDisplay modal failed, falling back:', e);
        }

        var modal = $('#seo-result-modal');
        var content = $('#seo-result-content');

        // 清空内容容器，确保没有重复标题
        content.empty();

        // 使用原有的模态框
        try {
            if (typeof window.SEOAnalyzer !== 'undefined') {
                // 使用新的完整显示逻辑，而不是旧的displaySimpleReport
                var html = '<div class="seo-ai-report-container">';

                // 手动构建完整的AI分析报告
                html += '<div class="report-header">';
                html += '<h2>🤖 AI SEO 完整分析报告</h2>';
                html += '<div class="report-meta">';
                html += '<span class="report-date">分析时间: ' + new Date().toLocaleString('zh-CN') + '</span>';
                html += '<span class="ai-provider">AI引擎: ' + (data.ai_provider || 'DeepSeek') + '</span>';
                html += '</div>';
                html += '</div>';

                // 显示AI分析数据
                if (data.raw_ai_analysis) {
                    html += '<div class="ai-full-analysis">';
                    html += '<h3>🧠 AI 完整分析</h3>';
                    try {
                        var aiData = JSON.parse(data.raw_ai_analysis);
                        if (aiData.keywords && aiData.keywords.length > 0) {
                            html += '<div class="keyword-section">';
                            html += '<h4>🎯 关键词</h4>';
                            aiData.keywords.forEach(function(keyword) {
                                html += '<span class="keyword-tag">' + keyword + '</span>';
                            });
                            html += '</div>';
                        }
                        if (aiData.recommendations && aiData.recommendations.length > 0) {
                            html += '<div class="recommendations-section">';
                            html += '<h4>💡 优化建议</h4>';
                            aiData.recommendations.forEach(function(rec, index) {
                                html += '<div class="recommendation-item">';
                                html += '<h5>' + (index + 1) + '. ' + (rec.title || '建议') + '</h5>';
                                if (rec.description) {
                                    html += '<p><strong>问题描述:</strong> ' + rec.description + '</p>';
                                }
                                if (rec.action) {
                                    html += '<p><strong>操作步骤:</strong> ' + rec.action + '</p>';
                                }
                                if (rec.impact) {
                                    html += '<p><strong>预期效果:</strong> ' + rec.impact + '</p>';
                                }
                                html += '</div>';
                            });
                            html += '</div>';
                        }
                    } catch (e) {
                        html += '<div class="raw-analysis">';
                        html += '<pre>' + data.raw_ai_analysis + '</pre>';
                        html += '</div>';
                    }
                    html += '</div>';
                }

                // 显示基础得分信息
                html += '<div class="score-details">';
                html += '<h3>📈 SEO 得分详情</h3>';
                html += '<p><strong>整体得分:</strong> ' + (data.overall_score || 0) + '</p>';
                html += '<p><strong>标题得分:</strong> ' + (data.title_score || 0) + '</p>';
                html += '<p><strong>内容得分:</strong> ' + (data.content_score || 0) + '</p>';
                html += '<p><strong>关键词得分:</strong> ' + (data.keyword_score || 0) + '</p>';
                html += '<p><strong>可读性得分:</strong> ' + (data.readability_score || 0) + '</p>';
                html += '</div>';

                html += '</div>';
                content.html(html);
            } else {
                // 完全降级方案
                var html = '<div class="seo-analysis-result">';
                html += '<h2>🤖 AI SEO 分析报告</h2>';
                html += '<p><strong>文章：</strong>' + (data.post_title || '未知') + '</p>';
                html += '<p><strong>整体得分：</strong>' + (data.overall_score || 0) + '</p>';

                // 显示原始AI分析数据
                if (data.raw_ai_analysis) {
                    html += '<div class="ai-analysis-section">';
                    html += '<h3>🧠 AI 分析内容</h3>';
                    html += '<div class="ai-content">';
                    html += '<pre>' + data.raw_ai_analysis + '</pre>';
                    html += '</div>';
                    html += '</div>';
                }
                html += '</div>';
                content.html(html);
            }
        } catch (error) {
            console.error('显示报告时出错:', error);
            content.html('<div class="notice notice-error"><p>显示报告时出错: ' + error.message + '</p></div>');
        }

        modal.show();
    }

    // 模态框关闭
    $('.modal-close, .modal-backdrop').on('click', function() {
        $('#seo-result-modal').hide();
    });

    // 全选/取消全选
    $('#cb-select-all-1').on('change', function() {
        $('.post-checkbox').prop('checked', $(this).prop('checked'));
    });

    // 生成摘要按钮点击事件
    $('.generate-excerpt-btn').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var postId = $button.data('post-id');

        if (!postId) {
            alert('文章ID无效');
            return;
        }

        // 禁用按钮，显示加载状态
        var originalText = $button.html();
        $button.prop('disabled', true)
               .html('🔄 生成中...')
               .css('opacity', '0.6');

        // 发送AJAX请求
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'auto_excerpt_generate',
                post_id: postId,
                nonce: AutoExcerptConfig.generateNonce
            },
            success: function(response) {
                // 恢复按钮状态
                $button.prop('disabled', false)
                       .html(originalText)
                       .css('opacity', '1');

                if (response.success) {
                    // 显示成功消息
                    $('<div class="notice notice-success is-dismissible"><p>' +
                      response.data.message + '</p></div>')
                        .insertAfter('.wrap h1')
                        .delay(3000)
                        .fadeOut(500, function() { $(this).remove(); });

                    // 刷新页面以更新摘要状态
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    // 显示错误消息
                    $('<div class="notice notice-error is-dismissible"><p>' +
                      (response.data.message || '生成摘要失败') + '</p></div>')
                        .insertAfter('.wrap h1')
                        .delay(5000)
                        .fadeOut(500, function() { $(this).remove(); });
                }
            },
            error: function(xhr, status, error) {
                // 恢复按钮状态
                $button.prop('disabled', false)
                       .html(originalText)
                       .css('opacity', '1');

                // 显示错误消息
                $('<div class="notice notice-error is-dismissible"><p>' +
                  '网络错误：' + error + '</p></div>')
                    .insertAfter('.wrap h1')
                    .delay(5000)
                    .fadeOut(500, function() { $(this).remove(); });
            }
        });
    });

    // 生成标签按钮点击事件
    $('.generate-tags-btn').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var postId = $button.data('post-id');

        if (!postId) {
            alert('文章ID无效');
            return;
        }

        // 禁用按钮，显示加载状态
        var originalText = $button.html();
        $button.prop('disabled', true)
               .html('🔄 生成中...')
               .css('opacity', '0.6');

        // 发送AJAX请求
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'auto_excerpt_generate_tags',
                post_id: postId,
                nonce: AutoExcerptConfig.tagsNonce
            },
            success: function(response) {
                // 恢复按钮状态
                $button.prop('disabled', false)
                       .html(originalText)
                       .css('opacity', '1');

                if (response.success) {
                    // 显示成功消息
                    $('<div class="notice notice-success is-dismissible"><p>' +
                      response.data.message + '</p></div>')
                        .insertAfter('.wrap h1')
                        .delay(3000)
                        .fadeOut(500, function() { $(this).remove(); });
                } else {
                    // 显示错误消息
                    $('<div class="notice notice-error is-dismissible"><p>' +
                      (response.data.message || '生成标签失败') + '</p></div>')
                        .insertAfter('.wrap h1')
                        .delay(5000)
                        .fadeOut(500, function() { $(this).remove(); });
                }
            },
            error: function(xhr, status, error) {
                // 恢复按钮状态
                $button.prop('disabled', false)
                       .html(originalText)
                       .css('opacity', '1');

                // 显示错误消息
                $('<div class="notice notice-error is-dismissible"><p>' +
                  '网络错误：' + error + '</p></div>')
                    .insertAfter('.wrap h1')
                    .delay(5000)
                    .fadeOut(500, function() { $(this).remove(); });
            }
        });
    });

})(jQuery);
