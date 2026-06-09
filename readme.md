# Saiita AI Content Toolkit

**AI-powered content toolkit for WordPress** — Generate article summaries, optimize SEO, auto-generate tags and category descriptions using AI.

[![Requires WordPress](https://img.shields.io/badge/WordPress-5.3%2B-blue)](https://wordpress.org)
[![Requires PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Stable](https://img.shields.io/badge/Version-2.0.23-orange)]()

---

## ✨ 功能特性

### 📝 文章优化
- AI 生成文章摘要
- 批量为无摘要文章生成摘要
- 单篇文章 SEO 分析与评分
- AI 根据文章内容生成标签
- 批量生成多篇文章标签

### 🏷️ 标签优化
- AI 生成标签描述
- 批量生成所有标签描述
- 提升标签页 SEO

### 📂 分类优化
- AI 生成分类描述
- 批量生成所有分类描述
- 提升分类归档页 SEO

### 🌐 网站优化
- 全站 SEO 综合分析
- 内容结构建议
- Meta 标签和标题分析

### 🤖 AI 分类
- 使用 AI 自动为文章分类
- 可配置置信度阈值
- 支持多个 AI 服务商

### ⚙️ AI 设置
- 统一的 AI 服务商管理
- 支持 DeepSeek、OpenAI、SiliconFlow
- 可配置模型选择和参数

### 📋 提示词设置
- 可自定义 AI 提示词模板
- 根据需求微调 AI 输出

### 🧮 年龄计算器
- 精确的年龄计算小工具
- 以年、月、日显示年龄

---

## 🔌 支持的 AI 服务商

| 服务商 | 特点 | 注册地址 |
|--------|------|----------|
| **DeepSeek** | 性价比高，中文优化 | [platform.deepseek.com](https://platform.deepseek.com) |
| **OpenAI** | 行业领先的 GPT 模型 | [openai.com](https://openai.com) |
| **SiliconFlow** | 专业 AI 模型平台 | [cloud.siliconflow.cn](https://cloud.siliconflow.cn/i/lZiQhOti) |

> ⚠️ 使用插件需要至少一个 AI 服务商的 API Key。

---

## 📦 安装

1. 将插件文件夹上传到 `/wp-content/plugins/`
2. 在 WordPress 后台「插件」菜单中激活插件
3. 进入后台侧边栏「Saiita AI Toolkit」
4. 配置 AI 服务商设置（API Key 和模型）
5. 开始使用 AI 功能

---

## ❓ 常见问题

### 支持哪些 AI 服务商？
DeepSeek、OpenAI 和 SiliconFlow。可以配置多个服务商并随时切换。

### 我的数据安全吗？
所有 API 调用均在服务端进行。文章内容仅在你手动触发 AI 功能时发送给 AI 服务商。AI 服务商不会存储超出其标准 API 处理范围的数据。

### 需要 API Key 吗？
是的，你需要至少一个受支持服务商的 API Key。可以从 DeepSeek、OpenAI 或 SiliconFlow 获取免费或付费的 Key。

---

## 🔒 数据安全说明

- 所有 API 调用在 WordPress 后端执行，不在浏览器端发送数据
- 管理员需自行配置 API Key，插件不使用任何共享或内置凭证
- 用户可以选择使用哪个 AI 服务商，或完全禁用 AI 功能
- 文章内容不会被插件额外存储或记录（仅标准 WordPress 文章存储）

### 发送的数据
- 文章内容（标题、正文）— 用于分析和优化
- 现有元数据（标签、分类、摘要）— 用于增强处理
- **不发送**个人用户数据或站点配置信息

---

## 📸 截图

1. 主仪表盘，快速访问所有功能
2. 文章优化页面，支持批量操作
3. 标签和分类优化界面
4. AI 设置配置页面

---

## 📝 更新日志

### 2.0.23
- **兼容性：** 更新至 WordPress 7.0，最低 WP 版本提升至 5.3
- **修复：** Singleton Trait self/static bug 导致类间共享实例存储
- **修复：** AI Category 自定义提示词设置因错误 option key 无法生效
- **修复：** 所有模块统一 option key 命名规范（含向后兼容）
- **修复：** 各设置页样式冲突，现已统一为 AI Settings 风格
- **改进：** 移除所有管理页面的 H1 标题，统一顶部间距
- **改进：** 清理 4 个未使用的冗余 CSS/JS 文件
- **改进：** 修复 CSS select 下拉箭头与文字重叠问题

### 2.0.21
- **安全：** 增强 WordPress.org 插件目录安全要求
- **修复：** 改进所有用户提交数据的输入清理
- **修复：** 为所有显示内容添加输出转义
- **修复：** 为所有 REST API 端点添加权限回调
- **修复：** 用 `wp_json_encode()` 替换 `json_encode()`
- **改进：** 更好的 JSON 解码选项清理

### 2.0.19
- **新增：** 新的 AI 功能和改进
- **改进：** UI/UX 增强
- **安全：** 安全措施增强

### 2.0.5
- **安全：** 恢复所有 AJAX 处理器的 nonce 验证
- **安全：** 为所有 `$_POST` 操作添加输入清理
- **修复：** 统一 option key 命名规范
- **新增：** 国际化支持（.pot 文件）
- **改进：** 代码质量和安全加固

### 2.0.0
- **完全重写：** 模块化架构
- **新增：** AI 自动分类
- **新增：** CookieGuard GDPR 合规
- **新增：** 提示词设置管理
- **改进：** 性能优化

---

## 📄 许可证

GPLv2 or later — [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## 👥 贡献者

- [saiita](https://github.com/a506488043)
