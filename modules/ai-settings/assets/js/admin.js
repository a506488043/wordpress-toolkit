/**
 * AI Settings Module - Admin JavaScript
 */
jQuery(document).ready(function($) {
    // 温度值显示更新
    $('#ai_temperature').on('input', function() {
        $('#temperature-value').text($(this).val());
    });

    // AI提供商切换功能
    function toggleProviderConfig() {
        var provider = $('#ai_provider').val();

        // 隐藏所有提供商配置
        $('.provider-config').hide();

        // 显示当前选中的提供商配置
        $('.' + provider + '-config').show();

        // 更新API测试按钮的字段
        updateApiTestFields(provider);
    }

    // 更新API测试按钮使用的字段
    function updateApiTestFields(provider) {
        var apiKeyField = provider + '_api_key';
        var apiBaseField = provider + '_api_base';
        var modelField = provider + '_model';

        // 更新API测试按钮的数据源
        $('#test-api-btn').data('api-key-field', apiKeyField);
        $('#test-api-btn').data('api-base-field', apiBaseField);
        $('#test-api-btn').data('model-field', modelField);
    }

    // 初始化提供商配置显示
    toggleProviderConfig();

    // 监听提供商切换
    $('#ai_provider').on('change', toggleProviderConfig);

    // API测试功能
    $('#test-api-btn').on('click', function() {
        var $btn = $(this);
        var $result = $('#api-test-result');
        var provider = $('#ai_provider').val();

        // 获取当前提供商的字段
        var apiKeyField = $btn.data('api-key-field') || 'deepseek_api_key';
        var apiBaseField = $btn.data('api-base-field') || 'deepseek_api_base';
        var modelField = $btn.data('model-field') || 'deepseek_model';

        $btn.prop('disabled', true).text(SaiitaAIToolkitSettings.i18n.testing);
        $result.removeClass('success error').html('');

        $.ajax({
            url: SaiitaAIToolkitSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'saiita_ai_toolkit_test_api',
                nonce: SaiitaAIToolkitSettings.nonce,
                api_key: $('#' + apiKeyField).val(),
                api_base: $('#' + apiBaseField).val(),
                model: $('#' + modelField).val(),
                provider: provider
            },
            success: function(response) {
                if (response.success) {
                    $result.addClass('success').html(response.data.message);
                } else {
                    $result.addClass('error').html(response.data.message);
                }
            },
            error: function() {
                $result.addClass('error').html(SaiitaAIToolkitSettings.i18n.networkError);
            },
            complete: function() {
                $btn.prop('disabled', false).text(SaiitaAIToolkitSettings.i18n.testApi);
            }
        });
    });
});
