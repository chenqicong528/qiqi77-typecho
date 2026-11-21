<?php
/**
 * Template Name: 归档
 * 用途：按年月分组列出全部文章
 * 依赖：View、Typecho\Db
 * 被调用：Typecho 自定义页面模板
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Support\Html;
use qiqi77\View;
use Typecho\Db;

$db = Db::get();
try {
    $rows = $db->fetchAll($db->select('cid', 'title', 'created', 'views')->from('table.contents')
        ->where('type = ?', 'post')->where('status = ?', 'publish')
        ->order('created', Db::SORT_DESC));
} catch (\Throwable $e) {
    $rows = [];
}

$groups = [];
foreach ($rows as $row) {
    $groups[date('Y', (int) $row['created'])][] = $row;
}
ksort($groups);
$groups = array_reverse($groups, true);

$ctx = ['type' => 'page', 'title' => '归档', 'desc' => '全部文章', 'url' => (string) $this->permalink];
View::show('chrome/head', ['archive' => $this, 'ctx' => $ctx]);
?>
<div class="qq-stage">
  <main class="qq-main qq-main--wide">
    <div class="qq-archive-head">
      <span class="qq-archive-head__label">归档</span>
      <h1 class="qq-archive-head__title">全部文章</h1>
      <p class="qq-archive-head__count">共 <?php echo count($rows); ?> 篇</p>
    </div>

    <?php foreach ($groups as $year => $list): ?>
    <section class="qq-panel qq-archive-year">
      <h2 class="qq-panel__title"><?php echo Html::e((string) $year); ?> 年 · <?php echo count($list); ?> 篇</h2>
      <ul class="qq-rank">
        <?php foreach ($list as $row): ?>
        <li>
          <span><?php echo Html::e(date('m-d', (int) $row['created'])); ?></span>
          <a href="<?php echo Html::e(\qiqi77\Options::url('/' . (int) $row['cid'])); ?>"><?php echo Html::e((string) $row['title']); ?></a>
          <span><?php echo (int) $row['views']; ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endforeach; ?>
  </main>
</div>
<?php View::show('chrome/tail', ['archive' => $this]); ?>
