/**
 * Category Optimization 模块管理后台脚本
 *
 * @version 1.0.0
 * @author WordPress Toolkit
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // 安全检查
        if (typeof SaiitaAIToolkitCategoryOptAdmin === 'undefined') {
            console.error('SaiitaAIToolkitCategoryOptAdmin 未定义');
            return;
        }

        // 使用共享功能检查使用限制
        if (typeof SaiitaSharedAdmin !== 'undefined') {
            SaiitaSharedAdmin.checkUsageLimits({
                isPro: SaiitaAIToolkitCategoryOptAdmin.isPro,
                remaining: parseInt(SaiitaAIToolkitCategoryOptAdmin.remaining, 10),
                buttonId: '#batch-generate-descriptions',
                bannerId: '#act-cat-remaining',
                moduleType: 'category'
            });
        }

        // 统计信息
        var stats = SaiitaAIToolkitCategoryOptAdmin.stats || {};

        // 适配字段名映射
        if (stats.categories_without_description !== undefined) {
            stats.without_description = stats.categories_without_description;
        }

        // 进度更新函数
        function updateProgress(title, percentage, processed, success, errors, currentCategory, totalCount) {
            // 更新标题和进度条
            if (percentage === 100) {
                $('#progress-title').text(title + ' - ' + currentCategory);
            } else {
                $('#progress-title').text(title + ' - 处理中...');
            }

            // 确保数据有效性
            processed = Math.max(0, processed || 0);
            success = Math.max(0, success || 0);
            errors = Math.max(0, errors || 0);

            $('#progress-fill').css('width', percentage + '%');
            $('#progress-text').text(percentage + '%');
            $('#current-category').text(currentCategory);
            $('#processed-count').text(processed);
            $('#success-count').text(success);
            $('#error-count').text(errors);

            // 更新总数显示
            if (totalCount !== undefined && totalCount !== null) {
                $('#total-count').text(totalCount);
            } else {
                // 智能更新总数显示
                var $totalCount = $('#total-count');
                if (percentage === 100 && processed > 0) {
                    $totalCount.text(processed);
                } else if (processed > 0 && percentage < 100) {
                    if ($totalCount.text() === '0' || $totalCount.text() === '?') {
                        var estimated = Math.round(processed * 100 / percentage);
                        $totalCount.text(estimated);
                    }
                }
            }

            // 完成时自动隐藏进度条
            if (percentage === 100) {
                setTimeout(function() {
                    $('#batch-generate-progress').fadeOut(500);
                }, 3000);
            }
        }

        // 批量生成描述
        $('#batch-generate-descriptions').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $spinner = $('#batch-generate-spinner');
            var $progress = $('#batch-generate-progress');
            var $result = $('#batch-generate-result');

            var estimatedTime = '30秒-2分钟';
            var showBatchOption = false;

            if (stats.without_description > 2000) {
                estimatedTime = '15-30分钟';
                showBatchOption = true;
            } else if (stats.without_description > 1000) {
                estimatedTime = '8-15分钟';
                showBatchOption = true;
            } else if (stats.without_description > 500) {
                estimatedTime = '5-10分钟';
            } else if (stats.without_description > 100) {
                estimatedTime = '2-5分钟';
            }

            var confirmMessage = '确定要为所有无描述分类批量生成描述吗？\n\n' +
                '• 需要处理的分类数量：' + stats.without_description + ' 个\n' +
                '• 预计处理时间：' + estimatedTime + '\n' +
                '• 处理期间请勿关闭页面\n' +
                '• 大量分类可能需要更长时间处理';

            if (showBatchOption) {
                confirmMessage += '\n\n💡 **建议：对于' + stats.without_description + '个分类**\n' +
                    '考虑分批处理以获得更好的稳定性：\n' +
                    '• 分3-5批处理，每批300-500个\n' +
                    '• 每批处理间隔2-3分钟\n' +
                    '• 可以降低服务器压力和超时风险\n\n' +
                    '点击"确定"继续处理全部分类，\n点击"取消"可以考虑分批处理。';
            } else {
                confirmMessage += '\n\n点击"确定"开始处理，或"取消"退出。';
            }

            if (!confirm(confirmMessage)) {
                return;
            }

            // 显示进度条
            $progress.show();
            $result.hide();
            $button.prop('disabled', true);

            // 初始化进度显示
            var initMessage = 'Processing ' + stats.without_description + ' categories without descriptions...';
            if (stats.without_description > 1000) {
                initMessage += '\nWarning: Large number of categories, please be patient';
            }
            updateProgress('生成描述', 0, 0, 0, 0, initMessage, stats.without_description);

            // 发送实际的批量生成请求
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                timeout: 600000, // 10分钟超时时间
                data: {
                    action: 'category_optimization_batch_generate',
                    nonce: SaiitaAIToolkitCategoryOptAdmin.batchNonce
                },
                beforeSend: function() {
                    updateProgress('生成描述', 10, 0, 0, 0, '正在发送请求到服务器...', stats.without_description);
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        // 确保显示真实的处理结果
                        var actualProcessed = data.success_count + data.error_count;
                        updateProgress('生成描述', 100, actualProcessed, data.success_count, data.error_count, '处理完成', stats.without_description);

                        var message = '<div class="notice notice-success is-dismissible"><p>' +
                            '<strong>批量生成描述完成！</strong><br>' +
                            '✅ 成功处理：' + data.success_count + ' 个分类<br>' +
                            (data.error_count > 0 ? '❌ 处理失败：' + data.error_count + ' 个分类<br>' : '') +
                            '📊 总计处理：' + (data.success_count + data.error_count) + ' 个分类';

                        if (data.error_count > 0) {
                            message += '<br><small>详细信息请查看错误日志</small>';
                        }

                        message += '</p></div>';
                        $result.html(message).show();

                        // 5秒后隐藏进度条
                        setTimeout(function() {
                            $progress.hide();
                        }, 5000);

                    } else {
                        updateProgress('生成描述', 100, 0, 0, 0, '处理失败：' + response.data.message, stats.without_description);
                        $result.html('<div class="notice notice-error"><p><strong>描述生成失败：</strong><br>' + response.data.message + '</p></div>').show();
                        setTimeout(function() {
                            $progress.hide();
                        }, 5000);
                    }

                    $button.prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    var errorMessage = '';
                    if (status === 'timeout') {
                        var partialMessage = '\n\n⚠️ **处理可能仍在继续**\n\n' +
                            '对于大量分类（' + stats.without_description + ' 个）的处理：\n' +
                            '• 服务器可能仍在后台继续处理\n' +
                            '• 建议等待5-10分钟后刷新页面查看结果\n' +
                            '• 如果仍有大量分类未处理，可以再次运行\n' +
                            '• 考虑分批次处理（每次处理200-300个）';

                        errorMessage = '请求超时：处理时间过长，服务器响应超时。' + partialMessage;
                        updateProgress('生成描述', 100, 0, 0, 0, '请求超时，但处理可能仍在继续', stats.without_description);
                    } else if (status === 'abort') {
                        errorMessage = '请求被取消';
                        updateProgress('生成描述', 100, 0, 0, 0, '请求被取消', stats.without_description);
                    } else if (xhr.status === 0) {
                        errorMessage = '网络连接失败：无法连接到服务器，请检查网络连接';
                        updateProgress('生成描述', 100, 0, 0, 0, '网络连接失败', stats.without_description);
                    } else if (xhr.status === 500) {
                        errorMessage = '服务器内部错误：服务器处理请求时发生错误 (HTTP 500)';
                        updateProgress('生成描述', 100, 0, 0, 0, '服务器错误', stats.without_description);
                    } else {
                        errorMessage = '网络错误：' + (error || '未知错误') + ' (HTTP ' + xhr.status + ')';
                        updateProgress('生成描述', 100, 0, 0, 0, '网络错误', stats.without_description);
                    }

                    $result.html('<div class="notice notice-error"><p><strong>处理失败：</strong><br>' + errorMessage + '</p>' +
                        '<p><strong>建议：</strong></p>' +
                        '<ul>' +
                        '<li>检查网络连接是否正常</li>' +
                        '<li>刷新页面后重试</li>' +
                        '<li>如果是大量分类处理，建议分批处理</li>' +
                        '<li>如果问题持续，请联系服务器管理员</li>' +
                        '</ul></div>').show();

                    setTimeout(function() {
                        $progress.hide();
                    }, 8000);
                    $button.prop('disabled', false);
                }
            });
        });

        // 单个分类生成描述
        $('.generate-description-single').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var categoryId = $button.data('category-id');
            var categoryName = $button.data('category-name');
            var originalText = $button.html();

            // 确认对话框
            if (!confirm('确定要为分类 "' + categoryName + '" 生成AI描述吗？\n\n描述生成后将自动保存到分类中。')) {
                return;
            }

            // 显示加载状态
            $button.prop('disabled', true).html('<span class="dashicons dashicons-spinner"></span><span>生成中...</span>');

            // 发送AJAX请求
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'category_optimization_generate_description',
                    category_id: categoryId,
                    nonce: SaiitaAIToolkitCategoryOptAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var message = '<div class="notice notice-success is-dismissible"><p>' +
                            '✅ 描述生成并保存成功！<br>' +
                            '分类：' + data.category_name + '<br>' +
                            '描述：' + data.description +
                            '</p></div>';

                        // 显示成功消息
                        $('#batch-generate-result').html(message).show();

                        // 更新按钮状态
                        $button.removeClass('button-primary').addClass('button-secondary')
                               .html('<span class="dashicons dashicons-yes"></span><span>已生成</span>')
                               .prop('disabled', true);

                        // 更新表格中的状态显示
                        var $row = $button.closest('tr');
                        var statusHtml = '<span class="status-active">有描述</span>';
                        $row.find('td:nth-child(2)').html(statusHtml);
                        $row.find('td:nth-child(3)').text(data.description.length + ' 字符');

                        // 更新剩余次数
                        if (data.remaining !== undefined && data.limit !== undefined) {
                            $('#act-cat-remaining').text(data.remaining + '/' + data.limit);
                        }

                    } else {
                        // 显示错误消息
                        $('#batch-generate-result').html('<div class="notice notice-error"><p>描述生成失败：' + response.data.message + '</p></div>').show();
                        $button.html(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    $('#batch-generate-result').html('<div class="notice notice-error"><p>网络错误，请重试</p></div>').show();
                    $button.html(originalText).prop('disabled', false);
                }
            });
        });
    });

})(jQuery);
