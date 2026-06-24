<?php

/**
 * Reading UX on single posts: auto table of contents, reading-progress bar,
 * estimated reading time, and copy buttons on code blocks.
 */

namespace App;

add_action('wp_footer', function () {
    if (! is_singular('post')) {
        return;
    }
    ?>
    <script>
    (function(){
      var content = document.querySelector('.post-prose');
      if(!content) return;

      var bar = document.createElement('div');
      bar.className = 'mh-progress';
      document.body.appendChild(bar);
      function onScroll(){
        var h = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.width = (h > 0 ? (window.scrollY / h) * 100 : 0) + '%';
      }
      window.addEventListener('scroll', onScroll, {passive:true});
      onScroll();

      var words = (content.innerText || '').trim().split(/\s+/).length;
      var mins = Math.max(1, Math.round(words / 200));
      var meta = document.querySelector('.post-single-header .post-meta');
      if(meta){
        var rt = document.createElement('span');
        rt.className = 'mh-readtime';
        rt.textContent = ' · ' + mins + ' min read';
        meta.appendChild(rt);
      }

      var heads = content.querySelectorAll('h2, h3');
      if(heads.length >= 3){
        var toc = document.createElement('nav');
        toc.className = 'mh-toc';
        toc.setAttribute('aria-label', 'Table of contents');
        var t = document.createElement('p');
        t.className = 'mh-toc-title';
        t.textContent = 'On this page';
        toc.appendChild(t);
        var ul = document.createElement('ul');
        heads.forEach(function(h, i){
          if(!h.id){
            h.id = 'sec-' + i + '-' + (h.textContent || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
          }
          var li = document.createElement('li');
          if(h.tagName === 'H3'){ li.className = 'mh-toc-sub'; }
          var a = document.createElement('a');
          a.href = '#' + h.id;
          a.textContent = h.textContent;
          li.appendChild(a);
          ul.appendChild(li);
        });
        toc.appendChild(ul);
        content.parentNode.insertBefore(toc, content);
      }

      content.querySelectorAll('pre').forEach(function(pre){
        var btn = document.createElement('button');
        btn.className = 'mh-copy';
        btn.type = 'button';
        btn.textContent = 'Copy';
        btn.addEventListener('click', function(){
          navigator.clipboard.writeText(pre.innerText).then(function(){
            btn.textContent = 'Copied';
            setTimeout(function(){ btn.textContent = 'Copy'; }, 1500);
          });
        });
        pre.appendChild(btn);
      });
    })();
    </script>
    <?php
}, 40);
