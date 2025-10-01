(function () {
  // 读取后端传入的开关
  var cfg = (window.__TCHL__) || { showLineNumber: false, macBar: true };

  // 高亮所有 <pre><code>
  function highlightAll() {
    if (!window.hljs || typeof hljs.highlightElement !== 'function') return;
    document.querySelectorAll('pre code').forEach(function (el) {
      hljs.highlightElement(el);
    });
  }

  // 行号
  function addLineNumbers() {
    try {
      if (cfg.showLineNumber && window.hljs && typeof hljs.initLineNumbersOnLoad === 'function') {
        document.querySelectorAll('pre').forEach(function (pre) {
          if (!pre.classList.contains('line-numbers')) pre.classList.add('line-numbers');
        });
        hljs.initLineNumbersOnLoad();
      }
    } catch (e) {
      console.warn('[TypechoCodeHighlight] line-numbers init error:', e);
    }
  }

  // macOS 卡片包裹
  function wrapMacLike() {
    if (!cfg.macBar) return;
    document.querySelectorAll('pre').forEach(function (pre) {
      if (pre.closest('.code-macos')) return; // 已处理过
      var wrap = document.createElement('figure');
      wrap.className = 'code-macos';

      var bar = document.createElement('div');
      bar.className = 'code-macos__bar';

      var dotR = document.createElement('span'); dotR.className = 'code-macos__dot code-macos__dot--red';
      var dotY = document.createElement('span'); dotY.className = 'code-macos__dot code-macos__dot--yellow';
      var dotG = document.createElement('span'); dotG.className = 'code-macos__dot code-macos__dot--green';

      var title = document.createElement('div');
      title.className = 'code-macos__title';
      title.textContent = deriveTitle(pre);

      bar.appendChild(dotR); bar.appendChild(dotY); bar.appendChild(dotG); bar.appendChild(title);

      var body = document.createElement('div');
      body.className = 'code-macos__body';

      var parent = pre.parentNode;
      parent.insertBefore(wrap, pre);
      body.appendChild(pre);
      wrap.appendChild(bar);
      wrap.appendChild(body);
    });
  }

  function deriveTitle(pre) {
    var code = pre.querySelector('code');
    if (!code) return 'code';
    var cls = code.className || '';
    var lang = (cls.match(/language-([\w-]+)/i) || cls.match(/lang-([\w-]+)/i) || [])[1];
    if (!lang) return 'code';
    // 友好的别名
    lang = lang.replace(/^text$/i,'plain')
               .replace(/^(js|javascript)$/i,'JavaScript')
               .replace(/^ts$/i,'TypeScript')
               .replace(/^py$/i,'Python')
               .replace(/^rb$/i,'Ruby')
               .replace(/^php$/i,'PHP')
               .replace(/^c\+\+$/i,'C++')
               .replace(/^(csharp|cs)$/i,'C#');
    var fname = code.getAttribute('data-filename');
    return fname ? (fname + ' (' + lang + ')') : lang;
  }

  document.addEventListener('DOMContentLoaded', function () {
    highlightAll();     // 先高亮
    addLineNumbers();   // 可选行号
    wrapMacLike();      // 可选 macOS 外框
  });
})();
