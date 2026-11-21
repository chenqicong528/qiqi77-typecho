<?php
/**
 * 用途：首页——置顶优先 + 大图卡片流 + 无限加载
 * 依赖：View、Card、Bootstrap、Options
 * 被调用：Typecho
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Bootstrap;
use qiqi77\Content\Card;
use qiqi77\Options;
use qiqi77\Support\Html;
use qiqi77\View;
use Typecho\Db;

$metrics = Bootstrap::get('metrics');
$parser = new \qiqi77\Content\Parser();

/* 置顶：旧配置 sticky 存的是 cid 列表 */
$stickyRaw = trim((string) Options::get('qq_pinned_notice', ''));
$stickyIds = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $stickyRaw) ?: [])));
$stickyPosts = [];
if ($stickyIds) {
    try {
        $rows = Db::get()->fetchAll(Db::get()->select('cid', 'title', 'text', 'created', 'commentsNum', 'order')
            ->from('table.contents')
            ->where('type = ?', 'post')->where('status = ?', 'publish')
            ->where('cid IN (' . implode(',', array_map('intval', $stickyIds)) . ')'));
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['cid']] = $row;
        }
        /* 置顶卡也要带上自己的封面 */
        $covers = [];
        try {
            $rows2 = Db::get()->fetchAll(Db::get()->select('cid', 'str_value')->from('table.fields')
                ->where('name = ?', 'wzimg')
                ->where('cid IN (' . implode(',', array_map('intval', $stickyIds)) . ')'));
            foreach ($rows2 as $r2) {
                $covers[(int) $r2['cid']] = (string) $r2['str_value'];
            }
        } catch (\Throwable $e) {
            $covers = [];
        }
        foreach ($stickyIds as $cid) {
            if (isset($map[$cid])) {
                $map[$cid]['cover'] = $covers[$cid] ?? '';
                $stickyPosts[] = $map[$cid];
            }
        }
    } catch (\Throwable $e) {
        $stickyPosts = [];
    }
}

$ctx = [
    'type'  => 'home',
    'title' => (int) $this->request->get('page') > 1
        ? '第 ' . (int) $this->request->get('page') . ' 页'
        : (string) \Helper::options()->title,
    'desc'  => (string) Options::get('qq_description', ''),
    'url'   => rtrim((string) \Helper::options()->siteUrl, '/') . '/',
];

View::show('chrome/head', ['archive' => $this, 'ctx' => $ctx]);
?>
<div class="qq-stage">
  <main class="qq-main">
    <div class="qq-grid qq-grid--home" data-qq-grid>
      <?php foreach ($stickyPosts as $row): ?>
      <?php
        echo View::render('partials/card', ['card' => Card::make([
            'cid'      => (int) $row['cid'],
            'title'    => (string) $row['title'],
            'text'     => (string) $row['text'],
            'created'  => (int) $row['created'],
            'comments' => (int) $row['commentsNum'],
            'order'    => 1,
            'url'      => '/' . (int) $row['cid'],
            'cover'    => (string) ($row['cover'] ?? ''),
            'type'     => 'article',
            'remark'   => '',
        ], $metrics)]);
      ?>
      <?php endforeach; ?>

      <?php
      while ($this->next()):
          if ($stickyIds && in_array((int) $this->cid, $stickyIds, true)) {
              continue;
          }
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
    </div>

    <nav class="qq-more" data-qq-more>
      <?php $this->pageLink('加载更多', 'next'); ?>
      <span class="qq-more__end">没有更多了</span>
    </nav>
  </main>
  <?php View::show('partials/sidebar', ['archive' => $this]); ?>
</div>
<?php View::show('chrome/tail', ['archive' => $this]); ?>
