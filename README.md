# WordPress Toolkit

**语言 / Language:** [中文](readme.md) | [English](README.md)

A powerful, secure, and reliable WordPress comprehensive toolkit that integrates four practical tool modules to provide comprehensive functionality support for websites.

## 📋 Basic Information

- **Plugin Name**: WordPress Toolkit
- **Version**: 1.0.3
- **Author**: www.saiita.com.cn
- **License**: GPL v2 or later
- **Minimum Requirements**: WordPress 5.0+, PHP 7.4+
- **Tested Compatibility**: WordPress 6.4
- **Plugin URL**: https://www.saiita.com.cn

## 🛠️ Core Modules

### 🌐 Website Cards (Custom Card)
**Version**: 1.0.3

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

### 📅 Age Calculator (Age Calculator)
**Version**: 1.0.3

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

### 📦 Item Management (Time Capsule)
**Version**: 1.0.6

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

### 🍪 Cookie Consent (CookieGuard)
**Version**: 1.0.3

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

## 🏗️ Technical Architecture

### Modular Design
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

## 🔒 Security Features

### Data Security
- ✅ **SQL Injection Protection**: All database queries use parameterized queries
- ✅ **XSS Protection**: Strict input data cleaning and escaping
- ✅ **CSRF Protection**: Complete nonce verification mechanism
- ✅ **File Operation Security**: Path validation prevents directory traversal attacks

### Cookie Security
- ✅ **Security Flags**: Use httponly, secure, samesite flags
- ✅ **Geo IP Security**: Secure IP address detection and proxy handling
- ✅ **User Privacy**: No personal data collection, local data storage

### Access Control
- ✅ **Permission Checks**: Complete user permission verification
- ✅ **Role Management**: Administrator and subscriber permission separation
- ✅ **Access Logs**: Secure access log recording

### Code Security
- ✅ **Input Validation**: All user inputs undergo strict validation
- ✅ **Output Escaping**: Prevent code injection and XSS attacks
- ✅ **Error Handling**: Secure error message handling
- ✅ **Audit Logs**: Debug mode controlled sensitive log recording

## ⚡ Performance Optimization

### Caching System
- **Multi-level Caching**: Database → Memcached → Opcache three-level caching
- **Smart Expiration**: Automatic cache invalidation and update detection
- **Preloading**: Support key data preloading
- **Compression Optimization**: CSS and JavaScript file compression

### On-demand Loading
- **Modular Loading**: Only load resources for activated modules
- **Conditional Loading**: Load corresponding resources based on page type
- **Asynchronous Processing**: AJAX asynchronous communication improves experience
- **Lazy Loading**: Non-critical resources delayed loading

### Code Optimization
- **Function Streamlining**: Remove all redundant and unused code (46% code reduction)
- **Database Optimization**: Efficient database queries and index design
- **Memory Management**: Prevent memory leaks and resource waste
- **Frontend Optimization**: CSS and JavaScript code optimization (40% file size reduction)

## 🌐 Internationalization Support

### Multi-language Support
- ✅ **Text Domain**: `wordpress-toolkit`
- ✅ **Language Files**: Standard .pot language packs
- ✅ **Modular Translation**: Independent translation support for each module
- ✅ **Localization Adaptation**: Support date and number format localization

### Regional Adaptation
- ✅ **Chinese Users**: Smart hiding of Cookie notifications
- ✅ **Timezone Support**: Automatic adaptation to WordPress timezone settings
- ✅ **Currency Format**: Support localized currency display
- ✅ **Date Format**: Date display conforming to regional customs

## 📱 Responsive Design

### Device Compatibility
- ✅ **Desktop**: Complete desktop browser support
- ✅ **Tablet**: Optimized tablet display effects
- ✅ **Mobile**: Perfect mobile experience
- ✅ **Touch Optimization**: Touch gesture and interaction optimization

### Browser Compatibility
- ✅ **Modern Browsers**: Chrome, Firefox, Safari, Edge
- ✅ **Mobile Browsers**: iOS Safari, Chrome Mobile
- ✅ **Progressive Enhancement**: Core functions available in older browsers

## 🎨 UI/UX Design

### Design Principles
- **Consistency**: Unified design language and interaction patterns
- **Simplicity**: Clear and intuitive user interface
- **Accessibility**: WCAG 2.1 AA compliant
- **Performance**: Priority on loading speed and response performance

### Theme Compatibility
- ✅ **Default Themes**: Perfect compatibility with WordPress default themes
- ✅ **Third-party Themes**: Extensive theme compatibility testing
- ✅ **Custom Styles**: Support theme style overriding
- ✅ **Block Editor**: Deep integration with Gutenberg editor

## 📊 Data Management

### Data Storage
- **WordPress Standards**: Use WordPress standard database table structures
- **Custom Tables**: Efficient custom data table design
- **Data Backup**: Support WordPress standard backup processes
- **Data Migration**: Provide data import and export functions

### Data Statistics
- **Access Statistics**: Detailed access and usage statistics
- **User Behavior**: User operation behavior analysis
- **Performance Monitoring**: Page loading performance monitoring
- **Error Tracking**: System errors and exception recording

## 🚀 Installation & Configuration

### System Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Memory**: Minimum 64MB, recommended 128MB

### Installation Steps

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

## 🔧 Management Interface

### Toolkit Main Menu
- **Function Descriptions**: Detailed module function introductions
- **Quick Navigation**: Convenient module quick access
- **User Guides**: Usage methods for each module
- **Configuration Suggestions**: Best configuration recommendations

### Module Management
- **Website Cards**: Card list, cache management, settings configuration
- **Age Calculator**: Calculator settings, display configuration, user preferences
- **Item Management**: Item list, category management, statistical analysis
- **Cookie Consent**: Style configuration, text settings, behavior configuration

### Settings Pages
- **Website Card Settings**: Cache configuration, fetch settings, display options
- **Age Calculator Settings**: Default format, user permissions, display configuration
- **Cookie Consent Settings**: Style selection, text configuration, regional settings

## 📈 Use Cases

### Enterprise Websites
- **Website Cards**: Display partners and client websites
- **Cookie Consent**: Ensure GDPR compliance
- **Item Management**: Manage company assets and equipment

### Personal Blogs
- **Age Calculator**: Display author age or anniversaries
- **Cookie Consent**: Protect visitor privacy
- **Website Cards**: Recommend related websites and resources

### E-commerce Platforms
- **Website Cards**: Display brands and suppliers
- **Item Management**: Manage inventory and warranty information
- **Cookie Consent**: Compliant Cookie management

### Content Websites
- **Website Cards**: Enrich content display forms
- **Cookie Consent**: Privacy protection and compliance
- **Age Calculator**: Increase interactivity and fun

## 🛠️ Development Information

### Code Quality
- **Coding Standards**: Follow WordPress coding standards
- **Complete Documentation**: Detailed code comments and documentation
- **Test Coverage**: Core functionality test coverage
- **Performance Monitoring**: Continuous performance monitoring and optimization

### Technology Stack
- **Backend**: PHP 7.4+, WordPress API, MySQL
- **Frontend**: HTML5, CSS3, JavaScript (jQuery)
- **Caching**: Memcached, Opcache
- **Security**: Nonce verification, data cleaning, permission control

### Extensibility
- **Hook System**: Complete WordPress hook support
- **API Interface**: Provide REST API interfaces
- **Theme Integration**: Deep integration with theme system
- **Plugin Compatibility**: Compatible with mainstream WordPress plugins

## 🔄 Version History

### v1.0.3 (2025-10-23)
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

### v1.0.2
**Security Release**:
- 🛡️ Fixed SQL injection vulnerabilities
- 🔒 Enhanced file operation security
- 🍪 Improved Cookie security settings
- 🌐 Optimized IP address handling
- 📝 Completed logging system

### v1.0.0
**Initial Release**:
- 🎉 Integrated four core tool modules
- 🎨 Unified management interface design
- ⚡ Optimized performance and caching mechanisms
- 🔒 Enhanced security and data protection
- 🌍 Complete internationalization support

## ❓ Frequently Asked Questions

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

## 🔗 Related Links

- **Plugin Homepage**: https://www.saiita.com.cn
- **Technical Support**: https://www.saiita.com.cn/support
- **Documentation Center**: https://www.saiita.com.cn/docs
- **GitHub Repository**: [Project Repository Link]

## 📄 License

This plugin is released under the GPLv2 or later license.

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

**WordPress Toolkit** - Make WordPress websites more powerful! 🚀