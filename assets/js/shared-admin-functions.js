/**
 * 共享管理页面功能
 * 用于多个模块的管理页面，提供通用的功能
 *
 * @version 1.0.0
 * @author Saiita AI Content Toolkit
 */

(function($) {
    'use strict';

    // 通用使用限制检查功能
    window.SaiitaSharedAdmin = {
        /**
         * 检查使用限制并相应地更新UI
         * @param {Object} options - 配置选项
         * @param {string} options.buttonId - 批量操作按钮ID
         * @param {string} options.bannerId - 显示使用信息的横幅ID
         * @param {string} options.moduleType - 模块类型 ('category', 'tag', 等)
         */
        checkUsageLimits: function(options) {
            var defaults = {
                buttonId: '#batch-generate-descriptions',
                bannerId: '',
                moduleType: 'category'
            };

            var settings = $.extend({}, defaults, options);

            // 所有功能现在都完全可用，无需检查使用限制
            $(settings.buttonId).prop('disabled', false);

            // 更新使用信息显示
            if (settings.bannerId) {
                this.updateUsageInfo(settings.bannerId);
            }
        },

        /**
         * 更新使用信息显示
         * @param {string} bannerId - 横幅元素ID
         */
        updateUsageInfo: function(bannerId) {
            var $banner = $(bannerId);
            if ($banner.length) {
                $banner.text('功能完全可用');
            }
        },

        /**
         * 显示进度条和批量操作结果
         * @param {Object} options - 配置选项
         * @param {string} options.containerId - 容器元素ID
         * @param {number} options.total - 总数
         * @param {number} options.current - 当前处理数
         * @param {number} options.success - 成功数
         * @param {number} options.failed - 失败数
         */
        showProgress: function(options) {
            var defaults = {
                containerId: '#batch-generate-result',
                total: 0,
                current: 0,
                success: 0,
                failed: 0
            };

            var settings = $.extend({}, defaults, options);
            var percentage = settings.total > 0 ? (settings.current / settings.total * 100) : 0;

            var progressHTML = '<div class="progress-container">' +
                '<h4>批量处理进度</h4>' +
                '<div class="progress-bar-container">' +
                '<div class="progress-bar">' +
                '<div class="progress-fill" style="width: ' + percentage + '%"></div>' +
                '</div>' +
                '<span class="progress-text">' + Math.round(percentage) + '%</span>' +
                '</div>' +
                '<div class="progress-details">' +
                '<span>总计: <span>' + settings.total + '</span></span>' +
                '<span>已完成: <span>' + settings.current + '</span></span>' +
                '<span>成功: <span>' + settings.success + '</span></span>' +
                '<span>失败: <span>' + settings.failed + '</span></span>' +
                '</div>' +
                '</div>';

            $(settings.containerId).html(progressHTML);
        },

        /**
         * 显示操作结果消息
         * @param {string} message - 消息内容
         * @param {string} type - 消息类型 ('success', 'error', 'warning', 'info')
         */
        showMessage: function(message, type) {
            type = type || 'info';
            var className = 'notice notice-' + type + ' is-dismissible';
            var messageHTML = '<div class="' + className + '"><p>' + message + '</p></div>';

            // 在页面顶部显示消息
            $('.wrap > h1').after(messageHTML);

            // 自动消失
            setTimeout(function() {
                $('.' + className).fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * 安全的AJAX请求包装器
         * @param {Object} options - AJAX选项
         */
        safeAjax: function(options) {
            var defaults = {
                type: 'POST',
                url: ajaxurl,
                dataType: 'json'
            };

            var settings = $.extend({}, defaults, options);

            return $.ajax(settings).fail(function(xhr, status, error) {
                console.error('AJAX请求失败:', error);
                SaiitaSharedAdmin.showMessage('操作失败，请稍后重试。', 'error');
            });
        }
    };

    // 在文档加载完成后初始化
    $(document).ready(function() {
        // 这里可以添加通用的初始化代码
    });

})(jQuery);