<?php
/**
 * 用途：友情链接页（读 Links 插件表，支持随机排序与分页）
 * 依赖：View、Options、Typecho\Db
 * 被调用：独立页面模板 links
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Options;
use qiqi77\Support\Html;
use qiqi77\View;
use Typecho\Db;

$db = Db::get();
$rows = [];
try {
    $rows = $db->fetchAll($db->select()->from('table.links')->order('order', Db::SORT_ASC));
} catch (\Throwable $e) {
    $rows = [];
}

if (Options::flag('qq_links_shuffle')) {
    shuffle($rows);
}

$perPage = max(1, (int) Options::get('qq_links_page_size', 20));
$page = max(1, (int) ($this->request->get('page') ?: 1));
$total = count($rows);
$pages = max(1, (int) ceil($total / $perPage));
$slice = array_slice($rows, ($page - 1) * $perPage, $perPage);

$ctx = [
    'type'  => 'page',
    'title' => '友情链接',
    'desc'  => '互换友链，欢迎交流',
    'url'   => (string) $this->permalink,
];

View::show('chrome/head', ['archive' => $this, 'ctx' => $ctx]);
?>
<div class="qq-stage">
  <main class="qq-main qq-main--wide">
    <div class="qq-archive-head">
      <span class="qq-archive-head__label">友链</span>
      <h1 class="qq-archive-head__title">友情链接</h1>
      <p class="qq-archive-head__count">共 <?php echo $total; ?> 个朋友</p>
    </div>

    <div class="qq-links">
      <?php foreach ($slice as $link): ?>
      <a class="qq-links__card" href="<?php echo Html::e((string) $link['url']); ?>" target="_blank" rel="noopener nofollow">
        <img src="<?php echo Html::e((string) ($link['image'] ?: Options::asset('images/logo.jpg'))); ?>" alt="" loading="lazy" width="44" height="44">
        <div>
          <b><?php echo Html::e((string) $link['name']); ?></b>
          <span><?php echo Html::e((string) ($link['description'] ?: $link['url'])); ?></span>
        </div>
      </a>
      <?php endforeach; ?>
      <?php if (!$slice): ?>
      <p class="qq-empty">还没有友链，去后台插件里添加吧。</p>
      <?php endif; ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="qq-pagination">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a class="<?php echo $i === $page ? 'is-on' : ''; ?>" href="<?php echo Html::e((string) $this->permalink . '?page=' . $i); ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
  </main>
</div>
<?php View::show('chrome/tail', ['archive' => $this]); ?>
