<?php
/**
 * 用途：外壳收尾（页脚 / 抽屉 / 脚本 / 注入位）
 * 依赖：Options、Site\Nav
 * 被调用：所有模板
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Bootstrap;
use qiqi77\View;
use qiqi77\Options;
use qiqi77\Support\Html;

/** @var \Widget\Archive $archive */
$archive = $data['archive'];
$year = date('Y');
$siteUrl = rtrim((string) \Helper::options()->siteUrl, '/');
$siteName = trim((string) \Helper::options()->title);

$footerText = trim((string) Options::get('qq_footer_note', ''));
$copyRaw = trim((string) Options::get('qq_copyright_text', ''));
/* 不传 fallback：让 SCHEMA 的默认地址生效 */


/* 备案号里的数字串，用来拼查询链接 */
$digits = static function (string $str): string {
    return preg_match('/\d+/', $str, $m) ? $m[0] : '';
};

/* 版权行：主题署名固定为柒葱 */
$copyHtml = 'Copyright © 2017–' . $year . ' 柒葱 · All Rights Reserved';
?>
  <footer class="qq-footer">
    <div class="qq-footer__inner">
      <?php if ($footerText !== ''): ?>
      <p class="qq-footer__text"><?php echo nl2br(Html::e($footerText)); ?></p>
      <?php endif; ?>


      
      
      <p class="qq-footer__meta">
        <span>Theme <a href="https://github.com/chenqicong528/qiqi77-typecho" target="_blank" rel="noopener">qiqi77</a> by <a href="https://qicong.zqhope.cn/" target="_blank" rel="noopener">柒葱</a></span>
        <i class="qq-footer__dot"></i>
        <span>Powered by <a href="https://www.typecho.org/" target="_blank" rel="noopener">Typecho</a></span>
      </p>

      <p class="qq-footer__copy"><?php echo $copyHtml; ?></p>
      <p class="qq-footer__ver">当前版本 V <?php echo Html::e(defined('QIQI77_VERSION') ? QIQI77_VERSION : '1.0.0'); ?></p>
    </div>
  </footer>
</div><!-- /.qq-shell -->
<!-- 暗水印：正常看不出，截图/调亮后可见 -->
<div class="qq-watermark" aria-hidden="true"></div>

<script id="qq-credit" nonce="<?php echo defined('QQ_NONCE') ? QQ_NONCE : ''; ?>">
/* 控制台版权信息：F12 打开即可见 */
(function () {
  if (!window.console || !console.log) return;
  var brand = 'font-size:16px;font-weight:700;color:#ff4081;padding:2px 0';
  var plain = 'font-size:12px;color:#888';
  var link  = 'font-size:12px;color:#4ea1ff;text-decoration:underline';
  try {
    console.log('%c柒葱 %c·  qiqi77', brand + ';font-size:20px', plain);
    console.log('%c关注我：' + <?php echo json_encode($siteUrl); ?>, link);
    console.log('%c© 2017–' + new Date().getFullYear() + ' 柒葱 · All Rights Reserved', plain);
    console.log('%c本站主题为定制开发，请勿倒卖或去除署名。', 'font-size:12px;color:#ff8a5c');
  } catch (e) {}
})();
</script>

<?php View::show('chrome/drawer', ['archive' => $archive]); ?>

<div class="qq-qr" id="qq-qr" aria-hidden="true">
  <div class="qq-qr__box">
    <div id="qq-qr-canvas"></div>
    <p>扫一扫，在手机上打开</p>
    <button type="button" class="qq-btn qq-btn--ghost" data-qq-qr-close>关闭</button>
  </div>
</div>

<script nonce="<?php echo defined('QQ_NONCE') ? QQ_NONCE : ''; ?>">
window.__QQ__ = {
  site: <?php echo json_encode(rtrim((string) \Helper::options()->siteUrl, '/')); ?>,
  pjaxContainer: <?php echo json_encode((string) (Options::get('qq_pjax_scope', '') ?: '#qq-shell')); ?>,
  qr: <?php echo Options::flag('qq_qr_enable', true) ? 'true' : 'false'; ?>,
  comments: <?php echo $archive->is('single') ? 'true' : 'false'; ?>,
  reading: <?php echo Options::flag('qq_reading_pct', true) ? 'true' : 'false'; ?>
};
</script>
<script src="<?php echo Html::e(Options::asset('js/nprogress.min.js')); ?>"></script>
<script src="<?php echo Html::e(Options::asset('js/pjax.min.js')); ?>"></script>
<script src="<?php echo Html::e(Options::asset('js/lazyload.min.js')); ?>"></script>
<script src="<?php echo Html::e(Options::asset('js/tocbot.min.js')); ?>"></script>
<script src="<?php echo Html::e(Options::asset('js/highlight.min.js')); ?>"></script>
<script src="<?php echo Html::e(Options::asset('js/clipboard.min.js')); ?>"></script>
<script src="<?php echo Html::e(Options::asset('js/fancybox.umd.min.js')); ?>"></script>
<script src="<?php echo Html::e(Options::asset('js/qrcode.min.js')); ?>"></script>
<script src="<?php echo Html::e(Options::asset('js/app.js')); ?>"></script>
<?php if ($archive->is('single')): ?>
<script src="<?php echo Html::e(Options::asset('js/comment.js')); ?>"></script>
<?php endif; ?>
<?php echo (string) Options::get('qq_inject_body', ''); ?>
<?php echo (string) Options::get('qq_analytics_code', ''); ?>
<?php
/* Microsoft Clarity：录屏 / 热图 / 停留时长 */
$clarityId = trim((string) Options::get('qq_clarity_id', ''));
if ($clarityId !== ''):
?>
<script type="text/javascript" nonce="<?php echo defined('QQ_NONCE') ? QQ_NONCE : ''; ?>">
(function(c,l,a,r,i,t,y){
  c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
  t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
  y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
})(window, document, "clarity", "script", <?php echo json_encode($clarityId); ?>);
</script>
<?php endif; ?>
<?php echo (string) Options::get('qq_inject_js', ''); ?>
</body>
</html>
