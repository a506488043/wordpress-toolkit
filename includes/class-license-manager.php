<?php
/**
 * License Manager
 * 所有功能完全免费版本
 */

if (!defined('ABSPATH')) {
    exit;
}

class Saiita_AI_Toolkit_License_Manager
{
    use Saiita_AI_Toolkit_Singleton;

    private $option_key = 'saiita_ai_content_toolkit_license';

    /**
     * 所有功能完全可用
     */
    public function is_pro()
    {
        return true;
    }

    /**
     * 所有功能完全可用
     */
    public function can_use($feature)
    {
        return array('allowed' => true, 'reason' => '功能完全可用');
    }

    /**
     * 激活许可证(已废弃,所有功能免费)
     */
    public function activate_license($license_key)
    {
        return array('success' => true, 'message' => '所有功能已完全免费');
    }

    /**
     * 停用许可证
     */
    public function deactivate_license()
    {
        delete_option($this->option_key);
        return array('success' => true);
    }

    /**
     * 获取许可证状态
     */
    public function get_license_status()
    {
        return array(
            'active' => true,
            'key' => 'FREE_VERSION',
            'type' => 'free',
            'expires' => '永久'
        );
    }

    /**
     * 增加使用次数(无操作)
     */
    public function increment_usage($feature, $count = 1)
    {
        return true;
    }
}
