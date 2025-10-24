# WordPress Toolkit

<div style="text-align: right; margin-bottom: 20px;">
  <button id="lang-switch" style="padding: 8px 16px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; transition: background-color 0.3s;">English</button>
</div>

<div id="content-zh" style="display: block;">
一个功能强大、安全可靠的WordPress综合工具包，集成了四个实用工具模块，为网站提供全面的功能支持。
</div>

<div id="content-en" style="display: none;">
A powerful, secure, and reliable WordPress comprehensive toolkit that integrates four practical tool modules to provide comprehensive functionality support for websites.
</div>

## 📋 基本信息

<div class="content-zh">
- **插件名称**: WordPress Toolkit
- **版本**: 1.0.3
- **作者**: www.saiita.com.cn
- **许可证**: GPL v2 或更高版本
- **最低要求**: WordPress 5.0+, PHP 7.4+
- **测试兼容**: WordPress 6.4
- **插件地址**: https://www.saiita.com.cn
</div>

<div class="content-en" style="display: none;">
- **Plugin Name**: WordPress Toolkit
- **Version**: 1.0.3
- **Author**: www.saiita.com.cn
- **License**: GPL v2 or later
- **Minimum Requirements**: WordPress 5.0+, PHP 7.4+
- **Tested Compatibility**: WordPress 6.4
- **Plugin URL**: https://www.saiita.com.cn
</div>

## 🛠️ 核心模块

### 🌐 网站卡片 (Custom Card)
<div class="content-zh">**版本**: 1.0.3</div>
<div class="content-en" style="display: none;">**Version**: 1.0.3</div>

<div class="content-zh">
自动抓取网站元数据并生成美观的卡片展示。

**核心功能**:
- ✅ 多源数据抓取：支持Open Graph、Twitter Cards、Schema.org
- ✅ 智能缓存系统：三级缓存（数据库→Memcached→Opcache）
- ✅ SSRF安全防护：完整的URL验证和安全检查
- ✅ Gutenberg集成：支持可视化编辑器区块
- ✅ 点击统计：详细的卡片访问数据统计
- ✅ 响应式设计：完美适配移动端和桌面端

**使用方式**:
```php
// 短代码调用
[custom_card url="https://example.com"]
[custom_card_lazy url="https://example.com"]

// PHP函数调用
echo do_shortcode('[custom_card url="https://example.com"]');
```
</div>

<div class="content-en" style="display: none;">
Automatically fetch website metadata and generate beautiful card displays.

**Core Features**:
- ✅ **Multi-source Data Fetching**: Supports Open Graph, Twitter Cards, Schema.org
- ✅ **Smart Caching System**: Three-level caching (Database → Memcached → Opcache)
- ✅ **SSRF Security Protection**: Complete URL validation and security checks
- ✅ **Gutenberg Integration**: Support for visual editor blocks
- ✅ **Click Statistics**: Detailed card access data statistics
- ✅ **Responsive Design**: Perfect adaptation for mobile and desktop

**Usage**:
```php
// Shortcode calls
[custom_card url="https://example.com"]
[custom_card_lazy url="https://example.com"]

// PHP function call
echo do_shortcode('[custom_card url="https://example.com"]');
```
</div>

### 📅 年龄计算器 (Age Calculator)
<div class="content-zh">**版本**: 1.0.3</div>
<div class="content-en" style="display: none;">**Version**: 1.0.3</div>

<div class="content-zh">
精确计算年龄，特别针对闰年2月29日优化。

**核心功能**:
- ✅ 精确计算：使用PHP DateTime类处理复杂日期
- ✅ 闰年优化：完美处理2月29日出生的情况
- ✅ 多种格式：支持年、月、天、详细等多种显示格式
- ✅ 用户集成：与WordPress用户系统深度集成
- ✅ 记忆功能：为登录用户保存生日信息
- ✅ 交互模式：支持即时计算和表单模式

**使用方式**:
```php
// 显示完整计算器
[manus_age_calculator]

// 仅显示计算表单
[manus_age_calculator_form]

// 显示特定年龄
[manus_age_calculator date="1990-02-28"]
```
</div>

<div class="content-en" style="display: none;">
Calculate age precisely, with special optimization for leap year February 29th.

**Core Features**:
- ✅ **Precise Calculation**: Uses PHP DateTime class for complex date handling
- ✅ **Leap Year Optimization**: Perfect handling of February 29th birthdays
- ✅ **Multiple Formats**: Support years, months, days, detailed display formats
- ✅ **User Integration**: Deep integration with WordPress user system
- ✅ **Memory Function**: Saves birthday information for logged-in users
- ✅ **Interactive Mode**: Supports instant calculation and form mode

**Usage**:
```php
// Display complete calculator
[manus_age_calculator]

// Display calculation form only
[manus_age_calculator_form]

// Display specific age
[manus_age_calculator date="1990-02-28"]
```
</div>

### 📦 物品管理 (Time Capsule)
<div class="content-zh">**版本**: 1.0.6</div>
<div class="content-en" style="display: none;">**Version**: 1.0.6</div>

<div class="content-zh">
记录和管理个人物品购买信息，追踪使用情况和保修状态。

**核心功能**:
- ✅ 物品档案：完整的物品信息管理系统
- ✅ 分类管理：支持多种物品类别（电子产品、家具、交通工具等）
- ✅ 保修追踪：自动计算保修状态和到期提醒
- ✅ 使用统计：详细的使用时长和频率统计
- ✅ 数据导出：支持CSV和JSON格式导出
- ✅ 用户隔离：管理员和订阅者数据分离
- ✅ 多维度筛选：按类别、状态、保修期、用户等筛选

**物品类别支持**:
- 🚗 交通工具（汽车、摩托车、自行车等）
- 📱 电子产品（手机、电脑、家电等）
- 🪑 家具用品（沙发、床、桌子等）
- 👔 服装鞋帽（上衣、裤子、鞋子等）
- 🍔 食品饮料（零食、饮料、调料等）
- 📚 书籍文具（图书、文具、办公用品等）
- ⚽ 运动器材（健身器材、球类、户外装备等）

**使用方式**:
```php
// 显示物品列表和添加表单
[time_capsule]

// 显示单个物品详情
[time_capsule_item id="123"]

// 显示分类物品
[time_capsule category="电子产品"]
```
</div>

<div class="content-en" style="display: none;">
Record and manage personal item purchase information, track usage and warranty status.

**Core Features**:
- ✅ **Item Archives**: Complete item information management system
- ✅ **Category Management**: Support multiple item categories (electronics, furniture, vehicles, etc.)
- ✅ **Warranty Tracking**: Automatic warranty status calculation and expiration reminders
- ✅ **Usage Statistics**: Detailed usage duration and frequency statistics
- ✅ **Data Export**: Support CSV and JSON format export
- ✅ **User Isolation**: Separate data for administrators and subscribers
- ✅ **Multi-dimensional Filtering**: Filter by category, status, warranty period, user, etc.

**Supported Item Categories**:
- 🚗 **Vehicles** (cars, motorcycles, bicycles, etc.)
- 📱 **Electronics** (phones, computers, appliances, etc.)
- 🪑 **Furniture** (sofas, beds, tables, etc.)
- 👔 **Clothing & Shoes** (shirts, pants, shoes, etc.)
- 🍔 **Food & Beverages** (snacks, drinks, seasonings, etc.)
- 📚 **Books & Stationery** (books, stationery, office supplies, etc.)
- ⚽ **Sports Equipment** (fitness equipment, balls, outdoor gear, etc.)

**Usage**:
```php
// Display item list and add form
[time_capsule]

// Display single item details
[time_capsule_item id="123"]

// Display category items
[time_capsule category="Electronics"]
```
</div>

### 🍪 Cookie同意 (CookieGuard)
<div class="content-zh">**版本**: 1.0.3</div>
<div class="content-en" style="display: none;">**Version**: 1.0.3</div>

<div class="content-zh">
符合GDPR要求的专业Cookie同意通知系统。

**核心功能**:
- ✅ GDPR合规：完全符合欧盟数据保护法规
- ✅ 苹果风格设计：毛玻璃效果，现代化界面
- ✅ 智能地理检测：自动识别用户地理位置
- ✅ 无障碍支持：完整的键盘导航和屏幕阅读器支持
- ✅ 深色模式适配：自动适配系统深色偏好
- ✅ 多语言支持：国际化文本支持
- ✅ 用户偏好记忆：保存用户的Cookie选择

**特色设计**:
- 中国用户智能隐藏（符合本地化需求）
- 平滑动画过渡效果
- 自定义样式和文案配置
- 优雅的毛玻璃背景效果
</div>

<div class="content-en" style="display: none;">
Professional Cookie consent notification system compliant with GDPR requirements.

**Core Features**:
- ✅ **GDPR Compliant**: Fully compliant with EU data protection regulations
- ✅ **Apple-style Design**: Frosted glass effect, modern interface
- ✅ **Smart Geo-detection**: Automatic user geographic location identification
- ✅ **Accessibility Support**: Complete keyboard navigation and screen reader support
- ✅ **Dark Mode Adaptation**: Automatic adaptation to system dark preferences
- ✅ **Multi-language Support**: International text support
- ✅ **User Preference Memory**: Save user's Cookie choices

**Special Design**:
- Smart hiding for Chinese users (localization compliant)
- Smooth animation transition effects
- Custom style and text configuration
- Elegant frosted glass background effects
</div>

## 🏗️ 技术架构

### 模块化设计
<div class="content-zh">
```
wordpress-toolkit/
├── wordpress-toolkit.php          # 主插件文件
├── modules/                       # 功能模块目录
│   ├── custom-card/              # 网站卡片模块
│   ├── age-calculator/           # 年龄计算器模块
│   ├── time-capsule/             # 物品管理模块
│   └── cookieguard/              # Cookie同意模块
├── assets/                       # 资源文件
│   ├── css/                      # 样式文件
│   └── js/                       # JavaScript文件
├── includes/                     # 核心类库
│   ├── class-admin-page-template.php
│   ├── class-logger.php
│   └── i18n.php
└── languages/                     # 语言文件
    └── wordpress-toolkit.pot
```

### 统一管理界面
- **工具箱菜单**: 所有工具统一在"工具箱"菜单下管理
- **权限分级**: 不同功能模块设置不同用户权限
- **设置页面**: 每个模块都有独立的设置页面
- **快速导航**: 提供便捷的功能说明和快速链接
</div>

<div class="content-en" style="display: none;">
```
wordpress-toolkit/
├── wordpress-toolkit.php          # Main plugin file
├── modules/                       # Function module directory
│   ├── custom-card/              # Website card module
│   ├── age-calculator/           # Age calculator module
│   ├── time-capsule/             # Item management module
│   └── cookieguard/              # Cookie consent module
├── assets/                       # Asset files
│   ├── css/                      # Style files
│   └── js/                       # JavaScript files
├── includes/                     # Core library
│   ├── class-admin-page-template.php
│   ├── class-logger.php
│   └── i18n.php
└── languages/                     # Language files
    └── wordpress-toolkit.pot
```

### Unified Management Interface
- **Toolkit Menu**: All tools managed under the unified "Toolkit" menu
- **Permission Levels**: Different user permissions for different function modules
- **Settings Pages**: Each module has independent settings pages
- **Quick Navigation**: Convenient function descriptions and quick links
</div>

## 🔒 安全特性

### 数据安全
<div class="content-zh">
- ✅ **SQL注入防护**: 所有数据库查询使用参数化查询
- ✅ **XSS防护**: 输入数据严格清理和转义
- ✅ **CSRF防护**: 完整的nonce验证机制
- ✅ **文件操作安全**: 路径验证防止目录遍历攻击
</div>

<div class="content-en" style="display: none;">
- ✅ **SQL Injection Protection**: All database queries use parameterized queries
- ✅ **XSS Protection**: Strict input data cleaning and escaping
- ✅ **CSRF Protection**: Complete nonce verification mechanism
- ✅ **File Operation Security**: Path validation prevents directory traversal attacks
</div>

### Cookie安全
<div class="content-zh">
- ✅ **安全标志**: 使用httponly、secure、samesite标志
- ✅ **地理IP安全**: 安全的IP地址检测和代理处理
- ✅ **用户隐私**: 不收集任何个人数据，数据本地存储
</div>

<div class="content-en" style="display: none;">
- ✅ **Security Flags**: Use httponly, secure, samesite flags
- ✅ **Geo IP Security**: Secure IP address detection and proxy handling
- ✅ **User Privacy**: No personal data collection, local data storage
</div>

## 🚀 安装配置

### 系统要求
<div class="content-zh">
- **WordPress**: 5.0 或更高版本
- **PHP**: 7.4 或更高版本
- **MySQL**: 5.6 或更高版本
- **内存**: 最低64MB，推荐128MB
</div>

<div class="content-en" style="display: none;">
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Memory**: Minimum 64MB, recommended 128MB
</div>

### 安装步骤
<div class="content-zh">
#### 方法一：自动安装
1. 登录WordPress管理后台
2. 进入"插件" → "安装插件"
3. 搜索"WordPress Toolkit"
4. 点击"现在安装"并激活插件

#### 方法二：手动安装
1. 下载插件zip文件
2. 进入WordPress管理后台
3. 进入"插件" → "安装插件" → "上传插件"
4. 选择zip文件并上传安装
5. 激活插件

### 初次配置
1. 激活插件后，进入"工具箱"菜单
2. 查看功能说明和快速导航
3. 根据需要配置各个工具模块
4. 在设置页面中进行详细配置
</div>

<div class="content-en" style="display: none;">
#### Method 1: Automatic Installation
1. Log in to WordPress admin dashboard
2. Go to "Plugins" → "Add New"
3. Search for "WordPress Toolkit"
4. Click "Install Now" and activate the plugin

#### Method 2: Manual Installation
1. Download the plugin zip file
2. Go to WordPress admin dashboard
3. Go to "Plugins" → "Add New" → "Upload Plugin"
4. Select the zip file and upload to install
5. Activate the plugin

### Initial Configuration
1. After activating the plugin, go to the "Toolkit" menu
2. View function descriptions and quick navigation
3. Configure each tool module as needed
4. Perform detailed configuration in settings pages
</div>

## ❓ 常见问题

<div class="content-zh">
### Q: 这个插件包含哪些工具？
A: WordPress Toolkit包含四个核心工具：
1. **网站卡片** - 自动抓取网站元数据
2. **年龄计算器** - 精确计算年龄
3. **物品管理** - 物品管理和保修追踪
4. **Cookie同意** - GDPR合规的Cookie通知

### Q: 是否可以单独使用某个工具？
A: 是的，每个工具都是独立的模块，您可以根据需要启用或禁用相应的模块，不会影响其他功能的正常使用。

### Q: 插件是否影响网站性能？
A: 不会。插件采用模块化设计，按需加载资源，并且使用了智能缓存机制，对网站性能的影响最小化。

### Q: 是否支持多语言？
A: 是的，插件支持多语言和本地化，您可以根据需要翻译为任何语言。

### Q: 是否与所有主题兼容？
A: 是的，插件与所有WordPress主题兼容，包括自定义主题。

### Q: 如何获取技术支持？
A: 如需技术支持，请访问：https://www.saiita.com.cn
</div>

<div class="content-en" style="display: none;">
### Q: What tools does this plugin include?
A: WordPress Toolkit includes four core tools:
1. **Website Cards** - Automatically fetch website metadata
2. **Age Calculator** - Precisely calculate age
3. **Item Management** - Item management and warranty tracking
4. **Cookie Consent** - GDPR compliant Cookie notifications

### Q: Can I use individual tools separately?
A: Yes, each tool is an independent module. You can enable or disable corresponding modules as needed without affecting other functions.

### Q: Does the plugin affect website performance?
A: No. The plugin uses modular design, loads resources on demand, and uses smart caching mechanisms to minimize impact on website performance.

### Q: Does it support multiple languages?
A: Yes, the plugin supports multiple languages and localization. You can translate it to any language as needed.

### Q: Is it compatible with all themes?
A: Yes, the plugin is compatible with all WordPress themes, including custom themes.

### Q: How to get technical support?
A: For technical support, please visit: https://www.saiita.com.cn
</div>

## 🔗 相关链接

<div class="content-zh">
- **插件主页**: https://www.saiita.com.cn
- **技术支持**: https://www.saiita.com.cn/support
- **文档中心**: https://www.saiita.com.cn/docs
- **GitHub仓库**: [项目仓库链接]
</div>

<div class="content-en" style="display: none;">
- **Plugin Homepage**: https://www.saiita.com.cn
- **Technical Support**: https://www.saiita.com.cn/support
- **Documentation Center**: https://www.saiita.com.cn/docs
- **GitHub Repository**: [Project Repository Link]
</div>

## 📄 许可证

<div class="content-zh">
本插件基于GPLv2或更高版本许可证发布。
</div>

<div class="content-en" style="display: none;">
This plugin is released under the GPLv2 or later license.
</div>

```
WordPress Toolkit
Copyright (C) 2025 www.saiita.com.cn

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

---

<div class="content-zh">
**WordPress Toolkit** - 让WordPress网站功能更强大！🚀
</div>

<div class="content-en" style="display: none;">
**WordPress Toolkit** - Make WordPress websites more powerful! 🚀
</div>

<script>
document.getElementById('lang-switch').addEventListener('click', function() {
  const zhContents = document.querySelectorAll('.content-zh, #content-zh');
  const enContents = document.querySelectorAll('.content-en, #content-en');
  const button = this;

  if (zhContents[0].style.display !== 'none') {
    // Switch to English
    zhContents.forEach(el => el.style.display = 'none');
    enContents.forEach(el => el.style.display = 'block');
    button.textContent = '中文';
  } else {
    // Switch to Chinese
    zhContents.forEach(el => el.style.display = 'block');
    enContents.forEach(el => el.style.display = 'none');
    button.textContent = 'English';
  }
});
</script>