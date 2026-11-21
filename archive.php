<?php
/**
 * 用途：归档 / 分类 / 标签 / 搜索 / 作者页
 * 依赖：View、Card、Bootstrap
 * 被调用：Typecho
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Bootstrap;
use qiqi77\Content\Card;
use qiqi77\Support\Html;
use qiqi77\View;

$metrics = Bootstrap::get('metrics');

if ($this->is('category')) {
    $label = '分类';
    $title = (string) $this->getArchiveTitle();
} elseif ($this->is('tag')) {
    $label = '标签';
    $title = (string) $this->getArchiveTitle();
} elseif ($this->is('search')) {
    $label = '搜索';
    $kw = trim((string) $this->request->get('s'));
    if ($kw === '') {
        $p = (string) parse_url((string) $this->request->getRequestUrl(), PHP_URL_PATH);
        if (preg_match('#/search/(.+?)/?$#u', $p, $sm)) {
            $kw = urldecode($sm[1]);
        }
    }
    $title = '搜索：' . $kw;
} elseif ($this->is('author')) {
    $label = '作者';
    $title = (string) $this->getArchiveTitle();
} elseif ($this->is('archive')) {
    $label = '归档';
    $year = (string) $this->request->get('year');
    $month = (string) $this->request->get('month');
    if ($year !== '' && $month !== '') {
        $title = $year . ' 年 ' . (int) $month . ' 月';
    } else {
        $title = (string) $this->getArchiveTitle() ?: '全部文章';
    }
} else {
    $label = '归档';
    $title = '全部文章';
}

/* 分页页补个页码，别和首页标题一模一样 */
$__page = (int) $this->request->get('page');
if ($__page > 1) {
    $title .= '（第 ' . $__page . ' 页）';
}

$ctx = [
    'type'  => 'archive',
    'title' => $title,
    'desc'  => $label . ' · ' . (string) \Helper::options()->title,
    'url'   => rtrim((string) \Helper::options()->siteUrl, '/') . (string) parse_url((string) $this->request->getRequestUrl(), PHP_URL_PATH),
];

View::show('chrome/head', ['archive' => $this, 'ctx' => $ctx]);
?>

<div class="qq-stage">
  <main class="qq-main">
    <?php if ($this->is('search')): ?>
    <?php View::show('partials/search-hero', ['archive' => $this]); ?>
    <?php else: ?>
    <div class="qq-archive-head">
      <span class="qq-archive-head__label"><?php echo Html::e($label); ?></span>
      <h1 class="qq-archive-head__title"><?php echo Html::e($title); ?></h1>
      <p class="qq-archive-head__count">共 <?php echo (int) $this->getTotal(); ?> 篇</p>
    </div>
    <?php endif; ?>

    <div class="qq-grid" data-qq-grid>
      <?php if ($this->have()): ?>
      <?php
      $parser = new \qiqi77\Content\Parser();
      while ($this->next()):
          $type = (string) $this->fields->articleType ?: 'article';
          $card = Card::make([
              'cid'      => (int) $this->cid,
              'title'    => Html::capture(fn () => $this->title()),
              'text'     => (string) $this->text,
              'created'  => (int) $this->created,
              'comments' => (int) $this->commentsNum,
              'order'    => (int) $this->order,
              'url'      => (string) $this->permalink,
              'cover'    => (string) ($this->fields->wzimg ?: $this->fields->thumb),
              'type'     => $type,
              'remark'   => (string) $this->fields->remark,
          ], $metrics);
          if ($type === 'daily') {
              $card = Card::withBody($card, $parser->run((string) $this->content));
          }
          echo View::render('partials/card', ['card' => $card]);
      endwhile;
      ?>
      <?php elseif (!$this->is('search')): ?>
      <p class="qq-empty">这里什么都没有，换个词试试？</p>
      <?php endif; ?>
    </div>

    <?php if ($this->have()): ?>
    <nav class="qq-more" data-qq-more>
      <?php $this->pageLink('加载更多', 'next'); ?>
      <span class="qq-more__end">没有更多了</span>
    </nav>
    <?php endif; ?>
  </main>
  <?php View::show('partials/sidebar', ['archive' => $this]); ?>
</div>
<?php View::show('chrome/tail', ['archive' => $this]); ?>
