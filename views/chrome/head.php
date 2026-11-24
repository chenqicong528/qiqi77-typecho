<?php
/**
 * 用途：<head> 全量输出 + 顶栏 + 外壳开头
 * 依赖：Site\Seo、Options、Support\Html
 * 被调用：所有模板
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Options;
use qiqi77\Site\Seo;
use qiqi77\Support\Html;

/** @var \Widget\Archive $archive */
$archive = $data['archive'];
$ctx = $data['ctx'] ?? [];
$siteName = trim((string) \Helper::options()->title);
$brandTitle = $siteName;
$pageTitle = trim((string) ($ctx['title'] ?? ''));
$docTitle = ($pageTitle !== '' && $pageTitle !== $siteName && $siteName !== '')
    ? $pageTitle . ' · ' . $siteName
    : ($pageTitle !== '' ? $pageTitle : $siteName);
$canonical = (string) ($ctx['url'] ?? rtrim((string) \Helper::options()->siteUrl, '/') . '/');
$backdrop = (string) Options::get('qq_backdrop_url', '');
$backdropMobile = (string) Options::get('qq_backdrop_mobile', '');
$dark = (string) Options::get('qq_dark_schedule', '19-7');
$fontFamily = (string) Options::get('qq_font_family', '');
$fontUrl = (string) Options::get('qq_font_url', '');

/* 每次整页加载都放启动画面；PJAX 换页不经过这里，所以只有大刷新才看到 */
$showSplash = true;

/* 外观变量：后台可调，直接落到 CSS 自定义属性 */
$accent = trim((string) Options::get('qq_accent_color', '#ff4081'));
if (!preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\([^)]*\)|hsla?\([^)]*\)|[a-zA-Z]+)$/', $accent)) {
    $accent = '#ff4081';
}
$radiusPx = max(0, min(28, (int) Options::get('qq_radius', 14)));
$cardH = max(160, min(400, (int) Options::get('qq_card_height', 260)));
$veilA = max(0, min(100, (int) Options::get('qq_card_veil', 84))) / 100;
$dimA = max(0, min(100, (int) Options::get('qq_backdrop_dim', 42))) / 100;
/* 可见度 30 => 系数 1（维持原样），100 => 系数 0（几乎全露） */
$bdShow = max(0, min(100, (int) Options::get('qq_backdrop_show', 30)));
$bdK = (100 - $bdShow) / 70;
$bdBlur = max(0, min(24, (int) Options::get('qq_backdrop_blur', 0)));
$cardCols = max(2, min(4, (int) Options::get('qq_card_cols')));
$barA = max(0, min(100, (int) Options::get('qq_topbar_alpha', 82)));

/* 细节微调：颜色 / 排版 / 尺寸 */
$pickColor = static function ($v, string $fallback): string {
    $v = trim((string) $v);
    return preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\([^)]*\)|hsla?\([^)]*\)|[a-zA-Z]+)$/', $v) ? $v : $fallback;
};
$cBg = $pickColor(Options::get('qq_color_bg', '#f4f6fa'), '#f4f6fa');
$cSurface = $pickColor(Options::get('qq_color_surface', '#ffffff'), '#ffffff');
$cText = $pickColor(Options::get('qq_color_text', '#1c1f26'), '#1c1f26');
$cMuted = $pickColor(Options::get('qq_color_muted', '#96a0b0'), '#96a0b0');

$fsBody = max(13, min(18, (float) Options::get('qq_font_size', 15.5)));
$lhBody = max(1.5, min(2.3, (float) Options::get('qq_line_height', 1.92)));
$containerW = max(960, min(1440, (int) Options::get('qq_container_w', 1180)));
$cardGap = max(8, min(32, (int) Options::get('qq_card_gap', 18)));
$sideW = max(240, min(360, (int) Options::get('qq_sidebar_w', 300)));
$topbarH = max(52, min(80, (int) Options::get('qq_topbar_h', 60)));
$shadowK = max(0, min(100, (int) Options::get('qq_shadow_alpha', 100))) / 100;
$avatarR = Options::get('qq_avatar_shape', 'rounded') === 'circle' ? '50%' : '12px';
$surfA = max(40, min(100, (int) Options::get('qq_surface_alpha', 100)));
$surfaceCss = $surfA >= 100
    ? $cSurface
    : 'color-mix(in srgb, ' . $cSurface . ' ' . $surfA . '%, transparent)';

/* 水印：把文字塞进 SVG，浅色/深色各一份 */
$wmText = trim((string) Options::get('qq_watermark_text', 'Qicong 柒葱'));
$wmSvg = static function (string $text, string $fill): string {
    if ($text === '') {
        return '';
    }
    $safe = htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='240' height='130'>"
        . "<g transform='translate(16,84) rotate(-22)'>"
        . "<text font-family='-apple-system,Segoe UI,sans-serif' font-size='15' fill='{$fill}'>{$safe}</text>"
        . "</g></svg>";
    return 'url("data:image/svg+xml,' . rawurlencode($svg) . '")';
};
$wmLight = $wmSvg($wmText, 'rgba(0,0,0,0.028)');
$wmDark = $wmSvg($wmText, 'rgba(255,255,255,0.035)');
?>
<!DOCTYPE html>
<html lang="zh-CN" data-qq-theme="auto" data-qq-dark="<?php echo Html::e($dark); ?>" class="<?php echo $showSplash ? 'qq-loading' : ''; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo Html::e($docTitle); ?></title>
<?php $archive->header('generator=0&pingback=0&xmlrpc=0&wlw=0'); ?>
<?php foreach (Seo::meta($ctx) as $tag): ?>
<?php if (($tag['name'] ?? '') === 'description') { continue; } ?>
<meta <?php echo Html::e(array_key_first($tag)); ?>="<?php echo Html::e($tag[array_key_first($tag)]); ?>" content="<?php echo Html::e($tag['content']); ?>">
<?php endforeach; ?>
<link rel="canonical" href="<?php echo Html::e($canonical); ?>">
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1">
<?php if (is_file(__TYPECHO_ROOT_DIR__ . '/sitemap.xml')): ?>
<link rel="sitemap" type="application/xml" href="<?php echo Html::e(Options::siteBase()); ?>/sitemap.xml">
<?php endif; ?>
<meta name="theme-color" content="#101216">
<?php
$fav = trim((string) Options::get('qq_favicon_url'));
if ($fav === '') {
    /* 默认用站点根的 /favicon.ico；没有就用主题自带的那张 */
    $fav = is_file(__TYPECHO_ROOT_DIR__ . '/favicon.ico')
        ? Options::siteBase() . '/favicon.ico'
        : Options::themeBase() . '/assets/images/favicon.ico';
} elseif (str_starts_with($fav, '/')) {
    $fav = Options::siteBase() . $fav;
}
?>
<link rel="icon" href="<?php echo Html::e($fav); ?>">
<link rel="stylesheet" href="<?php echo Html::e(Options::asset('css/app.css')); ?>">
<link rel="stylesheet" href="<?php echo Html::e(Options::asset('css/iconfont.min.css')); ?>">
<link rel="stylesheet" href="<?php echo Html::e(Options::asset('css/atom-one-dark.min.css')); ?>">
<?php if ($fontUrl !== ''): ?>
<link rel="preload" as="font" href="<?php echo Html::e($fontUrl); ?>" crossorigin>
<?php endif; ?>
<style id="qq-boot">
  html.qq-loading, html.qq-loading body { overflow: hidden; background: #0d0f13; }
  .qq-splash { position: fixed; inset: 0; z-index: 9000; display: grid; place-content: center; justify-items: center; gap: 18px;
    background: radial-gradient(120% 110% at 50% 0%, #1b2030 0%, #0c0e12 60%); transition: opacity .4s ease, visibility .4s; }
  .qq-splash.is-out { opacity: 0; visibility: hidden; }
  .qq-splash__ring { width: 78px; height: 78px; border-radius: 50%; display: grid; place-items: center;
    background: conic-gradient(#ff4d8d var(--p, 0deg), rgba(255,255,255,.08) 0); position: relative; }
  .qq-splash__ring::after { content: ''; position: absolute; inset: 5px; border-radius: 50%; background: #12151b; }
  .qq-splash__pct { position: relative; z-index: 1; font: 700 15px/1 ui-monospace, monospace; color: #fff; }
  .qq-splash__bar { width: 190px; height: 3px; border-radius: 99px; background: rgba(255,255,255,.1); overflow: hidden; }
  .qq-splash__bar i { display: block; height: 100%; width: 0; background: linear-gradient(90deg, #ff8fbc, #ff2d78); }
  .qq-splash__meta { font: 11px/1.6 ui-monospace, monospace; color: rgba(255,255,255,.45); text-align: center; }

  .qq-splash__avatar { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; box-shadow: 0 10px 24px rgba(0,0,0,.45); }
  .qq-splash__name { font-size: 13px; letter-spacing: .14em; color: rgba(255,255,255,.62); }
  .qq-splash__file { font: 10.5px/1.5 ui-monospace, SFMono-Regular, Consolas, monospace; color: rgba(255,255,255,.34); max-width: 240px; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .qq-splash__tip { margin-top: 8px; font-size: 11px; color: rgba(255,255,255,.3); }
  <?php if ($fontFamily !== ''): ?>
  body { font-family: <?php echo Html::e($fontFamily); ?>, system-ui, sans-serif; }
  <?php endif; ?>
</style>
<style id="qq-vars">
:root {
  --qq-c-accent: <?php echo $accent; ?>;
  --qq-c-accent-soft: color-mix(in srgb, <?php echo $accent; ?> 14%, transparent);
  --qq-c-bg: <?php echo $cBg; ?>;
  --qq-c-surface: <?php echo $surfaceCss; ?>;
  --qq-c-text: <?php echo $cText; ?>;
  --qq-c-text-3: <?php echo $cMuted; ?>;
  --qq-r-md: <?php echo $radiusPx; ?>px;
  --qq-r-lg: calc(<?php echo $radiusPx; ?>px + 8px);
  --qq-card-h: <?php echo $cardH; ?>px;
  --qq-veil-a: <?php echo number_format($veilA, 2, '.', ''); ?>;
  --qq-backdrop-a: <?php echo number_format($dimA, 2, '.', ''); ?>;
  --qq-bd-k: <?php echo number_format($bdK, 2, '.', ''); ?>;
  --qq-bd-blur: <?php echo $bdBlur; ?>px;
  --qq-card-cols: <?php echo $cardCols; ?>;
  --qq-topbar-a: <?php echo $barA; ?>%;
  --qq-fs-body: <?php echo $fsBody; ?>px;
  --qq-lh-body: <?php echo $lhBody; ?>;
  --qq-w: <?php echo $containerW; ?>px;
  --qq-gap: <?php echo $cardGap; ?>px;
  --qq-side-w: <?php echo $sideW; ?>px;
  --qq-topbar-h: <?php echo $topbarH; ?>px;
  --qq-avatar-r: <?php echo $avatarR; ?>;
  --qq-c-shadow: 0 2px 8px rgba(16, 20, 30, <?php echo number_format(0.05 * $shadowK, 3, '.', ''); ?>), 0 12px 32px rgba(16, 20, 30, <?php echo number_format(0.06 * $shadowK, 3, '.', ''); ?>);
<?php if ($wmLight !== ''): ?>
  --qq-watermark: <?php echo $wmLight; ?>;
  --qq-watermark-dark: <?php echo $wmDark; ?>;
<?php endif; ?>
}
</style>
<?php $injectCss = trim((string) Options::get('qq_inject_css', '')); ?>
<?php if ($injectCss !== ''): ?><style><?php echo $injectCss; ?></style><?php endif; ?>
<?php if (!empty($ctx['ld'] ?? null)): ?>
<script type="application/ld+json"><?php echo json_encode($ctx['ld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<?php else: ?>
<script type="application/ld+json"><?php echo json_encode(\qiqi77\Site\Seo::siteLd(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<?php endif; ?>
<?php echo (string) Options::get('qq_inject_head', ''); ?>
<?php $onlineEp = is_file(__TYPECHO_ROOT_DIR__ . '/online.php') ? Options::siteBase() . '/online.php' : ''; ?>
<script nonce="<?php echo defined('QQ_NONCE') ? QQ_NONCE : ''; ?>">window.QQ_BASE = <?php echo json_encode(Options::siteBase(), JSON_UNESCAPED_SLASHES); ?>; window.QQ_ONLINE = <?php echo json_encode($onlineEp, JSON_UNESCAPED_SLASHES); ?>;</script>
</head>
<body class="qq-body">
<?php if ($showSplash): ?>
<div class="qq-splash" id="qq-splash" aria-hidden="true">
  <img class="qq-splash__avatar" id="qq-splash-avatar" src="<?php echo Html::e(\qiqi77\Options::ownerAvatar()); ?>" alt="">
  <div class="qq-splash__name"><?php echo Html::e(trim((string) \Helper::options()->title)); ?></div>
  <div class="qq-splash__ring" id="qq-ring"><span class="qq-splash__pct" id="qq-pct">0%</span></div>
  <div class="qq-splash__bar"><i id="qq-bar"></i></div>
  <div class="qq-splash__meta" id="qq-meta">已加载 0 个资源 · 0 B</div>
  <div class="qq-splash__file" id="qq-file">正在建立连接…</div>
</div>
<script id="qq-boot-js" nonce="<?php echo defined('QQ_NONCE') ? QQ_NONCE : ''; ?>">
/* 启动页：只有本次会话第一次进入才全屏显示，之后直接跳过 */
(function () {
  var el = document.getElementById('qq-splash');
  if (!el) return;


  function clearLoading() {
    document.documentElement.className = document.documentElement.className.replace(/\s*qq-loading/g, '');
  }

  function dismiss() {
    el.className += ' is-out';
    clearLoading();
    setTimeout(function () { el.parentNode && el.parentNode.removeChild(el); }, 420);
  }


  var bar = document.getElementById('qq-bar'),
      pct = document.getElementById('qq-pct'),
      ring = document.getElementById('qq-ring'),
      meta = document.getElementById('qq-meta'),
      file = document.getElementById('qq-file');

  var n = 0, bytes = 0, cur = '', p = 0, done = false, t0 = Date.now();

  function size(v) {
    if (v < 1024) return v + ' B';
    if (v < 1048576) return (v / 1024).toFixed(v < 10240 ? 1 : 0) + ' KB';
    return (v / 1048576).toFixed(1) + ' MB';
  }

  if (window.PerformanceObserver) {
    try {
      new PerformanceObserver(function (list) {
        list.getEntries().forEach(function (e) {
          if (e.name === location.href) return;
          n++;
          bytes += (e.transferSize || e.encodedBodySize || 0);
          var tail = e.name.split('?')[0].split('/').pop();
          if (tail && tail.length > 3) { cur = tail; }
          else { try { cur = new URL(e.name).hostname; } catch (err2) { if (tail) cur = tail; } }
        });
        paint();
      }).observe({ type: 'resource', buffered: true });
    } catch (err) {}
  }

  function paint() {
    if (done) return;
    var target = 6 + (n / Math.max(n + 4, 12)) * 62;
    if (document.readyState !== 'loading') target = Math.max(target, 74);
    if (document.readyState === 'interactive') target = Math.max(target, 80);
    if (document.readyState === 'complete') target = Math.max(target, 92);
    p = Math.max(p, Math.min(target, p + 8));

    if (bar) bar.style.width = p.toFixed(1) + '%';
    if (pct) pct.textContent = Math.round(p) + '%';
    if (ring) ring.style.setProperty('--p', (p * 3.6).toFixed(0) + 'deg');
    if (meta) meta.textContent = '已加载 ' + n + ' 个资源 · ' + size(bytes) + ' · ' + ((Date.now() - t0) / 1000).toFixed(1) + 's';
    if (file) file.textContent = cur ? '正在加载 ' + cur : '正在建立连接…';
  }

  setInterval(paint, 120);
  paint();

  window.qqSplashDone = function () {
    if (done) return;
    done = true;
    if (bar) bar.style.width = '100%';
    if (pct) pct.textContent = '100%';
    if (meta) meta.textContent = '共 ' + n + ' 个资源 · ' + size(bytes);
    setTimeout(dismiss, 200);
  };

  window.addEventListener('load', window.qqSplashDone);
  setTimeout(window.qqSplashDone, 4200);
})();
</script>
<?php endif; ?>
<div class="qq-backdrop"<?php echo $backdrop !== '' ? ' data-desktop="' . Html::e($backdrop) . '"' : ''; ?><?php echo $backdropMobile !== '' ? ' data-mobile="' . Html::e($backdropMobile) . '"' : ''; ?> aria-hidden="true"></div>

<div class="qq-shell" id="qq-shell">
  <header class="qq-topbar" id="qq-topbar">
    <button class="qq-iconbtn" type="button" data-qq-drawer aria-label="打开菜单">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <a class="qq-brand" href="<?php echo Html::e(Options::url('/')); ?>"><?php echo Html::e($brandTitle); ?></a>
    <form class="qq-search" role="search" action="<?php echo Html::e(Options::url('/')); ?>" method="get">
      <input type="search" name="s" placeholder="<?php echo Html::e((string) Options::get('qq_search_ph', '搜索一下…')); ?>" aria-label="站内搜索" autocomplete="off">
      <button type="submit" class="qq-iconbtn" aria-label="搜索">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
      </button>
    </form>
    <div class="qq-topbar__tools">
      <button class="qq-iconbtn qq-search-btn" type="button" data-qq-search aria-label="搜索">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
      </button>
      <button class="qq-iconbtn" type="button" data-qq-theme-toggle aria-label="切换深浅色">
        <svg viewBox="0 0 24 24" aria-hidden="true" data-qq-icon="moon"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
      </button>
      <?php if (Options::flag('qq_qr_enable', true)): ?>
      <button class="qq-iconbtn" type="button" data-qq-qr-toggle aria-label="二维码">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM16 18h2v2h-2z"/></svg>
      </button>
      <?php endif; ?>
    </div>
  </header>
