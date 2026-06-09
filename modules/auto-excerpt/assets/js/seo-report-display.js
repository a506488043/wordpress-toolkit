/**
 * SEO分析报告显示组件
 * 全新的完整AI分析报告展示
 */

class SEOReportDisplay {

    constructor() {
        this.initializeDisplay();
    }

    initializeDisplay() {
        // 初始化显示组件
    }

    /**
     * 显示完整的SEO AI分析报告
     */
    displayCompleteReport(analysisData, container) {
        const containerElement = document.querySelector(container);
        if (!containerElement) return;

        containerElement.innerHTML = this.buildReportHTML(analysisData);
    }

    /**
     * 构建报告HTML - 使用模态框结构
     */
    buildReportHTML(data) {
        // 创建遮罩层
        let html = '<div class="seo-modal-overlay" onclick="closeSEOReportModal()"></div>';
        
        // 主容器
        html += '<div class="seo-modal-content">';
        
        // 内容区域
        html += '<div class="seo-modal-body">';
        
        // 1. 文章信息
        html += this.buildArticleInfo(data);
        
        // 2. SEO得分
        html += this.buildSEOScores(data);
        
        // 3. 标题结构
        html += this.buildHeadingStructure(data);
        
        // 4. AI分析
        html += this.buildAIAnalysis(data);
        
        html += '</div>';
        
        // 底部固定关闭按钮
        html += '<div class="seo-modal-footer">';
        html += '<button class="button button-secondary" onclick="closeSEOReportModal()">关闭</button>';
        html += '</div>';
        
        html += '</div>';
        
        return html;
    }

    /**
     * 构建报告头部 - 移除重复标题
     */
    buildReportHeader(data) {
        const now = new Date();
        const dateStr = now.toLocaleString('zh-CN');
        const provider = data.ai_provider || 'DeepSeek';

        return '<div class="report-header">' +
               '<div class="report-meta">' +
               '<span class="report-date">分析时间: ' + dateStr + '</span>' +
               '<span class="ai-provider">AI引擎: ' + provider + '</span>' +
               '</div>' +
               '</div>';
    }

    /**
     * 构建文章信息
     */
    buildArticleInfo(data) {
        const title = data.post_title || data.title || '未知标题';
        const postId = data.post_id || 0;
        
        let html = '<div class="seo-report-section">';
        html += '<h3>📄 文章信息</h3>';
        html += '<p><strong>标题:</strong> ' + this.escapeHtml(title) + '</p>';
        
        // 如果有文章ID，也显示
        if (postId) {
            html += '<p class="report-post-id">文章ID: ' + postId + '</p>';
        }
        
        html += '</div>';
        return html;
    }

    /**
     * 构建SEO得分
     */
    buildSEOScores(data) {
        // 尝试从不同数据结构中获取得分
        const scores = [
            { label: '整体得分', value: data.overall_score || data.score?.overall || 0 },
            { label: '标题得分', value: data.title_score || data.score?.title || 0 },
            { label: '内容得分', value: data.content_score || data.score?.content || 0 },
            { label: '关键词得分', value: data.keyword_score || data.score?.keyword || 0 },
            { label: '可读性得分', value: data.readability_score || data.score?.readability || 0 }
        ];

        let html = '<div class="seo-report-section">';
        html += '<h3>📊 SEO得分</h3>';
        html += '<div class="score-grid">';

        scores.forEach(score => {
            html += '<div class="score-item">';
            html += '<strong>' + score.label + ':</strong> ';
            html += '<span class="score-value">' + score.value + '</span>';
            html += '</div>';
        });

        html += '</div>';
        
        // 统计信息
        html += '<div class="stats-grid">';
        html += '<div class="stat-item"><strong>字数:</strong> ' + (data.word_count || 0) + ' 字</div>';
        html += '<div class="stat-item"><strong>标题长度:</strong> ' + (data.title_length || 0) + ' 字符</div>';
        html += '</div>';
        
        html += '</div>';
        return html;
    }

    /**
     * 构建标题结构
     */
    buildHeadingStructure(data) {
        // 尝试从不同数据结构中获取标题计数
        const headingCounts = data.heading_counts || data.headings || {};
        
        let html = '<div class="seo-report-section">';
        html += '<h3>📝 标题结构</h3>';
        html += '<div class="heading-grid">';

        // 显示H1-H6
        for (let i = 1; i <= 6; i++) {
            const tag = 'h' + i;
            const count = headingCounts[tag] || 0;
            html += '<div class="heading-item">';
            html += '<span class="heading-tag">' + tag.toUpperCase() + '</span>';
            html += '<span class="heading-count">' + count + '</span>';
            html += '</div>';
        }

        html += '</div>';
        html += '</div>';
        return html;
    }

    /**
     * 构建AI分析
     */
    buildAIAnalysis(data) {
        const rawAnalysis = data.raw_ai_analysis || data.ai_analysis || '';
        const parsedData = this.safeParseJSON(rawAnalysis);
        
        let html = '<div class="seo-report-section">';
        html += '<h3>🤖 AI分析</h3>';
        
        if (parsedData && parsedData.analysis) {
            html += '<div class="analysis-details">';
            html += '<h4>📊 AI分析详情</h4>';
            
            Object.entries(parsedData.analysis).forEach(([key, value]) => {
                const labels = {
                    title_analysis: '标题分析',
                    content_analysis: '内容分析',
                    keyword_analysis: '关键词分析',
                    readability_analysis: '可读性分析'
                };
                
                html += '<div class="analysis-item">';
                html += '<h5>' + (labels[key] || key) + ':</h5>';
                html += '<p>' + this.escapeHtml(value) + '</p>';
                html += '</div>';
            });
            
            html += '</div>';
        } else {
            // 显示原始AI数据
            html += '<div class="raw-ai-analysis">';
            html += '<h4>📄 AI 原始分析</h4>';
            html += '<div class="ai-content">';
            html += '<pre>' + this.escapeHtml(rawAnalysis || '暂无AI分析数据') + '</pre>';
            html += '</div>';
            html += '</div>';
        }
        
        // AI推荐
        if (parsedData && parsedData.recommendations && parsedData.recommendations.length > 0) {
            html += '<div class="ai-recommendations">';
            html += '<h4>🤖 AI优化建议</h4>';
            
            parsedData.recommendations.forEach((rec, index) => {
                html += '<div class="recommendation-item">';
                html += '<div class="rec-header">';
                html += '<span class="rec-number">' + (index + 1) + '</span>';
                html += '<h5 class="rec-title">' + this.escapeHtml(rec.title || '建议') + '</h5>';
                html += '</div>';
                
                if (rec.description) {
                    html += '<p class="rec-description">' + this.escapeHtml(rec.description) + '</p>';
                }
                
                if (rec.action) {
                    html += '<p class="rec-action"><strong>操作:</strong> ' + this.escapeHtml(rec.action) + '</p>';
                }
                
                if (rec.impact) {
                    html += '<p class="rec-impact"><strong>效果:</strong> ' + this.escapeHtml(rec.impact) + '</p>';
                }
                
                html += '</div>';
            });
            
            html += '</div>';
        }
        
        html += '</div>';
        return html;
    }

    /**
     * 构建AI完整分析展示
     */
    buildAIFullAnalysis(data) {
        const rawAnalysis = data.raw_ai_analysis || '';
        const parsedData = this.safeParseJSON(rawAnalysis);

        let content = '';
        if (parsedData) {
            content = this.displayParsedAIData(parsedData);
        } else {
            content = this.displayRawAIData(rawAnalysis);
        }

        return '<div class="ai-full-analysis">' +
               '<h3>🧠 AI 完整分析</h3>' +
               content +
               '</div>';
    }

    /**
     * 显示解析后的AI数据
     */
    displayParsedAIData(parsedData) {
        let html = '';

        // 处理提取失败的情况
        if (parsedData.extraction_failed) {
            html += '<div class="extraction-failed" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 15px 0;">';
            html += '<h4 style="color: #856404; margin: 0 0 10px 0;">⚠️ 数据提取失败</h4>';
            html += '<p style="color: #856404; margin: 0 0 10px 0;">' + this.escapeHtml(parsedData.error_message) + '</p>';
            if (parsedData.raw_data_preview) {
                html += '<details style="margin-top: 10px;">';
                html += '<summary style="cursor: pointer; color: #856404; font-weight: bold;">查看原始数据预览</summary>';
                html += '<pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 10px; overflow-x: auto; font-size: 12px; max-height: 200px; overflow-y: auto;">' + this.escapeHtml(parsedData.raw_data_preview) + '</pre>';
                html += '</details>';
            }
            html += '</div>';
            return html;
        }

        // 显示部分提取的提示
        if (parsedData.extraction_partial && parsedData.extraction_note) {
            html += '<div class="extraction-partial" style="background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 6px; padding: 12px; margin: 15px 0;">';
            html += '<p style="color: #005a87; margin: 0;">ℹ️ ' + this.escapeHtml(parsedData.extraction_note) + '</p>';
            html += '</div>';
        }

        // 分析详情
        if (parsedData.analysis) {
            html += '<div class="analysis-details">';
            html += '<h4>📊 AI分析详情</h4>';

            Object.entries(parsedData.analysis).forEach(([key, value]) => {
                const labels = {
                    title_analysis: '标题分析',
                    content_analysis: '内容分析',
                    keyword_analysis: '关键词分析',
                    readability_analysis: '可读性分析'
                };

                // 如果是"AI analysis parsing failed"，显示特殊提示
                if (value === 'AI analysis parsing failed') {
                    html += '<div class="analysis-item parsing-failed">';
                    html += '<h5>' + (labels[key] || key) + ':</h5>';
                    html += '<div class="notice notice-warning">';
                    html += '<p>⚠️ ' + this.escapeHtml(value) + '</p>';
                    html += '<p><strong>建议:</strong>请检查AI API连接或重新生成分析。</p>';
                    html += '</div>';
                    html += '</div>';
                } else {
                    html += '<div class="analysis-item">';
                    html += '<h5>' + (labels[key] || key) + ':</h5>';
                    html += '<p>' + this.escapeHtml(value) + '</p>';
                    html += '</div>';
                }
            });

            html += '</div>';
        }

        // 显示推荐信息
        if (parsedData.recommendations && parsedData.recommendations.length > 0) {
            html += '<div class="ai-recommendations">';
            html += '<h4>🤖 AI优化建议</h4>';

            parsedData.recommendations.forEach((rec, index) => {
                html += '<div class="recommendation-item">';
                html += '<div class="rec-header">';
                html += '<span class="rec-number">' + (index + 1) + '</span>';
                html += '<h5 class="rec-title">' + this.escapeHtml(rec.title || '建议') + '</h5>';
                html += '</div>';

                if (rec.description) {
                    html += '<p class="rec-description">' + this.escapeHtml(rec.description) + '</p>';
                }

                if (rec.action) {
                    html += '<p class="rec-action"><strong>操作:</strong> ' + this.escapeHtml(rec.action) + '</p>';
                }

                if (rec.impact) {
                    html += '<p class="rec-impact"><strong>效果:</strong> ' + this.escapeHtml(rec.impact) + '</p>';
                }

                html += '</div>';
            });

            html += '</div>';
        }

        return html;
    }

    /**
     * 显示原始AI数据
     */
    displayRawAIData(rawData) {
        const content = rawData || '暂无AI分析数据';

        return '<div class="raw-ai-analysis">' +
               '<h4>📄 AI 原始分析</h4>' +
               '<div class="ai-content">' +
               '<pre>' + this.escapeHtml(content) + '</pre>' +
               '</div>' +
               '</div>';
    }

    /**
     * 构建SEO得分详情
     */
    buildScoreDetails(data) {
        const scores = [
            { label: '整体得分', value: data.overall_score, key: 'overall' },
            { label: '标题得分', value: data.title_score, key: 'title' },
            { label: '内容得分', value: data.content_score, key: 'content' },
            { label: '关键词得分', value: data.keyword_score, key: 'keyword' },
            { label: '可读性得分', value: data.readability_score, key: 'readability' }
        ];

        let html = '';
        html += '<div class="score-details">';
        html += '<h3>📈 SEO 得分详情</h3>';
        html += '<div class="scores-grid">';

        scores.forEach(score => {
            const scoreClass = this.getScoreClass(score.value);
            html += '<div class="score-item">';
            html += '<div class="score-header">';
            html += '<span class="score-label">' + score.label + '</span>';
            html += '<span class="score-value ' + scoreClass + '">' + score.value + '</span>';
            html += '</div>';
            html += '<div class="score-bar">';
            html += '<div class="score-fill ' + scoreClass + '" style="width: ' + score.value + '%"></div>';
            html += '</div>';
            html += '</div>';
        });

        html += '</div>';
        html += '</div>';

        return html;
    }

    /**
     * 构建关键词分析
     */
    buildKeywordAnalysis(data) {
        const keywords = data.ai_keywords || [];
        const focusKeywords = data.ai_meta_info ? data.ai_meta_info.focus_keywords || [] : [];

        let html = '';

        html += '<div class="keyword-analysis">';
        html += '<h3>🎯 关键词分析</h3>';
        html += '<div class="keyword-sections">';
        html += '<div class="keyword-section">';
        html += '<h4>相关关键词</h4>';
        html += '<div class="keywords-list">';

        if (keywords.length > 0) {
            keywords.forEach(keyword => {
                html += '<span class="keyword-tag">' + this.escapeHtml(keyword) + '</span>';
            });
        } else {
            html += '<p class="no-keywords">暂无关键词数据</p>';
        }

        html += '</div>';
        html += '</div>';

        if (focusKeywords.length > 0) {
            html += '<div class="keyword-section">';
            html += '<h4>核心关键词</h4>';
            html += '<div class="keywords-list">';

            focusKeywords.forEach(keyword => {
                html += '<span class="keyword-tag focus">' + this.escapeHtml(keyword) + '</span>';
            });

            html += '</div>';
            html += '</div>';
        }

        html += '</div>';
        html += '</div>';

        return html;
    }

    /**
     * 构建详细优化建议
     */
    buildDetailedRecommendations(data) {
        const recommendations = data.ai_recommendations || [];
        let html = '';

        html += '<div class="detailed-recommendations">';
        html += '<h3>💡 详细优化建议</h3>';
        html += '<div class="recommendations-list">';

        if (recommendations.length > 0) {
            recommendations.forEach((rec, index) => {
                const priority = rec.priority || 'medium';
                html += '<div class="recommendation-item priority-' + priority + '">';
                html += '<div class="rec-header">';
                html += '<span class="rec-number">' + (index + 1) + '</span>';
                html += '<h4 class="rec-title">' + this.escapeHtml(rec.title || '') + '</h4>';
                html += '<span class="priority-badge ' + priority + '">';
                html += this.getPriorityLabel(rec.priority);
                html += '</span>';
                html += '</div>';

                if (rec.description) {
                    html += '<div class="rec-description">';
                    html += '<strong>问题描述:</strong>';
                    html += '<p>' + this.escapeHtml(rec.description) + '</p>';
                    html += '</div>';
                }

                if (rec.action) {
                    html += '<div class="rec-action">';
                    html += '<strong>🔧 具体操作步骤:</strong>';
                    html += '<div class="action-steps">' + this.formatActionSteps(rec.action) + '</div>';
                    html += '</div>';
                }

                if (rec.impact) {
                    html += '<div class="rec-impact">';
                    html += '<strong>✨ 预期效果:</strong>';
                    html += '<p>' + this.escapeHtml(rec.impact) + '</p>';
                    html += '</div>';
                }

                html += '</div>';
            });
        } else {
            html += '<p class="no-recommendations">暂无优化建议</p>';
        }

        html += '</div>';
        html += '</div>';

        return html;
    }

    /**
     * 构建技术分析
     */
    buildTechnicalAnalysis(data) {
        let html = '';

        html += '<div class="technical-analysis">';
        html += '<h3>🔧 技术分析</h3>';
        html += '<div class="tech-stats">';
        html += '<div class="tech-item">';
        html += '<label>文章字数:</label>';
        html += '<span>' + (data.word_count || 0) + ' 字</span>';
        html += '</div>';
        html += '<div class="tech-item">';
        html += '<label>标题长度:</label>';
        html += '<span>' + (data.title_length || 0) + ' 字符</span>';
        html += '</div>';
        html += '<div class="tech-item">';
        html += '<label>图片数量:</label>';
        html += '<span>' + (data.image_count || 0) + ' 个</span>';
        html += '</div>';
        html += '<div class="tech-item">';
        html += '<label>内部链接:</label>';
        html += '<span>' + (data.internal_links || 0) + ' 个</span>';
        html += '</div>';
        html += '<div class="tech-item">';
        html += '<label>外部链接:</label>';
        html += '<span>' + (data.external_links || 0) + ' 个</span>';
        html += '</div>';
        html += '</div>';

        if (data.heading_counts) {
            html += '<div class="heading-stats">';
            html += '<h4>标题标签统计</h4>';
            html += '<div class="headings-grid">';

            Object.entries(data.heading_counts).forEach(([tag, count]) => {
                html += '<div class="heading-item">';
                html += '<span class="heading-tag">' + tag.toUpperCase() + '</span>';
                html += '<span class="heading-count">' + count + '</span>';
                html += '</div>';
            });

            html += '</div>';
            html += '</div>';
        }

        html += '</div>';

        return html;
    }

    /**
     * 构建元信息建议
     */
    buildMetaInfo(data) {
        const metaInfo = data.ai_meta_info || {};

        let html = '';

        html += '<div class="meta-info">';
        html += '<h3>📝 元信息建议</h3>';
        html += '<div class="meta-suggestions">';

        if (metaInfo.suggested_title) {
            html += '<div class="meta-item">';
            html += '<label>建议标题:</label>';
            html += '<div class="meta-value selectable-text" data-type="suggested-title">' + this.escapeHtml(metaInfo.suggested_title) + '</div>';
            html += '<button class="copy-meta-btn" data-text="' + this.escapeHtml(metaInfo.suggested_title) + '" title="复制建议标题">📋 复制</button>';
            html += '</div>';
        }

        if (metaInfo.meta_description) {
            html += '<div class="meta-item">';
            html += '<label>Meta描述:</label>';
            html += '<div class="meta-value selectable-text" data-type="meta-description">' + this.escapeHtml(metaInfo.meta_description) + '</div>';
            html += '<button class="copy-meta-btn" data-text="' + this.escapeHtml(metaInfo.meta_description) + '" title="复制Meta描述">📋 复制</button>';
            html += '</div>';
        }

        html += '</div>';
        html += '</div>';

        return html;
    }

    /**
     * 安全解析JSON - 简化版本
     */
    safeParseJSON(jsonString) {
        try {
            if (!jsonString || typeof jsonString !== 'string') {
                return null;
            }
            return JSON.parse(jsonString);
        } catch (e) {
            console.warn('JSON解析失败:', e);

            // 简单的JSON修复
            const fixedJson = this.simpleJSONFix(jsonString);
            if (fixedJson) {
                try {
                    return JSON.parse(fixedJson);
                } catch (fixError) {
                    console.warn('修复后的JSON解析也失败:', fixError);
                }
            }
            return null;
        }
    }

    /**
     * 简单的JSON修复方法
     */
    simpleJSONFix(jsonString) {
        if (!jsonString || typeof jsonString !== 'string') {
            return null;
        }

        let fixed = jsonString.trim();

        // 移除```json标记
        if (fixed.startsWith('```json')) {
            fixed = fixed.replace(/^```json\s*/, '').replace(/\s*```$/, '');
        }

        // 基本的花括号修复
        const openBraces = (fixed.match(/\{/g) || []).length;
        const closeBraces = (fixed.match(/\}/g) || []).length;
        if (openBraces > closeBraces) {
            fixed += '}'.repeat(openBraces - closeBraces);
        }

        // 移除结尾的逗号
        fixed = fixed.replace(/,\s*([}\]])/g, '$1');

        return fixed;
    }

    /**
     * 修复损坏的JSON字符串 - 智能分段处理
     */
    fixBrokenJSON(jsonString) {
        if (!jsonString || typeof jsonString !== 'string') {
            return null;
        }

        let fixed = jsonString.trim();

        // 提取JSON内容（移除```json标记）
        if (fixed.startsWith('```json')) {
            fixed = fixed.replace(/^```json\s*/, '').replace(/\s*```$/, '');
        }


        // 首先尝试提取和修复完整的JSON
        const completeJson = this.extractCompleteJSON(fixed);
        if (completeJson) {
            return completeJson;
        }

        // 如果无法提取完整JSON，尝试分段提取
        const segmentedData = this.extractSegmentedData(fixed);
        if (segmentedData) {
            return JSON.stringify(segmentedData);
        }

        return JSON.stringify({
            extraction_failed: true,
            error_message: "JSON数据完全无法解析",
            raw_data_preview: fixed.substring(0, 500) + '...'
        });
    }

    /**
     * 提取完整的JSON对象
     */
    extractCompleteJSON(jsonString) {
        let fixed = jsonString;

        // 1. 修复花括号不匹配
        const openBraces = (fixed.match(/\{/g) || []).length;
        const closeBraces = (fixed.match(/\}/g) || []).length;
        if (openBraces > closeBraces) {
            fixed += '}'.repeat(openBraces - closeBraces);
        }

        // 2. 移除控制字符
        fixed = fixed.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');

        // 3. 修复未闭合的字符串 - 智能截断
        fixed = this.fixTruncatedStrings(fixed);

        // 4. 移除多余的逗号
        fixed = fixed.replace(/,\s*([}\]])/g, '$1');

        // 5. 尝试解析
        try {
            JSON.parse(fixed);
            return fixed;
        } catch (e) {
            return null;
        }
    }

    /**
     * 修复截断的字符串
     */
    fixTruncatedStrings(jsonString) {
        // 找到最后一个完整的字符串值
        let fixed = jsonString;

        // 匹配 "key": "value" 模式，确保value是完整的
        const keyValuePattern = /"([^"]*)"\s*:\s*"([^"]*)"/g;
        let lastMatch = null;
        let match;

        while ((match = keyValuePattern.exec(fixed)) !== null) {
            lastMatch = match;
        }

        if (lastMatch) {
            // 找到最后一个完整的键值对
            const lastIndex = lastMatch.index + lastMatch[0].length;
            // 在最后一个完整键值对后截断
            fixed = fixed.substring(0, lastIndex);

            // 添加必要的闭合结构
            const openBraces = (fixed.match(/\{/g) || []).length;
            const closeBraces = (fixed.match(/\}/g) || []).length;
            if (openBraces > closeBraces) {
                fixed += '}'.repeat(openBraces - closeBraces);
            }

        }

        return fixed;
    }

    /**
     * 分段提取数据 - 从截断的JSON中提取可用部分
     */
    extractSegmentedData(jsonString) {
        const data = {};
        let extractedCount = 0;

        try {
            // 提取keywords
            const keywordsMatch = jsonString.match(/"keywords"\s*:\s*\[([^\]]*)\]/);
            if (keywordsMatch) {
                const keywordsStr = keywordsMatch[1];
                const keywords = [];
                const keywordMatches = keywordsStr.match(/"([^"]*)"/g);
                if (keywordMatches) {
                    keywordMatches.forEach(match => {
                        const keyword = match.replace(/"/g, '');
                        if (keyword) keywords.push(keyword);
                    });
                }
                if (keywords.length > 0) {
                    data.keywords = keywords;
                    extractedCount++;
                }
            }

            // 提取score
            const scoreMatch = jsonString.match(/"score"\s*:\s*\{([^}]*)\}/);
            if (scoreMatch) {
                const scoreStr = scoreMatch[1];
                const score = {};

                // 提取各个分数
                const scorePairs = scoreStr.match(/"([^"]*)"\s*:\s*(\d+)/g);
                if (scorePairs) {
                    scorePairs.forEach(pair => {
                        const scoreMatch = pair.match(/"([^"]*)"\s*:\s*(\d+)/);
                        if (scoreMatch) {
                            score[scoreMatch[1]] = parseInt(scoreMatch[2]);
                        }
                    });
                }
                if (Object.keys(score).length > 0) {
                    data.score = score;
                    extractedCount++;
                }
            }

            // 提取analysis
            const analysisMatch = jsonString.match(/"analysis"\s*:\s*\{([^}]*)\}/);
            if (analysisMatch) {
                const analysisStr = analysisMatch[1];
                const analysis = {};

                // 提取各个分析项
                const analysisEntries = analysisStr.match(/"([^"]*)"\s*:\s*"([^"]*)"/g);
                if (analysisEntries) {
                    analysisEntries.forEach(entry => {
                        const entryMatch = entry.match(/"([^"]*)"\s*:\s*"([^"]*)"/);
                        if (entryMatch) {
                            analysis[entryMatch[1]] = entryMatch[2];
                        }
                    });
                }
                if (Object.keys(analysis).length > 0) {
                    data.analysis = analysis;
                    extractedCount++;
                }
            }

            // 提取recommendations (如果有的话)
            const recommendationsMatch = jsonString.match(/"recommendations"\s*:\s*\[([^\]]*)\]/);
            if (recommendationsMatch) {
                // 简化处理：如果有recommendations但被截断，创建一个通用的建议
                data.recommendations = [
                    {
                        title: "AI分析建议",
                        description: "基于当前内容的SEO优化建议",
                        action: "请查看详细分析结果并根据建议进行优化",
                        impact: "提升文章的搜索引擎排名和用户体验"
                    }
                ];
                extractedCount++;
            }

            // 如果没有提取到任何数据，添加提示信息
            if (extractedCount === 0) {
                data.extraction_failed = true;
                data.error_message = "JSON数据解析失败，无法提取有效的SEO分析数据";
                data.raw_data_preview = jsonString.substring(0, 500) + '...';
            } else {
                data.extraction_partial = extractedCount < 4; // 最多4个数据段
                if (data.extraction_partial) {
                    data.extraction_note = `成功提取 ${extractedCount}/4 个数据段，JSON数据可能不完整`;
                }
            }

            return data;

        } catch (e) {
            return {
                extraction_failed: true,
                error_message: "数据提取过程发生错误: " + e.message,
                raw_data_preview: jsonString.substring(0, 500) + '...'
            };
        }
    }

    /**
     * 获取得分样式类
     */
    getScoreClass(score) {
        if (score >= 80) return 'excellent';
        if (score >= 70) return 'good';
        if (score >= 60) return 'average';
        return 'poor';
    }

    /**
     * 获取优先级标签
     */
    getPriorityLabel(priority) {
        const labels = {
            high: '高优先级',
            medium: '中优先级',
            low: '低优先级'
        };
        return labels[priority] || '中优先级';
    }

    /**
     * HTML转义
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * 格式化操作步骤
     */
    formatActionSteps(action) {
        if (!action) return '';

        // 将分步骤的文本格式化
        const steps = action.split(/\d+\.?\s*|\n|\r\n/)
                          .filter(step => step.trim())
                          .map((step, index) => '<div class="action-step">' + (index + 1) + '. ' + this.escapeHtml(step.trim()) + '</div>')
                          .join('');
        return steps;
    }
}

// 导出类
window.SEOReportDisplay = SEOReportDisplay;

/**
 * 关闭SEO报告弹框
 */
function closeSEOReportModal() {
    // 移除弹框和遮罩层
    const modal = document.querySelector('.seo-modal-content');
    const overlay = document.querySelector('.seo-modal-overlay');
    
    if (modal) {
        modal.remove();
    }
    
    if (overlay) {
        overlay.remove();
    }
    
    // 恢复body滚动
    document.body.style.overflow = '';
}

/**
 * 显示SEO报告弹框
 */
function showSEOReportModal(analysisData) {
    // 创建弹框实例
    const reportDisplay = new SEOReportDisplay();
    
    // 创建容器元素
    const container = document.createElement('div');
    container.id = 'seo-report-modal-container';
    container.style.position = 'fixed';
    container.style.top = '0';
    container.style.left = '0';
    container.style.width = '100%';
    container.style.height = '100%';
    container.style.zIndex = '10000';
    container.style.pointerEvents = 'none';
    
    // 生成HTML
    container.innerHTML = reportDisplay.buildReportHTML(analysisData);
    
    // 添加到body
    document.body.appendChild(container);
    
    // 禁用body滚动
    document.body.style.overflow = 'hidden';
    
    // 添加键盘事件监听（ESC关闭）
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSEOReportModal();
        }
    });
}