<?php
/**
 * 用途：单张卡片——大图铺底、文字压底、悬停浮起（对齐旧站 index-card）
 * 依赖：Support\Html
 * 被调用：index.php、archive.php
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Support\Html;

/** @var array $card */
$c = $data['card'];
$type = $c['type'] ?? 'article';
?>
<?php if ($type === 'daily'): ?>
<article class="qq-card-daily" data-qq-card>
  <div class="qq-daily__icon" aria-hidden="true">💬</div>
  <div class="qq-daily__main">
    <p class="qq-daily__time">
      <time datetime="<?php echo Html::e($c['iso']); ?>"><?php echo Html::e($c['when']); ?></time>
      <?php if ($c['pinned']): ?><span class="qq-pin">[置顶]</span><?php endif; ?>
    </p>
    <div class="qq-daily__body"><?php echo $c['body'] ?? Html::e($c['excerpt']); ?></div>
    <p class="qq-daily__foot">
      <a href="<?php echo Html::e($c['url']); ?>">查看详情</a>
      <span><?php echo (int) $c['views']; ?> 浏览</span>
      <?php if ($c['showMeta']): ?><span><?php echo (int) $c['comments']; ?> 评论</span><?php endif; ?>
    </p>
  </div>
</article>
<?php elseif ($type === 'normal'): ?>
<a class="qq-card-text<?php echo $c['pinned'] ? ' is-pinned' : ''; ?>" href="<?php echo Html::e($c['url']); ?>" data-qq-card>
  <h2 class="qq-card-text__title"><?php if ($c['pinned']): ?><span class="qq-pin">[置顶]</span><?php endif; ?><?php echo Html::e($c['title']); ?></h2>
  <p class="qq-card-text__meta">
    <time datetime="<?php echo Html::e($c['iso']); ?>"><?php echo Html::e($c['when']); ?></time>
    <span><?php echo (int) $c['views']; ?> 浏览</span>
    <?php if ($c['showMeta']): ?><span><?php echo (int) $c['comments']; ?> 评论</span><?php endif; ?>
    <span>约 <?php echo (int) $c['minutes']; ?> 分钟</span>
  </p>
  <?php if ($c['remark'] !== ''): ?>
  <span class="qq-card-text__chip"><?php echo Html::e($c['remark']); ?></span>
  <?php endif; ?>
</a>
<?php else: ?>
<a class="qq-card<?php echo $c['pinned'] ? ' is-pinned' : ''; ?><?php echo $c['cover'] === '' ? ' qq-card--plain' : ''; ?>" href="<?php echo Html::e($c['url']); ?>"
   data-qq-card<?php if ($c['cover'] !== ''): ?> style="background-image:url('<?php echo Html::e($c['cover']); ?>')"<?php endif; ?>>
  <span class="qq-card__veil" aria-hidden="true"></span>
  <div class="qq-card__text">
    <h2 class="qq-card__title"><?php if ($c['pinned']): ?><span class="qq-pin">[置顶]</span><?php endif; ?><?php echo Html::e($c['title']); ?></h2>
    <p class="qq-card__meta">
      <time datetime="<?php echo Html::e($c['iso']); ?>"><?php echo Html::e($c['when']); ?></time>
      <?php if ($c['showMeta']): ?><span><?php echo (int) $c['comments']; ?> 条评论</span><?php endif; ?>
      <span><?php echo (int) $c['views']; ?> 浏览</span>
      <span>约 <?php echo (int) $c['minutes']; ?> 分钟</span>
    </p>
    <?php if ($c['excerpt'] !== ''): ?>
    <p class="qq-card__excerpt"><?php echo Html::e($c['excerpt']); ?></p>
    <?php endif; ?>
  </div>
</a>
<?php endif; ?>
