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

<script>
document.getElementById('lang-switch').addEventListener('click', function() {
  const zhContents = document.querySelectorAll('.content-zh, #content-zh');
  const enContents = document.querySelectorAll('.content-en, #content-en');
  const zhTitles = document.querySelectorAll('.title-zh');
  const enTitles = document.querySelectorAll('.title-en');
  const button = this;

  if (zhContents[0].style.display !== 'none') {
    // Switch to English
    zhContents.forEach(el => el.style.display = 'none');
    enContents.forEach(el => el.style.display = 'block');
    zhTitles.forEach(el => el.style.display = 'none');
    enTitles.forEach(el => el.style.display = 'inline');
    button.textContent = '中文';
  } else {
    // Switch to Chinese
    zhContents.forEach(el => el.style.display = 'block');
    enContents.forEach(el => el.style.display = 'none');
    zhTitles.forEach(el => el.style.display = 'inline');
    enTitles.forEach(el => el.style.display = 'none');
    button.textContent = 'English';
  }
});
</script>

## 📋 <span class="title-zh">基本信息</span><span class="title-en" style="display: none;">Basic Information</span>

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

## 🛠️ <span class="title-zh">核心模块</span><span class="title-en" style="display: none;">Core Modules</span>

### 🌐 <span class="title-zh">网站卡片</span><span class="title-en" style="display: none;">Website Cards</span> (Custom Card)
<div class="title-zh">**版本**: 1.0.3</div>
<div class="title-en" style="display: none;">**Version**: 1.0.3</div>

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

### 📅 <span class="title-zh">年龄计算器</span><span class="title-en" style="display: none;">Age Calculator</span> (Age Calculator)
<div class="title-zh">**版本**: 1.0.3</div>
<div class="title-en" style="display: none;">**Version**: 1.0.3</div>

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

### 📦 <span class="title-zh">物品管理</span><span class="title-en" style="display: none;">Item Management</span> (Time Capsule)
<div class="title-zh">**版本**: 1.0.6</div>
<div class="title-en" style="display: none;">**Version**: 1.0.6</div>

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

### 🍪 <span class="title-zh">Cookie同意</span><span class="title-en" style="display: none;">Cookie Consent</span> (CookieGuard)
<div class="title-zh">**版本**: 1.0.3</div>
<div class="title-en" style="display: none;">**Version**: 1.0.3</div>

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

## 🏗️ <span class="title-zh">技术架构</span><span class="title-en" style="display: none;">Technical Architecture</span>

### <span class="title-zh">模块化设计</span><span class="title-en" style="display: none;">Modular Design</span>
```
wordpress-toolkit/
├── wordpress-toolkit.php          # <span class="title-zh">主插件文件</span><span class="title-en" style="display: none;">Main plugin file</span>
├── modules/                       # <span class="title-zh">功能模块目录</span><span class="title-en" style="display: none;">Function module directory</span>
│   ├── custom-card/              # <span class="title-zh">网站卡片模块</span><span class="title-en" style="display: none;">Website card module</span>
│   ├── age-calculator/           # <span class="title-zh">年龄计算器模块</span><span class="title-en" style="display: none;">Age calculator module</span>
│   ├── time-capsule/             # <span class="title-zh">物品管理模块</span><span class="title-en" style="display: none;">Item management module</span>
│   └── cookieguard/              # <span class="title-zh">Cookie同意模块</span><span class="title-en" style="display: none;">Cookie consent module</span>
├── assets/                       # <span class="title-zh">资源文件</span><span class="title-en" style="display: none;">Asset files</span>
│   ├── css/                      # <span class="title-zh">样式文件</span><span class="title-en" style="display: none;">Style files</span>
│   └── js/                       # <span class="title-zh">JavaScript文件</span><span class="title-en" style="display: none;">JavaScript files</span>
├── includes/                     # <span class="title-zh">核心类库</span><span class="title-en" style="display: none;">Core library</span>
│   ├── class-admin-page-template.php
│   ├── class-logger.php
│   └── i18n.php
└── languages/                     # <span class="title-zh">语言文件</span><span class="title-en" style="display: none;">Language files</span>
    └── wordpress-toolkit.pot
```

### <span class="title-zh">统一管理界面</span><span class="title-en" style="display: none;">Unified Management Interface</span>
<div class="content-zh">
- **工具箱菜单**: 所有工具统一在"工具箱"菜单下管理
- **权限分级**: 不同功能模块设置不同用户权限
- **设置页面**: 每个模块都有独立的设置页面
- **快速导航**: 提供便捷的功能说明和快速链接
</div>

<div class="content-en" style="display: none;">
- **Toolkit Menu**: All tools managed under the unified "Toolkit" menu
- **Permission Levels**: Different user permissions for different function modules
- **Settings Pages**: Each module has independent settings pages
- **Quick Navigation**: Convenient function descriptions and quick links
</div>

## 🔒 <span class="title-zh">安全特性</span><span class="title-en" style="display: none;">Security Features</span>

### <span class="title-zh">数据安全</span><span class="title-en" style="display: none;">Data Security</span>
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

### <span class="title-zh">Cookie安全</span><span class="title-en" style="display: none;">Cookie Security</span>
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

### <span class="title-zh">访问控制</span><span class="title-en" style="display: none;">Access Control</span>
<div class="content-zh">
- ✅ **权限检查**: 完整的用户权限验证
- ✅ **角色管理**: 管理员和订阅者权限分离
- ✅ **访问日志**: 安全的访问日志记录
</div>

<div class="content-en" style="display: none;">
- ✅ **Permission Checks**: Complete user permission verification
- ✅ **Role Management**: Administrator and subscriber permission separation
- ✅ **Access Logs**: Secure access log recording
</div>

### <span class="title-zh">代码安全</span><span class="title-en" style="display: none;">Code Security</span>
<div class="content-zh">
- ✅ **输入验证**: 所有用户输入都经过严格验证
- ✅ **输出转义**: 防止代码注入和XSS攻击
- ✅ **错误处理**: 安全的错误信息处理
- ✅ **审计日志**: 调试模式控制的敏感日志记录
</div>

<div class="content-en" style="display: none;">
- ✅ **Input Validation**: All user inputs undergo strict validation
- ✅ **Output Escaping**: Prevent code injection and XSS attacks
- ✅ **Error Handling**: Secure error message handling
- ✅ **Audit Logs**: Debug mode controlled sensitive log recording
</div>

## ⚡ <span class="title-zh">性能优化</span><span class="title-en" style="display: none;">Performance Optimization</span>

### <span class="title-zh">缓存系统</span><span class="title-en" style="display: none;">Caching System</span>
<div class="content-zh">
- **多级缓存**: 数据库→Memcached→Opcache三级缓存
- **智能失效**: 自动检测缓存失效和更新
- **预加载**: 支持关键数据预加载
- **压缩优化**: CSS和JavaScript文件压缩
</div>

<div class="content-en" style="display: none;">
- **Multi-level Caching**: Database → Memcached → Opcache three-level caching
- **Smart Expiration**: Automatic cache invalidation and update detection
- **Preloading**: Support key data preloading
- **Compression Optimization**: CSS and JavaScript file compression
</div>

### <span class="title-zh">按需加载</span><span class="title-en" style="display: none;">On-demand Loading</span>
<div class="content-zh">
- **模块化加载**: 只加载激活的模块资源
- **条件加载**: 根据页面类型加载相应资源
- **异步处理**: AJAX异步通信提升体验
- **延迟加载**: 非关键资源延迟加载
</div>

<div class="content-en" style="display: none;">
- **Modular Loading**: Only load resources for activated modules
- **Conditional Loading**: Load corresponding resources based on page type
- **Asynchronous Processing**: AJAX asynchronous communication improves experience
- **Lazy Loading**: Non-critical resources delayed loading
</div>

### <span class="title-zh">代码优化</span><span class="title-en" style="display: none;">Code Optimization</span>
<div class="content-zh">
- **函数精简**: 删除所有冗余和未使用的代码（减少46%代码量）
- **数据库优化**: 高效的数据库查询和索引设计
- **内存管理**: 防止内存泄漏和资源浪费
- **前端优化**: CSS和JavaScript代码优化（减少40%文件大小）
</div>

<div class="content-en" style="display: none;">
- **Function Streamlining**: Remove all redundant and unused code (46% code reduction)
- **Database Optimization**: Efficient database queries and index design
- **Memory Management**: Prevent memory leaks and resource waste
- **Frontend Optimization**: CSS and JavaScript code optimization (40% file size reduction)
</div>

## 🌐 <span class="title-zh">国际化支持</span><span class="title-en" style="display: none;">Internationalization Support</span>

### <span class="title-zh">多语言支持</span><span class="title-en" style="display: none;">Multi-language Support</span>
<div class="content-zh">
- ✅ **文本域**: `wordpress-toolkit`
- ✅ **语言文件**: 标准的.pot语言包
- ✅ **模块化翻译**: 每个模块独立的翻译支持
- ✅ **本地化适配**: 支持日期、数字格式本地化
</div>

<div class="content-en" style="display: none;">
- ✅ **Text Domain**: `wordpress-toolkit`
- ✅ **Language Files**: Standard .pot language packs
- ✅ **Modular Translation**: Independent translation support for each module
- ✅ **Localization Adaptation**: Support date and number format localization
</div>

### <span class="title-zh">地区适配</span><span class="title-en" style="display: none;">Regional Adaptation</span>
<div class="content-zh">
- ✅ **中国用户**: Cookie通知智能隐藏
- ✅ **时区支持**: 自动适配WordPress时区设置
- ✅ **货币格式**: 支持本地化货币显示
- ✅ **日期格式**: 符合地区习惯的日期显示
</div>

<div class="content-en" style="display: none;">
- ✅ **Chinese Users**: Smart hiding of Cookie notifications
- ✅ **Timezone Support**: Automatic adaptation to WordPress timezone settings
- ✅ **Currency Format**: Support localized currency display
- ✅ **Date Format**: Date display conforming to regional customs
</div>

## 📱 <span class="title-zh">响应式设计</span><span class="title-en" style="display: none;">Responsive Design</span>

### <span class="title-zh">设备兼容</span><span class="title-en" style="display: none;">Device Compatibility</span>
<div class="content-zh">
- ✅ **桌面端**: 完整的桌面浏览器支持
- ✅ **平板设备**: 优化的平板显示效果
- ✅ **移动设备**: 完美的手机端体验
- ✅ **触摸优化**: 触摸手势和交互优化
</div>

<div class="content-en" style="display: none;">
- ✅ **Desktop**: Complete desktop browser support
- ✅ **Tablet**: Optimized tablet display effects
- ✅ **Mobile**: Perfect mobile experience
- ✅ **Touch Optimization**: Touch gesture and interaction optimization
</div>

### <span class="title-zh">浏览器兼容</span><span class="title-en" style="display: none;">Browser Compatibility</span>
<div class="content-zh">
- ✅ **现代浏览器**: Chrome, Firefox, Safari, Edge
- ✅ **移动浏览器**: iOS Safari, Chrome Mobile
- ✅ **渐进增强**: 核心功能在老旧浏览器中可用
</div>

<div class="content-en" style="display: none;">
- ✅ **Modern Browsers**: Chrome, Firefox, Safari, Edge
- ✅ **Mobile Browsers**: iOS Safari, Chrome Mobile
- ✅ **Progressive Enhancement**: Core functions available in older browsers
</div>

## 🎨 <span class="title-zh">UI/UX设计</span><span class="title-en" style="display: none;">UI/UX Design</span>

### <span class="title-zh">设计原则</span><span class="title-en" style="display: none;">Design Principles</span>
<div class="content-zh">
- **一致性**: 统一的设计语言和交互模式
- **简洁性**: 清晰直观的用户界面
- **可访问性**: 符合WCAG 2.1 AA标准
- **性能**: 优先考虑加载速度和响应性能
</div>

<div class="content-en" style="display: none;">
- **Consistency**: Unified design language and interaction patterns
- **Simplicity**: Clear and intuitive user interface
- **Accessibility**: WCAG 2.1 AA compliant
- **Performance**: Priority on loading speed and response performance
</div>

### <span class="title-zh">主题兼容</span><span class="title-en" style="display: none;">Theme Compatibility</span>
<div class="content-zh">
- ✅ **默认主题**: 与WordPress默认主题完美兼容
- ✅ **第三方主题**: 广泛的主题兼容性测试
- ✅ **自定义样式**: 支持主题样式覆盖
- ✅ **区块编辑器**: 与Gutenberg编辑器深度集成
</div>

<div class="content-en" style="display: none;">
- ✅ **Default Themes**: Perfect compatibility with WordPress default themes
- ✅ **Third-party Themes**: Extensive theme compatibility testing
- ✅ **Custom Styles**: Support theme style overriding
- ✅ **Block Editor**: Deep integration with Gutenberg editor
</div>

## 📊 <span class="title-zh">数据管理</span><span class="title-en" style="display: none;">Data Management</span>

### <span class="title-zh">数据存储</span><span class="title-en" style="display: none;">Data Storage</span>
<div class="content-zh">
- **WordPress标准**: 使用WordPress标准的数据库表结构
- **自定义表**: 高效的自定义数据表设计
- **数据备份**: 支持WordPress标准备份流程
- **数据迁移**: 提供数据导入导出功能
</div>

<div class="content-en" style="display: none;">
- **WordPress Standards**: Use WordPress standard database table structures
- **Custom Tables**: Efficient custom data table design
- **Data Backup**: Support WordPress standard backup processes
- **Data Migration**: Provide data import and export functions
</div>

### <span class="title-zh">数据统计</span><span class="title-en" style="display: none;">Data Statistics</span>
<div class="content-zh">
- **访问统计**: 详细的访问和使用统计
- **用户行为**: 用户操作行为分析
- **性能监控**: 页面加载性能监控
- **错误追踪**: 系统错误和异常记录
</div>

<div class="content-en" style="display: none;">
- **Access Statistics**: Detailed access and usage statistics
- **User Behavior**: User operation behavior analysis
- **Performance Monitoring**: Page loading performance monitoring
- **Error Tracking**: System errors and exception recording
</div>

## 🚀 <span class="title-zh">安装配置</span><span class="title-en" style="display: none;">Installation & Configuration</span>

### <span class="title-zh">系统要求</span><span class="title-en" style="display: none;">System Requirements</span>
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

### <span class="title-zh">安装步骤</span><span class="title-en" style="display: none;">Installation Steps</span>

#### <span class="title-zh">方法一：自动安装</span><span class="title-en" style="display: none;">Method 1: Automatic Installation</span>
<div class="content-zh">
1. 登录WordPress管理后台
2. 进入"插件" → "安装插件"
3. 搜索"WordPress Toolkit"
4. 点击"现在安装"并激活插件
</div>

<div class="content-en" style="display: none;">
1. Log in to WordPress admin dashboard
2. Go to "Plugins" → "Add New"
3. Search for "WordPress Toolkit"
4. Click "Install Now" and activate the plugin
</div>

#### <span class="title-zh">方法二：手动安装</span><span class="title-en" style="display: none;">Method 2: Manual Installation</span>
<div class="content-zh">
1. 下载插件zip文件
2. 进入WordPress管理后台
3. 进入"插件" → "安装插件" → "上传插件"
4. 选择zip文件并上传安装
5. 激活插件
</div>

<div class="content-en" style="display: none;">
1. Download the plugin zip file
2. Go to WordPress admin dashboard
3. Go to "Plugins" → "Add New" → "Upload Plugin"
4. Select the zip file and upload to install
5. Activate the plugin
</div>

### <span class="title-zh">初次配置</span><span class="title-en" style="display: none;">Initial Configuration</span>
<div class="content-zh">
1. 激活插件后，进入"工具箱"菜单
2. 查看功能说明和快速导航
3. 根据需要配置各个工具模块
4. 在设置页面中进行详细配置
</div>

<div class="content-en" style="display: none;">
1. After activating the plugin, go to the "Toolkit" menu
2. View function descriptions and quick navigation
3. Configure each tool module as needed
4. Perform detailed configuration in settings pages
</div>

## 🔧 <span class="title-zh">管理界面</span><span class="title-en" style="display: none;">Management Interface</span>

### <span class="title-zh">工具箱主菜单</span><span class="title-en" style="display: none;">Toolkit Main Menu</span>
<div class="content-zh">
- **功能说明**: 详细的模块功能介绍
- **快速导航**: 便捷的模块快速访问
- **使用指南**: 每个模块的使用方法
- **配置建议**: 最佳配置建议
</div>

<div class="content-en" style="display: none;">
- **Function Descriptions**: Detailed module function introductions
- **Quick Navigation**: Convenient module quick access
- **User Guides**: Usage methods for each module
- **Configuration Suggestions**: Best configuration recommendations
</div>

### <span class="title-zh">模块管理</span><span class="title-en" style="display: none;">Module Management</span>
<div class="content-zh">
- **网站卡片**: 卡片列表、缓存管理、设置配置
- **年龄计算器**: 计算器设置、显示配置、用户偏好
- **物品管理**: 物品列表、分类管理、统计分析
- **Cookie同意**: 样式配置、文案设置、行为配置
</div>

<div class="content-en" style="display: none;">
- **Website Cards**: Card list, cache management, settings configuration
- **Age Calculator**: Calculator settings, display configuration, user preferences
- **Item Management**: Item list, category management, statistical analysis
- **Cookie Consent**: Style configuration, text settings, behavior configuration
</div>

### <span class="title-zh">设置页面</span><span class="title-en" style="display: none;">Settings Pages</span>
<div class="content-zh">
- **网站卡片设置**: 缓存配置、抓取设置、显示选项
- **年龄计算器设置**: 默认格式、用户权限、显示配置
- **Cookie同意设置**: 样式选择、文案配置、地区设置
</div>

<div class="content-en" style="display: none;">
- **Website Card Settings**: Cache configuration, fetch settings, display options
- **Age Calculator Settings**: Default format, user permissions, display configuration
- **Cookie Consent Settings**: Style selection, text configuration, regional settings
</div>

## 📈 <span class="title-zh">使用场景</span><span class="title-en" style="display: none;">Use Cases</span>

### <span class="title-zh">企业网站</span><span class="title-en" style="display: none;">Enterprise Websites</span>
<div class="content-zh">
- **网站卡片**: 展示合作伙伴和客户网站
- **Cookie同意**: 确保GDPR合规
- **物品管理**: 管理公司资产和设备
</div>

<div class="content-en" style="display: none;">
- **Website Cards**: Display partners and client websites
- **Cookie Consent**: Ensure GDPR compliance
- **Item Management**: Manage company assets and equipment
</div>

### <span class="title-zh">个人博客</span><span class="title-en" style="display: none;">Personal Blogs</span>
<div class="content-zh">
- **年龄计算器**: 显示作者年龄或纪念日
- **Cookie同意**: 保护访客隐私
- **网站卡片**: 推荐相关网站和资源
</div>

<div class="content-en" style="display: none;">
- **Age Calculator**: Display author age or anniversaries
- **Cookie Consent**: Protect visitor privacy
- **Website Cards**: Recommend related websites and resources
</div>

### <span class="title-zh">电商平台</span><span class="title-en" style="display: none;">E-commerce Platforms</span>
<div class="content-zh">
- **网站卡片**: 展示品牌和供应商
- **物品管理**: 管理库存和保修信息
- **Cookie同意**: 合规的Cookie管理
</div>

<div class="content-en" style="display: none;">
- **Website Cards**: Display brands and suppliers
- **Item Management**: Manage inventory and warranty information
- **Cookie Consent**: Compliant Cookie management
</div>

### <span class="title-zh">内容网站</span><span class="title-en" style="display: none;">Content Websites</span>
<div class="content-zh">
- **网站卡片**: 丰富内容展示形式
- **Cookie同意**: 隐私保护和合规
- **年龄计算器**: 增加互动性和趣味性
</div>

<div class="content-en" style="display: none;">
- **Website Cards**: Enrich content display forms
- **Cookie Consent**: Privacy protection and compliance
- **Age Calculator**: Increase interactivity and fun
</div>

## 🛠️ <span class="title-zh">开发信息</span><span class="title-en" style="display: none;">Development Information</span>

### <span class="title-zh">代码质量</span><span class="title-en" style="display: none;">Code Quality</span>
<div class="content-zh">
- **编码标准**: 遵循WordPress编码规范
- **文档完整**: 详细的代码注释和文档
- **测试覆盖**: 核心功能的测试覆盖
- **性能监控**: 持续的性能监控和优化
</div>

<div class="content-en" style="display: none;">
- **Coding Standards**: Follow WordPress coding standards
- **Complete Documentation**: Detailed code comments and documentation
- **Test Coverage**: Core functionality test coverage
- **Performance Monitoring**: Continuous performance monitoring and optimization
</div>

### <span class="title-zh">技术栈</span><span class="title-en" style="display: none;">Technology Stack</span>
<div class="content-zh">
- **后端**: PHP 7.4+, WordPress API, MySQL
- **前端**: HTML5, CSS3, JavaScript (jQuery)
- **缓存**: Memcached, Opcache
- **安全**: Nonce验证, 数据清理, 权限控制
</div>

<div class="content-en" style="display: none;">
- **Backend**: PHP 7.4+, WordPress API, MySQL
- **Frontend**: HTML5, CSS3, JavaScript (jQuery)
- **Caching**: Memcached, Opcache
- **Security**: Nonce verification, data cleaning, permission control
</div>

### <span class="title-zh">扩展性</span><span class="title-en" style="display: none;">Extensibility</span>
<div class="content-zh">
- **钩子系统**: 完整的WordPress钩子支持
- **API接口**: 提供REST API接口
- **主题集成**: 与主题系统深度集成
- **插件兼容**: 与主流WordPress插件兼容
</div>

<div class="content-en" style="display: none;">
- **Hook System**: Complete WordPress hook support
- **API Interface**: Provide REST API interfaces
- **Theme Integration**: Deep integration with theme system
- **Plugin Compatibility**: Compatible with mainstream WordPress plugins
</div>

## 🔄 <span class="title-zh">版本历史</span><span class="title-en" style="display: none;">Version History</span>

### v1.0.3 (2025-10-23)
<div class="content-zh">
**主要更新**:
- 🎨 **UI统一**: 后台管理界面样式统一优化
- 🧹 **代码清理**: 清理冗余代码，减少46%代码量
- ⚡ **性能提升**: CSS和JS文件大小减少40%
- 🔒 **安全增强**: 修复函数重复声明问题
- 📱 **响应式优化**: 移动端体验改进

**技术改进**:
- 统一后台管理界面样式
- 优化物品管理表格布局
- 清理未使用的函数和样式
- 修复PHP语法错误
- 改进错误处理机制
</div>

<div class="content-en" style="display: none;">
**Major Updates**:
- 🎨 **UI Unification**: Backend management interface style unification optimization
- 🧹 **Code Cleanup**: Clean redundant code, 46% code reduction
- ⚡ **Performance Improvement**: CSS and JS file size reduced by 40%
- 🔒 **Security Enhancement**: Fixed function redeclaration issues
- 📱 **Responsive Optimization**: Mobile experience improvements

**Technical Improvements**:
- Unified backend management interface styles
- Optimized item management table layout
- Cleaned unused functions and styles
- Fixed PHP syntax errors
- Improved error handling mechanisms
</div>

### v1.0.2
<div class="content-zh">
**安全发布**:
- 🛡️ 修复SQL注入漏洞
- 🔒 增强文件操作安全
- 🍪 改进Cookie安全设置
- 🌐 优化IP地址处理
- 📝 完善日志记录系统
</div>

<div class="content-en" style="display: none;">
**Security Release**:
- 🛡️ Fixed SQL injection vulnerabilities
- 🔒 Enhanced file operation security
- 🍪 Improved Cookie security settings
- 🌐 Optimized IP address handling
- 📝 Completed logging system
</div>

### v1.0.0
<div class="content-zh">
**初始版本**:
- 🎉 集成四个核心工具模块
- 🎨 统一的管理界面设计
- ⚡ 优化的性能和缓存机制
- 🔒 增强的安全性和数据保护
- 🌍 完整的国际化支持
</div>

<div class="content-en" style="display: none;">
**Initial Release**:
- 🎉 Integrated four core tool modules
- 🎨 Unified management interface design
- ⚡ Optimized performance and caching mechanisms
- 🔒 Enhanced security and data protection
- 🌍 Complete internationalization support
</div>

## ❓ <span class="title-zh">常见问题</span><span class="title-en" style="display: none;">Frequently Asked Questions</span>

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

## 🔗 <span class="title-zh">相关链接</span><span class="title-en" style="display: none;">Related Links</span>

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

## 📄 <span class="title-zh">许可证</span><span class="title-en" style="display: none;">License</span>

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