<?php
/**
 * Template Name: 在线访客
 * 用途：实时在线列表 + 今日数据 + 历史峰值
 * 依赖：View、Bootstrap、Site\Online、Comment\Ua
 * 被调用：Typecho 自定义页面模板
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use qiqi77\Bootstrap;
use qiqi77\Comment\Ua;
use qiqi77\Support\Html;
use qiqi77\View;

$online = Bootstrap::get('online');
$live = $online->live();
$today = $online->today();
$visitors = $online->visitors(20);
$peaks = $online->peaks(5);

/* 设备分布：电脑 / 手机 / 平板 / 爬虫 */
$devices = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0, 'bot' => 0];
foreach ($visitors as $v) {
    $dev = Ua::parse((string) $v['agent'])['device']['type'];
    if (isset($devices[$dev])) {
        $devices[$dev]++;
    }
}

$ctx = ['type' => 'page', 'title' => '在线访客', 'desc' => '实时在线情况', 'url' => (string) $this->permalink];
View::show('chrome/head', ['archive' => $this, 'ctx' => $ctx]);
?>
<div class="qq-stage">
  <main class="qq-main qq-main--wide">
    <div class="qq-archive-head">
      <span class="qq-archive-head__label">实时</span>
      <h1 class="qq-archive-head__title">在线访客</h1>
      <p class="qq-archive-head__count"><span class="qq-dot"></span>当前 <?php echo (int) $live['total']; ?> 人在线</p>
    </div>

    <section class="qq-metrics">
      <div class="qq-metrics__item"><b><?php echo (int) $live['total']; ?></b><span>在线总数</span></div>
      <div class="qq-metrics__item"><b><?php echo (int) $live['friend']; ?></b><span>好友</span></div>
      <div class="qq-metrics__item"><b><?php echo (int) $live['guest']; ?></b><span>访客</span></div>
      <div class="qq-metrics__item"><b><?php echo (int) $today['uniques']; ?></b><span>今日独立</span></div>
      <div class="qq-metrics__item"><b><?php echo (int) $today['views']; ?></b><span>今日浏览</span></div>
      <div class="qq-metrics__item"><b><?php echo (int) $today['peak']; ?></b><span>今日峰值</span></div>
    </section>

    <section class="qq-panel">
      <h2 class="qq-panel__title">正在浏览</h2>
      <div class="qq-devices">
        <span><i class="iconfont icon-windows"></i>电脑 <b><?php echo (int) $devices['desktop']; ?></b></span>
        <span><i class="iconfont icon-android"></i>手机 <b><?php echo (int) $devices['mobile']; ?></b></span>
        <span><i class="iconfont icon-ios"></i>平板 <b><?php echo (int) $devices['tablet']; ?></b></span>
        <span><i class="iconfont icon-os"></i>爬虫 <b><?php echo (int) $devices['bot']; ?></b></span>
      </div>

      <ul class="qq-live">
        <?php foreach ($visitors as $v): ?>
        <?php
          $ua = Ua::parse((string) $v['agent']);
          $role = (string) ($v['role'] ?? 'guest');
          $roleName = ['blogger' => '博主', 'friend' => '朋友'][$role] ?? '游客';
          $stay = max(0, (int) $v['last_seen'] - (int) $v['first_seen']);
        ?>
        <li class="qq-live__row">
          <span class="qq-live__role is-<?php echo Html::e($role); ?>"><?php echo Html::e($roleName); ?></span>

          <span class="qq-live__cell">
            <i class="iconfont <?php echo Html::e($ua['browser']['icon']); ?>"></i>
            <em><?php echo Html::e($ua['browser']['name']); ?></em>
          </span>

          <span class="qq-live__cell">
            <i class="iconfont <?php echo Html::e($ua['os']['icon']); ?>"></i>
            <em><?php echo Html::e($ua['os']['name']); ?></em>
          </span>

          <span class="qq-live__cell qq-live__cell--dev">
            <i class="iconfont <?php echo Html::e($ua['device']['icon']); ?>"></i>
            <em><?php echo Html::e($ua['device']['name']); ?></em>
          </span>

          <span class="qq-live__path" title="<?php echo Html::e((string) ($v['path'] ?: '/')); ?>"><?php echo Html::e((string) ($v['path'] ?: '/')); ?></span>
          <span class="qq-live__stay"><?php echo $stay >= 60 ? floor($stay / 60) . ' 分' : max(1, $stay) . ' 秒'; ?></span>
        </li>
        <?php endforeach; ?>
        <?php if (!$visitors): ?>
        <li class="qq-live__empty">
          <p>现在还没有人在线</p>
          <span>安静得只剩下你和我</span>
        </li>
        <?php endif; ?>
    </section>

    <section class="qq-panel">
      <h2 class="qq-panel__title">历史峰值</h2>
      <ol class="qq-rank">
        <?php foreach ($peaks as $i => $row): ?>
        <li>
          <em><?php echo $i + 1; ?></em>
          <span><?php echo Html::e((string) $row['day']); ?></span>
          <span><?php echo (int) $row['peak']; ?> 人同时在线</span>
        </li>
        <?php endforeach; ?>
        <?php if (!$peaks): ?><p class="qq-empty">暂无记录</p><?php endif; ?>
      </ol>
    </section>
  </main>
</div>
<?php View::show('chrome/tail', ['archive' => $this]); ?>
