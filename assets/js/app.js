/**
 * app.js — 主题运行时（无 jQuery）
 * 模块：主题切换 / 抽屉 / PJAX / 无限加载 / 目录 / 高亮 / 复制 / 灯箱 / 二维码 / 赏 / 表情 / 进度 / 启动画面
 */
(function () {
  'use strict';

  var CFG = window.__QQ__ || {};
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var on = function (el, ev, fn, opt) { el && el.addEventListener(ev, fn, opt || false); };

  /* ---------------- 主题 ---------------- */
  var THEME_KEY = 'qq_theme';

  function readTheme() {
    var m = document.cookie.match(new RegExp('(?:^|; )' + THEME_KEY + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : 'auto';
  }

  function applyTheme(mode) {
    var root = document.documentElement;
    if (mode === 'auto') {
      root.setAttribute('data-qq-theme', 'auto');
    } else {
      root.setAttribute('data-qq-theme', mode);
    }
    root.setAttribute('data-qq-mode', mode);
    document.cookie = THEME_KEY + '=' + mode + ';path=/;max-age=31536000;SameSite=Lax';
  }

  function cycleTheme() {
    // 先算出「现在实际是深还是浅」，再直接取反，保证点一次就变
    var cur = readTheme();
    var sysDark = !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    var isDark = cur === 'dark' || (cur === 'auto' && sysDark);
    var next = isDark ? 'light' : 'dark';
    applyTheme(next);
    return next;
  }

  /* 启动画面由 head 里的内联脚本负责，这里只保留调用入口 */
  function splashDone() {
    if (typeof window.qqSplashDone === 'function') {
      window.qqSplashDone();
    }
  }
  /* ---------------- 抽屉 ---------------- */
  function initDrawer() {
    var drawer = $('#qq-drawer');
    if (!drawer) return;
    on(document, 'click', function (e) {
      var open = e.target.closest('[data-qq-drawer]');
      if (open) { drawer.classList.add('is-open'); drawer.setAttribute('aria-hidden', 'false'); document.documentElement.style.overflow = 'hidden'; return; }
      if (e.target.closest('[data-qq-drawer-close]')
          || (drawer.classList.contains('is-open')
              && e.target.closest('.qq-dnav__link, .qq-dnav__body a, .qq-drawer__stats'))) {
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.documentElement.style.overflow = '';
      }
    });
  }

  /* ---------------- 顶栏状态 + 阅读进度 ---------------- */
  function initScrollFx() {
    var bar = document.querySelector('.qq-progress');
    if (!bar) {
      bar = document.createElement('div');
      bar.className = 'qq-progress';
      document.body.appendChild(bar);
    }

    var topbar = $('#qq-topbar');
    var last = 0;
    on(window, 'scroll', function () {
      var y = window.scrollY || 0;
      if (topbar) topbar.classList.toggle('is-scrolled', y > 8);
      // 文章页由阅读进度控制这条线，这里不抢
      if (document.querySelector('[data-qq-body]') || document.getElementById('qq-post-body')) return;
      var h = document.documentElement.scrollHeight - window.innerHeight;
      var p = h > 0 ? Math.min(1, y / h) : 0;
      if (Math.abs(p - last) > 0.001) {
        last = p;
        bar.style.transform = 'scaleX(' + p.toFixed(4) + ')';
      }
    }, { passive: true });
  }

  /* ---------------- 目录 / 高亮 ---------------- */
  function initToc() {
    var box = $('.js-toc');
    if (!box || !window.tocbot) return;
    if (!$('#qq-post-body')) return;
    try {
      tocbot.init({
        tocSelector: '.js-toc',
        contentSelector: '#qq-post-body',
        headingSelector: 'h2, h3',
        hasInnerContainers: false,
        scrollSmooth: true,
        headingsOffset: 80,
        scrollSmoothOffset: -80
      });
    } catch (e) {}
  }

  function initHighlight() {
    if (!window.hljs) return;
    $$('#qq-post-body pre code').forEach(function (block) {
      try { hljs.highlightElement(block); } catch (e) {}
    });
  }

  /* ---------------- 复制 / 灯箱 ---------------- */
  function initCopy() {
    if (!window.ClipboardJS) return;
    $$('[data-qq-copy]').forEach(function (btn) {
      if (btn.__bound) return;
      btn.__bound = true;
      var pre = btn.closest('pre');
      var code = pre ? pre.querySelector('code') : null;
      if (!code) return;
      new ClipboardJS(btn, { text: function () { return code.innerText; } });
      btn.addEventListener('click', function () {
        btn.textContent = '已复制';
        setTimeout(function () { btn.textContent = '复制'; }, 1400);
      });
    });
  }

  /* 原生灯箱：不依赖 Fancybox，点正文图片放大 */
  var zoom = { box: null, img: null };

  function openZoom(src, alt) {
    if (!zoom.box || !src) return;
    zoom.img.src = src;
    zoom.img.alt = alt || '';
    zoom.box.classList.add('is-open');
    document.documentElement.style.overflow = 'hidden';
  }

  function closeZoom() {
    if (!zoom.box) return;
    zoom.box.classList.remove('is-open');
    document.documentElement.style.overflow = '';
  }

  function initLightbox() {
    var body = document.getElementById('qq-post-body');
    if (!body) return;

    if (!zoom.box) {
      var box = document.createElement('div');
      box.className = 'qq-zoom';
      box.innerHTML = '<button class="qq-zoom__close" type="button" aria-label="关闭">×</button><img alt="">';
      document.body.appendChild(box);
      zoom.box = box;
      zoom.img = box.querySelector('img');

      box.addEventListener('click', function (e) {
        if (e.target === box || !e.target.closest('img')) closeZoom();
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeZoom();
      });
    }

    // 正文换页后要重新绑（元素是新的）
    if (body.__qqZoomBound) return;
    body.__qqZoomBound = 1;
    body.addEventListener('click', function (e) {
      var t = e.target;
      if (!t || t.tagName !== 'IMG') return;
      if (t.closest('a')) return;             // 图片套了链接时让链接生效
      if (t.closest('pre')) return;           // 代码块里的图不处理
      e.preventDefault();
      openZoom(t.currentSrc || t.src, t.alt);
    });
  }
  function initLazy() {
    if (!window.LazyLoad) return;
    new LazyLoad({ elements_selector: 'img[loading="lazy"]' });
  }

  /* ---------------- 二维码 ---------------- */
  function initQr() {
    // 每次打开都按当前地址重画：PJAX 换页后二维码必须跟着变
    var builtFor = '';

    function build(modal) {
      var canvas = modal.querySelector('[data-qq-qr-canvas]') || modal.querySelector('#qq-qr-canvas');
      if (!canvas || !window.QRCode) return;
      var url = location.href.split('#')[0];
      if (builtFor === url) return;

      canvas.innerHTML = '';
      try {
        new QRCode(canvas, {
          text: url,
          width: 180,
          height: 180,
          correctLevel: QRCode.CorrectLevel.M
        });
        builtFor = url;
      } catch (err) {}
    }

    on(document, 'click', function (e) {
      var modal = document.getElementById('qq-qr');
      if (!modal) return;

      if (e.target.closest('[data-qq-qr-toggle]')) {
        build(modal);
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
      }
      if (e.target.closest('[data-qq-qr-close]') || e.target === modal) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
      }
    });
  }
  /* ---------------- 打赏 ---------------- */
  function initReward() {
    // 弹窗随 PJAX 换页会重建，不能提前缓存引用
    on(document, 'click', function (e) {
      var modal = document.getElementById('qq-reward-modal');
      if (!modal) return;

      if (e.target.closest('[data-qq-reward]')) {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.documentElement.style.overflow = 'hidden';
      }
      if (e.target.closest('[data-qq-reward-close]') || e.target === modal) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.documentElement.style.overflow = '';
      }
    });

    on(document, 'keydown', function (e) {
      if (e.key !== 'Escape') return;
      var modal = document.getElementById('qq-reward-modal');
      if (modal && modal.classList.contains('is-open')) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.documentElement.style.overflow = '';
      }
    });
  }

  /* ---------------- 表情面板 ---------------- */

  /* ---------------- 回复 ---------------- */
  function initReply() {
    var form = $('#qq-comment-form');
    if (!form) return;
    var parentInput = $('#qq-parent');
    var tip = $('#qq-reply-tip');

    on(document, 'click', function (e) {
      var btn = e.target.closest('[data-qq-reply]');
      if (btn) {
        var id = btn.getAttribute('data-qq-reply');
        var name = btn.getAttribute('data-qq-author') || '';
        if (parentInput) parentInput.value = id;
        if (tip) { tip.hidden = false; tip.querySelector('b').textContent = name; }
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        var ta = $('.qq-form__text', form);
        ta && ta.focus();
      }
      if (e.target.closest('[data-qq-reply-cancel]')) {
        if (parentInput) parentInput.value = '0';
        if (tip) tip.hidden = true;
      }
    });
  }

  /* ---------------- 加载更多：首屏自动一次，之后手动 ---------------- */
  var inf = { io: null, busy: false, done: false, autoUsed: false, seen: {} };

  function initInfinite() {
    var more = document.querySelector('[data-qq-more]');
    if (!more) return;

    if (inf.io) { inf.io.disconnect(); inf.io = null; }
    inf.busy = false;
    inf.done = false;
    inf.autoUsed = false;
    inf.seen = {};

    var link = more.querySelector('a.next');
    var grid = document.querySelector('[data-qq-grid]');
    if (!link || !grid) { more.classList.add('is-end'); return; }

    link.classList.add('qq-loadbtn');

    // 已在页面上的条目，避免重复追加
    Array.prototype.forEach.call(grid.children, function (node) {
      var a = node.tagName === 'A' ? node : node.querySelector('a[href]');
      if (a) inf.seen[a.getAttribute('href')] = 1;
    });

    function finish() {
      inf.done = true;
      if (inf.io) { inf.io.disconnect(); inf.io = null; }
      link.classList.remove('is-loading');
      if (link.parentNode) link.parentNode.removeChild(link);
      more.classList.add('is-end');
    }

    // auto = true 表示这次由滚动触发；只允许发生一次
    function load(auto) {
      if (inf.busy || inf.done) return;
      if (auto && inf.autoUsed) return;

      var href = link.getAttribute('href');
      if (!href || inf.seen[href]) { finish(); return; }

      inf.busy = true;
      if (auto) {
        inf.autoUsed = true;
        if (inf.io) { inf.io.disconnect(); inf.io = null; }   // 之后交给按钮
      }
      link.classList.add('is-loading');

      fetch(href, { credentials: 'same-origin', cache: 'no-store' })
        .then(function (r) {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.text();
        })
        .then(function (html) {
          var doc = new DOMParser().parseFromString(html, 'text/html');
          var src = doc.querySelector('[data-qq-grid]');
          var added = 0;

          if (src) {
            Array.prototype.forEach.call(src.children, function (node) {
              var a = node.tagName === 'A' ? node : node.querySelector('a[href]');
              var url = a ? a.getAttribute('href') : '';
              if (url && inf.seen[url]) return;
              if (url) inf.seen[url] = 1;

              var copy = document.importNode(node, true);
              copy.classList.add('qq-in');
              copy.style.animationDelay = Math.min(added * 45, 360) + 'ms';
              grid.appendChild(copy);
              added++;
            });
          }

          var next = doc.querySelector('[data-qq-more] a.next');
          var nextHref = next ? next.getAttribute('href') : '';
          if (added > 0 && nextHref && !inf.seen[nextHref]) {
            link.setAttribute('href', nextHref);
          } else {
            finish();
          }
          initHighlight();
          initLazy();
          initCopy();
          fixGridTail();
        })
        .catch(function () { finish(); })
        .then(function () {
          inf.busy = false;
          if (link.parentNode) link.classList.remove('is-loading');
        });
    }

    // 首屏进入视口：自动加载一次，然后断开
    if ('IntersectionObserver' in window) {
      inf.io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting && !inf.autoUsed) load(true);
        });
      }, { rootMargin: '120px 0px' });
      inf.io.observe(more);
    }

    // 按钮：之后都靠点
    if (!link.__qqBound) {
      link.__qqBound = true;
      link.addEventListener('click', function (e) {
        e.preventDefault();
        load(false);
      });
    }
  }
  /* ---------------- PJAX ---------------- */
  function initPjax() {
    if (!window.Pjax || window.__qqPjax) return;
    window.__qqPjax = 1;

    // 交给 Pjax 自己匹配容易和自定义选择器打架，这里只让它做引擎，点击由我们转发
    var engine = new Pjax({
      elements: 'a[data-qq-pjax]',
      selectors: ['title', CFG.pjaxContainer || '#qq-shell'],
      timeout: 8000
    });
    window.__pjax = engine;

    // 捕获阶段接管：Pjax 自己的冒泡监听不会再重复处理
    on(document, 'click', function (e) {
      if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var a = e.target.closest ? e.target.closest('a[href]') : null;
      if (!a) return;
      if (a.target && a.target !== '_self') return;
      if (a.hasAttribute('download') || a.hasAttribute('data-qq-no-pjax')) return;
      if (a.closest('[data-qq-more]') || a.classList.contains('qq-loadbtn')) return;   // 加载更多自己处理

      var href = a.getAttribute('href');
      if (!href || href.charAt(0) === '#' || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return;
      if (href.indexOf('/action/') > -1) return;
      if (a.host && a.host !== location.host) return;

      e.preventDefault();
      // 统一用站内相对路径，避免绝对地址与重定向被 Pjax 判成失败
      var target = a.pathname + a.search + a.hash;
      window.__qqNav = (window.__qqNav || 0) + 1;
      window.__qqPending = target;
      engine.loadUrl(target);
    }, true);

    document.addEventListener('pjax:send', function () {
      if (window.NProgress) NProgress.start();
      transitOn();
      transitRun(location.href);
      splashDone();
      var drawer = $('#qq-drawer');
      if (drawer) { drawer.classList.remove('is-open'); document.documentElement.style.overflow = ''; }
    });

    document.addEventListener('pjax:complete', function () {
      if (window.NProgress) NProgress.done();
      transitEnd();
      window.scrollTo(0, 0);
      boot(true);
    });

    document.addEventListener('pjax:error', function (ev) {
      if (window.NProgress) NProgress.done();
      transitEnd();
      window.__qqErr = ev && ev.detail ? (ev.detail.status || 0) : -1;
      // 失败就退回普通跳转，不要原地刷新把用户弹回去
      location.href = window.__qqPending || location.href;
    });
  }
  /* ---------------- 背景图 ---------------- */
  function initBackdrop() {
    var bg = $('.qq-backdrop');
    if (!bg) return;
    var d = bg.getAttribute('data-desktop');
    var m = bg.getAttribute('data-mobile');
    if (d) bg.style.setProperty('--qq-bg-d', 'url("' + d + '")');
    if (m) bg.style.setProperty('--qq-bg-m', 'url("' + m + '")');
  }


  /* ---------------- 在线心跳 ---------------- */
  var pulse = { timer: null };

  function paintOnline(d) {
    if (!d) return;
    var put = function (sel, v) {
      var el = typeof sel === 'string' ? document.querySelector(sel) : sel;
      if (el && v !== undefined && v !== null) el.textContent = v;
    };
    put('[data-qq-on-total]', d.total);
    if (d.counts) {
      put('[data-qq-on-owner]', d.counts.blogger);
      put('[data-qq-on-friend]', d.counts.friend);
      put('[data-qq-on-guest]', d.counts.guest);
    }
    if (d.today) {
      put('[data-qq-on-today]', d.today.uniques);
      put('[data-qq-on-views]', d.today.views);
    }
  }

  function ping(isPv) {
    if (!window.fetch || !window.QQ_ONLINE) return;
    var url = window.QQ_ONLINE + '?do=ping' + (isPv ? '&pv=1' : '');
    fetch(url, { method: 'POST', credentials: 'same-origin', cache: 'no-store', keepalive: true })
      .then(function (r) { return r.json(); })
      .then(paintOnline)
      .catch(function () {});
  }

  function initHeartbeat() {
    ping(true);
    if (pulse.timer) clearInterval(pulse.timer);
    pulse.timer = setInterval(function () { ping(false); }, 45000);
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible') ping(false);
    });
  }

  /* ---------------- 回到顶部 ---------------- */
  function initBackTop() {
    var btn = document.querySelector('[data-qq-top]');
    if (!btn) {
      btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'qq-top';
      btn.setAttribute('data-qq-top', '');
      btn.setAttribute('aria-label', '回到顶部');
      btn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>';
      document.body.appendChild(btn);
      btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
    var show = function () {
      btn.classList.toggle('is-on', (window.scrollY || 0) > 320);
    };
    on(window, 'scroll', show, { passive: true });
    show();
  }

  /* ---------------- 移动端目录浮层 ---------------- */
  function initTocFab() {
    var toc = document.querySelector('[data-qq-toc]');
    if (!toc) return;
    var old = document.querySelector('[data-qq-toc-fab]');
    if (old) old.parentNode.removeChild(old);

    var fab = document.createElement('button');
    fab.type = 'button';
    fab.className = 'qq-toc-fab';
    fab.setAttribute('data-qq-toc-fab', '');
    fab.setAttribute('aria-label', '目录');
    fab.textContent = '目录';
    document.body.appendChild(fab);

    fab.addEventListener('click', function () {
      toc.classList.toggle('is-open');
      fab.classList.toggle('is-on', toc.classList.contains('is-open'));
    });
    toc.addEventListener('click', function (e) {
      if (e.target.closest('a')) {
        toc.classList.remove('is-open');
        fab.classList.remove('is-on');
      }
    });
  }

  /* ---------------- 运行时间 ---------------- */
  var uptimeTimer = null;

  function initUptime() {
    if (uptimeTimer) { clearInterval(uptimeTimer); uptimeTimer = null; }
    var el = document.querySelector('[data-qq-since]');
    if (!el) return;
    var since = parseInt(el.getAttribute('data-qq-since'), 10) * 1000;
    if (!since) return;
    var tick = function () {
      var days = Math.max(0, Math.floor((Date.now() - since) / 86400000));
      var years = Math.floor(days / 365);
      el.innerHTML = '本站已硬撑 <b>' + years + '</b> 年 <b>' + (days - years * 365) + '</b> 天';
    };
    tick();
    uptimeTimer = setInterval(tick, 60000);
  }

  /* ---------------- 抽屉手风琴 ---------------- */
  function initAccordion() {
    on(document, 'click', function (e) {
      var head = e.target.closest('.qq-dnav__head');
      if (!head) return;
      var group = head.parentNode;
      var open = group.classList.toggle('is-open');
      head.setAttribute('aria-expanded', open ? 'true' : 'false');

      // 同一层只留一个展开
      var siblings = group.parentNode.children;
      for (var i = 0; i < siblings.length; i++) {
        var sib = siblings[i];
        if (sib !== group && sib.classList && sib.classList.contains('qq-dnav__group')) {
          sib.classList.remove('is-open');
          var h = sib.querySelector('.qq-dnav__head');
          if (h) h.setAttribute('aria-expanded', 'false');
        }
      }
    });
  }

  /* ---------------- 页面切换过渡 ---------------- */
  var transit = null;

  function initTransit() {
    if (document.querySelector('.qq-transit')) {
      transit = document.querySelector('.qq-transit');
      return;
    }
    transit = document.createElement('div');
    transit.className = 'qq-transit';
    transit.setAttribute('aria-hidden', 'true');
    transit.innerHTML =
      '<div class="qq-transit__box">' +
      '<div class="qq-transit__num"><b>0</b>%</div>' +
      '<div class="qq-transit__bar"><i></i></div>' +
      '<div class="qq-transit__stage">正在打开</div>' +
      '</div>';
    document.body.appendChild(transit);
  }

  /* 过渡进度：请求期间爬到 78%，落地瞬间补满 */
  var transitTick = null;

  function transitPaint(p, stage) {
    if (!transit) return;
    var num = transit.querySelector('.qq-transit__num b');
    var bar = transit.querySelector('.qq-transit__bar i');
    var tip = transit.querySelector('.qq-transit__stage');
    if (num) num.textContent = Math.round(p);
    if (bar) bar.style.width = p.toFixed(1) + '%';
    if (tip && stage) tip.textContent = stage;
  }

  function transitRun(url) {
    if (!transit) return;
    var p = 0;
    var name = url || '';
    try {
      name = new URL(url, location.href).pathname;
    } catch (err) {}
    transitPaint(0, '正在打开 ' + name);
    if (transitTick) clearInterval(transitTick);
    transitTick = setInterval(function () {
      p += Math.max(0.8, (78 - p) * 0.12);
      if (p > 78) p = 78;
      transitPaint(p, '正在组装内容');
    }, 120);
  }

  function transitEnd() {
    if (transitTick) { clearInterval(transitTick); transitTick = null; }
    transitPaint(100, '完成');
    setTimeout(function () {
      if (transit) transit.classList.remove('is-on');
    }, 180);
  }

  function transitOn() {
    if (!transit) return;
    clearTimeout(transit.__timer);
    // 慢于 120ms 才出遮罩，快请求不闪一下
    transit.__timer = setTimeout(function () {
      transit.classList.add('is-on');
    }, 120);
  }

  function transitOff() {
    if (!transit) return;
    clearTimeout(transit.__timer);
    transit.classList.remove('is-on');
  }

  /* ---------------- 悬停预取：让站内跳转更快 ---------------- */
  function initPrefetch() {
    var seen = {};
    var warm = function (e) {
      var a = e.target.closest ? e.target.closest('a[href]') : null;
      if (!a) return;
      var href = a.getAttribute('href');
      if (!href || href.charAt(0) === '#' || href.indexOf('//') === 0) return;
      if (href.indexOf('mailto:') === 0 || href.indexOf('/action/') > -1) return;
      if (seen[href]) return;
      seen[href] = 1;
      if (!window.fetch) return;
      try {
        fetch(href, { credentials: 'same-origin', cache: 'force-cache', priority: 'low' })
          .catch(function () {});
      } catch (err) {}
    };
    on(document, 'mouseover', warm, { passive: true });
    on(document, 'touchstart', warm, { passive: true });
  }

  /* 手机端搜索展开 */
  function initSearchToggle() {
    on(document, "click", function (e) {
      var btn = e.target.closest("[data-qq-search]");
      if (!btn) return;
      var bar = document.querySelector(".qq-topbar");
      if (!bar) return;
      var open = bar.classList.toggle("is-search");
      if (open) {
        var input = bar.querySelector(".qq-search input");
        if (input) setTimeout(function () { input.focus(); }, 60);
      }
    });

    // 点空白处收起
    on(document, "click", function (e) {
      var bar = document.querySelector(".qq-topbar");
      if (!bar || !bar.classList.contains("is-search")) return;
      if (e.target.closest(".qq-search") || e.target.closest("[data-qq-search]")) return;
      bar.classList.remove("is-search");
    });

    // Esc 收起
    on(document, "keydown", function (e) {
      if (e.key !== "Escape") return;
      var bar = document.querySelector(".qq-topbar");
      if (bar) bar.classList.remove("is-search");
    });
  }
  /* 只绑一次的东西（PJAX 换壳后依然有效） */
  var onceMap = {};
  function once(key, fn) {
    if (onceMap[key]) return;
    onceMap[key] = 1;
    try {
      fn();
    } catch (err) {
      // 单个初始化失败不能拖垮整页
      if (window.console && console.warn) console.warn('[qiqi77] init failed:', key, err);
    }
  }
  /* ---------------- 代码块：超长折叠 + 展开后可滚动 ---------------- */
  function initCodeFold() {
    var body = document.getElementById('qq-post-body');
    if (!body) return;

    Array.prototype.forEach.call(body.querySelectorAll('pre'), function (pre) {
      if (pre.__qqFold) return;
      pre.__qqFold = 1;

      var code = pre.querySelector('code');
      if (!code) return;

      var lines = (code.textContent || '').split('\n').length;
      if (pre.scrollHeight <= 460) return;   // 短的不用折叠

      pre.classList.add('qq-code--tall');

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'qq-code__fold';
      btn.textContent = '展开全部（' + lines + ' 行）';
      pre.appendChild(btn);

      btn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        var open = pre.classList.toggle('is-open');
        btn.textContent = open ? '收起代码' : '展开全部（' + lines + ' 行）';
      });
    });
  }
  /* 两列网格里最后落单的那张拉通，避免半格空着 */
  /* 最后一行只剩一张时把它拉通，免得旁边空半格。
     不用奇偶判断——置顶卡、跨列卡都会让奇偶失真，直接看实际位置 */
  function fixGridTail() {
    var grid = document.querySelector('[data-qq-grid]');
    if (!grid) return;

    var cards = Array.prototype.slice.call(grid.children);
    cards.forEach(function (c) { c.classList.remove('qq-card--wide'); });
    if (cards.length < 2) return;

    var last = cards[cards.length - 1];
    var prev = cards[cards.length - 2];

    // 不同行 = 最后一张独占一行
    if (last.offsetTop > prev.offsetTop + 4) {
      last.classList.add('qq-card--wide');
    }
  }

  /* ---------------- 阅读进度（按正文，不是整页） ---------------- */
  var reading = { pill: null, ticking: false };

  function initReading() {
    if (CFG.reading === false) return;

    var body = document.getElementById('qq-post-body');
    var bar = document.querySelector('.qq-progress');

    if (!body) {
      if (reading.pill) { reading.pill.remove(); reading.pill = null; }
      if (bar) bar.style.transform = 'scaleX(0)';
      return;
    }

    if (!reading.pill) {
      var pill = document.createElement('div');
      pill.className = 'qq-reading-pct';
      pill.innerHTML = '<b>0</b>%';
      document.body.appendChild(pill);
      reading.pill = pill;
    }

    var paint = function () {
      reading.ticking = false;
      if (!reading.pill) return;          // 换页后旧闭包可能还在跑
      var rect = body.getBoundingClientRect();
      var total = rect.height - window.innerHeight;
      var done = -rect.top;
      var p = total > 0 ? Math.min(1, Math.max(0, done / total)) : (rect.top < 0 ? 1 : 0);

      if (bar) bar.style.transform = 'scaleX(' + p.toFixed(4) + ')';
      var num = reading.pill.querySelector('b');
      if (num) num.textContent = Math.round(p * 100);
      reading.pill.classList.toggle('is-on', p > 0.02);
      reading.pill.classList.toggle('is-done', p >= 0.995);
    };

    var onScroll = function () {
      if (reading.ticking) return;
      reading.ticking = true;
      window.requestAnimationFrame(paint);
    };

    if (window.__qqReadingScroll) window.removeEventListener('scroll', window.__qqReadingScroll);
    window.__qqReadingScroll = onScroll;
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    paint();
  }
  function guard(name, fn) {
    try { fn(); } catch (err) {
      if (window.console && console.warn) console.warn('[qiqi77]', name, err);
    }
  }

  function boot(fromPjax) {
    /* 启动页头像加载失败就隐藏（原先是内联 onerror，CSP 下会被拦） */
    var splashAvatar = document.getElementById('qq-splash-avatar');
    if (splashAvatar && !splashAvatar.dataset.qqBound) {
      splashAvatar.dataset.qqBound = '1';
      splashAvatar.addEventListener('error', function () { splashAvatar.style.display = 'none'; });
    }

    guard('theme', function () { applyTheme(readTheme()); });

    // 只绑一次（PJAX 换壳后仍然有效）
    once('backdrop', initBackdrop);
    once('drawer', initDrawer);
    once('accordion', initAccordion);
    once('transit', initTransit);
    once('prefetch', initPrefetch);
    once('searchtoggle', initSearchToggle);
    once('scrollfx', initScrollFx);
    once('qr', initQr);
    once('reward', initReward);
    once('reply', initReply);
    once('heartbeat', initHeartbeat);
    once('backtop', initBackTop);

    // 每次换页重跑
    guard('uptime', initUptime);
    guard('toc', initToc);
    guard('highlight', initHighlight);
    guard('copy', initCopy);
    guard('lightbox', initLightbox);
    guard('lazy', initLazy);
    guard('tocfab', initTocFab);
    guard('infinite', initInfinite);
    guard('gridtail', fixGridTail);
    guard('reading', initReading);
    guard('codefold', initCodeFold);

    if (fromPjax) once('pjax', initPjax);
  }
  on(document, 'click', function (e) {
    if (e.target.closest('[data-qq-theme-toggle]')) cycleTheme();
  });

  /* 定时深色：读 html[data-qq-dark] 的 19-7 区间 */
  (function scheduleDark() {
    var mode = readTheme();
    if (mode !== 'auto') return;
    var range = (document.documentElement.getAttribute('data-qq-dark') || '').split('-');
    if (range.length !== 2) return;
    var from = parseInt(range[0], 10);
    var to = parseInt(range[1], 10);
    if (isNaN(from) || isNaN(to)) return;
    var h = new Date().getHours();
    var night = from > to ? (h >= from || h < to) : (h >= from && h < to);
    if (night) document.documentElement.setAttribute('data-qq-theme', 'dark');
  })();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { boot(true); });
  } else {
    boot(true);
  }

})();
