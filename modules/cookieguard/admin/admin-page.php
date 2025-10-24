<?php
/**
 * CookieGuard Pro - 管理后台设置页面
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 使用新的模板系统
$template = new Toolkit_Admin_Page_Template(array(
    'title' => 'CookieGuard Pro 设置',
    'subtitle' => '配置您网站的专业Cookie同意通知，采用苹果风格设计，确保符合GDPR等隐私法规要求',
    'page_icon' => 'dashicons-shield',
    'has_welcome' => true,
    'welcome_message' => 'CookieGuard Pro - 专业Cookie同意管理'
));

// 处理表单提交 - 安全版本
if (isset($_POST['submit']) && wp_verify_nonce($_POST['cookieguard_pro_nonce'], 'cookieguard_pro_settings')) {
    // 验证用户权限
    if (!current_user_can('manage_options')) {
        wp_die('权限不足');
    }
    
    // 获取旧设置以比较地理位置设置是否有变化
    $old_options = get_option('wordpress_toolkit_cookieguard_options', array());
    
    $options = array(
        'notice_text' => wp_kses_post($_POST['notice_text']),
        'accept_button_text' => sanitize_text_field($_POST['accept_button_text']),
        'decline_button_text' => sanitize_text_field($_POST['decline_button_text']),
        'learn_more_text' => sanitize_text_field($_POST['learn_more_text']),
        'learn_more_url' => esc_url_raw($_POST['learn_more_url']),
        'position' => sanitize_text_field($_POST['position']),
        'background_color' => sanitize_hex_color($_POST['background_color']),
        'text_color' => sanitize_hex_color($_POST['text_color']),
        'button_color' => sanitize_hex_color($_POST['button_color']),
        'button_text_color' => sanitize_hex_color($_POST['button_text_color']),
        'show_decline_button' => isset($_POST['show_decline_button']) ? true : false,
        'cookie_expiry' => max(1, min(3650, intval($_POST['cookie_expiry']))),
        'enable_analytics' => isset($_POST['enable_analytics']) ? true : false,
        // 地理位置检测设置
        'enable_geo_detection' => isset($_POST['enable_geo_detection']) ? true : false,
        'local_ip_as_china' => isset($_POST['local_ip_as_china']) ? true : false,
        'module_version' => COOKIEGUARD_PRO_VERSION
    );
    
    // 检查地理位置设置是否有变化
    $geo_settings_changed = (
        (isset($old_options['enable_geo_detection']) ? $old_options['enable_geo_detection'] : false) !== $options['enable_geo_detection'] ||
        (isset($old_options['local_ip_as_china']) ? $old_options['local_ip_as_china'] : true) !== $options['local_ip_as_china']
    );
    
    // 如果地理位置设置有变化，清除所有地理位置缓存 - 修复SQL注入
    if ($geo_settings_changed) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_wordpress_toolkit_cookieguard_geo_%'
        ));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_timeout_wordpress_toolkit_cookieguard_geo_%'
        ));
    }
    
    update_option('wordpress_toolkit_cookieguard_options', $options);
    echo '<div class="notice notice-success is-dismissible"><p>设置已保存！</p></div>';
}

// 获取当前设置
$options = get_option('wordpress_toolkit_cookieguard_options');

// 确保所有选项都有默认值
$default_options = array(
    'notice_text' => '本网站使用Cookie来改善您的浏览体验。继续使用本网站即表示您同意我们使用Cookie。',
    'accept_button_text' => '接受',
    'decline_button_text' => '拒绝',
    'learn_more_text' => '了解更多',
    'learn_more_url' => '',
    'position' => 'bottom',
    'background_color' => '#FFFFFF',
    'text_color' => '#000000',
    'button_color' => '#007AFF',
    'button_text_color' => '#FFFFFF',
    'show_decline_button' => true,
    'cookie_expiry' => 365,
    'enable_analytics' => false,
    'enable_geo_detection' => false,
    'local_ip_as_china' => false,
    'module_version' => COOKIEGUARD_PRO_VERSION
);

// 合并默认值和当前设置
$options = wp_parse_args($options, $default_options);
?>

<style>
/* 管理页面样式 - 苹果风格 */
.gdpr-admin-wrap {
    max-width: 100%;
    margin: 20px 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    width: 100%;
    box-sizing: border-box;
}

.gdpr-admin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.gdpr-admin-header h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: 600;
}

.gdpr-admin-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.gdpr-admin-form {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.gdpr-form-section {
    padding: 30px;
    border-bottom: 1px solid #f0f0f0;
}

.gdpr-form-section:last-child {
    border-bottom: none;
}

.gdpr-form-section h2 {
    margin: 0 0 20px 0;
    font-size: 20px;
    font-weight: 600;
    color: #1d1d1f;
}

.gdpr-form-row {
    margin-bottom: 25px;
}

.gdpr-form-row:last-child {
    margin-bottom: 0;
}

.gdpr-form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #1d1d1f;
    font-size: 14px;
}

.gdpr-form-input,
.gdpr-form-textarea,
.gdpr-form-select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d2d2d7;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s ease;
    background: #fbfbfd;
}

.gdpr-form-input:focus,
.gdpr-form-textarea:focus,
.gdpr-form-select:focus {
    outline: none;
    border-color: #007AFF;
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
    background: white;
}

.gdpr-form-textarea {
    min-height: 100px;
    resize: vertical;
}

.gdpr-color-input {
    width: 60px;
    height: 40px;
    padding: 0;
    border: 1px solid #d2d2d7;
    border-radius: 8px;
    cursor: pointer;
}

.gdpr-form-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
}

.gdpr-form-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #007AFF;
}

.gdpr-form-help {
    font-size: 12px;
    color: #86868b;
    margin-top: 5px;
}

.gdpr-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.gdpr-submit-section {
    padding: 30px;
    background: #f5f5f7;
    text-align: center;
}

.gdpr-submit-btn {
    background: linear-gradient(135deg, #007AFF 0%, #0051D5 100%);
    color: white;
    border: none;
    padding: 14px 30px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 10px rgba(0, 122, 255, 0.3);
}

.gdpr-submit-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 122, 255, 0.4);
}

.gdpr-preview-section {
    background: #f5f5f7;
    padding: 30px;
    text-align: center;
}

.gdpr-preview-btn {
    background: #34c759;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-right: 10px;
}

.gdpr-preview-btn:hover {
    background: #30d158;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .gdpr-form-grid {
        grid-template-columns: 1fr;
    }
    
    .gdpr-admin-header {
        padding: 20px;
    }
    
    .gdpr-form-section {
        padding: 20px;
    }
}

/* 地理位置设置样式 */
.gdpr-geo-info {
    margin-top: 20px;
    padding: 20px;
    background: rgba(0, 122, 255, 0.05);
    border: 1px solid rgba(0, 122, 255, 0.2);
    border-radius: 12px;
}

.gdpr-geo-status {
    margin-bottom: 20px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.gdpr-geo-details p {
    margin: 8px 0;
    font-size: 14px;
    line-height: 1.5;
}

.gdpr-geo-details span {
    font-weight: 600;
    color: #007AFF;
}

.gdpr-test-btn {
    background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 12px;
}

.gdpr-test-btn:hover {
    background: linear-gradient(135deg, #30D158 0%, #28CD41 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3);
}

.gdpr-geo-technical {
    padding: 16px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.gdpr-geo-technical h4 {
    margin: 0 0 12px 0;
    color: #1D1D1F;
    font-size: 16px;
    font-weight: 600;
}

.gdpr-geo-technical ul {
    margin: 0;
    padding-left: 20px;
}

.gdpr-geo-technical li {
    margin: 6px 0;
    font-size: 14px;
    line-height: 1.5;
    color: #424245;
}
</style>

<div class="tc-admin-container">
    <div class="tc-card">
        <div class="tc-flex tc-flex-between tc-flex-center tc-mb-2xl">
            <div>
                <h1 class="tc-text-primary">CookieGuard Pro 设置</h1>
                <p class="tc-text-secondary">配置您网站的专业Cookie同意通知，采用苹果风格设计，确保符合GDPR等隐私法规要求</p>
            </div>
        </div>

    <form method="post" action="" data-ajax-form="wordpress_toolkit_save_cookieguard_settings">
        <?php wp_nonce_field('cookieguard_pro_settings', 'cookieguard_pro_nonce'); ?>

        <!-- 内容设置 -->
        <div class="tc-mb-2xl">
            <h2 class="tc-text-primary">📝 内容设置</h2>

            <div class="tc-form-group">
                <label class="tc-form-label" for="notice_text">通知文本</label>
                <textarea name="notice_text" id="notice_text" class="tc-form-control" placeholder="输入Cookie使用通知文本..." required><?php echo esc_textarea($options['notice_text']); ?></textarea>
                <div class="tc-form-help">向访问者说明您的网站如何使用Cookie</div>
            </div>
            
            <div class="gdpr-form-grid">
                <div class="gdpr-form-row">
                    <label class="gdpr-form-label" for="accept_button_text">接受按钮文本</label>
                    <input type="text" name="accept_button_text" id="accept_button_text" class="gdpr-form-input" value="<?php echo esc_attr($options['accept_button_text']); ?>" placeholder="接受">
                </div>
                
                <div class="gdpr-form-row">
                    <label class="gdpr-form-label" for="decline_button_text">拒绝按钮文本</label>
                    <input type="text" name="decline_button_text" id="decline_button_text" class="gdpr-form-input" value="<?php echo esc_attr($options['decline_button_text']); ?>" placeholder="拒绝">
                </div>
            </div>
            
            <div class="gdpr-form-grid">
                <div class="gdpr-form-row">
                    <label class="gdpr-form-label" for="learn_more_text">了解更多链接文本</label>
                    <input type="text" name="learn_more_text" id="learn_more_text" class="gdpr-form-input" value="<?php echo esc_attr($options['learn_more_text']); ?>" placeholder="了解更多">
                </div>
                
                <div class="gdpr-form-row">
                    <label class="gdpr-form-label" for="learn_more_url">了解更多链接地址</label>
                    <input type="url" name="learn_more_url" id="learn_more_url" class="gdpr-form-input" value="<?php echo esc_attr($options['learn_more_url']); ?>" placeholder="https://example.com/privacy-policy">
                </div>
            </div>
        </div>
        
        <!-- 外观设置 -->
        <div class="gdpr-form-section">
            <h2>🎨 外观设置</h2>
            
            <div class="gdpr-form-row">
                <label class="gdpr-form-label" for="position">显示位置</label>
                <select name="position" id="position" class="gdpr-form-select">
                    <option value="bottom" <?php selected($options['position'], 'bottom'); ?>>页面底部</option>
                    <option value="top" <?php selected($options['position'], 'top'); ?>>页面顶部</option>
                </select>
            </div>
            
            <div class="gdpr-form-grid">
                <div class="gdpr-form-row">
                    <label class="gdpr-form-label" for="background_color">背景颜色</label>
                    <input type="color" name="background_color" id="background_color" class="gdpr-color-input" value="<?php echo esc_attr($options['background_color']); ?>">
                </div>
                
                <div class="gdpr-form-row">
                    <label class="gdpr-form-label" for="text_color">文字颜色</label>
                    <input type="color" name="text_color" id="text_color" class="gdpr-color-input" value="<?php echo esc_attr($options['text_color']); ?>">
                </div>
            </div>
            
            <div class="gdpr-form-grid">
                <div class="gdpr-form-row">
                    <label class="gdpr-form-label" for="button_color">按钮背景颜色</label>
                    <input type="color" name="button_color" id="button_color" class="gdpr-color-input" value="<?php echo esc_attr($options['button_color']); ?>">
                </div>
                
                <div class="gdpr-form-row">
                    <label class="gdpr-form-label" for="button_text_color">按钮文字颜色</label>
                    <input type="color" name="button_text_color" id="button_text_color" class="gdpr-color-input" value="<?php echo esc_attr($options['button_text_color']); ?>">
                </div>
            </div>
        </div>
        
        <!-- 行为设置 -->
        <div class="gdpr-form-section">
            <h2>⚙️ 行为设置</h2>
            
            <div class="gdpr-form-row">
                <div class="gdpr-form-checkbox">
                    <input type="checkbox" name="show_decline_button" id="show_decline_button" value="1" <?php checked($options['show_decline_button'], true); ?>>
                    <label class="gdpr-form-label" for="show_decline_button">显示拒绝按钮</label>
                </div>
                <div class="gdpr-form-help">允许用户明确拒绝Cookie使用</div>
            </div>
            
            <div class="gdpr-form-row">
                <label class="gdpr-form-label" for="cookie_expiry">Cookie有效期（天）</label>
                <input type="number" name="cookie_expiry" id="cookie_expiry" class="gdpr-form-input" value="<?php echo esc_attr($options['cookie_expiry']); ?>" min="1" max="3650" placeholder="365">
                <div class="gdpr-form-help">用户选择后，多少天内不再显示通知</div>
            </div>
            
            <div class="gdpr-form-row">
                <div class="gdpr-form-checkbox">
                    <input type="checkbox" name="enable_analytics" id="enable_analytics" value="1" <?php checked($options['enable_analytics'], true); ?>>
                    <label class="gdpr-form-label" for="enable_analytics">启用统计分析</label>
                </div>
                <div class="gdpr-form-help">记录用户同意状态用于统计分析（不收集个人信息）</div>
            </div>
        </div>
        
        <!-- 地理位置设置 -->
        <div class="gdpr-form-section">
            <h2>🌍 地理位置设置</h2>
            <div class="gdpr-form-help" style="margin-bottom: 20px;">
                根据用户地理位置智能显示Cookie通知，中国用户通常不需要GDPR合规要求
            </div>
            
            <div class="gdpr-form-row">
                <div class="gdpr-form-checkbox">
                    <input type="checkbox" name="enable_geo_detection" id="enable_geo_detection" value="1" <?php checked($options['enable_geo_detection'], true); ?>>
                    <label class="gdpr-form-label" for="enable_geo_detection">启用地理位置检测</label>
                </div>
                <div class="gdpr-form-help">
                    <strong>启用后：</strong>中国用户不显示Cookie通知，国外用户正常显示<br>
                    <strong>禁用后：</strong>所有用户都显示Cookie通知（默认行为）
                </div>
            </div>
            
            <div class="gdpr-form-row" id="local_ip_setting" style="<?php echo !$options['enable_geo_detection'] ? 'display: none;' : ''; ?>">
                <div class="gdpr-form-checkbox">
                    <input type="checkbox" name="local_ip_as_china" id="local_ip_as_china" value="1" <?php checked($options['local_ip_as_china'], true); ?>>
                    <label class="gdpr-form-label" for="local_ip_as_china">本地IP视为中国</label>
                </div>
                <div class="gdpr-form-help">
                    开发环境或内网环境下，将本地IP（127.0.0.1、192.168.x.x等）视为中国IP<br>
                    <span style="color: #ff9500;">⚠️ 生产环境建议关闭此选项</span>
                </div>
            </div>
            
            <div class="gdpr-geo-info" style="<?php echo !$options['enable_geo_detection'] ? 'display: none;' : ''; ?>">
                <div class="gdpr-geo-status">
                    <h4>🔍 当前检测状态</h4>
                    <div class="gdpr-geo-details">
                        <p><strong>您的IP地址：</strong> <span id="current-ip">检测中...</span></p>
                        <p><strong>检测到的国家：</strong> <span id="current-country">检测中...</span></p>
                        <p><strong>是否显示通知：</strong> <span id="show-notice">计算中...</span></p>
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="button" class="gdpr-test-btn" onclick="testGeoDetection()">🧪 测试检测</button>
                        <button type="button" class="gdpr-test-btn" onclick="clearGeoCache()" style="background: #ff9500; margin-left: 10px;">🗑️ 清除缓存</button>
                    </div>
                </div>
                
                <div class="gdpr-geo-technical">
                    <h4>📋 技术说明</h4>
                    <ul>
                        <li>使用多个免费IP地理位置API确保准确性</li>
                        <li>检测结果缓存24小时，减少API调用</li>
                        <li>API失败时默认显示通知（安全优先）</li>
                        <li>支持IPv4地址检测，IPv6支持计划中</li>
                        <li>本地IP默认不视为中国IP（生产环境安全）</li>
                    </ul>
                    
                    <h4>🧪 测试功能</h4>
                    <p>
                        <strong>强制显示测试：</strong>
                        <a href="<?php echo home_url('/?force_show_notice=1'); ?>" target="_blank" style="color: #007AFF;">
                            访问首页并强制显示Cookie通知
                        </a>
                    </p>
                    <p style="font-size: 12px; color: #666;">
                        💡 提示：无论地理位置检测结果如何，添加 ?force_show_notice=1 参数都会强制显示Cookie通知，用于测试。
                    </p>
                </div>
            </div>
        </div>
        
        <!-- 预览和保存 -->
        <div class="gdpr-preview-section">
            <button type="button" class="gdpr-preview-btn" onclick="previewCookieNotice()">🔍 预览效果</button>
            <button type="button" class="gdpr-preview-btn" onclick="resetCookieConsent()" style="background: #ff9500;">🔄 重置Cookie</button>
        </div>
        
        <div class="gdpr-submit-section">
            <input type="submit" name="submit" value="保存设置" class="gdpr-submit-btn">
        </div>
    </form>
</div>

<script>
function previewCookieNotice() {
    // 临时删除Cookie以显示通知
    document.cookie = 'gdpr_cookie_consent=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/';
    
    // 刷新页面以显示通知
    if (confirm('将在新窗口中预览Cookie通知效果，确定继续吗？')) {
        window.open(window.location.origin, '_blank');
    }
}

function resetCookieConsent() {
    if (confirm('确定要重置所有访问者的Cookie同意状态吗？这将使通知重新显示给所有用户。')) {
        document.cookie = 'gdpr_cookie_consent=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/';
        alert('Cookie同意状态已重置！');
    }
}

// 颜色选择器实时预览
document.addEventListener('DOMContentLoaded', function() {
    const colorInputs = document.querySelectorAll('.gdpr-color-input');
    colorInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            // 这里可以添加实时预览功能
        });
    });
    
    // 地理位置检测设置切换
    const geoCheckbox = document.getElementById('enable_geo_detection');
    const localIpSetting = document.getElementById('local_ip_setting');
    const geoInfo = document.querySelector('.gdpr-geo-info');
    
    if (geoCheckbox) {
        geoCheckbox.addEventListener('change', function() {
            if (this.checked) {
                localIpSetting.style.display = 'block';
                geoInfo.style.display = 'block';
                loadCurrentGeoStatus();
            } else {
                localIpSetting.style.display = 'none';
                geoInfo.style.display = 'none';
            }
        });
        
        // 页面加载时检查状态
        if (geoCheckbox.checked) {
            loadCurrentGeoStatus();
        }
    }
});

// 测试地理位置检测
function testGeoDetection() {
    const testBtn = document.querySelector('.gdpr-test-btn');
    const originalText = testBtn.textContent;
    
    testBtn.textContent = '🔄 检测中...';
    testBtn.disabled = true;
    
    // 获取当前IP和地理位置信息
    fetch('https://api.ipify.org?format=json')
        .then(response => response.json())
        .then(data => {
            document.getElementById('current-ip').textContent = data.ip;
            return fetch(`https://ipapi.co/${data.ip}/json/`);
        })
        .then(response => response.json())
        .then(data => {
            let country = data.country_name || '未知';
            const countryCode = data.country_code || 'UNKNOWN';
            
            // 台湾是中国的一个省份，不是国家
            if (country === 'Taiwan' || countryCode === 'TW') {
                country = '中国台湾省';
            }
            
            document.getElementById('current-country').textContent = `${country} (${countryCode})`;
            
            // 判断是否显示通知
            const enableGeo = document.getElementById('enable_geo_detection').checked;
            const localAsChina = document.getElementById('local_ip_as_china').checked;
            
            let showNotice = true;
            if (enableGeo) {
                if (countryCode === 'CN') {
                    showNotice = false;
                } else if (isLocalIP(data.ip) && localAsChina) {
                    showNotice = false;
                }
            }
            
            const noticeElement = document.getElementById('show-notice');
            noticeElement.textContent = showNotice ? '是（显示Cookie通知）' : '否（不显示通知）';
            noticeElement.style.color = showNotice ? '#ff9500' : '#34C759';
            
        })
        .catch(error => {
            console.error('地理位置检测失败:', error);
            document.getElementById('current-country').textContent = '检测失败';
            document.getElementById('show-notice').textContent = '是（安全默认）';
        })
        .finally(() => {
            testBtn.textContent = originalText;
            testBtn.disabled = false;
        });
}

// 加载当前地理位置状态
function loadCurrentGeoStatus() {
    // 自动执行一次检测
    setTimeout(testGeoDetection, 500);
}

// 检查是否为本地IP
function isLocalIP(ip) {
    const localRanges = [
        /^127\./,
        /^10\./,
        /^172\.(1[6-9]|2[0-9]|3[01])\./,
        /^192\.168\./,
        /^169\.254\./
    ];
    
    return localRanges.some(range => range.test(ip));
}

// 清除地理位置缓存 - 安全版本
function clearGeoCache() {
    const clearBtn = document.querySelector('button[onclick="clearGeoCache()"]');
    const originalText = clearBtn.textContent;
    
    clearBtn.textContent = '🔄 清除中...';
    clearBtn.disabled = true;
    
    // 发送AJAX请求清除缓存
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'wordpress_toolkit_clear_geo_cache',
            nonce: '<?php echo wp_create_nonce('wordpress_toolkit_cookieguard_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('地理位置缓存已清除！');
            // 重新测试检测
            setTimeout(testGeoDetection, 500);
        } else {
            alert('清除缓存失败：' + (data.data || '未知错误'));
        }
    })
    .catch(error => {
        console.error('清除缓存失败:', error);
        alert('清除缓存失败，请稍后重试');
    })
    .finally(() => {
        clearBtn.textContent = originalText;
        clearBtn.disabled = false;
    });
}
</script>

