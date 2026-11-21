<?php
/**
 * 用途：文章页——正文、目录、版权、打赏、上下篇、评论
 * 依赖：View、Parser、Thread、Metrics、Seo
 * 被调用：Typecho
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Bootstrap;
use qiqi77\Content\Parser;
use qiqi77\Options;
use qiqi77\Site\Seo;
use qiqi77\Support\Html;
use qiqi77\Support\Str;
use qiqi77\View;

$metrics = Bootstrap::get('metrics');
$thread  = Bootstrap::get('thread');
$parser  = new Parser();

$cid     = (int) $this->cid;
$title   = Html::capture(fn () => $this->title());
$content = (string) $this->content;
$html    = $parser->run($content);
$minutes = $metrics->minutes($content);
$metrics->touch($cid);

$cover = Str::cover((string) ($this->fields->wzimg ?: $this->fields->thumb), $content, $cid);
$images = [];
preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $mm);
if (!empty($mm[1])) {
    $images = $mm[1];
}
$heroImage = $cover !== '' ? $cover : ($images[0] ?? '');
$desc = Str::clip(Str::plain($content), 140);
$url = (string) $this->permalink;

$catalogMode = trim((string) $this->fields->catalog);
if ($catalogMode === '') {
    $catalogMode = (string) Options::get('qq_toc_fallback', 'auto');
}
$headingCount = preg_match_all('/<h[23][^>]*>/i', $html);
$showToc = $catalogMode === 'true' || ($catalogMode !== 'off' && $headingCount >= 3);

$cp = max(1, (int) ($this->request->get('cp') ?: 1));
/* 关闭评论的文章也要展示历史评论，只是不给发 */
$comments = $thread->build($cid, (int) $this->authorId);
$stats = $thread->stats($comments);
$pageSize = max(5, (int) Options::get('qq_comment_page', 20));
$pages = max(1, (int) ceil($stats['total'] / $pageSize));
$cp = min($cp, $pages);
$viewComments = array_slice($comments, ($cp - 1) * $pageSize, $pageSize);

/* 打赏码：地址在 Options::SCHEMA 里，后台可改 */
$rewardW = trim((string) Options::get('qq_reward_wechat'));
$rewardA = trim((string) Options::get('qq_reward_alipay'));
$rewardQ = trim((string) Options::get('qq_reward_qq'));

$ctx = [
    'type'      => 'post',
    'title'     => $title,
    'desc'      => $desc,
    'url'       => $url,
    'image'     => $heroImage,
    'published' => Str::iso((int) $this->created),
    'updated'   => Str::iso((int) $this->modified),
];

View::show('chrome/head', ['archive' => $this, 'ctx' => $ctx]);
?>
<div class="qq-stage">
  <main class="qq-main">
    <article class="qq-post" data-qq-post="<?php echo $cid; ?>">
      <header class="qq-post__head<?php echo $heroImage !== '' ? ' qq-post__head--split' : ''; ?>">
        <div class="qq-post__headtext">
          <?php if ((int) $this->order > 0): ?><span class="qq-post__pin">置顶</span><?php endif; ?>
          <h1 class="qq-post__title"><?php echo Html::e($title); ?></h1>
          <?php if ((string) $this->fields->subtitle !== ''): ?>
          <p class="qq-post__sub"><?php echo Html::e((string) $this->fields->subtitle); ?></p>
          <?php endif; ?>
          <p class="qq-post__meta">
            <time datetime="<?php echo Html::e(Str::iso((int) $this->created)); ?>"><?php echo Html::e(date('Y-m-d', (int) $this->created)); ?></time>
            <span><?php echo $metrics->views($cid); ?> 次浏览</span>
            <span>约 <?php echo $minutes; ?> 分钟</span>
            <span class="qq-post__cats">分类：<?php echo trim(Html::capture(fn () => $this->category(' / '))); ?></span>
          </p>
        </div>
        <?php if ($heroImage !== ''): ?>
        <figure class="qq-post__thumb">
          <img src="<?php echo Html::e($heroImage); ?>" alt="<?php echo Html::e($title); ?>" fetchpriority="high" decoding="async">
        </figure>
        <?php endif; ?>
      </header>

      <div class="qq-post__layout<?php echo $showToc ? '' : ' qq-post__layout--single'; ?>">
        <div class="qq-post__body" id="qq-post-body" data-qq-body>
          <?php echo $html; ?>
        </div>
        <?php if ($showToc): ?>
        <aside class="qq-post__toc" data-qq-toc aria-label="文章目录">
          <p class="qq-post__toc-title">目录</p>
          <div class="js-toc"></div>
        </aside>
        <?php endif; ?>
      </div>

      <div class="qq-copyright">
        <p><b>作者</b><?php echo Html::e((string) \Helper::options()->title); ?></p>
        <p><b>链接</b><a href="<?php echo Html::e($url); ?>"><?php echo Html::e($url); ?></a></p>
        <p><b>协议</b>署名-非商业性使用-相同方式共享 4.0（CC BY-NC-SA 4.0）</p>
        <p><b>声明</b>转载请注明出处，商业用途请联系作者授权。</p>
      </div>

      <?php if ($rewardW !== '' || $rewardA !== '' || $rewardQ !== ''): ?>
      <div class="qq-reward__bar">
        <button type="button" class="qq-reward__btn" data-qq-reward>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s-7-4.35-7-9a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 4.65-7 9-7 9z"/></svg>
          <span>打赏</span>
        </button>
      </div>
      <?php endif; ?>

      <nav class="qq-pager">
        <?php $prev = trim(Html::capture(fn () => $this->thePrev('上一篇：%s', '已是最早一篇'))); ?>
        <?php $next = trim(Html::capture(fn () => $this->theNext('下一篇：%s', '已是最新一篇'))); ?>
        <div class="qq-pager__item"><?php echo $prev !== '' ? $prev : '<span>已是最早一篇</span>'; ?></div>
        <div class="qq-pager__item qq-pager__item--next"><?php echo $next !== '' ? $next : '<span>已是最新一篇</span>'; ?></div>
      </nav>
    </article>

    <?php if ($comments || $this->allow('comment')): ?>
    <section class="qq-comments" id="comments">
      <h2 class="qq-comments__title">评论 <em><?php echo (int) $stats['total']; ?></em></h2>

      <?php if ($comments): ?>
      <ol class="qq-comments__list">
        <?php foreach ($viewComments as $one): ?>
        <?php View::show('partials/comment', ['c' => $one]); ?>
        <?php endforeach; ?>
      </ol>
      <?php else: ?>
      <p class="qq-comments__empty">还没有人说话，来占个沙发？</p>
      <?php endif; ?>

      <?php if ($pages > 1): ?>
      <nav class="qq-comments__pager">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a class="<?php echo $i === $cp ? 'is-on' : ''; ?>" href="?cp=<?php echo $i; ?>#comments"><?php echo $i; ?></a>
        <?php endfor; ?>
      </nav>
      <?php endif; ?>

      <?php if ($this->allow('comment')): ?>
      <form class="qq-form" id="qq-comment-form" method="post"
            action="<?php echo Html::e(Html::capture(fn () => $this->commentUrl())); ?>">
        <input type="hidden" name="parent" id="qq-parent" value="0">
        <input type="hidden" name="_" value="<?php echo Html::e($this->security->getToken($this->request->getRequestUrl())); ?>">
        <p class="qq-form__reply" id="qq-reply-tip" hidden>正在回复 <b></b><button type="button" data-qq-reply-cancel>取消</button></p>
        <textarea name="text" rows="6" required placeholder="说点什么…支持粘贴 emoji" class="qq-form__text"><?php echo Html::e((string) $this->remember('text')); ?></textarea>

        <?php if (!$this->user->hasLogin()): ?>
        <div class="qq-form__ident">
          <span class="qq-form__avatar" data-qq-avatar>
            <img alt="" width="44" height="44" loading="lazy">
          </span>
          <div class="qq-form__fields">
            <label class="qq-field qq-field--qq">
              <span>QQ 号</span>
              <span class="qq-qq">
                <input type="text" name="qq" inputmode="numeric" maxlength="11" autocomplete="off"
                       placeholder="输入后点获取" value="<?php echo Html::e((string) $this->remember('qq')); ?>">
                <button type="button" class="qq-qq__go" data-qq-fetch>获取</button>
              </span>
            </label>
            <label class="qq-field">
              <span>邮箱</span>
              <input type="email" name="mail" required placeholder="用于接收回复提醒"
                     value="<?php echo Html::e((string) $this->remember('mail')); ?>">
            </label>
            <label class="qq-field">
              <span>昵称</span>
              <input type="text" name="author" required placeholder="怎么称呼你"
                     value="<?php echo Html::e((string) $this->remember('author')); ?>">
            </label>
            <label class="qq-field qq-field--wide">
              <span>网站</span>
              <input type="url" name="url" placeholder="选填" value="<?php echo Html::e((string) $this->remember('url')); ?>">
            </label>
          </div>
        </div>
        <?php else: ?>
        <p class="qq-form__login">已登录：<?php $this->user->screenName(); ?></p>
        <?php endif; ?>

        <div class="qq-form__foot">
          <button type="submit" class="qq-btn qq-btn--accent">发表评论</button>
        </div>      </form>
      <?php else: ?>
      <p class="qq-comments__closed">本文的评论已关闭，但历史评论仍然保留。</p>
      <?php endif; ?>
    </section>
    <?php endif; ?>
  </main>
  <?php View::show('partials/sidebar', ['archive' => $this]); ?>
</div>

<?php if ($rewardW !== '' || $rewardA !== '' || $rewardQ !== ''): ?>
<div class="qq-modal" id="qq-reward-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-label="打赏">
  <div class="qq-modal__box qq-rewardbox">
    <button type="button" class="qq-reward__close" data-qq-reward-close aria-label="关闭">×</button>
    <div class="qq-reward__head">
      <b>感谢支持</b>
      <span>扫码请作者喝杯咖啡</span>
    </div>
    <div class="qq-reward__grid">
      <?php if ($rewardW !== ''): ?>
      <figure class="qq-reward__item">
        <img src="<?php echo Html::e($rewardW); ?>" alt="微信打赏" loading="lazy">
        <figcaption>微信</figcaption>
      </figure>
      <?php endif; ?>
      <?php if ($rewardA !== ''): ?>
      <figure class="qq-reward__item">
        <img src="<?php echo Html::e($rewardA); ?>" alt="支付宝打赏" loading="lazy">
        <figcaption>支付宝</figcaption>
      </figure>
      <?php endif; ?>
      <?php if ($rewardQ !== ''): ?>
      <figure class="qq-reward__item">
        <img src="<?php echo Html::e($rewardQ); ?>" alt="QQ 打赏" loading="lazy">
        <figcaption>QQ</figcaption>
      </figure>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<script type="application/ld+json"><?php echo json_encode(Seo::postLd($ctx), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<?php View::show('chrome/tail', ['archive' => $this]); ?>
