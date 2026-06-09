/**
 * Website Optimization 模块管理后台脚本
 *
 * @version 1.0.0
 * @author WordPress Toolkit
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // 检查免费次数，置灰按钮
        function checkUsageLimits() {
            var isPro = SaiitaAIToolkitWebsiteOptConfig.isPro;
            var remaining = parseInt(SaiitaAIToolkitWebsiteOptConfig.remaining, 10);

            if (!isPro && remaining <= 0) {
                $("#analyze-website-seo").prop("disabled", true).addClass("button-secondary").removeClass("button-primary").attr("title", "免费次数已用完，请升级PRO");
                if ($("#usage-limit-notice").length === 0) {
                    $("<div id=\"usage-limit-notice\" class=\"notice notice-warning is-dismissible\"><p>免费次数已用完，请升级 PRO 版本解锁无限使用。</p></div>").insertBefore("#analysis-progress");
                }
            }
        }

        // 更新免费次数显示
        function updateRemainingUsage(remaining) {
            var $banner = $('#act-website-remaining');
            if ($banner.length && typeof remaining !== 'undefined') {
                $banner.text(remaining + '/1');
                if (remaining <= 0) {
                    checkUsageLimits();
                }
            }
        }

        checkUsageLimits();

        // 页面加载时检查是否有保存的分析报告
        checkSavedAnalysis();

        // 分析网站SEO
        $('#analyze-website-seo').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $spinner = $('#analysis-spinner');
            var $progress = $('#analysis-progress');
            var $result = $('#analysis-result');

            // 显示进度条
            $progress.show();
            $result.hide();
            $button.prop('disabled', true);

            // 初始化进度显示
            updateProgress('分析网站SEO', 0, 0, 0, 0, '正在准备分析...', 5);

            // 发送AJAX请求
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'website_optimization_analyze',
                    nonce: SaiitaAIToolkitWebsiteOptConfig.analyzeNonce,
                    timestamp: Date.now()
                },
                beforeSend: function() {
                    updateProgress('分析网站SEO', 10, 0, 0, 0, '正在发送请求到服务器...', 5);
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        updateProgress('分析网站SEO', 100, 5, 5, 0, '分析完成', 5);
                        // 更新剩余次数
                        if (data && typeof data.remaining !== 'undefined') {
                            updateRemainingUsage(data.remaining);
                        }

                        // 显示SEO分析报告
                        displaySEOAnalysisReport(data.seo_report);

                        // 显示成功消息
                        var message = '<div class="notice notice-success is-dismissible"><p>' +
                            '<strong>网站SEO分析完成！</strong><br>' +
                            '分析时间：' + data.analysis_date +
                            '</p></div>';
                        $result.html(message).show();

                        // 5秒后隐藏进度条
                        setTimeout(function() {
                            $progress.hide();
                        }, 5000);

                    } else {
                        updateProgress('分析网站SEO', 100, 0, 0, 0, '分析失败：' + response.data.message, 5);
                        $result.html('<div class="notice notice-error"><p><strong>SEO分析失败：</strong><br>' + response.data.message + '</p></div>').show();
                        setTimeout(function() {
                            $progress.hide();
                        }, 5000);
                    }

                    $button.prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    var errorMessage = '';
                    if (status === 'timeout') {
                        errorMessage = '请求超时：处理时间过长，请稍后重试。';
                    } else {
                        errorMessage = '网络错误：' + (error || '未知错误');
                    }

                    updateProgress('分析网站SEO', 100, 0, 0, 0, errorMessage, 5);
                    $result.html('<div class="notice notice-error"><p><strong>分析失败：</strong><br>' + errorMessage + '</p></div>').show();
                    setTimeout(function() {
                        $progress.hide();
                    }, 5000);
                    $button.prop('disabled', false);
                }
            });
        });

        // 更新进度显示
        function updateProgress(title, percentage, processed, success, errors, currentAnalysis, totalCount) {
            $('#progress-title').text(title);
            $('#progress-fill').css('width', percentage + '%');
            $('#progress-text').text(percentage + '%');
            $('#current-analysis').text(currentAnalysis);
            $('#processed-count').text(processed);
            $('#success-count').text(success);
            $('#error-count').text(errors);
            $('#total-count').text(totalCount);

            if (percentage === 100) {
                setTimeout(function() {
                    $('#analysis-progress').fadeOut(500);
                }, 3000);
            }
        }

        // 显示SEO分析报告
        function displaySEOAnalysisReport(seoReport) {
            // 显示SEO分析报告区域
            $('#seo-analysis-report').show();

            // 显示标题SEO报告
            if (seoReport.title_report) {
                var titleReport = seoReport.title_report;
                $('#current-title').text(titleReport.current_title);
                $('#title-length').text(titleReport.title_length);

                // 显示分析结果
                var $titleAnalysis = $('#title-analysis');
                $titleAnalysis.empty();
                titleReport.analysis.forEach(function(item) {
                    $titleAnalysis.append('<li>' + item + '</li>');
                });

                // 显示优化建议
                var $titleRecommendations = $('#title-recommendations');
                $titleRecommendations.empty();
                titleReport.recommendations.forEach(function(item) {
                    $titleRecommendations.append('<li>' + item + '</li>');
                });

                $('#title-report-section').show();
            }

            // 显示描述SEO报告
            if (seoReport.description_report) {
                var descriptionReport = seoReport.description_report;
                $('#current-description').text(descriptionReport.current_description);
                $('#description-length').text(descriptionReport.description_length);

                // 显示分析结果
                var $descriptionAnalysis = $('#description-analysis');
                $descriptionAnalysis.empty();
                descriptionReport.analysis.forEach(function(item) {
                    $descriptionAnalysis.append('<li>' + item + '</li>');
                });

                // 显示优化建议
                var $descriptionRecommendations = $('#description-recommendations');
                $descriptionRecommendations.empty();
                descriptionReport.recommendations.forEach(function(item) {
                    $descriptionRecommendations.append('<li>' + item + '</li>');
                });

                $('#description-report-section').show();
            }

            // 显示关键字SEO报告
            if (seoReport.keyword_report) {
                var keywordReport = seoReport.keyword_report;
                $('#current-keywords').text(keywordReport.current_keywords);
                $('#keyword-count').text(keywordReport.keyword_count);

                // 显示分析结果
                var $keywordAnalysis = $('#keyword-analysis');
                $keywordAnalysis.empty();
                keywordReport.analysis.forEach(function(item) {
                    $keywordAnalysis.append('<li>' + item + '</li>');
                });

                // 显示优化建议
                var $keywordRecommendations = $('#keyword-recommendations');
                $keywordRecommendations.empty();
                keywordReport.recommendations.forEach(function(item) {
                    $keywordRecommendations.append('<li>' + item + '</li>');
                });

                $('#keyword-report-section').show();
            }

            // 显示总体优化建议
            if (seoReport.overall_recommendations) {
                var $overallRecommendations = $('#overall-recommendations');
                $overallRecommendations.empty();
                seoReport.overall_recommendations.forEach(function(item) {
                    $overallRecommendations.append('<li>' + item + '</li>');
                });
                $('#overall-recommendations-section').show();
            }
        }

        // 检查是否有保存的分析报告
        function checkSavedAnalysis() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'website_optimization_get_saved_analysis',
                    nonce: SaiitaAIToolkitWebsiteOptConfig.analyzeNonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // 显示保存的分析报告
                        displaySEOAnalysisReport(response.data.seo_report);

                        // 显示成功消息
                        var message = '<div class="notice notice-info is-dismissible"><p>' +
                            '<strong>已加载保存的SEO分析报告</strong><br>' +
                            '分析时间：' + response.data.analysis_date +
                            '</p></div>';
                        $('#analysis-result').html(message).show();
                    }
                },
                error: function() {
                    // 没有保存的分析报告，静默失败
                }
            });
        }
    });

})(jQuery);
