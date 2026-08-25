import hljs from 'highlight.js/lib/core';
import bash from 'highlight.js/lib/languages/bash';
import css from 'highlight.js/lib/languages/css';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import markdown from 'highlight.js/lib/languages/markdown';
import nginx from 'highlight.js/lib/languages/nginx';
import php from 'highlight.js/lib/languages/php';
import phpTemplate from 'highlight.js/lib/languages/php-template';
import plaintext from 'highlight.js/lib/languages/plaintext';
import scss from 'highlight.js/lib/languages/scss';
import sql from 'highlight.js/lib/languages/sql';
import twig from 'highlight.js/lib/languages/twig';
import typescript from 'highlight.js/lib/languages/typescript';
import xml from 'highlight.js/lib/languages/xml';
import yaml from 'highlight.js/lib/languages/yaml';

hljs.registerLanguage('bash', bash);
hljs.registerLanguage('css', css);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('json', json);
hljs.registerLanguage('markdown', markdown);
hljs.registerLanguage('nginx', nginx);
hljs.registerLanguage('php', php);
hljs.registerLanguage('php-template', phpTemplate);
hljs.registerLanguage('plaintext', plaintext);
hljs.registerLanguage('scss', scss);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('twig', twig);
hljs.registerLanguage('typescript', typescript);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('html', xml);
hljs.registerLanguage('yaml', yaml);

// Note: 'html' is normalised to 'xml' before labelFor() is called, so
// 'xml' carries the display label. The 'html' entry is intentionally
// absent to avoid dead code (see highlightCode()).
const LANG_LABELS = {
  bash: 'Bash',
  css: 'CSS',
  javascript: 'JavaScript',
  json: 'JSON',
  markdown: 'Markdown',
  nginx: 'Nginx',
  php: 'PHP',
  'php-template': 'PHP',
  plaintext: 'Plain Text',
  scss: 'SCSS',
  sql: 'SQL',
  twig: 'Twig',
  typescript: 'TypeScript',
  xml: 'HTML',
  yaml: 'YAML',
};

function declaredLanguage(code, pre) {
  const blob = `${code.className} ${pre.className} ${pre.getAttribute('lang') || ''} ${code.getAttribute('lang') || ''}`;
  const match = blob.match(/language-([a-z0-9+-]+)/i) || blob.match(/\blang-([a-z0-9+-]+)/i);
  return match ? match[1].toLowerCase() : '';
}

function labelFor(id) {
  if (!id) {
    return 'Code';
  }
  return LANG_LABELS[id] || id.replace(/[-_]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function highlightCode(code, pre) {
  const text = code.textContent || '';
  const declared = declaredLanguage(code, pre);
  let lang = declared;

  if (!lang || !hljs.getLanguage(lang)) {
    if (/^\s*</.test(text) && !/<\?(?:php|=)/i.test(text)) {
      lang = 'xml';
    } else {
      lang = hljs.highlightAuto(text, [
        'xml',
        'css',
        'scss',
        'javascript',
        'typescript',
        'php',
        'php-template',
        'bash',
        'json',
        'markdown',
        'sql',
        'yaml',
        'twig',
        'nginx',
      ]).language || 'plaintext';
    }
  }

  if (lang === 'html') {
    lang = 'xml';
  }
  const result = hljs.highlight(text, { language: lang, ignoreIllegals: true });
  code.innerHTML = result.value;
  code.classList.add('hljs', `language-${lang}`);
  return lang === 'xml' ? 'html' : lang;
}

function wrapBlock(pre) {
  if (pre.closest('.mh-code')) {
    return pre.closest('.mh-code');
  }
  const wrap = document.createElement('div');
  wrap.className = 'mh-code';
  const bar = document.createElement('div');
  bar.className = 'mh-code-chrome';
  bar.innerHTML = `
    <span class="mh-code-traffic" aria-hidden="true"><i></i><i></i><i></i></span>
    <span class="mh-code-lang"></span>
    <button type="button" class="mh-code-copy">Copy</button>
  `;
  pre.parentNode.insertBefore(wrap, pre);
  wrap.append(bar, pre);
  return wrap;
}

export function initCodeBlocks() {
  document.querySelectorAll('.post-prose pre, pre.snippet, .comment-content pre').forEach((pre) => {
    const code = pre.querySelector('code') || pre;
    const wrap = wrapBlock(pre);
    if (pre.dataset.mhHl === '1') {
      return;
    }
    pre.dataset.mhHl = '1';
    const lang = highlightCode(code instanceof HTMLElement ? code : pre, pre);
    const label = wrap.querySelector('.mh-code-lang');
    if (label) {
      label.textContent = labelFor(lang);
    }
    const copy = wrap.querySelector('.mh-code-copy');
    copy?.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(code.textContent || '');
        copy.textContent = 'Copied';
      } catch {
        copy.textContent = 'Copy failed';
      }
      window.setTimeout(() => {
        copy.textContent = 'Copy';
      }, 1600);
    });
  });
}
