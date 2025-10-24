/**
 * Time Capsule - 管理后台JavaScript (重构版本)
 * 使用统一的核心框架，功能保持不变
 */

(function($) {
    'use strict';

    // 确保TimeCapsuleAdmin对象存在
    if (typeof TimeCapsuleAdmin === 'undefined') {
        window.TimeCapsuleAdmin = $.extend({}, ToolkitCore, {
            ajaxUrl: window.ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: '',
            strings: {
                saveSuccess: '保存成功！',
                saveError: '保存失败，请重试。',
                networkError: '网络错误，请重试。',
                confirmDelete: '确定要删除这个物品吗？此操作不可撤销。',
                deleteSuccess: '删除成功！',
                deleteError: '删除失败，请重试。'
            }
        });
    } else {
        // 扩展现有的TimeCapsuleAdmin对象
        window.TimeCapsuleAdmin = $.extend(TimeCapsuleAdmin, ToolkitCore);
    }

    // 扩展TimeCapsuleAdmin对象的功能
    $.extend(TimeCapsuleAdmin, {

        init: function() {
            this.bindEvents();
            this.initModal();
        },

        bindEvents: function() {
            // 使用ToolkitCore的自动绑定功能
            // 表单提交
            $(document).on('submit', '#tc-item-form', this.handleFormSubmit);

            // 编辑物品
            $(document).on('click', '.tc-edit-item', this.handleEditItem);

            // 查看物品
            $(document).on('click', '.tc-view-item', this.handleViewItem);

            // 删除物品
            $(document).on('click', '.tc-delete-item', this.handleDeleteItem);

            // 导出数据
            $(document).on('click', '#tc-export-btn', this.handleExport);

            // 模态框关闭
            $(document).on('click', '.tc-modal-close, #tc-modal-cancel', this.closeModal);

            // 模态框保存
            $(document).on('click', '#tc-modal-save', this.handleModalSave);

            // 点击模态框外部关闭
            $(document).on('click', '.tc-modal', function(e) {
                if (e.target === this) {
                    TimeCapsuleAdmin.closeModal();
                }
            });
        },

        handleFormSubmit: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();

            // 禁用提交按钮
            TimeCapsuleAdmin.setButtonLoading($submitBtn, true);

            // 收集表单数据
            var formData = {
                action: 'time_capsule_save_item',
                nonce: TimeCapsuleAdmin.nonce
            };

            $form.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                if (name && name !== 'tc_nonce') {
                    formData[name] = value;
                }
            });

            // 使用核心框架的AJAX方法
            TimeCapsuleAdmin.ajax({
                data: formData
            }).done(function(response) {
                if (response.success) {
                    var successMessage = TimeCapsuleAdmin.strings.saveSuccess || '保存成功！';
                    TimeCapsuleAdmin.showNotice('success', response.data && response.data.message ? response.data.message : successMessage);

                    // 如果是新增，重定向到编辑页面
                    if (!formData.id && response.data && response.data.id) {
                        setTimeout(function() {
                            window.location.href = window.location.href.split('?')[0] +
                                '?page=wordpress-toolkit-time-capsule&tab=add&edit=' + response.data.id;
                        }, 1000);
                    }
                } else {
                    var errorMessage = TimeCapsuleAdmin.strings.saveError || '保存失败，请重试。';
                    TimeCapsuleAdmin.showNotice('error', response.data && response.data.message ? response.data.message : errorMessage);
                }
            }).fail(function(xhr, status, error) {
                console.error('=== AJAX REQUEST FAILED ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('XHR Status:', xhr.status);
                console.error('XHR Status Text:', xhr.statusText);
                console.error('XHR Response Text:', xhr.responseText);
                console.error('==========================');

                var networkError = TimeCapsuleAdmin.strings.networkError || '网络错误，请重试。';
                TimeCapsuleAdmin.showNotice('error', networkError);
            }).always(function() {
                TimeCapsuleAdmin.setButtonLoading($submitBtn, false);
            });
        },

        handleEditItem: function(e) {
            e.preventDefault();
            var itemId = $(this).data('item-id');
            var baseUrl = window.location.origin + window.location.pathname;
            window.location.href = baseUrl + '?page=wordpress-toolkit-time-capsule&tab=add&edit=' + itemId;
        },

        handleViewItem: function(e) {
            e.preventDefault();
            var itemId = $(this).data('item-id');
            TimeCapsuleAdmin.loadItemDetails(itemId);
        },

        handleDeleteItem: function(e) {
            e.preventDefault();

            var confirmMessage = TimeCapsuleAdmin.strings.confirmDelete || '确定要删除这个物品吗？此操作不可撤销。';
            if (!confirm(confirmMessage)) {
                return;
            }

            var $btn = $(this);
            var itemId = $btn.data('item-id');

            TimeCapsuleAdmin.setButtonLoading($btn, true);

            TimeCapsuleAdmin.ajax({
                data: {
                    action: 'time_capsule_delete_item',
                    item_id: itemId
                }
            }).done(function(response) {
                if (response.success) {
                    var successMessage = TimeCapsuleAdmin.strings.deleteSuccess || '删除成功！';
                    TimeCapsuleAdmin.showNotice('success', response.data && response.data.message ? response.data.message : successMessage);

                    // 移除表格行或重新加载页面
                    var $row = $btn.closest('tr');
                    if ($row.length) {
                        $row.fadeOut(function() {
                            $row.remove();
                        });
                    } else {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    var errorMessage = TimeCapsuleAdmin.strings.deleteError || '删除失败，请重试。';
                    TimeCapsuleAdmin.showNotice('error', response.data && response.data.message ? response.data.message : errorMessage);
                    TimeCapsuleAdmin.setButtonLoading($btn, false);
                }
            }).fail(function() {
                var networkError = TimeCapsuleAdmin.strings.networkError || '网络错误，请重试。';
                TimeCapsuleAdmin.showNotice('error', networkError);
                TimeCapsuleAdmin.setButtonLoading($btn, false);
            });
        },

        handleExport: function(e) {
            e.preventDefault();

            var format = prompt('请选择导出格式：\n1. CSV\n2. JSON\n\n请输入 1 或 2：');

            if (format === '1' || format === '2') {
                var exportFormat = format === '1' ? 'csv' : 'json';
                var url = ajaxurl + '?action=time_capsule_export_data&format=' + exportFormat +
                         '&nonce=' + ($('#tc_nonce').val() || $('input[name="tc_nonce"]').val());

                TimeCapsuleAdmin.downloadFile(url, 'time-capsule-export.' + exportFormat);
            }
        },

        loadItemDetails: function(itemId) {
            var self = this;

            this.openModal('物品详情', '加载中...', {
                showFooter: false
            });

            this.ajax({
                data: {
                    action: 'time_capsule_get_item_details',
                    item_id: itemId
                }
            }).done(function(response) {
                if (response.success && response.data) {
                    self.renderItemDetails(response.data);
                } else {
                    $('#tc-modal-body').html('<p>加载失败，请重试。</p>');
                }
            }).fail(function() {
                $('#tc-modal-body').html('<p>网络错误，请重试。</p>');
            });
        },

        renderItemDetails: function(item) {
            var html = '<div class="tc-item-details">';

            // 头部信息
            html += '<div class="tc-detail-header">';
            html += '<div class="tc-detail-avatar">' + this.getItemAvatar(item.category) + '</div>';
            html += '<div class="tc-detail-info">';
            html += '<h4>' + this.escapeHtml(item.name) + '</h4>';
            if (item.brand) {
                html += '<div class="tc-detail-brand">' + this.escapeHtml(item.brand) + '</div>';
            }
            html += '<div class="tc-detail-category">' + this.escapeHtml(item.category_display_name) + '</div>';
            html += '</div>';
            html += '</div>';

            // 主要内容区域
            html += '<div class="tc-detail-content">';

            // 基本信息卡片
            html += '<div class="tc-card tc-mb-lg">';
            html += '<h5 class="tc-text-primary">基本信息</h5>';
            html += '<div class="tc-grid tc-grid-2">';
            html += '<div><label>购买日期</label><span>' + item.purchase_date + '</span></div>';
            if (item.purchase_source) {
                html += '<div><label>购买来源</label><span>' + this.escapeHtml(item.purchase_source) + '</span></div>';
            }
            if (item.price) {
                html += '<div><label>价格</label><span>' + this.formatCurrency(item.price) + '</span></div>';
            }
            html += '<div><label>状态</label><span class="tc-badge ' + this.getStatusClass(item.status) + '">' + this.getStatusText(item.status) + '</span></div>';
            html += '</div>';
            html += '</div>';

            // 描述信息
            if (item.description) {
                html += '<div class="tc-card tc-mb-lg">';
                html += '<h5 class="tc-text-primary">物品描述</h5>';
                html += '<p>' + this.escapeHtml(item.description) + '</p>';
                html += '</div>';
            }

            // 其他信息...
            if (item.warranty_period > 0) {
                html += '<div class="tc-card tc-mb-lg">';
                html += '<h5 class="tc-text-primary">保修信息</h5>';
                html += '<div class="tc-grid tc-grid-2">';
                html += '<div><label>保修期</label><span>' + item.warranty_period + ' 个月</span></div>';
                html += '<div><label>保修状态</label><span class="tc-badge ' + this.getWarrantyStatusClass(item.warranty_status) + '">' + this.getWarrantyStatusText(item.warranty_status) + '</span></div>';
                html += '</div>';
                html += '</div>';
            }

            html += '</div>';
            html += '</div>';

            $('#tc-modal-body').html(html);
        },

        initModal: function() {
            // 模态框已通过核心框架初始化
        },

        handleModalSave: function() {
            TimeCapsuleAdmin.closeModal();
        },

        // 辅助方法
        getItemAvatar: function(category) {
            var avatars = {
                'vehicle': '🚗',
                'electronics': '📱',
                'food': '🍿',
                'furniture': '🪑'
            };
            return avatars[category] || '📦';
        },

        getStatusClass: function(status) {
            return 'tc-badge-' + status;
        },

        getStatusText: function(status) {
            var statusTexts = {
                'active': '使用中',
                'inactive': '闲置',
                'disposed': '已处置'
            };
            return statusTexts[status] || '未知';
        },

        getWarrantyStatusText: function(status) {
            var statusTexts = {
                'valid': '保修中',
                'expiring': '即将过保',
                'expired': '已过保',
                'no_warranty': '无保修'
            };
            return statusTexts[status] || '未知';
        },

        getWarrantyStatusClass: function(status) {
            return 'tc-badge-' + status;
        }
    });

    // 初始化
    $(document).ready(function() {
        TimeCapsuleAdmin.init();
    });

})(jQuery);