<?php
/**
 * 用途：左侧抽屉（资料头 / 菜单树 / 站点计数 / 底部图标）
 * 依赖：Site\Drawer、Support\Html
 * 被调用：views/chrome/tail.php
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Bootstrap;
use qiqi77\Options;
use qiqi77\Support\Html;

/** @var \Widget\Archive $archive */
$archive = $data['archive'];
$drawer = Bootstrap::get('drawer');

$nodes = $drawer->nodes();
$socials = $drawer->socials();
$foot = $drawer->footer();
$counters = $drawer->counters();

$here = \qiqi77\Options::here();

/* 菜单图标：material 名称映射到内联 SVG，避免额外加载图标字体 */
$iconPaths = [
    'home'           => '<path d="M3 11l9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    'view_list'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'access_time'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'view_carousel'  => '<rect x="4" y="5" width="16" height="14" rx="2"/>',
    'rss_feed'       => '<path d="M5 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/><path d="M5 11a8 8 0 0 1 8 8"/><path d="M5 5a14 14 0 0 1 14 14"/>',
    'link'           => '<path d="M10 13a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1"/>',
    'diversity_3'    => '<circle cx="8" cy="9" r="3"/><circle cx="17" cy="10" r="2.4"/><path d="M2.5 19c.6-3 3-4.6 5.6-4.6S13.1 16 13.7 19"/><path d="M15 19c.3-2.2 1.9-3.5 3.8-3.5 1.2 0 2.3.4 3 1.2"/>',
    'insights'       => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
];

$svg = static function (string $name) use ($iconPaths): string {
    $path = $iconPaths[$name] ?? $iconPaths['link'];
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $path . '</svg>';
};
?>
<aside class="qq-drawer" id="qq-drawer" aria-hidden="true">
  <div class="qq-drawer__scrim" data-qq-drawer-close></div>
  <div class="qq-drawer__panel">

    <header class="qq-drawer__head">
      <img class="qq-drawer__avatar"
           src="<?php echo Html::e(\qiqi77\Options::ownerAvatar()); ?>"
           alt="" loading="lazy">
      <strong class="qq-drawer__title"><?php echo Html::e(trim((string) \Helper::options()->title)); ?></strong>
      <span class="qq-drawer__desc"><?php echo Html::e((string) Options::get('qq_description', '')); ?></span>
      <?php if ($socials): ?>
      <div class="qq-drawer__socials">
        <?php foreach ($socials as $one): ?>
        <a href="<?php echo Html::e($one['url']); ?>" target="_blank" rel="noopener" aria-label="<?php echo Html::e($one['name']); ?>">
          <i class="iconfont <?php echo Html::e($one['icon']); ?>"></i>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </header>

    <nav class="qq-drawer__nav" aria-label="站点导航">
      <a class="qq-dnav__link<?php echo $here === '/' ? ' is-on' : ''; ?>" href="<?php echo Html::e(\qiqi77\Options::url('/')); ?>">
        <span class="qq-dnav__icon"><?php echo $svg('home'); ?></span>首页
      </a>

      <?php foreach ($nodes as $node): ?>
        <?php if ($node['kind'] === 'split'): ?>
          <hr class="qq-dnav__split">
        <?php elseif ($node['kind'] === 'link'): ?>
          <a class="qq-dnav__link" href="<?php echo Html::e($node['url']); ?>"<?php echo !empty($node['blank']) ? ' target="_blank" rel="noopener"' : ''; ?>>
            <span class="qq-dnav__icon"><?php echo $svg($node['icon']); ?></span><?php echo Html::e($node['label']); ?>
          </a>
        <?php else: ?>
          <div class="qq-dnav__group" data-qq-acc>
            <button type="button" class="qq-dnav__head" aria-expanded="false">
              <span class="qq-dnav__icon"><?php echo $svg($node['icon']); ?></span>
              <span class="qq-dnav__label"><?php echo Html::e($node['label']); ?></span>
              <svg class="qq-dnav__arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <ul class="qq-dnav__body">
              <?php foreach ($node['items'] as $item): ?>
              <li>
                <a href="<?php echo Html::e($item['url']); ?>">
                  <span><?php echo Html::e($item['name']); ?></span>
                  <?php if (($item['extra'] ?? '') !== ''): ?><em><?php echo Html::e($item['extra']); ?></em><?php endif; ?>
                </a>
              </li>
              <?php endforeach; ?>
              <?php if (!$node['items']): ?><li class="qq-dnav__empty">暂无内容</li><?php endif; ?>
            </ul>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <a class="qq-drawer__stats" href="<?php echo Html::e(\qiqi77\Options::pageUrl('stats')); ?>" title="站点统计">
      <div><b><?php echo (int) $counters['posts']; ?></b><span>文章</span></div>
      <div><b><?php echo (int) $counters['comments']; ?></b><span>评论</span></div>
      <div><b><?php echo (int) $counters['cats']; ?></b><span>分类</span></div>
    </a>

    <footer class="qq-drawer__foot">
      <?php foreach ($foot['icons'] as $one): ?>
      <a href="<?php echo Html::e($one['url']); ?>" target="_blank" rel="noopener" aria-label="<?php echo Html::e($one['name']); ?>">
        <i class="iconfont icon-<?php echo Html::e($one['name']); ?>"></i>
      </a>
      <?php endforeach; ?>
      <span class="qq-drawer__credit">© <?php echo date('Y'); ?> <?php echo Html::e(trim((string) \Helper::options()->title)); ?></span>
    </footer>
  </div>
</aside>
