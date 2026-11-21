<?php
/**
 * 用途：独立页面
 * 依赖：View、Parser
 * 被调用：Typecho
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Content\Parser;
use qiqi77\Support\Html;
use qiqi77\Support\Str;
use qiqi77\View;

$parser = new Parser();
$title = Html::capture(fn () => $this->title());
$html = $parser->run((string) $this->content);

$ctx = [
    'type'  => 'page',
    'title' => $title,
    'desc'  => Str::clip(Str::plain((string) $this->content), 140),
    'url'   => (string) $this->permalink,
];

View::show('chrome/head', ['archive' => $this, 'ctx' => $ctx]);
?>
<div class="qq-stage">
  <main class="qq-main qq-main--wide">
    <article class="qq-post qq-post--page">
      <header class="qq-post__head">
        <h1 class="qq-post__title"><?php echo Html::e($title); ?></h1>
        <p class="qq-post__meta">
          <time datetime="<?php echo Html::e(Str::iso((int) $this->created)); ?>"><?php echo Html::e(date('Y-m-d', (int) $this->created)); ?></time>
        </p>
      </header>
      <div class="qq-post__body"><?php echo $html; ?></div>
    </article>
  </main>
</div>
<?php View::show('chrome/tail', ['archive' => $this]); ?>
