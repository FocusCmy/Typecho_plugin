
---

# TypechoCodeHighlight 插件使用说明

## 功能简介

* 使用 **本地 highlight.js** 实现代码高亮，无需联网。
* 支持多种高亮主题（如 `atom-one-light`、`atom-one-dark`、`github` 等）。
* 可选开启 **代码行号**。
* 可选启用 **macOS 风格代码框**（顶部红黄绿按钮 + 标题栏）。

---

## 安装步骤

1. 插件目录：

```
/usr/plugins/TypechoCodeHighlight/
├─ Plugin.php                  # PHP 插件主体（第1部分）
└─ assets/                     # 静态资源（第2部分）
   ├─ highlight.min.js         # highlight.js 主文件（从官网包拷贝）
   ├─ highlightjs-line-numbers.min.js   #【可选】行号插件
   ├─ styles/
   │  ├─ atom-one-light.min.css # 主题（可存放多种）
   │  └─ atom-one-dark.min.css
   ├─ codehighlight.css        # 你的自定义样式（如 macOS 外框）
   └─ codehighlight.js         # 你的初始化逻辑（DOM 高亮、行号、包裹等）
```
2. 确保插件目录名为 **TypechoCodeHighlight**。
3. 在 Typecho 后台 → 插件 → 启用 **TypechoCodeHighlight**。

---

## 插件配置

* **选择代码高亮主题**：从本地 `assets/styles/` 中的样式文件选择。
* **是否显示行号**：勾选后，在代码左侧显示行号。
* **启用 macOS 风格工具栏**：勾选后，代码块会带上红黄绿按钮和标题栏外观。

---

## 使用方式

在文章中插入代码块时，确保格式如下（支持 `lang-xxx` 或 `language-xxx`）：

```html
<pre><code class="language-php">echo "Hello, World!";</code></pre>
```

如果需要在 macOS 样式栏中显示文件名，可以加上 `data-filename` 属性：

```html
<pre><code class="language-javascript" data-filename="app.js">
function hello(){ console.log("Hello!"); }
</code></pre>
```

---

## 常见问题

* **没有高亮效果**：请确认插件启用，且本地的 `highlight.min.js` 和主题 CSS 能访问。
* **行号错位**：请确认已加载 `highlightjs-line-numbers.min.js` 和 `line-numbers.css`。
* **样式冲突**：如主题本身带有代码样式，建议在主题 CSS 中移除或覆盖相关样式。

---
