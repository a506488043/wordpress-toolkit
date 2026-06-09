/**
 * AI Category JavaScript
 * AI自动分类功能的前端交互脚本
 */

jQuery(document).ready(function($) {
    'use strict';

    // 文章编辑页面的AI分类按钮
    $(document).on('click', '#ai-categorize-btn', function() {
        var $button = $(this);
        var $status = $('#ai-categorize-status');
        var postId = $('#post_ID').val();

        if (!postId) {
            $status.text('错误：无法获取文章ID').css('color', 'red');
            return;
        }

        // 显示加载状态
        $button.prop('disabled', true)
               .text('🤖 正在分析中...')
               .css('background-color', '#ffb900');

        $status.text('正在调用AI分析文章内容，请稍候...')
             .css('color', '#0073aa')
             .show();

        // 发送AJAX请求
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'saiita_ai_categorize_post',
                post_id: postId,
                nonce: aiCategory.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.text(response.data.message || '✅ 分类完成！')
                          .css('color', '#46b450');

                    // 刷新分类选择器
                    if (response.data.categories && response.data.categories.length > 0) {
                        // 更新分类选择框
                        updateCategoryChecklist(response.data.categories);

                        // 显示成功信息
                        var categoryNames = response.data.category_names || [];
                        if (categoryNames.length > 0) {
                            $status.append('<br>已选择分类：' + categoryNames.join(', '));
                        }
                    }

                    // 2秒后恢复按钮状态
                    setTimeout(function() {
                        $button.prop('disabled', false)
                               .text('🤖 智能选择分类')
                               .css('background-color', '');
                        $status.fadeOut();
                    }, 3000);

                } else {
                    $status.text('❌ ' + (response.data.message || '分类失败'))
                          .css('color', '#dc3232');

                    // 恢复按钮状态
                    $button.prop('disabled', false)
                           .text('🤖 智能选择分类')
                           .css('background-color', '');
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = '网络错误，请重试';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMsg = xhr.responseJSON.data.message;
                }

                $status.text('❌ ' + errorMsg)
                      .css('color', '#dc3232');

                // 恢复按钮状态
                $button.prop('disabled', false)
                       .text('🤖 智能选择分类')
                       .css('background-color', '');
            }
        });
    });

    // 更新分类选择框
    function updateCategoryChecklist(selectedCategories) {
        // 清除所有选择
        $('.categorychecklist input[type="checkbox"]').prop('checked', false);

        // 选择AI推荐的分类
        selectedCategories.forEach(function(catId) {
            $('.categorychecklist input[type="checkbox"][value="' + catId + '"]').prop('checked', true);
        });

        // 触发分类选择变化事件
        $('.categorychecklist input[type="checkbox"]').first().trigger('change');
    }

    
    // 页面加载时自动检查并显示AI分类建议（如果设置了自动分类）
    function checkAutoCategory() {
        // 检查是否启用了自动分类
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'saiita_ai_category_get_settings',
                nonce: aiCategory.nonce
            },
            success: function(response) {
                if (response.success && response.data.enabled) {
                    // 如果启用了自动分类，可以在页面加载时显示提示
                    var postId = $('#post_ID').val();
                    if (postId && !$('#ai-categorize-btn').data('auto-shown')) {
                        $('#ai-categorize-status')
                            .text('💡 已启用AI自动分类，点击上方按钮可手动分类')
                            .css('color', '#0073aa')
                            .show();
                        $('#ai-categorize-btn').data('auto-shown', true);
                    }
                }
            }
        });
    }

    // 在文章编辑页面初始化时检查
    if ($('#post_ID').length && $('.categorychecklist').length) {
        checkAutoCategory();
    }

    // 添加键盘快捷键支持
    $(document).on('keydown', function(e) {
        // Ctrl + Shift + C 触发AI分类
        if (e.ctrlKey && e.shiftKey && e.key === 'C') {
            if ($('#ai-categorize-btn').length && !$('#ai-categorize-btn').prop('disabled')) {
                e.preventDefault();
                $('#ai-categorize-btn').click();
            }
        }
    });

    // 添加AI分类按钮的工具提示
    $('#ai-categorize-btn').attr('title',
        '使用AI智能分析文章内容并选择合适的分类\n' +
        '快捷键：Ctrl + Shift + C\n' +
        '会自动移除"笔记"分类（如设置中启用）'
    );

    // 监听分类变化，提供AI建议反馈
    $('.categorychecklist input[type="checkbox"]').on('change', function() {
        var checkedCount = $('.categorychecklist input[type="checkbox"]:checked').length;

        if (checkedCount === 0) {
            $('#ai-categorize-status')
                .text('💡 建议使用AI分类功能为文章选择合适的分类')
                .css('color', '#ffb900')
                .show();
        } else if (checkedCount > 5) {
            $('#ai-categorize-status')
                .text('⚠️ 分类过多，建议使用AI重新分类以获得更好的SEO效果')
                .css('color', '#ffb900')
                .show();
        }
    });

    });