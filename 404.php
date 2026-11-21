<?php
/**
 * 用途：404 页（带彩蛋）
 * 依赖：View、Html
 * 被调用：Typecho
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Support\Html;
use qiqi77\View;

$ctx = [
    'type'  => 'page',
    'title' => (string) \qiqi77\Options::get('qq_404_title'),
    'desc'  => '没有找到这个页面',
    'url'   => rtrim((string) \Helper::options()->siteUrl, '/') . '/404',
];

/* 随机几篇，供「摇一篇」使用 */
$pool = [];
try {
    $db = \Typecho\Db::get();
    $rows = $db->fetchAll($db->select('cid', 'title')->from('table.contents')
        ->where('type = ?', 'post')->where('status = ?', 'publish')
        ->order('RAND()')->limit(8));
    foreach ($rows as $row) {
        $pool[] = ['t' => (string) $row['title'], 'u' => '/' . (int) $row['cid']];
    }
} catch (\Throwable $e) {
}

View::show('chrome/head', ['archive' => $this, 'ctx' => $ctx]);
?>
<div class="qq-stage">
  <main class="qq-main qq-main--center">
    <div class="qq-404" data-qq-404>

      <div class="qq-404__sky" aria-hidden="true">
        <?php for ($i = 0; $i < 22; $i++): ?>
        <i style="--x:<?php echo mt_rand(2, 98); ?>%;--y:<?php echo mt_rand(2, 96); ?>%;--d:<?php echo mt_rand(0, 40) / 10; ?>s;--s:<?php echo mt_rand(1, 3); ?>px"></i>
        <?php endfor; ?>
        <span class="qq-404__shooting"></span>
      </div>

      <p class="qq-404__code" data-qq-404-code>404</p>
      <h1 class="qq-404__title"><?php echo Html::e((string) \qiqi77\Options::get('qq_404_title')); ?></h1>
      <p class="qq-404__sub"><?php echo Html::e((string) \qiqi77\Options::get('qq_404_desc')); ?></p>
      <p class="qq-404__desc" data-qq-404-quip>你要找的内容不存在，或者已经搬家。</p>

      <form class="qq-404__search" action="<?php echo Html::e(rtrim((string) \Helper::options()->siteUrl, '/')); ?>" method="get" role="search">
        <input type="search" name="s" placeholder="搜点什么？" aria-label="站内搜索">
        <button type="submit">搜索</button>
      </form>

      <div class="qq-404__actions">
        <a class="qq-btn qq-btn--accent" href="<?php echo Html::e(rtrim((string) \Helper::options()->siteUrl, '/')); ?>">回首页</a>
        <a class="qq-btn qq-btn--ghost" href="<?php echo Html::e(\qiqi77\Options::url('/archive/')); ?>">看归档</a>
        <button type="button" class="qq-btn qq-btn--ghost" data-qq-shake>摇一篇</button>
      </div>

      <?php
      $qqOnline = \qiqi77\Bootstrap::get('online');
      $qqLive = $qqOnline ? $qqOnline->live() : ['owner' => 0, 'total' => 0];
      $qqLast = $qqOnline ? $qqOnline->lastOwner() : 0;
      $qqOwnerOn = (int) ($qqLive['owner'] ?? 0) > 0;
      ?>
      <p class="qq-404__online">
        <span class="qq-404__dot<?php echo $qqOwnerOn ? ' is-on' : ''; ?>"></span>
        <?php if ($qqOwnerOn): ?>
        博主正在线上，留言大概率秒回
        <?php elseif ($qqLast > 0): ?>
        博主上次在线 <?php echo Html::e(\qiqi77\Site\Online::humanize($qqLast)); ?>
        <?php else: ?>
        博主不常在线，留言也会看到
        <?php endif; ?>
        <a href="<?php echo Html::e(\qiqi77\Options::pageUrl('online')); ?>">在线详情</a>
      </p>
      <div class="qq-404__egg" data-qq-egg hidden>
        <span class="qq-404__egg-icon">🥚</span>
        <div>
          <b>你把这页挖穿了。</b>
          <p>既然都到这儿了，送你一句话：<em>写博客最难的不是写，是坚持写。</em></p>
          <p class="qq-404__egg-foot">— 来自一个把 404 做成彩蛋的站长</p>
        </div>
      </div>
    </div>
  </main>
</div>

<script nonce="<?php echo defined('QQ_NONCE') ? QQ_NONCE : ''; ?>">
(function () {
  var pool = <?php echo json_encode($pool, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  var quips = [
    '你要找的内容不存在，或者已经搬家。',
    '这个地址是空的，就像周一早上的脑子。',
    '页面可能被猫踩掉了，我们正在找它。',
    '404 不是错误，是命运给你的一个岔路口。',
    '别慌，你先深呼吸，然后点上面那个按钮。'
  ];
  var q = document.querySelector('[data-qq-404-quip]');
  if (q) q.textContent = quips[Math.floor(Math.random() * quips.length)];

  var shake = document.querySelector('[data-qq-shake]');
  if (shake && pool.length) {
    shake.addEventListener('click', function () {
      var pick = pool[Math.floor(Math.random() * pool.length)];
      shake.textContent = '去《' + pick.t.slice(0, 12) + '》';
      setTimeout(function () { location.href = pick.u; }, 340);
    });
  }

  /* 上上下下左右左右 */
  var seq = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight'];
  var idx = 0;
  document.addEventListener('keydown', function (e) {
    if (e.key === seq[idx]) {
      idx++;
      if (idx === seq.length) {
        idx = 0;
        var egg = document.querySelector('[data-qq-egg]');
        if (egg) {
          egg.hidden = false;
          egg.classList.add('is-on');
          var box = document.querySelector('[data-qq-404]');
          if (box) box.classList.add('is-egg');
        }
      }
    } else {
      idx = e.key === seq[0] ? 1 : 0;
    }
  });

  var code = document.querySelector('[data-qq-404-code]');
  if (code) {
    code.addEventListener('click', function () {
      code.classList.remove('is-wobble');
      void code.offsetWidth;
      code.classList.add('is-wobble');
    });
  }
})();
</script>
<?php View::show('chrome/tail', ['archive' => $this]); ?>
