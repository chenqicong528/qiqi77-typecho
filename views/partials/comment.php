<?php
/**
 * 用途：单条评论
 * 依赖：Support\Html
 * 被调用：post.php
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Support\Html;

/** @var array $c */
$c = $data['c'];
$depth = (int) $c['depth'];
?>
<li class="qq-c<?php echo $depth > 0 ? ' qq-c--reply' : ''; ?><?php echo $c['isOwner'] ? ' is-owner' : ''; ?>"
    id="comment-<?php echo (int) $c['id']; ?>"
    data-qq-comment="<?php echo (int) $c['id']; ?>"
    data-depth="<?php echo $depth; ?>">
  <img class="qq-c__avatar" src="<?php echo Html::e($c['avatar']); ?>" alt="" loading="lazy" referrerpolicy="no-referrer" width="40" height="40">
  <div class="qq-c__main">
    <p class="qq-c__head">
      <?php if ($c['url'] !== ''): ?>
      <a class="qq-c__name" href="<?php echo Html::e($c['url']); ?>" target="_blank" rel="nofollow noopener"><?php echo Html::e($c['author']); ?></a>
      <?php else: ?>
      <span class="qq-c__name"><?php echo Html::e($c['author']); ?></span>
      <?php endif; ?>
      <?php if ($c['isOwner']): ?><span class="qq-c__tag qq-c__tag--owner">博主</span><?php endif; ?>
      <?php if ($c['isFriend']): ?><span class="qq-c__tag">好友</span><?php endif; ?>
      <span class="qq-c__floor">#<?php echo (int) $c['floor']; ?></span>
      <time datetime="<?php echo Html::e($c['iso']); ?>"><?php echo Html::e($c['when']); ?></time>
      <span class="qq-c__ua" title="<?php echo Html::e($c['ua']['os']['name'] . ' · ' . $c['ua']['browser']['name']); ?>">
        <i class="iconfont <?php echo Html::e($c['ua']['os']['icon']); ?>"></i>
        <i class="iconfont <?php echo Html::e($c['ua']['browser']['icon']); ?>"></i>
      </span>
    </p>
    <div class="qq-c__text"><?php echo $c['html']; ?></div>
    <button type="button" class="qq-c__reply" data-qq-reply="<?php echo (int) $c['id']; ?>" data-qq-author="<?php echo Html::e($c['author']); ?>">回复</button>
  </div>
</li>
