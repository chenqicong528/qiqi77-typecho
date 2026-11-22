<?php
/**
 * 用途：侧栏——资料卡 / 欢迎与运行时间 / 站点数据 / 最新回复 / 友情链接
 * 依赖：Bootstrap、Options、Support\Html
 * 被调用：index.php、archive.php、post.php
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Bootstrap;
use qiqi77\Options;
use qiqi77\Support\Html;
use Typecho\Db;

/** @var \Widget\Archive $archive */
$archive = $data['archive'];
$db = Db::get();
$online = Bootstrap::get('online');
$live = $online->live();

/* ---- 统计 ---- */
$stat = ['posts' => 0, 'comments' => 0, 'cats' => 0, 'words' => 0];
try {
    $stat['posts'] = (int) $db->fetchRow($db->select(['COUNT(cid)' => 'n'])
        ->from('table.contents')->where('type = ?', 'post')->where('status = ?', 'publish'))['n'];
    $stat['comments'] = (int) $db->fetchRow($db->select(['COUNT(coid)' => 'n'])
        ->from('table.comments')->where('status = ?', 'approved'))['n'];
    $stat['cats'] = (int) $db->fetchRow($db->select(['COUNT(mid)' => 'n'])
        ->from('table.metas')->where('type = ?', 'category'))['n'];

    $texts = $db->fetchAll($db->select('text')->from('table.contents')
        ->where('type = ?', 'post')->where('status = ?', 'publish'));
    foreach ($texts as $one) {
        $stat['words'] += mb_strlen(trim(strip_tags((string) $one['text'])), 'UTF-8');
    }
} catch (\Throwable $e) {
}

/* ---- 分类 ---- */
try {
    $cats = $db->fetchAll($db->select('name', 'slug', 'count')->from('table.metas')
        ->where('type = ?', 'category')->order('count', Db::SORT_DESC)->limit(12));
} catch (\Throwable $e) {
    $cats = [];
}

/* ---- 最新回复 ---- */
try {
    $recent = $db->fetchAll($db->select('coid', 'cid', 'author', 'text')->from('table.comments')
        ->where('status = ?', 'approved')->order('created', Db::SORT_DESC)->limit(5));
} catch (\Throwable $e) {
    $recent = [];
}

/* ---- 友链 ---- */
$links = [];
$linkLimit = (int) Options::get('qq_links_page_size', 20);
if ($linkLimit > 0) {
    try {
        $links = $db->fetchAll($db->select('name', 'url')->from('table.links')
            ->order('order', Db::SORT_ASC)->limit($linkLimit));
    } catch (\Throwable $e) {
        $links = [];
    }
}


$totalViews = 0;
try {
    $totalViews = (int) $db->fetchRow($db->select(['SUM(views)' => 'v'])->from('table.contents'))['v'];
} catch (\Throwable $e) {
}
?>
<aside class="qq-side">
  <!-- 资料卡 -->
<?php
/* 资料卡头像：跟随 Typecho 站长邮箱（QQ 邮箱→QQ 头像，其他→Gravatar） */
$ownerAvatar = '';
$ownerMail = '';
try {
    $row = \Typecho\Db::get()->fetchRow(
        \Typecho\Db::get()->select('mail')->from('table.users')->order('uid', \Typecho\Db::SORT_ASC)->limit(1)
    );
    $ownerMail = (string) ($row['mail'] ?? '');
} catch (\Throwable $e) {
}
$ownerAvatar = \qiqi77\Comment\Avatar::resolve('', $ownerMail, (string) \Helper::options()->title);
/* 运行天数：服务端先算好，JS 只负责继续走 */
$since = trim((string) Options::get('qq_uptime_since'));
$sinceTs = $since !== '' ? strtotime($since) : false;
if (!$sinceTs || $sinceTs > time()) {
    $sinceTs = 0;
} else {
    $upDays = max(0, (int) floor((time() - $sinceTs) / 86400));
    $upYears = intdiv($upDays, 365);
    $upRest = $upDays - $upYears * 365;
}
?>
  <section class="qq-card-info">
    <?php $cover = trim((string) Options::get('qq_sidebar_cover')); ?>
    <div class="qq-card-info__cover"<?php echo $cover !== '' ? ' style="background-image:url(\'' . Html::e($cover) . '\')"' : ''; ?>>
      <span class="qq-card-info__avatar" style="background-image:url('<?php echo Html::e($ownerAvatar); ?>')"></span>
    </div>
    <div class="qq-card-info__body">
      <h3 class="qq-card-info__name"><?php echo Html::e(trim((string) \Helper::options()->title)); ?></h3>
      <p class="qq-card-info__desc"><?php echo Html::e((string) Options::get('qq_description', '')); ?></p>
    </div>
  </section>

  <!-- 欢迎 / 运行时间 -->
  <section class="qq-module">
    <h4 class="qq-module__title">欢迎！</h4>
    <div class="qq-module__welcome">
      <p>你是第 <?php echo number_format(max(1, $totalViews)); ?> 位相遇的小伙伴</p>
      <?php if ($sinceTs > 0): ?>
      <p class="qq-uptime" data-qq-since="<?php echo (int) $sinceTs; ?>">本站已硬撑 <b><?php echo $upYears; ?></b> 年 <b><?php echo $upRest; ?></b> 天</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- 站点数据 -->
  <section class="qq-module">
    <h4 class="qq-module__title">站点数据</h4>
    <div class="qq-stat4">
      <div><b><?php echo (int) $stat['posts']; ?></b><span>文章</span></div>
      <div><b><?php echo (int) $stat['comments']; ?></b><span>评论</span></div>
      <div><b><?php echo (int) $stat['cats']; ?></b><span>分类</span></div>
      <div><b><?php echo number_format($stat['words']); ?></b><span>字数</span></div>
    </div>
    <div class="qq-cloud">
      <?php foreach ($cats as $cat): ?>
      <a href="<?php echo Html::e(\qiqi77\Options::catUrl((string) $cat['slug'])); ?>" class="qq-side__cat"><?php echo Html::e((string) $cat['name']); ?><em><?php echo (int) $cat['count']; ?></em></a>
      <?php endforeach; ?>
    </div>
    <div class="qq-online">
      <p class="qq-online__title"><span class="qq-dot"></span>当前在线 <b data-qq-on-total><?php echo (int) $live['total']; ?></b></p>
      <p class="qq-online__roles">
        <span>博主<b data-qq-on-owner><?php echo (int) $live['owner']; ?></b></span>
        <span>朋友<b data-qq-on-friend><?php echo (int) $live['friend']; ?></b></span>
        <span>游客<b data-qq-on-guest><?php echo (int) $live['guest']; ?></b></span>
      </p>
    </div>
    <p class="qq-module__links">
      <a href="<?php echo Html::e(\qiqi77\Options::pageUrl('online')); ?>">在线详情</a>
      <a href="<?php echo Html::e(\qiqi77\Options::pageUrl('stats')); ?>">站点统计</a>
    </p>
  </section>

  <!-- 最新回复 -->
  <?php if ($recent): ?>
  <section class="qq-module">
    <h4 class="qq-module__title">最新回复</h4>
    <ul class="qq-recent">
      <?php foreach ($recent as $one): ?>
      <li>
        <a href="<?php echo Html::e(\qiqi77\Options::postUrl((int) $one['cid'])); ?>#comment-<?php echo (int) $one['coid']; ?>">
          <b><?php echo Html::e((string) $one['author']); ?></b>：<?php echo Html::e(\qiqi77\Support\Str::clip(\qiqi77\Support\Str::plain((string) $one['text']), 36)); ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <!-- 友情链接 -->
  <?php if ($links): ?>
  <section class="qq-module">
    <h4 class="qq-module__title">友情链接</h4>
    <div class="qq-friendlinks">
      <?php foreach ($links as $one): ?>
      <a href="<?php echo Html::e((string) $one['url']); ?>" target="_blank" rel="noopener nofollow"><?php echo Html::e((string) $one['name']); ?></a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</aside>
