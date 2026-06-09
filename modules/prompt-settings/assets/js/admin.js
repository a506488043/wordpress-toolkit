/**
 * Prompt Settings 模块管理后台脚本
 *
 * @version 1.0.0
 * @author WordPress Toolkit
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // 重置提示词按钮
        $('.reset-prompt-btn').on('click', function() {
            var $btn = $(this);
            var module = $btn.data('module');
            var nonce = $btn.data('nonce');

            $btn.prop('disabled', true).text(SaiitaAIToolkitPromptSettings.resettingText);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'saiita_ai_toolkit_reset_prompt_default',
                    module: module,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#' + module + '_prompt').val(response.data.prompt);
                        alert(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(SaiitaAIToolkitPromptSettings.requestFailedText);
                },
                complete: function() {
                    $btn.prop('disabled', false).text(SaiitaAIToolkitPromptSettings.resetText);
                }
            });
        });
    });

})(jQuery);
