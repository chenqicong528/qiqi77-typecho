<?php
/**
 * 用途：搜索结果页头（含彩蛋）
 * 依赖：Html、随机文章
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Support\Html;

/** @var \Widget\Archive $archive */
$archive = $data['archive'];
$kw = trim((string) $archive->request->get('s'));
/* 路由式搜索 /search/关键词/ 时 s 为空，从 URL 里取 */
if ($kw === '') {
    $path = (string) parse_url((string) $archive->request->getRequestUrl(), PHP_URL_PATH);
    if (preg_match('#/search/(.+?)/?$#u', $path, $m)) {
        $kw = urldecode($m[1]);
    }
}
$total = (int) $archive->getTotal();

/* 彩蛋文案池：按结果数切换情绪 */
$cheer = [
    '找到 %d 篇，比想象中多一点。',
    '一共 %d 篇，慢慢翻。',
    '%d 篇相关，顺手点一篇？',
];
$blank = [
    '这个星球上没有找到「%s」。',
    '「%s」躲起来了，要不换个词？',
    '翻了半天，也没逮住「%s」。',
];
$pick = static fn (array $arr): string => $arr[array_rand($arr)];

/* 无结果时给三篇随机推荐 */
$picks = [];
if ($total === 0) {
    try {
        $db = \Typecho\Db::get();
        $rows = $db->fetchAll($db->select('cid', 'title')->from('table.contents')
            ->where('type = ?', 'post')->where('status = ?', 'publish')
            ->order('RAND()')->limit(3));
        foreach ($rows as $row) {
            $picks[] = ['title' => (string) $row['title'], 'url' => '/' . (int) $row['cid']];
        }
    } catch (\Throwable $e) {
    }
}
?>
<section class="qq-search-hero<?php echo $total === 0 ? ' is-empty' : ''; ?>">
  <div class="qq-search-hero__glow" aria-hidden="true"></div>

  <form class="qq-search-hero__form" action="<?php echo Html::e(\qiqi77\Options::url('/')); ?>" method="get" role="search">
    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
    <input type="search" name="s" value="<?php echo Html::e($kw); ?>"
           placeholder="<?php echo Html::e((string) \qiqi77\Options::get('qq_search_ph', '搜标题、正文、标签…')); ?>" autocomplete="off" aria-label="站内搜索">
    <button type="submit">搜索</button>
  </form>

  <?php if ($kw === ''): ?>
  <p class="qq-search-hero__hint">输入点什么，或者看看下面的随机推荐</p>
  <?php elseif ($total > 0): ?>
  <p class="qq-search-hero__meta">
    <?php echo Html::e(sprintf($pick($cheer), $total)); ?>
    <span class="qq-search-hero__kw"><?php echo Html::e($kw); ?></span>
  </p>
  <?php else: ?>
  <p class="qq-search-hero__meta">
    <?php echo Html::e(sprintf($pick($blank), $kw)); ?>
  </p>
  <p class="qq-search-hero__hint">没准是它换了个名字 —— 下面这几篇也许有惊喜</p>
  <?php endif; ?>

  <?php if ($total === 0 && $picks): ?>
  <ul class="qq-search-hero__picks">
    <?php foreach ($picks as $i => $p): ?>
    <li style="--i:<?php echo $i; ?>">
      <a href="<?php echo Html::e($p['url']); ?>"><?php echo Html::e($p['title']); ?></a>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if (mb_stripos($kw, '彩蛋') !== false): ?>
  <p class="qq-search-hero__egg">
    <span>🥚</span> 你找到了隐藏彩蛋：这条站是手写的，连彩蛋都是。
  </p>
  <?php endif; ?>
</section>
