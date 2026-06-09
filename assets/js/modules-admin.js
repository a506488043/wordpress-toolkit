/**
 * WordPress Toolkit - 模块管理脚本
 * 整合所有模块的管理后台JavaScript功能，避免重复
 */

(function($) {
    'use strict';

    // 模块管理器
    window.ToolkitModules = {

        /**
         * 初始化所有模块功能
         */
        init: function() {
            this.initTabs();
            this.initAjaxForms();
            this.initBatchOperations();
            this.initFilters();
            this.initTooltips();
            this.initConfirmActions();
        },

        /**
         * 初始化标签页功能
         */
        initTabs: function() {
            $(document).on('click', '.nav-tab-wrapper .nav-tab', function(e) {
                e.preventDefault();

                var $tab = $(this);
                var $wrapper = $tab.closest('.nav-tab-wrapper');
                var tabId = $tab.attr('href') || $tab.data('tab');

                // 更新标签状态
                $wrapper.find('.nav-tab').removeClass('nav-tab-active');
                $tab.addClass('nav-tab-active');

                // 显示对应内容
                $('.tab-content').hide();
                $(tabId).show();

                // 触发自定义事件
                $(document).trigger('toolkit:tab:changed', [tabId]);
            });
        },

        /**
         * 初始化AJAX表单提交
         */
        initAjaxForms: function() {
            $(document).on('submit', 'form[data-ajax-form]', function(e) {
                e.preventDefault();

                var $form = $(this);
                var action = $form.data('ajax-form');
                var $submitBtn = $form.find('button[type="submit"]');
                var originalText = $submitBtn.text();

                // 显示加载状态
                $submitBtn.prop('disabled', true).text('处理中...');

                // 收集表单数据
                var formData = new FormData($form[0]);
                formData.append('action', action);
                formData.append('nonce', SaiitaAIToolkitConfig.nonce);

                // 发送AJAX请求
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            ToolkitCore.showNotice('success', response.data.message || '操作成功！');

                            // 触发自定义事件
                            $(document).trigger('toolkit:form:success', [action, response.data]);
                        } else {
                            ToolkitCore.showNotice('error', response.data.message || '操作失败，请重试。');
                        }
                    },
                    error: function() {
                        ToolkitCore.showNotice('error', '网络错误，请重试。');
                    },
                    complete: function() {
                        // 恢复按钮状态
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        },

        /**
         * 初始化批量操作
         */
        initBatchOperations: function() {
            // 全选/取消全选
            $(document).on('change', '.select-all-checkbox', function() {
                var $selectAll = $(this);
                var $checkboxes = $selectAll.closest('table').find('.item-checkbox');
                $checkboxes.prop('checked', $selectAll.is(':checked'));
                this.updateBatchActionsState();
            }.bind(this));

            // 单个复选框变化
            $(document).on('change', '.item-checkbox', function() {
                this.updateBatchActionsState();
            }.bind(this));

            // 批量操作按钮
            $(document).on('click', '[data-batch-action]', function(e) {
                e.preventDefault();

                var $btn = $(this);
                var action = $btn.data('batch-action');
                var confirmMessage = $btn.data('confirm');

                if (confirmMessage && !confirm(confirmMessage)) {
                    return;
                }

                var selectedIds = [];
                $('.item-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    ToolkitCore.showNotice('warning', '请至少选择一个项目。');
                    return;
                }

                this.executeBatchAction(action, selectedIds);
            }.bind(this));
        },

        /**
         * 更新批量操作按钮状态
         */
        updateBatchActionsState: function() {
            var $checkedBoxes = $('.item-checkbox:checked');
            var hasSelection = $checkedBoxes.length > 0;

            $('[data-batch-action]').prop('disabled', !hasSelection);

            // 更新全选框状态
            var $allCheckboxes = $('.item-checkbox');
            var $selectAll = $('.select-all-checkbox');

            if ($allCheckboxes.length === 0) {
                $selectAll.prop('checked', false);
            } else {
                $selectAll.prop('checked', $allCheckboxes.length === $checkedBoxes.length);
            }
        },

        /**
         * 执行批量操作
         */
        executeBatchAction: function(action, ids) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    ids: ids,
                    nonce: SaiitaAIToolkitConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ToolkitCore.showNotice('success', response.data.message || '批量操作成功！');

                        // 刷新页面或移除已处理的项
                        if (response.data.reload) {
                            location.reload();
                        } else {
                            ids.forEach(function(id) {
                                $('.item-checkbox[value="' + id + '"]').closest('tr').fadeOut(400, function() {
                                    $(this).remove();
                                });
                            });
                        }

                        // 触发自定义事件
                        $(document).trigger('toolkit:batch:success', [action, ids]);
                    } else {
                        ToolkitCore.showNotice('error', response.data.message || '批量操作失败，请重试。');
                    }
                },
                error: function() {
                    ToolkitCore.showNotice('error', '网络错误，请重试。');
                }
            });
        },

        /**
         * 初始化筛选功能
         */
        initFilters: function() {
            $(document).on('change', '.filter-control', function() {
                var $filter = $(this);
                var filterName = $filter.attr('name');
                var filterValue = $filter.val();

                // 更新URL
                var url = new URL(window.location);
                if (filterValue) {
                    url.searchParams.set(filterName, filterValue);
                } else {
                    url.searchParams.delete(filterName);
                }

                // 跳转到新页面
                window.location.href = url.toString();
            });

            // 搜索框实时搜索（带防抖）
            var searchTimer;
            $(document).on('input', '.search-control', function() {
                var $search = $(this);
                var searchValue = $search.val().trim();

                clearTimeout(searchTimer);

                if (searchValue.length === 0 || searchValue.length >= 2) {
                    searchTimer = setTimeout(function() {
                        var url = new URL(window.location);
                        if (searchValue) {
                            url.searchParams.set('search', searchValue);
                        } else {
                            url.searchParams.delete('search');
                        }
                        window.location.href = url.toString();
                    }, 500);
                }
            });
        },

        /**
         * 初始化工具提示
         */
        initTooltips: function() {
            $('.tooltip-trigger').hover(function() {
                var $trigger = $(this);
                var tooltip = $trigger.data('tooltip');

                if (tooltip) {
                    var $tooltip = $('<div class="tooltip">' + tooltip + '</div>');
                    $('body').append($tooltip);

                    var position = $trigger.offset();
                    $tooltip.css({
                        position: 'absolute',
                        top: position.top - $tooltip.outerHeight() - 10,
                        left: position.left + ($trigger.outerWidth() - $tooltip.outerWidth()) / 2
                    }).fadeIn(200);
                }
            }, function() {
                $('.tooltip').fadeOut(200, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * 初始化确认操作
         */
        initConfirmActions: function() {
            $(document).on('click', '[data-confirm]', function(e) {
                var confirmMessage = $(this).data('confirm');
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            });
        },

        /**
         * 显示状态指示器
         */
        showStatus: function(element, status, message) {
            var $element = $(element);
            var statusHtml = '';

            switch (status) {
                case 'loading':
                    statusHtml = '<span class="status-indicator status-loading">加载中...</span>';
                    break;
                case 'success':
                    statusHtml = '<span class="status-indicator status-success">' + (message || '成功') + '</span>';
                    break;
                case 'error':
                    statusHtml = '<span class="status-indicator status-error">' + (message || '错误') + '</span>';
                    break;
                default:
                    statusHtml = '<span class="status-indicator status-default">' + message + '</span>';
            }

            $element.html(statusHtml);
        },

        /**
         * 格式化数字
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            } else {
                return num.toString();
            }
        },

        /**
         * 复制到剪贴板
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    ToolkitCore.showNotice('success', '已复制到剪贴板');
                });
            } else {
                // 降级方案
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();
                ToolkitCore.showNotice('success', '已复制到剪贴板');
            }
        }
    };

    // Auto Excerpt 模块专用功能
    window.ToolkitModules.AutoExcerpt = {

        /**
         * 生成单个文章摘要
         */
        generateExcerpt: function(postId) {
            var $row = $('#post-' + postId);
            var $excerptCell = $row.find('.excerpt-cell');

            ToolkitModules.showStatus($excerptCell, 'loading');

  
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'auto_excerpt_generate',
                    post_id: postId,
                    nonce: SaiitaAIToolkitConfig.nonce
                },
                success: function(response) {

                    if (response.success) {
                        var excerpt = response.data.excerpt;
                        var isAI = response.data.is_ai;
                        var aiBadge = isAI ? '<span class="ai-badge">🤖 AI</span>' : '';

                        $excerptCell.html(
                            '<div class="excerpt-text">' + excerpt + '</div>' + aiBadge
                        );

                        ToolkitCore.showNotice('success', '摘要生成成功！');
                    } else {
                        ToolkitModules.showStatus($excerptCell, 'error', '生成失败');
                          ToolkitCore.showNotice('error', response.data.message || '生成失败');
                    }
                },
                error: function() {
                    ToolkitModules.showStatus($excerptCell, 'error', '网络错误');
                    ToolkitCore.showNotice('error', '网络错误，请重试。');
                }
            });
        },

        /**
         * 批量生成摘要 - 已禁用，由主插件文件处理
         * 避免与主插件文件中的 #batch-generate-excerpts 处理程序冲突
         */
        batchGenerateExcerpts: function() {
            // 此功能已移至主插件文件，防止重复绑定和冲突
              return;
        }
    };

    // Category/Tag Optimization 模块专用功能
    window.ToolkitModules.Optimization = {

        /**
         * 优化单个项目
         */
        optimizeItem: function(itemId, type) {
            var $card = $('.item-card[data-id="' + itemId + '"]');
            var $descriptionCell = $card.find('.item-description');

            ToolkitModules.showStatus($descriptionCell, 'loading');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: type + '_optimize_single',
                    item_id: itemId,
                    nonce: SaiitaAIToolkitConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var description = response.data.description;
                        var isAI = response.data.is_ai;
                        var aiBadge = isAI ? '<span class="ai-badge">🤖 AI</span>' : '';

                        $descriptionCell.html(description + aiBadge);
                        $card.addClass('optimized');

                        ToolkitCore.showNotice('success', '优化成功！');

                        // 更新统计
                        var $stats = $('.stats-panel');
                        $stats.find('.stat-number[data-stat="optimized"]').text(
                            parseInt($stats.find('.stat-number[data-stat="optimized"]').text()) + 1
                        );
                    } else {
                        ToolkitModules.showStatus($descriptionCell, 'error', '优化失败');
                        ToolkitCore.showNotice('error', response.data.message || '优化失败');
                    }
                },
                error: function() {
                    ToolkitModules.showStatus($descriptionCell, 'error', '网络错误');
                    ToolkitCore.showNotice('error', '网络错误，请重试。');
                }
            });
        },

        /**
         * 批量优化
         */
        batchOptimize: function(type) {
            var $btn = $('#batch-optimize-' + type + 's');
            var originalText = $btn.text();

            $btn.prop('disabled', true).text('批量优化中...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: type + '_batch_optimize',
                    nonce: SaiitaAIToolkitConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        ToolkitCore.showNotice('success', '批量优化完成！共优化 ' + response.data.count + ' 个项目。');

                        // 刷新页面
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        ToolkitCore.showNotice('error', response.data.message || '批量优化失败');
                    }
                },
                error: function() {
                    ToolkitCore.showNotice('error', '网络错误，请重试。');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        }
    };

    // 页面加载完成后初始化
    $(document).ready(function() {
        // 检查是否有必要的全局变量
        if (typeof SaiitaAIToolkitConfig !== 'undefined' && SaiitaAIToolkitConfig.nonce) {
            ToolkitModules.init();
        } else {
                // 如果配置不可用，重新加载页面
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });

})(jQuery);