=== Saiita AI Content Toolkit ===
Contributors: saiita
Tags: ai, content, seo, excerpt, optimization
Requires at least: 5.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.23
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered content toolkit for WordPress. Generate article summaries, optimize SEO, auto-generate tags and category descriptions using AI.

== External Services ==

This plugin requires AI service providers to function. The plugin connects to external AI APIs to process your content.

**Supported AI Providers:**

* **DeepSeek** - Affordable AI models optimized for Chinese content
  * Service: https://platform.deepseek.com
  * Privacy Policy: https://platform.deepseek.com/privacy
  * Terms of Service: https://platform.deepseek.com/terms

* **OpenAI** - Industry-leading GPT models
  * Service: https://openai.com
  * Privacy Policy: https://openai.com/policies/privacy-policy
  * Terms of Service: https://openai.com/policies/terms-of-use

* **SiliconFlow** - Professional AI model platform
  * Service: https://siliconflow.cn
  * Privacy Policy: https://siliconflow.cn/privacy
  * Terms of Service: https://siliconflow.cn/terms

**How it works:**
1. You need to obtain an API key from one or more of these AI providers
2. Configure the API key in the plugin settings
3. The plugin sends your content to the selected AI provider for processing
4. Results are returned and stored in your WordPress database

**What data is sent:**
* Article content (title, body text) for analysis and optimization
* Existing metadata (tags, categories, excerpts) for enhancement
* No personal user data or site configuration information is transmitted

**Referral Program:**
The plugin contains referral links to AI service providers. Using these links to register for services may provide you with bonus credits and helps support continued plugin development at no cost to you.

== Description ==

Saiita AI Content Toolkit is a comprehensive AI-powered content management plugin for WordPress. It leverages leading AI providers (DeepSeek, OpenAI, SiliconFlow) to help content creators and administrators streamline their workflow.

**Key Features:**

= Article Optimization =
* AI-powered article summary generation
* Batch generate summaries for articles without excerpts
* SEO analysis and scoring for individual articles
* AI-generated tags based on article content
* Batch tag generation for multiple articles

= Tag Optimization =
* AI-generated tag descriptions
* Batch generate descriptions for all tags
* Improve tag pages SEO with meaningful descriptions

= Category Optimization =
* AI-generated category descriptions
* Batch generate descriptions for all categories
* Improve category archive SEO

= Website Optimization =
* Comprehensive website SEO analysis
* Content structure recommendations
* Meta tag and heading analysis

= AI Category =
* Automatically categorize articles using AI
* Configurable confidence threshold
* Support for multiple AI providers

= AI Settings =
* Unified AI provider management
* Support for DeepSeek, OpenAI, and SiliconFlow
* Configurable model selection and parameters

= Prompt Settings =
* Customizable AI prompt templates
* Fine-tune AI output for your specific needs

= Age Calculator =
* Precise age calculation widget
* Display age in years, months, and days

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the "Plugins" menu in WordPress
3. Go to "Saiita AI Toolkit" in the admin sidebar
4. Configure your AI provider settings (API Key and model)
5. Start using the AI features

== Frequently Asked Questions ==

= Which AI providers are supported? =
DeepSeek, OpenAI, and SiliconFlow. You can configure multiple providers and switch between them.

= Is my data safe? =
All API calls are made server-side. Article content is only sent to the AI provider when you explicitly trigger an AI feature. No data is stored by the AI providers beyond their standard API processing.

= Do I need an API key? =
Yes, you need an API key from at least one supported AI provider. You can get a free or paid key from DeepSeek, OpenAI, or SiliconFlow.

== Screenshots ==

1. Main dashboard with quick access to all features
2. Article optimization page with batch operations
3. Tag and category optimization interface
4. AI settings configuration

== Changelog ==

= 2.0.23 =
* Compatibility: Updated for WordPress 7.0, raised minimum WP version to 5.3
* Fixed: Singleton Trait self/static bug causing shared instance storage across classes
* Fixed: AI Category custom prompt settings never working due to wrong option key
* Fixed: Unified option key naming convention across all modules (with backward compatibility)
* Fixed: Styling conflicts between settings pages, all now match AI Settings style
* Improved: Removed H1 titles from all admin pages, unified top spacing
* Improved: Cleaned up 4 unused redundant CSS/JS files
* Improved: Fixed CSS select dropdown arrow overlap with text

= 2.0.21 =
* Security: Enhanced security measures for WordPress.org plugin directory requirements
* Fixed: Improved input sanitization for all user-submitted data
* Fixed: Added proper output escaping for all displayed content
* Fixed: Added permission callbacks for all REST API endpoints
* Fixed: Replaced json_encode() with wp_json_encode() for better security
* Fixed: Enhanced field validation in AI category settings
* Improved: Better sanitization for JSON-decoded options
* Improved: Proper escaping for translation functions

= 2.0.19 =
* Added: New AI-powered features and improvements
* Improved: UI/UX enhancements
* Security: Enhanced security measures

= 2.0.5 =
* Security: Restored nonce verification for all AJAX handlers
* Security: Added input sanitization for all $_POST operations
* Fixed: Unified option key naming convention
* Added: Internationalization support (.pot file)
* Improved: Code quality and security hardening

= 2.0.0 =
* Complete rewrite with modular architecture
* Added AI category auto-classification
* Added CookieGuard GDPR compliance
* Added prompt settings management
* Performance improvements

== External services ==

This plugin connects to third-party AI service APIs to provide content generation and analysis features. Below is a detailed description of each external service used.

=== DeepSeek AI ===

* **Service:** AI content generation, including article summarization, SEO analysis, tag generation, and category description generation.
* **What data is sent:** Article titles, content, metadata, and existing tags/categories. Data is sent only when an administrator explicitly triggers an AI feature (e.g., clicking "Generate Summary" or "Generate Tags").
* **When data is sent:** Only upon explicit administrator action. No automatic or background data transmission occurs.
* **Where data is sent:** API endpoint at `api.deepseek.com`
* **Terms of Service:** https://platform.deepseek.com/terms-of-use
* **Privacy Policy:** https://platform.deepseek.com/privacy-policy

=== OpenAI ===

* **Service:** Alternative AI provider for all content generation features (summarization, SEO analysis, tag and category descriptions).
* **What data is sent:** Same as DeepSeek — article titles, content, and metadata sent only upon explicit administrator action.
* **When data is sent:** Only upon explicit administrator action.
* **Where data is sent:** API endpoint at `api.openai.com`
* **Terms of Service:** https://openai.com/policies/terms-of-use
* **Privacy Policy:** https://openai.com/policies/privacy-policy

=== SiliconFlow ===

* **Service:** Alternative AI provider for all content generation features.
* **What data is sent:** Same as DeepSeek and OpenAI — article content and metadata sent only upon explicit administrator action.
* **When data is sent:** Only upon explicit administrator action.
* **Where data is sent:** API endpoint at `api.siliconflow.cn`
* **Terms of Service:** https://siliconflow.cn/terms
* **Privacy Policy:** https://siliconflow.cn/privacy

**Important notes:**
* All API calls are made server-side (WordPress backend). No end-user data is sent directly from browsers.
* Administrators must configure their own API keys. The plugin does not use any shared or built-in API credentials.
* Users can choose which AI provider to use or disable AI features entirely.
* Article content is not stored or logged by this plugin beyond the standard WordPress post storage.
