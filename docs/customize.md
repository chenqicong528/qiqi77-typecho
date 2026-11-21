# 进阶定制

后台能改的 80 项之外，想再动点东西就看这里。

## 样式从哪来

`assets/css/app.css` 是打包产物，**不要直接改**——下次更新会被覆盖。

真正的源文件在 `assets/css/` 下，按顺序拼接成 app.css：

```
tokens.css   设计变量（颜色/圆角/间距/字体）
base.css     重置与基础排版
layout.css   外壳、顶栏、栅格、背景
cards.css    首页卡片
sidebar.css  侧栏与页脚
post.css     文章页
pages.css    归档、搜索、404、友链
comment.css  评论区
dark.css     深色模式
```

改完源文件要重新拼：

```bash
cd assets/css
cat tokens.css base.css layout.css cards.css sidebar.css \
    post.css pages.css comment.css dark.css > app.css
```

也可以不碰文件，直接在后台「性能与高级 → 自定义 CSS」里覆盖：

```css
/* 卡片加个描边 */
.qq-card { box-shadow: inset 0 0 0 1px rgba(255,255,255,.5); }

/* 顶栏高度（和后台的设置等效，但更细） */
:root { --qq-topbar-h: 64px; }
```

## 设计变量

`tokens.css` 里的 `:root` 定义了一整套变量，主题里所有尺寸和颜色都引用它们：

```css
:root {
  --qq-c-accent: #ff4081;      /* 主题色 */
  --qq-c-bg: #f4f6fa;          /* 页面底色 */
  --qq-c-surface: #ffffff;     /* 卡片底色 */
  --qq-c-text: #1c1f26;        /* 正文 */
  --qq-c-text-3: #96a0b0;      /* 次要文字 */
  --qq-c-line: rgba(16,20,30,.08);  /* 分割线 */
  --qq-r-md: 14px;             /* 中圆角 */
  --qq-r-lg: 22px;             /* 大圆角 */
  --qq-gap: 18px;              /* 卡片间距 */
  --qq-w: 1180px;              /* 内容区宽度 */
  --qq-fs-body: 15.5px;        /* 正文字号 */
  --qq-lh-body: 1.92;          /* 正文行高 */
}
```

后台「外观与配色」「细节微调」两组本质上就是在改这些变量，值会以 `<style id="qq-vars">` 的形式输出在 `<head>` 里，优先级高于 tokens.css。

**所以想精确控制，用后台；想加新样式，用自定义 CSS。**

## 加一个自定义页面模板

Typecho 支持给独立页面选模板。比如加一个「关于我」的特殊布局：

1. 在主题根目录建 `tpl-about.php`
2. 文件开头写上模板名：

```php
<?php
/**
 * 关于我
 * @package qiqi77
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>
<?php $this->need('views/chrome/head.php'); ?>

<main class="qq-main">
  <article class="qq-post">
    <h1 class="qq-post__title"><?php $this->title(); ?></h1>
    <div class="qq-post__body"><?php $this->content(); ?></div>
  </article>
</main>

<?php $this->need('views/chrome/tail.php'); ?>
```

3. 后台新建页面时，右侧「自定义模板」里就能选到「关于我」

现有的 `tpl-archive.php`、`tpl-links.php`、`tpl-online.php`、`tpl-stats.php` 都是这么写的，可以照抄。

## 改文章卡片的样子

卡片的渲染在 `views/partials/card.php`，数据来自 `src/Content/Card.php`。

想加个字段（比如显示字数）：

```php
// src/Content/Card.php 里给数组加一项
$card['words'] = mb_strlen(strip_tags($content));
```

```php
// views/partials/card.php 里输出
<span class="qq-card__meta"><?php echo $card['words']; ?> 字</span>
```

## 换字体

**方式一（推荐）**：后台「外观与配色」

- 正文字体：填字体名，如 `"LXGW WenKai"`
- 字体文件：填 woff2 的直链

**方式二**：自定义 CSS

```css
@font-face {
  font-family: "MyFont";
  src: url("https://your-cdn.com/font.woff2") format("woff2");
  font-display: swap;
}
body { font-family: "MyFont", system-ui, sans-serif; }
```

字体文件放自己服务器或图床，别盗链别人的。

## 缩略图与封面

文章封面走 Typecho 的自定义字段 `wzimg`（兼容旧数据）。

后台「首页与卡片 → 封面来源」控制没有封面时怎么补：

- `none`：不补，显示主题色渐变
- `random`：从「随机图地址」里随机取

随机图地址支持 `{rand}` 占位符：

```
https://api.example.com/rand?n={rand}
https://cdn.example.com/img/1.jpg
https://cdn.example.com/img/2.jpg
```

每行一条，`{rand}` 每次刷新换成随机数字。

## 二次开发注意

- **不要改 `src/Options.php` 里的 SCHEMA 键名**，键名对应数据库里的配置，改了等于清空用户设置
- 加新配置项：在 SCHEMA 里加一行，指定分组（`basic`/`look`/...），后台会自动出现
- 加新样式文件：记得同步改拼接命令，否则线上不生效
- 主题目录名必须是 `qiqi77`，配置键是 `theme:qiqi77`

## 想提 PR

欢迎。提交前请确认：

1. PHP 文件能通过 `php -l` 检查
2. 没引入新的第三方依赖
3. 改了 CSS 记得重新生成 `app.css`
4. 新配置项在 `docs/settings.md` 里补一行说明
