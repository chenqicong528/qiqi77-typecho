<?php
/**
 * Template Name: 站点统计
 * 用途：数据看板（内容量 / 互动量 / 近期访问趋势）
 * 依赖：View、Bootstrap、Site\Online
 * 被调用：Typecho 自定义页面模板
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Bootstrap;
use qiqi77\Support\Html;
use qiqi77\Support\Str;
use qiqi77\View;
use Typecho\Db;

$db = Db::get();
$online = Bootstrap::get('online');

$agg = ['posts' => 0, 'views' => 0, 'words' => 0, 'comments' => 0, 'cats' => 0, 'tags' => 0, 'days' => 0];
try {
    $row = $db->fetchRow($db->select(['COUNT(cid)' => 'n', 'SUM(views)' => 'v'])
        ->from('table.contents')->where('type = ?', 'post')->where('status = ?', 'publish'));
    $agg['posts'] = (int) ($row['n'] ?? 0);
    $agg['views'] = (int) ($row['v'] ?? 0);

    $texts = $db->fetchAll($db->select('text')->from('table.contents')
        ->where('type = ?', 'post')->where('status = ?', 'publish'));
    foreach ($texts as $t) {
        $agg['words'] += Str::chars((string) $t['text']);
    }

    $agg['comments'] = (int) $db->fetchRow($db->select(['COUNT(coid)' => 'n'])
        ->from('table.comments')->where('status = ?', 'approved'))['n'];
    $agg['cats'] = (int) $db->fetchRow($db->select(['COUNT(mid)' => 'n'])
        ->from('table.metas')->where('type = ?', 'category'))['n'];
    $agg['tags'] = (int) $db->fetchRow($db->select(['COUNT(mid)' => 'n'])
        ->from('table.metas')->where('type = ?', 'tag'))['n'];

    $first = $db->fetchRow($db->select('created')->from('table.contents')
        ->where('type = ?', 'post')->order('created', Db::SORT_ASC)->limit(1));
    $agg['days'] = $first ? max(1, (int) floor((time() - (int) $first['created']) / 86400)) : 0;
} catch (\Throwable $e) {
}

$top = [];
try {
    $top = $db->fetchAll($db->select('cid', 'title', 'views')->from('table.contents')
        ->where('type = ?', 'post')->where('status = ?', 'publish')
        ->order('views', Db::SORT_DESC)->limit(6));
} catch (\Throwable $e) {
}

$daily = $online->recent(14);
$peak = 1;
foreach ($daily as $d) {
    $peak = max($peak, (int) $d['uniques']);
}

$ctx = ['type' => 'page', 'title' => '站点统计', 'desc' => '数据看板', 'url' => (string) $this->permalink];
View::show('chrome/head', ['archive' => $this, 'ctx' => $ctx]);
?>
<div class="qq-stage">
  <main class="qq-main qq-main--wide">
    <div class="qq-archive-head">
      <span class="qq-archive-head__label">数据</span>
      <h1 class="qq-archive-head__title">站点统计</h1>
      <p class="qq-archive-head__count">已经写了 <?php echo $agg['days']; ?> 天</p>
    </div>

    <section class="qq-metrics">
      <div class="qq-metrics__item"><b><?php echo $agg['posts']; ?></b><span>篇文章</span></div>
      <div class="qq-metrics__item"><b><?php echo number_format($agg['words']); ?></b><span>总字数</span></div>
      <div class="qq-metrics__item"><b><?php echo number_format($agg['views']); ?></b><span>总浏览</span></div>
      <div class="qq-metrics__item"><b><?php echo $agg['comments']; ?></b><span>条评论</span></div>
      <div class="qq-metrics__item"><b><?php echo $agg['cats']; ?></b><span>个分类</span></div>
      <div class="qq-metrics__item"><b><?php echo $agg['tags']; ?></b><span>个标签</span></div>
    </section>

    <section class="qq-panel">
      <h2 class="qq-panel__title">近 14 天访客</h2>
      <div class="qq-chart">
        <?php foreach ($daily as $day => $d): ?>
        <div class="qq-chart__col" title="<?php echo Html::e($day . '：' . $d['uniques'] . ' 位访客'); ?>">
          <i style="height:<?php echo max(4, (int) round($d['uniques'] / $peak * 100)); ?>%"></i>
          <span><?php echo Html::e(date('m-d', strtotime($day))); ?></span>
        </div>
        <?php endforeach; ?>
        <?php if (!$daily): ?><p class="qq-empty">还没有访问数据</p><?php endif; ?>
      </div>
    </section>

    <section class="qq-panel">
      <h2 class="qq-panel__title">浏览最多</h2>
      <ol class="qq-rank">
        <?php foreach ($top as $i => $row): ?>
        <li>
          <em><?php echo $i + 1; ?></em>
          <a href="<?php echo Html::e(\qiqi77\Options::url('/' . (int) $row['cid'])); ?>"><?php echo Html::e((string) $row['title']); ?></a>
          <span><?php echo number_format((int) $row['views']); ?></span>
        </li>
        <?php endforeach; ?>
      </ol>
    </section>
  </main>
</div>
<?php View::show('chrome/tail', ['archive' => $this]); ?>
