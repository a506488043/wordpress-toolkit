            jQuery(document).ready(function($) {
                // 统计信息
                // 检查免费次数，置灰按钮
                function checkUsageLimits() {
                    var isPro = saiitaAdminData.isPro;
                    var remaining = parseInt(saiitaAdminData.remaining, 10);

                    // 批量按钮：次数用完置灰
                    if (!isPro && remaining <= 0) {
                        $('#batch-generate-excerpts, #batch-generate-tags').prop('disabled', true).attr('title', '免费次数已用完，请升级PRO');
                    }
                    // 单个按钮：次数用完置灰
                    if (!isPro && remaining <= 0) {
                        $('.generate-excerpt-single, .generate-tags-single, .seo-analyze-single').prop('disabled', true).attr('title', '免费次数已用完，请升级PRO');
                        if ($('#usage-limit-notice').length === 0) {
                            $('<div id="usage-limit-notice" class="notice notice-warning is-dismissible"><p>免费次数已用完，请升级 PRO 版本解锁无限使用。</p></div>').insertBefore('#batch-generate-result');
                        }
                    }
                }
                checkUsageLimits();

                var stats = {
                    total_posts: saiitaAdminData.stats.total_posts,
                    without_excerpt: saiitaAdminData.stats.without_excerpt
                };
                // 进度更新函数
                function updateProgress(title, percentage, processed, success, errors, currentPost, totalCount) {
                    // 更新标题和进度条
                    if (percentage === 100) {
                        $('#progress-title').text(title + ' - ' + currentPost);
                    } else {
                        $('#progress-title').text(title + ' - 处理中...');
                    }

                    // 确保数据有效性
                    processed = Math.max(0, processed || 0);
                    success = Math.max(0, success || 0);
                    errors = Math.max(0, errors || 0);

                    $('#progress-fill').css('width', percentage + '%');
                    $('#progress-text').text(percentage + '%');
                    $('#current-post').text(currentPost);
                    $('#processed-count').text(processed);
                    $('#success-count').text(success);
                    $('#error-count').text(errors);

                    // 更新总数显示
                    if (totalCount !== undefined && totalCount !== null) {
                        $('#total-count').text(totalCount);
                    } else {
                        // 智能更新总数显示（兼容旧代码）
                        var $totalCount = $('#total-count');
                        if (percentage === 100 && processed > 0) {
                            // 完成时，总数等于已处理数
                            $totalCount.text(processed);
                        } else if (processed > 0 && percentage < 100) {
                            // 处理中时，估算总数
                            if ($totalCount.text() === '0' || $totalCount.text() === '?') {
                                // 首次估算：假设当前进度是准确的，反推总数
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

                // 显示加载状态的函数
                function showProcessingStatus(title, totalPosts, operationType) {
                    var messageCount = 0;
                    var cycleCount = 0;

                    // 根据操作类型选择不同的状态消息
                    var statusMessages, processingMessages;

                    if (operationType === 'tags') {
                        // 标签生成的状态消息
                        statusMessages = [
                            '正在准备标签生成环境...',
                            '正在加载AI标签模型...',
                            '正在分析文章标题和内容...',
                            '正在获取文章列表...',
                            '正在初始化标签处理器...'
                        ];

                        processingMessages = [
                            '正在分析文章内容...',
                            '正在生成AI标签...',
                            '正在匹配现有标签...',
                            '正在保存标签结果...',
                            '正在验证标签准确性...'
                        ];
                    } else {
                        // 摘要生成的状态消息（默认）
                        statusMessages = [
                            '正在准备处理环境...',
                            '正在加载AI模型...',
                            '正在分析文章数据...',
                            '正在获取文章列表...',
                            '正在初始化处理器...'
                        ];

                        processingMessages = [
                            '正在分析文章内容...',
                            '正在生成智能摘要...',
                            '正在优化摘要长度...',
                            '正在保存处理结果...',
                            '正在验证摘要质量...'
                        ];
                    }

                    var interval = setInterval(function() {
                        if (messageCount < statusMessages.length) {
                            // 在准备阶段，显示渐进的准备进度
                            var progress = Math.round((messageCount + 1) * 8); // 8%, 16%, 24%, 32%, 40%
                            var simulatedProcessed = Math.round((progress / 100) * Math.min(totalPosts, 10)); // 最多模拟处理10篇
                            var simulatedSuccess = Math.round(simulatedProcessed * 0.9);

                            updateProgress(title, progress, simulatedProcessed, simulatedSuccess,
                                         simulatedProcessed - simulatedSuccess, statusMessages[messageCount], totalPosts);
                            messageCount++;
                        } else {
                            // 循环显示处理状态，模拟真实的处理进度
                            cycleCount++;

                            // For large numbers of articles，使用更慢的进度增长
                            var maxProgress = 95;
                            var progressIncrement = totalPosts > 1000 ? 0.5 : (totalPosts > 500 ? 1 : 2);
                            var baseProgress = 45;
                            var additionalProgress = Math.min(cycleCount * progressIncrement, maxProgress - baseProgress);
                            var progress = Math.min(baseProgress + additionalProgress, maxProgress);

                            var simulatedProcessed = Math.round((progress / 100) * totalPosts);
                            var simulatedSuccess = Math.round(simulatedProcessed * 0.85 + Math.random() * 10);
                            var simulatedErrors = simulatedProcessed - simulatedSuccess;

                            // 确保不超过总数
                            simulatedProcessed = Math.min(simulatedProcessed, totalPosts);
                            simulatedSuccess = Math.min(simulatedSuccess, simulatedProcessed);
                            simulatedErrors = Math.min(simulatedErrors, simulatedProcessed - simulatedSuccess);

                            var messageIndex = (cycleCount - 1) % processingMessages.length;
                            var currentMessage = processingMessages[messageIndex] + ' (' + simulatedProcessed + '/' + totalPosts + ')';

                            // For large numbers of articles，添加时间提示和进度检查点
                            if (totalPosts > 1000) {
                                if (cycleCount % 8 === 0) {
                                    var remainingMinutes = Math.round((100 - progress) / 10 * 1.5); // 估算剩余时间
                                    currentMessage += ' - 预计还需' + remainingMinutes + '分钟';
                                }

                                // 在特定进度点显示里程碑
                                if (progress >= 25 && progress < 27 && cycleCount % 50 === 0) {
                                    currentMessage += ' ✅ 已完成25%';
                                } else if (progress >= 50 && progress < 52 && cycleCount % 50 === 0) {
                                    currentMessage += ' 🎯 已完成50%';
                                } else if (progress >= 75 && progress < 77 && cycleCount % 50 === 0) {
                                    currentMessage += ' 🔥 已完成75%';
                                }
                            }

                            updateProgress(title, progress, simulatedProcessed, simulatedSuccess,
                                         simulatedErrors, currentMessage, totalPosts);
                        }
                    }, totalPosts > 1000 ? 3000 : 1500); // 大量文章时每3秒更新一次，减少频率

                    return interval;
                }

                // 批量生成摘要
                $('#batch-generate-excerpts').on('click', function(e) {
                    e.preventDefault();

                    var $button = $(this);
                    var $spinner = $('#batch-generate-spinner');
                    var $progress = $('#batch-generate-progress');
                    var $result = $('#batch-generate-result');

                    var estimatedTime = '30秒-2分钟';
                    var showBatchOption = false;

                    if (stats.without_excerpt > 2000) {
                        estimatedTime = '15-30分钟';
                        showBatchOption = true;
                    } else if (stats.without_excerpt > 1000) {
                        estimatedTime = '8-15分钟';
                        showBatchOption = true;
                    } else if (stats.without_excerpt > 500) {
                        estimatedTime = '5-10分钟';
                    } else if (stats.without_excerpt > 100) {
                        estimatedTime = '2-5分钟';
                    }

                    var confirmMessage = '确定要为所有无摘要文章批量生成摘要吗？\n\n' +
                        '• 需要处理的文章数量：' + stats.without_excerpt + ' 篇\n' +
                        '• 预计处理时间：' + estimatedTime + '\n' +
                        '• Do not close page during processing\n' +
                        '• Large number of articles may take longer to process';

                    if (showBatchOption) {
                        confirmMessage += '\n\n💡 **建议：对于' + stats.without_excerpt + '篇文章**\n' +
                            '考虑分批处理以获得更好的稳定性：\n' +
                            '• 分3-5批处理，每批300-500篇\n' +
                            '• 每批处理间隔2-3分钟\n' +
                            '• 可以降低服务器压力和超时风险\n\n' +
                            '点击"确定"继续处理全部文章，\n点击"取消"可以考虑分批处理。';
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
                    var initMessage = 'Processing ' + stats.without_excerpt + ' articles without excerpts...';
                    if (stats.without_excerpt > 1000) {
                        initMessage += '\nWarning: Large number of articles, please be patient';
                    }
                    updateProgress('生成摘要', 0, 0, 0, 0, initMessage, stats.without_excerpt);

                    // 显示处理状态
                    var statusInterval = showProcessingStatus('生成摘要', stats.without_excerpt, 'excerpts');

                    // 发送实际的批量生成请求
                    $.ajax({
                        url: saiitaAdminData.ajaxUrl,
                        type: 'POST',
                        timeout: 600000, // 10分钟超时时间（600秒）
                        data: {
                            action: 'saiita_ai_toolkit_batch_generate_excerpts',
                            nonce: saiitaAdminData.nonces.batchGenerateExcerpts
                        },
                        beforeSend: function() {
                            updateProgress('生成摘要', 10, 0, 0, 0, '正在发送请求到服务器...', stats.without_excerpt);
                        },
                        success: function(response) {
                            // 立即停止状态消息显示
                            clearInterval(statusInterval);

                            if (response.success) {
                                var data = response.data;
                                // 确保显示真实的处理结果
                                var actualProcessed = data.success_count + data.error_count;
                                updateProgress('生成摘要', 100, actualProcessed, data.success_count, data.error_count, '处理完成', stats.without_excerpt);

                                var message = '<div class="notice notice-success is-dismissible"><p>' +
                                    '<strong>批量生成摘要完成！</strong><br>' +
                                    '✅ 成功处理：' + data.success_count + ' 篇文章<br>' +
                                    (data.error_count > 0 ? '❌ 处理失败：' + data.error_count + ' 篇文章<br>' : '') +
                                    '📊 总计处理：' + (data.success_count + data.error_count) + ' 篇文章';

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
                                updateProgress('生成摘要', 100, 0, 0, 0, '处理失败：' + response.data.message, stats.without_excerpt);
                                $result.html('<div class="notice notice-error"><p><strong>摘要生成失败：</strong><br>' + response.data.message + '</p></div>').show();
                                setTimeout(function() {
                                    $progress.hide();
                                }, 5000);
                            }

                            $button.prop('disabled', false);
                        },
                        error: function(xhr, status, error) {
                            // 停止状态消息显示
                            clearInterval(statusInterval);

                            var errorMessage = '';
                            if (status === 'timeout') {
                                var partialMessage = '\n\n⚠️ **处理可能仍在继续**\n\n' +
                                    'For large numbers of articles（' + stats.without_excerpt + ' 篇）的处理：\n' +
                                    '• 服务器可能仍在后台继续处理\n' +
                                    '• 建议等待5-10分钟后刷新页面查看结果\n' +
                                    '• 如果仍有大量文章未处理，可以再次运行\n' +
                                    '• 考虑分批次处理（每次处理200-300篇）';

                                errorMessage = '请求超时：处理时间过长，服务器响应超时。' + partialMessage;
                                updateProgress('生成摘要', 100, 0, 0, 0, '请求超时，但处理可能仍在继续', stats.without_excerpt);
                            } else if (status === 'abort') {
                                errorMessage = '请求被取消';
                                updateProgress('生成摘要', 100, 0, 0, 0, '请求被取消', stats.without_excerpt);
                            } else if (xhr.status === 0) {
                                errorMessage = '网络连接失败：无法连接到服务器，请检查网络连接';
                                updateProgress('生成摘要', 100, 0, 0, 0, '网络连接失败', stats.without_excerpt);
                            } else if (xhr.status === 500) {
                                errorMessage = '服务器内部错误：服务器处理请求时发生错误 (HTTP 500)';
                                updateProgress('生成摘要', 100, 0, 0, 0, '服务器错误', stats.without_excerpt);
                            } else if (xhr.status === 503) {
                                errorMessage = '服务不可用：服务器暂时无法处理请求 (HTTP 503)';
                                updateProgress('生成摘要', 100, 0, 0, 0, '服务不可用', stats.without_excerpt);
                            } else if (xhr.status === 504) {
                                errorMessage = '网关超时：服务器处理时间过长 (HTTP 504)';
                                updateProgress('生成摘要', 100, 0, 0, 0, '网关超时', stats.without_excerpt);
                            } else {
                                errorMessage = '网络错误：' + (error || '未知错误') + ' (HTTP ' + xhr.status + ')';
                                updateProgress('生成摘要', 100, 0, 0, 0, '网络错误', stats.without_excerpt);
                            }

                            $result.html('<div class="notice notice-error"><p><strong>处理失败：</strong><br>' + errorMessage + '</p>' +
                                '<p><strong>建议：</strong></p>' +
                                '<ul>' +
                                '<li>检查网络连接是否正常</li>' +
                                '<li>刷新页面后重试</li>' +
                                '<li>如果是大量文章处理，recommend processing in batches</li>' +
                                '<li>如果问题持续，请联系服务器管理员</li>' +
                                '</ul></div>').show();

                            setTimeout(function() {
                                $progress.hide();
                            }, 8000); // 延长显示时间到8秒
                            $button.prop('disabled', false);
                        }
                    });
                });

                // 单个文章生成摘要
                $('.generate-excerpt-single').on('click', function(e) {
                    e.preventDefault();

                    var $button = $(this);
                    var postId = $button.data('post-id');
                    var originalText = $button.html();

                    // 显示加载状态
                    $button.prop('disabled', true).html('<span class="dashicons dashicons-spinner"></span><span>生成中...</span>');

                    // 发送AJAX请求
                    $.ajax({
                        url: saiitaAdminData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'auto_excerpt_generate',
                            post_id: postId,
                            nonce: saiitaAdminData.nonces.autoExcerptGenerate
                        },
                        success: function(response) {
                            if (response.success) {
                                var data = response.data;
                                var message = '<div class="notice notice-success is-dismissible"><p>' +
                                    '摘要生成成功！<br>' +
                                    '文章：' + data.post_title + '<br>' +
                                    '摘要长度：' + data.excerpt_length + ' 字符' +
                                    '</p></div>';

                                // 显示成功消息
                                $('#batch-generate-result').html(message).show();

                                // 更新按钮状态
                                $button.removeClass('button-primary').addClass('button-secondary')
                                       .html('<span class="dashicons dashicons-yes"></span><span>已生成</span>')
                                       .prop('disabled', true);

                                // 更新表格中的状态显示
                                var $row = $button.closest('tr');
                                var statusHtml = '<span class="status-active">有摘要</span>';
                                if (data.ai_generated) {
                                    statusHtml += '<span class="ai-badge" style="margin-left: 5px; background: #e6f3ff; color: #0073aa; padding: 2px 6px; border-radius: 3px; font-size: 11px; border: 1px solid #b3d9ff; font-weight: 500;">🤖 AI</span>';
                                }
                                $row.find('td:nth-child(2)').html(statusHtml);
                                $row.find('td:nth-child(3)').text(data.excerpt_length + ' 字符');

                            } else {
                                // 显示错误消息
                                $('#batch-generate-result').html('<div class="notice notice-error"><p>摘要生成失败：' + response.data.message + '</p></div>').show();
                                $button.html(originalText).prop('disabled', false);
                            }
                        },
                        error: function() {
                            $('#batch-generate-result').html('<div class="notice notice-error"><p>网络错误，请重试</p></div>').show();
                            $button.html(originalText).prop('disabled', false);
                        }
                    });
                });

                // AI生成标签功能
                $('.generate-tags-single').on('click', function(e) {
                    e.preventDefault();
                    var $button = $(this);
                    var postId = $button.data('post-id');
                    var postTitle = $button.data('title');

                    console.log('Generate tags clicked - Post ID:', postId, 'Title:', postTitle);

                    if (!postId) {
                        alert('文章ID无效');
                        return;
                    }

                    // 显示加载状态
                    var originalText = $button.html();
                    $button.html('<span class="dashicons dashicons-update rotating"></span> 生成中...').prop('disabled', true);

                    // 生成标签
                    $.ajax({
                        url: saiitaAdminData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'auto_excerpt_generate_tags',
                            post_id: postId,
                            nonce: saiitaAdminData.nonces.autoExcerptGenerateTags
                        },
                        beforeSend: function(xhr) {
                            console.log('Sending AJAX request for tags...');
                        },
                        success: function(response) {
                            console.log('AJAX response:', response);
                            $button.html(originalText).prop('disabled', false);

                            if (response.success) {
                                // 更新剩余次数
                                if (response.data && typeof response.data.remaining !== 'undefined') {
                                    updateRemainingUsage(response.data.remaining);
                                }
                                showTagDialog(postId, postTitle, response.data);
                            } else {
                                alert('标签生成失败：' + response.data.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX error:', status, error);
                            console.log('XHR response:', xhr.responseText);
                            $button.html(originalText).prop('disabled', false);
                            alert('网络错误，请重试');
                        }
                    });
                });


                // 更新免费次数显示
                function updateRemainingUsage(remaining) {
                    var $banner = $('#act-article-remaining');
                    if ($banner.length && typeof remaining !== 'undefined') {
                        $banner.text(remaining + '/6');
                        // 如果次数用完，触发置灰检查
                        if (remaining <= 0) {
                            checkUsageLimits();
                        }
                    }
                }

                // 显示标签选择对话框
                function showTagDialog(postId, postTitle, tagData) {
                    var existingTags = tagData.existing_tags || [];
                    var aiTags = tagData.ai_tags || [];
                    var suggestedAction = tagData.suggested_action || 'replace';

                    // 创建对话框内容
                    var dialogHtml = '<div id="tag-dialog" style="display: none;">' +
                        '<div class="tag-dialog-content">' +
                        '<h3>🏷️ AI标签生成 - ' + postTitle + '</h3>' +

                        '<div class="tag-section">' +
                        '<h4>📌 原有标签：</h4>' +
                        '<div class="tag-container" id="existing-tags">';

                    if (existingTags.length > 0) {
                        existingTags.forEach(function(tag) {
                            dialogHtml += '<span class="tag existing-tag">' + tag + '</span>';
                        });
                    } else {
                        dialogHtml += '<span class="no-tags">暂无标签</span>';
                    }

                    dialogHtml += '</div></div>' +

                        '<div class="tag-section">' +
                        '<h4>🤖 AI生成标签：</h4>' +
                        '<div class="tag-container" id="ai-tags">';

                    if (aiTags.length > 0) {
                        aiTags.forEach(function(tag) {
                            dialogHtml += '<span class="tag ai-tag" data-tag="' + tag + '">' + tag + '</span>';
                        });
                    } else {
                        dialogHtml += '<span class="no-tags">AI未生成标签</span>';
                    }

                    dialogHtml += '</div></div>' +

                        '<div class="tag-actions">' +
                        '<h4>选择操作：</h4>' +
                        '<label><input type="radio" name="tag_action" value="replace" ' + (suggestedAction === 'replace' ? 'checked' : '') + '> 替换所有标签</label>' +
                        '<label><input type="radio" name="tag_action" value="add" ' + (suggestedAction === 'add' ? 'checked' : '') + '> 添加到现有标签</label>' +
                        '<label><input type="radio" name="tag_action" value="merge"> 合并去重</label>' +
                        '</div>' +

                        '<div class="tag-dialog-buttons">' +
                        '<button type="button" class="button button-secondary" onclick="closeTagDialog()">取消</button>' +
                        '<button type="button" class="button button-primary" onclick="applyTags(' + postId + ')">应用标签</button>' +
                        '</div>' +
                        '</div></div>';

                    // 添加到页面
                    $('body').append(dialogHtml);

                    // 显示对话框
                    $('#tag-dialog').fadeIn(200);

                    // AI标签点击选择/取消
                    $('.ai-tag').on('click', function() {
                        $(this).toggleClass('selected');
                    });
                }

                // 关闭对话框
                window.closeTagDialog = function() {
                    $('#tag-dialog').fadeOut(200, function() {
                        $(this).remove();
                    });
                };

                // 应用标签
                window.applyTags = function(postId) {
                    var selectedTags = $('.ai-tag.selected').map(function() {
                        return $(this).data('tag');
                    }).get();

                    if (selectedTags.length === 0) {
                        alert('请选择要应用的标签');
                        return;
                    }

                    var actionType = $('input[name="tag_action"]:checked').val();

                    // 显示加载状态
                    $('.tag-dialog-buttons .button-primary').html('<span class="dashicons dashicons-update rotating"></span> 应用中...').prop('disabled', true);

                    $.ajax({
                        url: saiitaAdminData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'saiita_apply_ai_tags',
                            post_id: postId,
                            new_tags: selectedTags,
                            action_type: actionType,
                            nonce: saiitaAdminData.nonces.applyAiTags
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('标签应用成功！');
                                closeTagDialog();
                                // 刷新页面以显示更新的标签信息
                                location.reload();
                            } else {
                                alert('标签应用失败：' + response.data.message);
                                $('.tag-dialog-buttons .button-primary').html('应用标签').prop('disabled', false);
                            }
                        },
                        error: function() {
                            alert('网络错误，请重试');
                            $('.tag-dialog-buttons .button-primary').html('应用标签').prop('disabled', false);
                        }
                    });
                };

                // 批量生成标签
                $('#batch-generate-tags').on('click', function(e) {
                    e.preventDefault();

                    var $button = $(this);
                    var $spinner = $('#batch-generate-tags-spinner');
                    var $progress = $('#batch-generate-progress');
                    var $result = $('#batch-generate-result');

                    var estimatedTime = '1-3分钟';
                    var showBatchOption = false;

                    if (stats.total_posts > 2000) {
                        estimatedTime = '20-40分钟';
                        showBatchOption = true;
                    } else if (stats.total_posts > 1000) {
                        estimatedTime = '10-20分钟';
                        showBatchOption = true;
                    } else if (stats.total_posts > 500) {
                        estimatedTime = '6-12分钟';
                    } else if (stats.total_posts > 100) {
                        estimatedTime = '3-8分钟';
                    }

                    var confirmMessage = '确定要为所有文章批量生成标签吗？\n\n' +
                        '• 需要处理的文章数量：' + stats.total_posts + ' 篇\n' +
                        '• 预计处理时间：' + estimatedTime + '\n' +
                        '• 将为每篇文章生成AI标签并与现有标签合并\n' +
                        '• Do not close page during processing\n' +
                        '• Large number of articles may take longer to process';

                    if (showBatchOption) {
                        confirmMessage += '\n\n💡 **建议：对于' + stats.total_posts + '篇文章**\n' +
                            '标签生成更耗时，强烈建议分批处理：\n' +
                            '• 分4-6批处理，每批200-400篇\n' +
                            '• 每批处理间隔3-5分钟\n' +
                            '• 可以确保AI标签质量和处理稳定性\n\n' +
                            '点击"确定"继续处理全部文章，\n点击"取消"可以考虑分批处理。';
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
                    var initMessage = 'Processing ' + stats.total_posts + ' articles for tag generation...';
                    if (stats.total_posts > 1000) {
                        initMessage += '\nWarning: Large number of articles, processing may take longer';
                    }
                    updateProgress('生成标签', 0, 0, 0, 0, initMessage, stats.total_posts);

                    // 显示处理状态
                    var statusInterval = showProcessingStatus('生成标签', stats.total_posts, 'tags');

                    // 发送实际的批量生成请求
                    $.ajax({
                        url: saiitaAdminData.ajaxUrl,
                        type: 'POST',
                        timeout: 600000, // 10分钟超时时间（600秒）
                        data: {
                            action: 'saiita_ai_toolkit_batch_generate_tags',
                            nonce: saiitaAdminData.nonces.batchGenerateTags
                        },
                        beforeSend: function() {
                            updateProgress('生成标签', 10, 0, 0, 0, '正在发送请求到服务器...', stats.total_posts);
                        },
                        success: function(response) {
                            // 立即停止状态消息显示
                            clearInterval(statusInterval);

                            if (response.success) {
                                var data = response.data;
                                // 确保显示真实的处理结果
                                updateProgress('生成标签', 100, data.processed_count, data.success_count, data.error_count, '处理完成', stats.total_posts);

                                var message = '<div class="notice notice-success is-dismissible"><p>' +
                                    '<strong>批量生成标签完成！</strong><br>' +
                                    '✅ 成功处理：' + data.success_count + ' 篇文章<br>' +
                                    (data.error_count > 0 ? '❌ 处理失败：' + data.error_count + ' 篇文章<br>' : '') +
                                    '📊 总计处理：' + data.processed_count + ' 篇文章<br>' +
                                    '🏷️ 应用标签：' + data.total_applied_tags + ' 个';

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
                                updateProgress('生成标签', 100, 0, 0, 0, '处理失败：' + response.data.message, stats.total_posts);
                                $result.html('<div class="notice notice-error"><p><strong>批量生成标签失败：</strong><br>' + response.data.message + '</p></div>').show();
                                setTimeout(function() {
                                    $progress.hide();
                                }, 5000);
                            }

                            $button.prop('disabled', false);
                        },
                        error: function(xhr, status, error) {
                            // 停止状态消息显示
                            clearInterval(statusInterval);

                            var errorMessage = '';
                            if (status === 'timeout') {
                                var partialMessage = '\n\n⚠️ **处理可能仍在继续**\n\n' +
                                    'For large numbers of articles（' + stats.total_posts + ' 篇）的标签生成：\n' +
                                    '• 服务器可能仍在后台继续处理\n' +
                                    '• 建议等待10-15分钟后刷新页面查看结果\n' +
                                    '• 如果仍有大量文章未处理，可以再次运行\n' +
                                    '• 考虑分批次处理（每次处理200-300篇）';

                                errorMessage = '请求超时：处理时间过长，服务器响应超时。' + partialMessage;
                                updateProgress('生成标签', 100, 0, 0, 0, '请求超时，但处理可能仍在继续', stats.total_posts);
                            } else if (status === 'abort') {
                                errorMessage = '请求被取消';
                                updateProgress('生成标签', 100, 0, 0, 0, '请求被取消', stats.total_posts);
                            } else if (xhr.status === 0) {
                                errorMessage = '网络连接失败：无法连接到服务器，请检查网络连接';
                                updateProgress('生成标签', 100, 0, 0, 0, '网络连接失败', stats.total_posts);
                            } else if (xhr.status === 500) {
                                errorMessage = '服务器内部错误：服务器处理请求时发生错误 (HTTP 500)';
                                updateProgress('生成标签', 100, 0, 0, 0, '服务器错误', stats.total_posts);
                            } else if (xhr.status === 503) {
                                errorMessage = '服务不可用：服务器暂时无法处理请求 (HTTP 503)';
                                updateProgress('生成标签', 100, 0, 0, 0, '服务不可用', stats.total_posts);
                            } else if (xhr.status === 504) {
                                errorMessage = '网关超时：服务器处理时间过长 (HTTP 504)';
                                updateProgress('生成标签', 100, 0, 0, 0, '网关超时', stats.total_posts);
                            } else {
                                errorMessage = '网络错误：' + (error || '未知错误') + ' (HTTP ' + xhr.status + ')';
                                updateProgress('生成标签', 100, 0, 0, 0, '网络错误', stats.total_posts);
                            }

                            $result.html('<div class="notice notice-error"><p><strong>标签生成失败：</strong><br>' + errorMessage + '</p>' +
                                '<p><strong>建议：</strong></p>' +
                                '<ul>' +
                                '<li>检查网络连接是否正常</li>' +
                                '<li>刷新页面后重试</li>' +
                                '<li>如果是大量文章处理，recommend processing in batches</li>' +
                                '<li>如果问题持续，请联系服务器管理员</li>' +
                                '</ul></div>').show();

                            setTimeout(function() {
                                $progress.hide();
                            }, 8000); // 延长显示时间到8秒
                            $button.prop('disabled', false);
                        }
                    });
                });

                // SEO分析功能
                $('.seo-analyze-single').on('click', function(e) {
                    e.preventDefault();
                    var $button = $(this);
                    var postId = $button.data('post-id');

                    console.log('SEO分析按钮点击 - 文章ID:', postId);

                    if (!postId) {
                        alert('文章ID无效');
                        return;
                    }

                    // 显示加载状态
                    var originalText = $button.html();
                    $button.html('<span class="dashicons dashicons-update rotating"></span> 分析中...').prop('disabled', true);

                    // 发送SEO分析请求
                    $.ajax({
                        url: saiitaAdminData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'auto_excerpt_seo_analyze',
                            nonce: saiitaAdminData.nonces.autoExcerptSeoAnalyze,
                            post_id: postId
                        },
                        success: function(response) {
                            if (response.success) {
                                // 恢复按钮状态
                                $button.html(originalText).prop('disabled', false);
                                // 更新剩余次数
                                if (response.data && typeof response.data.remaining !== 'undefined') {
                                    updateRemainingUsage(response.data.remaining);
                                }

                                // 显示美观的SEO分析弹框
                                console.log('=== AI SEO分析数据结构 ===');
                                console.log('完整数据:', response.data);

                                if (response.data.recommendations) {
                                    console.log('建议数量:', response.data.recommendations.length);
                                    response.data.recommendations.forEach(function(rec, index) {
                                        console.log(`建议${index + 1}:`, {
                                            title: rec.title,
                                            has_action: !!rec.action,
                                            action_length: rec.action ? rec.action.length : 0,
                                            has_description: !!rec.description,
                                            priority: rec.priority
                                        });
                                    });
                                }

                                if (response.data.keywords) {
                                    console.log('关键词:', response.data.keywords);
                                }

                                console.log('=== 数据结构结束 ===');

                                showSEOReportModal(postId, response.data.analysis || response.data);

                                // 不自动刷新页面，让用户有足够时间阅读报告
                            } else {
                                alert('SEO分析失败：' + response.data.message);
                                $button.html(originalText).prop('disabled', false);
                            }
                        },
                        error: function() {
                            alert('网络错误，请重试');
                            $button.html(originalText).prop('disabled', false);
                        }
                    });
                });

                // JSON修复函数
                window.fixBrokenJSON = function(jsonString) {
                    if (!jsonString || typeof jsonString !== 'string') {
                        return null;
                    }

                    let fixed = jsonString.trim();

                    // 提取JSON内容（移除```json标记）
                    if (fixed.startsWith('```json')) {
                        fixed = fixed.replace(/^```json\s*/, '').replace(/\s*```$/, '');
                    }

                    // 1. 修复花括号不匹配
                    const openBraces = (fixed.match(/\{/g) || []).length;
                    const closeBraces = (fixed.match(/\}/g) || []).length;
                    if (openBraces > closeBraces) {
                        fixed += '}'.repeat(openBraces - closeBraces);
                        console.log('添加了 ' + (openBraces - closeBraces) + ' 个闭合花括号');
                    }

                    // 2. 移除控制字符
                    fixed = fixed.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');

                    // 3. 转义未转义的换行符
                    fixed = fixed.replace(/\n/g, '\\n').replace(/\r/g, '\\r').replace(/\t/g, '\\t');

                    // 4. 修复未闭合的字符串
                    fixed = fixed.replace(/"([^"]*?)$/, '"$1"');

                    // 5. 移除多余的逗号
                    fixed = fixed.replace(/,\s*([}\]])/g, '$1');

                    console.log('JSON修复完成');
                    return fixed;
                };

                // 构建结构化AI分析部分 - 优化样式
                window.buildStructuredAISection = function(data) {
                    var html = '<div class="seo-report-section ai-analysis-container">';

                    // AI分析详情
                    if (data.ai_analysis && Object.keys(data.ai_analysis).length > 0) {
                        html += '<div class="analysis-card">';
                        html += '<h4 class="card-title">📊 AI分析详情</h4>';

                        const labels = {
                            title_analysis: '标题分析',
                            content_analysis: '内容分析',
                            keyword_analysis: '关键词分析',
                            readability_analysis: '可读性分析'
                        };

                        Object.entries(data.ai_analysis).forEach(([key, value]) => {
                            html += '<div class="analysis-row">';
                            html += '<div class="analysis-content">';
                            html += '<h4 class="analysis-label">' + (labels[key] || key) + '</h6>';
                            html += '<p class="analysis-text">' + value + '</p>';
                            html += '</div>';
                            html += '</div>';
                        });

                        html += '</div>';
                    }

                    // AI关键词
                    if (data.ai_keywords && data.ai_keywords.length > 0) {
                        html += '<div class="keywords-card">';
                        html += '<h4 class="card-title">🏷️ AI推荐关键词</h4>';
                        html += '<div class="keywords-list">';
                        data.ai_keywords.forEach(function(keyword) {
                            html += '<span class="keyword-chip">' + keyword + '</span>';
                        });
                        html += '</div></div>';
                    }

                    // AI推荐
                    if (data.ai_recommendations && data.ai_recommendations.length > 0) {
                        html += '<div class="recommendations-card">';
                        html += '<h4 class="card-title">🤖 AI优化建议</h4>';

                        data.ai_recommendations.forEach(function(rec, index) {
                            html += '<div class="recommendation-card-item">';
                            html += '<h5 class="rec-title">' + (index + 1) + '. ' + (rec.title || '优化建议') + '</h5>';
                            if (rec.description) html += '<p class="rec-desc">' + rec.description + '</p>';
                            if (rec.action) {
                                html += '<div class="rec-action">';
                                html += '<span class="action-label">✓ 操作</span>';
                                html += '<span class="action-text">' + rec.action + '</span>';
                                html += '</div>';
                            }
                            if (rec.impact) {
                                html += '<div class="rec-impact">';
                                html += '<span class="impact-label">⭐ 效果</span>';
                                html += '<span class="impact-text">' + rec.impact + '</span>';
                                html += '</div>';
                            }
                            html += '</div>';
                        });

                        html += '</div>';
                    }

                    // 元信息建议
                    if (data.ai_meta_info) {
                        html += '<div class="meta-card">';
                        html += '<h4 class="card-title">📝 元信息建议</h4>';

                        if (data.ai_meta_info.suggested_title) {
                            html += '<div class="meta-item">';
                            html += '<h4 class="meta-label">📄 建议标题</h6>';
                            html += '<p class="meta-value">' + data.ai_meta_info.suggested_title + '</p>';
                            html += '</div>';
                        }

                        if (data.ai_meta_info.meta_description) {
                            html += '<div class="meta-item">';
                            html += '<h4 class="meta-label">📋 Meta描述</h6>';
                            html += '<p class="meta-value">' + data.ai_meta_info.meta_description + '</p>';
                            html += '</div>';
                        }

                        if (data.ai_meta_info.focus_keywords && data.ai_meta_info.focus_keywords.length > 0) {
                            html += '<div class="meta-item">';
                            html += '<h4 class="meta-label">🎯 核心关键词</h6>';
                            html += '<div class="keywords-list">';
                            data.ai_meta_info.focus_keywords.forEach(function(keyword) {
                                html += '<span class="focus-keyword-chip">' + keyword + '</span>';
                            });
                            html += '</div></div>';
                        }

                        html += '</div>';
                    }

                    html += '</div>';
                    return html;
                };

                // 构建AI分析部分
                window.buildAIAnalysisSection = function(aiData) {
                    var html = '<div class="seo-report-section">';
                    html += '<h3>🤖 AI分析</h3>';

                    // AI分析详情
                    if (aiData.analysis) {
                        html += '<div class="analysis-details">';
                        html += '<h4>📊 AI分析详情</h4>';

                        const labels = {
                            title_analysis: '标题分析',
                            content_analysis: '内容分析',
                            keyword_analysis: '关键词分析',
                            readability_analysis: '可读性分析'
                        };

                        Object.entries(aiData.analysis).forEach(([key, value]) => {
                            html += '<div class="analysis-item">';
                            html += '<h5>' + (labels[key] || key) + ':</h5>';
                            html += '<p>' + value + '</p>';
                            html += '</div>';
                        });

                        html += '</div>';
                    }

                    // AI推荐
                    if (aiData.recommendations && aiData.recommendations.length > 0) {
                        html += '<div class="ai-recommendations">';
                        html += '<h4>🤖 AI优化建议</h4>';

                        aiData.recommendations.forEach(function(rec, index) {
                            html += '<div class="recommendation-item">';
                            html += '<h5>' + (index + 1) + '. ' + (rec.title || '建议') + '</h5>';
                            if (rec.description) html += '<p>' + rec.description + '</p>';
                            if (rec.action) html += '<p><strong>操作:</strong> ' + rec.action + '</p>';
                            if (rec.impact) html += '<p><strong>效果:</strong> ' + rec.impact + '</p>';
                            html += '</div>';
                        });

                        html += '</div>';
                    }

                    html += '</div>';
                    return html;
                };

                // 构建原始分析部分
                window.buildRawAnalysisSection = function(rawAnalysis) {
                    var html = '<div class="seo-report-section">';
                    html += '<h3>🤖 AI分析</h3>';
                    html += '<div class="raw-analysis">';
                    html += '<h4>📄 原始分析数据</h4>';
                    html += '<pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 300px; overflow-y: auto;">' +
                           escapeHtml(rawAnalysis) + '</pre>';
                    html += '</div></div>';
                    return html;
                };

                // HTML转义函数
                window.escapeHtml = function(text) {
                    var div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                };

                // SEO报告弹框函数 - 简化版本
                window.showSEOReportModal = function(postId, data) {
                    // 检查是否为错误数据
                    if (data && data.error) {
                        var errorMsg = data.message || 'API请求失败，请检查API配置和网络连接';
                        // 构建错误弹框
                        var errorHtml = '<div class="seo-report-header">';
                        errorHtml += '<p class="report-post-id">文章ID: ' + postId + '</p>';
                        errorHtml += '</div>';
                        errorHtml += '<div class="seo-report-section">';
                        errorHtml += '<div class="notice notice-error" style="margin:0;padding:15px;">';
                        errorHtml += '<p><strong>❌ SEO分析失败：</strong></p>';
                        errorHtml += '<p>' + escapeHtml(errorMsg) + '</p>';
                        errorHtml += '<p style="margin-top:10px;color:#666;font-size:12px;">';
                        errorHtml += '建议：请检查AI设置中的API密钥和模型配置是否正确，或稍后重试。</p>';
                        errorHtml += '</div></div>';

                        showModalWithHtml(errorHtml);
                        return;
                    }

                    // 构建报告HTML - 基于实际数据结构
                    var reportHtml = '<div class="seo-report-header">';
                    reportHtml += '<p class="report-post-id">文章ID: ' + postId + '</p>';
                    reportHtml += '</div>';

                    // 基础信息
                    if (data.post_title) {
                        reportHtml += '<div class="seo-report-section">';
                        reportHtml += '<h3>📄 文章信息</h3>';
                        reportHtml += '<p><strong>标题:</strong> ' + data.post_title + '</p>';
                        reportHtml += '</div>';
                    }

                    // SEO得分展示
                    if (data.overall_score !== undefined) {
                        reportHtml += '<div class="seo-report-section">';
                        reportHtml += '<h3>📊 SEO得分</h3>';
                        reportHtml += '<div class="score-grid">';
                        reportHtml += '<div class="score-item"><strong>整体得分:</strong> <span class="score-value">' + data.overall_score + '</span></div>';
                        if (data.title_score) reportHtml += '<div class="score-item"><strong>标题得分:</strong> <span class="score-value">' + data.title_score + '</span></div>';
                        if (data.content_score) reportHtml += '<div class="score-item"><strong>内容得分:</strong> <span class="score-value">' + data.content_score + '</span></div>';
                        if (data.keyword_score) reportHtml += '<div class="score-item"><strong>关键词得分:</strong> <span class="score-value">' + data.keyword_score + '</span></div>';
                        if (data.readability_score) reportHtml += '<div class="score-item"><strong>可读性得分:</strong> <span class="score-value">' + data.readability_score + '</span></div>';
                        reportHtml += '</div></div>';
                    }

                    // 技术统计
                    reportHtml += '<div class="seo-report-section">';
                    reportHtml += '<div class="stats-grid">';
                    if (data.word_count) reportHtml += '<div class="stat-item"><strong>字数:</strong> ' + data.word_count + ' 字</div>';
                    if (data.title_length) reportHtml += '<div class="stat-item"><strong>标题长度:</strong> ' + data.title_length + ' 字符</div>';
                    if (data.image_count) reportHtml += '<div class="stat-item"><strong>图片数量:</strong> ' + data.image_count + ' 个</div>';
                    if (data.internal_links) reportHtml += '<div class="stat-item"><strong>内部链接:</strong> ' + data.internal_links + ' 个</div>';
                    if (data.external_links) reportHtml += '<div class="stat-item"><strong>外部链接:</strong> ' + data.external_links + ' 个</div>';
                    reportHtml += '</div></div>';

                    // 标题结构统计
                    if (data.heading_counts && Object.keys(data.heading_counts).length > 0) {
                        reportHtml += '<div class="seo-report-section">';
                        reportHtml += '<h3>📝 标题结构</h3>';
                        reportHtml += '<div class="heading-grid">';
                        Object.keys(data.heading_counts).forEach(function(tag) {
                            reportHtml += '<div class="heading-item"><span class="heading-tag">' + tag.toUpperCase() + '</span><span class="heading-count">' + data.heading_counts[tag] + '</span></div>';
                        });
                        reportHtml += '</div></div>';
                    }

                    // AI分析部分 - 使用analysis对象中的数据
                    if (data.analysis && (data.analysis.ai_analysis || data.analysis.ai_recommendations || data.analysis.ai_keywords)) {
                        console.log('使用analysis对象中的AI数据');
                        reportHtml += buildStructuredAISection(data.analysis);
                    } else if (data.analysis) {
                        console.log('使用analysis对象数据构建AI部分');
                        // 直接使用analysis对象
                        reportHtml += buildStructuredAISection(data.analysis);
                    } else if (data.raw_ai_analysis) {
                        // 备用：处理原始JSON数据
                        try {
                            var aiData = JSON.parse(data.raw_ai_analysis);
                            reportHtml += buildAIAnalysisSection(aiData);
                        } catch (e) {
                            console.log('JSON解析失败，尝试修复:', e);
                            reportHtml += buildRawAnalysisSection(data.raw_ai_analysis);
                        }
                    }

                    // 创建弹框 - 简化版本，无头部
                    showModalWithHtml(reportHtml);
                };

                // 显示模态框的通用函数
                window.showModalWithHtml = function(contentHtml) {
                    var modalHtml = '<div id="seo-report-modal" class="seo-report-modal" style="display: none;">';
                    modalHtml += '<div class="seo-modal-backdrop"></div>';
                    modalHtml += '<div class="seo-modal-content">';
                    modalHtml += '<div class="seo-modal-body">' + contentHtml + '</div>';
                    modalHtml += '<div class="seo-modal-footer">';
                    modalHtml += '<button class="button button-secondary" onclick="closeSEOReportModal()">关闭</button>';
                    modalHtml += '</div>';
                    modalHtml += '</div></div>';

                    // 添加到页面并显示
                    $('body').append(modalHtml);

                    var modal = $('#seo-report-modal');
                    if (modal.length > 0) {
                        modal.css({
                            'position': 'fixed',
                            'top': '0',
                            'left': '0',
                            'width': '100%',
                            'height': '100%',
                            'z-index': '99999'
                        }).addClass('seo-modal-overlay').show();
                    }
                };

                // 关闭SEO报告弹框
                window.closeSEOReportModal = function() {
                    $('#seo-report-modal').fadeOut(300, function() {
                        $(this).remove();
                    });
                };
            });
