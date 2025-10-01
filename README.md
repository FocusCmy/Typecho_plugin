# Typecho 插件集合

一个收录自用 Typecho 插件的小仓库，目前包含两款插件，帮助你在无需外部依赖的情况下增强站点的代码展示与访客统计能力。

- `TypechoCodeHighlight`：提供 highlight.js 本地化代码高亮、行号以及 macOS 风格外观。
- `VisitStats`：记录 PV/UV、设备与地域，并提供 ECharts 仪表盘。

## 仓库结构
```text
Typecho_Plugin/
├── TypechoCodeHighlight/   # 本地 highlight.js 代码高亮插件
│   ├── Plugin.php
│   ├── index.js
│   ├── assets/
│   └── TypechoCodeHighlight.md
├── VisitStats/             # PV/UV 统计与仪表盘
│   ├── Plugin.php
│   ├── Action.php
│   └── assets/
└── LICENSE
```

## 快速开始
1. 克隆仓库：`git clone https://github.com/FocusCmy/Typecho_Plugin.git`，或直接下载压缩包。
2. 根据需要选择插件目录，复制到你的 Typecho 安装目录 `usr/plugins/` 下（保持目录名不变）。
3. 登录 Typecho 后台 → 控制台 → 插件，启用目标插件。
4. 点击插件“设置”按钮，根据下文说明完成配置。

> 注意：如果你的站点启用了静态缓存，请确保缓存策略允许 PHP 正常执行插件逻辑。

## TypechoCodeHighlight

本地化的 highlight.js 集成，支持多主题、行号以及 macOS 风格的代码块外观。详细说明可参考 `TypechoCodeHighlight/TypechoCodeHighlight.md`。

### 功能特点
- 全量本地静态资源（highlight.js、主题 CSS、行号插件），不依赖 CDN。
- 自动扫描 `assets/styles/` 目录，提供 70+ 主题可选。
- 可选启用 `highlightjs-line-numbers` 行号显示，自动为代码块添加 `line-numbers` 类。
- 支持 macOS 风格代码框，顶部附带红黄绿按钮以及标题栏。
- 通过 `data-filename` 属性在标题栏展示文件名，增强可读性。

### 安装 / 升级
1. 将 `TypechoCodeHighlight` 目录复制到 `usr/plugins/`。
2. 后台启用插件，如需升级直接覆盖同名目录即可。
3. 主题缓存或静态资源缓存建议清空，以加载最新脚本和样式。

### 配置项
| 选项 | 默认值 | 说明 |
| --- | --- | --- |
| 高亮主题 | `atom-one-light` | 从 `assets/styles/` 读取主题列表，选择后会在前端引入对应 CSS。 |
| 显示行号 | 关闭 | 勾选后加载行号脚本和样式，为所有 `pre` 添加行号。 |
| 启用 macOS 外框 | 开启 | 包裹代码块为带标题栏的卡片，标题内容取自语言或 `data-filename`。 |

### 使用示例
```html
<pre><code class="language-php" data-filename="hello.php">
echo "Hello, Typecho!";
</code></pre>
```

### 常见问题
- 没有高亮效果：确认插件已启用且文章中的代码块使用 `pre > code` 结构。
- 行号未显示：确保勾选“显示行号”并保留 `highlightjs-line-numbers.min.js`、`highlightjs-line.css` 文件。
- 样式冲突：如主题自身对代码块有特殊样式，可在主题 CSS 中覆盖或删除相关规则。

## VisitStats

为 Typecho 提供轻量的 PV/UV 统计、访客日志记录与可视化仪表盘。默认使用服务端逻辑，无需布置额外追踪脚本。

### 功能特点
- 每次页面渲染时统计 PV，并基于当日 Cookie 进行 UV 去重。
- 根据 User-Agent 判定设备类型（PC / Phone / unknown）。
- 可选集成 `ip2region.xdb`，在本地解析访客地域信息。
- 自定义爬虫 UA 过滤列表，排除常见抓取工具。
- 提供 `/index.php/visit-stats` 仪表盘（管理员登录后访问），包含趋势图、设备分布、地域 TOP10 与最近日志。
- 统计数据存储于 `visit_stats`（按日汇总）与 `visit_stats_log`（访问明细）两张数据表。

### 安装 / 升级
1. 将 `VisitStats` 目录复制到 `usr/plugins/`。
2. 登录后台启用插件，激活流程会自动创建所需数据表并注册路由。
3. 如需升级直接覆盖，同步更新 `assets/echarts.min.js`、`ip2region.xdb` 等文件。

### 仪表盘入口
- 登录管理员账号后访问：`https://你的域名/index.php/visit-stats`
- 页面顶部提供时间区间、设备、地域筛选，并可跳回后台首页。

### 配置项
| 选项 | 默认值 | 说明 |
| --- | --- | --- |
| 单页应用 (SPA) 支持 | 关闭 | 预留开关。若站点采用前端路由，需要在路由切换时手动触发一次页面请求以记录统计。 |
| 排除爬虫 UA | 多行关键字 | 每行一个关键词（默认包含 `bot`、`spider`、`crawler` 等），命中后不会写入统计。 |
| 记录设备类型 | 开启 | 勾选后将 UA 判定结果写入 `device` 字段。 |
| 记录访客地域 | 关闭 | 需要在 `assets/` 中提供 `ip2region.xdb` 与 `XdbSearcher.php`，可自行替换为最新数据。 |
| 统计仪表盘地址 | 自动生成 | 便捷复制 `/visit-stats` 地址的只读字段。 |

### 数据表说明
- `${prefix}visit_stats`：字段 `ymd`（日期，例如 20240930）、`pv`、`uv`。
- `${prefix}visit_stats_log`：记录每次访问的 `ua`、`device`、`ip`、`geo`、`created`（时间戳），支持后台下载或自行分析。

### 使用提示
- 如果开启地域解析，建议定期更新 `ip2region.xdb` 以保持数据库准确度；文件较大（约 11 MB），请注意部署体积。
- 日志表可能随着流量增长而变大，可结合计划任务定期归档或清理历史数据。
- 访问仪表盘需管理员权限，未登录时会自动跳转至后台登录页。

## 开发与贡献
欢迎 issue、PR 或提交改进意见。建议在提交前说明所测试的 Typecho1.2.1+ 版本及使用环境，便于协作。

## 许可证
本仓库下所有插件均以 MIT License 发布，请在 `LICENSE` 文件中查看完整条款。
