# 安装教程

从零开始，大约 5 分钟。

## 前提条件

你需要有一个**已经装好 Typecho 的站点**，并且能登录后台。

还没装 Typecho？先去 [typecho.org](https://typecho.org) 下载，按官方文档装好再来。主题不管装 Typecho 这一步。

## 第一步：确认 PHP 版本

qiqi77 用了 PHP 8 的语法，**PHP 7.4 及以下会直接白屏**。

### 怎么看版本

在站点根目录临时建个文件 `phpinfo.php`：

```php
<?php phpinfo();
```

浏览器打开 `你的域名/phpinfo.php`，第一行就是版本号。

**看完记得删掉这个文件**，它会把服务器环境全部暴露出去。

### 怎么改版本

- **宝塔面板**：网站 → 设置 → PHP 版本 → 选 8.0 或 8.1 或 8.2 → 切换
- **虚拟主机**：控制面板里找「PHP 版本」，或联系主机商

推荐 **PHP 8.1**，兼容性最稳。

## 第二步：下载主题

### 方式 A：直接下载（推荐新手）

1. 打开 <https://github.com/chenqicong528/qiqi77-typecho>
2. 点绿色的 **Code** 按钮 → **Download ZIP**
3. 解压，得到一个 `qiqi77-typecho-main` 文件夹
4. **把文件夹改名为 `qiqi77`**

> 改名这一步不能省。主题的配置存在 `theme:qiqi77` 这个键下，文件夹名不对就读不到配置。

### 方式 B：命令行

```bash
cd /www/wwwroot/你的站点/usr/themes
git clone https://github.com/chenqicong528/qiqi77-typecho.git qiqi77
```

用 git 的好处是以后更新只要 `git pull`。

## 第三步：上传到服务器

主题要放在这个位置：

```
你的站点根目录/
└── usr/
    └── themes/
        └── qiqi77/          ← 最终应该是这个路径
            ├── functions.php
            ├── index.php
            ├── post.php
            └── ...
```

### 宝塔面板操作

1. 文件 → 进入 `你的站点/usr/themes`
2. 点「上传」，选择 `qiqi77` 整个文件夹（或先传 ZIP 再解压）
3. 确认解压后是 `usr/themes/qiqi77/functions.php`，**不要**变成 `usr/themes/qiqi77/qiqi77/functions.php`

多套一层是最常见的错误。如果套了，把里层的内容移出来，或者删掉重传。

### 命令行操作

```bash
# 假设站点在 /www/wwwroot/blog
cd /www/wwwroot/blog/usr/themes
unzip ~/qiqi77.zip          # 解压出来的文件夹名要是 qiqi77

# 检查结构对不对
ls qiqi77/functions.php     # 有输出就对了
```

### 顺手改个权限

```bash
chown -R www:www /www/wwwroot/blog/usr/themes/qiqi77
chmod -R 755 /www/wwwroot/blog/usr/themes/qiqi77
```

`www` 是宝塔默认的运行用户，不是宝塔的话按实际情况改（可能是 `nginx`、`apache`）。

## 第四步：启用主题

1. 登录后台：`你的域名/admin/`
2. 顶部菜单 **控制面板** → **外观**
3. 在「可以使用的外观」里找到 **qiqi77**
4. 点 **启用**

启用后首页立刻变成卡片布局。

如果列表里看不到 qiqi77，说明目录放错了——回到第三步检查 `usr/themes/qiqi77/functions.php` 是否存在。

## 第五步：基础配置

**外观 → 设置外观**，进去是一个两栏页面：左边分组导航，右边设置项。

第一次只需要改这几项：

| 在哪一组 | 改什么 |
|---|---|
| 基础信息 | 站点副标题、头像地址、站点图标 |
| 外观与配色 | 主题色（点色块选） |
| 文案微调 | 页面水印文字（不想显示就留空） |

改完滚到页面底部，点 **保存设置**。

> 每次改动都会在底部提示「改完记得保存」。没保存就离开，浏览器会拦一下。

## 第六步：检查

打开首页，应该能看到：

- 顶部半透明导航栏 + 搜索框
- 卡片式文章列表（大图卡和文字卡混排）
- 右侧个人信息卡 + 站点数据

### 样式没生效？

**先强刷**：`Ctrl + F5`（Mac 是 `Cmd + Shift + R`）。

还不行的话：

1. 后台 → 设置外观 → 性能与高级 → **资源来源** 改成「本地」
2. 检查 `usr/themes/qiqi77/assets/css/app.css` 能不能在浏览器里直接打开：
   `你的域名/usr/themes/qiqi77/assets/css/app.css`
3. 打不开说明文件没传全，重新上传

## 第七步（可选）：配置收录

主题自带 sitemap，不需要额外插件。

### sitemap 地址

```
https://你的域名/sitemap.xml
```

这个文件是动态生成的，发新文章自动更新，不用手动重建。

### 提交到搜索引擎

| 平台 | 提交入口 | 填什么 |
|---|---|---|
| Google | [Search Console](https://search.google.com/search-console/sitemaps) → 站点地图 | `sitemap.xml` |
| Bing | [Webmaster](https://www.bing.com/webmasters/sitemaps) | `https://你的域名/sitemap.xml` |
| 百度 | 搜索资源平台 → 普通收录 | 用后台的「百度准入密钥」 |
| 360 | 站长平台 → sitemap | `https://你的域名/sitemap.xml` |
| 搜狗 | 站长平台 → sitemap | 同上 |

百度需要先在[搜索资源平台](https://ziyuan.baidu.com)拿到准入密钥（16 位），填到后台「收录与统计 → 百度准入密钥」里，然后回到设置页顶部点「推最近 10 篇」。

### robots.txt

建议在站点根目录放一个 `robots.txt`：

```
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /action/
Disallow: /search/

Sitemap: https://你的域名/sitemap.xml
```

## 升级主题

### git 装的

```bash
cd /www/wwwroot/blog/usr/themes/qiqi77
git pull
```

### 手动装的

1. **先备份配置**：后台设置页顶部有「配置备份 → 备份当前」
2. 下载新版，解压覆盖（**不要删掉整个文件夹**，配置存在数据库里，删文件夹不丢配置，但保险起见先备份）
3. 后台 → 外观，重新点一次启用

升级不会丢配置，配置一直存在数据库的 `theme:qiqi77` 键里。

## 卸载

1. 后台 → 外观 → 启用别的主题
2. 删除 `usr/themes/qiqi77` 文件夹
3. （可选）清理数据库里残留的配置：

```sql
DELETE FROM typecho_options WHERE name LIKE 'theme:qiqi77%';
```

把 `typecho_` 换成你自己的表前缀。

---

装好了？接着看 [设置说明](settings.md)，把主题调成你想要的样子。
