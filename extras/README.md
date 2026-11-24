# 附加文件

这些文件**不放进主题目录**，需要手动复制到站点根（与 `index.php` 同级）。

---

## sitemap.php

生成 `/sitemap.php`，输出全站文章、独立页面、分类的 XML 地图。

**安装**

1. 把 `extras/sitemap.php` 复制到站点根目录
2. 浏览器访问 `https://你的域名/sitemap.php`，看到 XML 就成功

**让 /sitemap.xml 也能访问**

Nginx：

```nginx
location = /sitemap.xml {
    rewrite ^ /sitemap.php last;
}
```

Apache（`.htaccess`）：

```apache
RewriteRule ^sitemap\.xml$ /sitemap.php [L]
```

**说明**

- 地址由 Typecho 路由表生成，开了伪静态输出 `/archives/1/`，没开输出 `/index.php/archives/1/`
- 每次访问实时查询，文章量大时可加缓存（见 README 的待办）
- 不需要它也能正常用主题，只是搜索引擎抓取会慢一些

---

## nginx.conf

Nginx 伪静态样例。贴到站点配置的 `server { }` 里，装在子目录时把路径换成 `/blog/`。

Windows 版 Nginx **不支持** `unix:/tmp/php-cgi.sock`，`fastcgi_pass` 必须写 `127.0.0.1:9000`（端口以面板为准）。

## .htaccess

Apache 伪静态样例。放到站点根目录；子目录安装时把 `RewriteBase /` 改成 `RewriteBase /blog/`。

需要开启 `mod_rewrite`，并允许 PATH_INFO（样例里已带 `AcceptPathInfo On`）。

---

## online.php

在线访客的**写入端**。主题只负责显示，没有这个文件「在线访客」页面永远是 0。

**安装**

1. 把 `extras/online.php` 复制到站点根目录（与 `index.php` 同级）
2. 在数据库里建 4 张表（前缀按你的改成 `typecho_`）

```sql
CREATE TABLE IF NOT EXISTS `typecho_online` (
  `sid` char(32) NOT NULL,
  `role` varchar(10) NOT NULL DEFAULT 'guest',
  `ip` varchar(45) DEFAULT NULL,
  `agent` varchar(255) DEFAULT '',
  `path` varchar(120) DEFAULT NULL,
  `first_seen` int(10) unsigned NOT NULL DEFAULT 0,
  `last_seen` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`sid`),
  KEY `last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `typecho_online_daily` (
  `day` date NOT NULL,
  `uniques` int(10) unsigned NOT NULL DEFAULT 0,
  `views` int(10) unsigned NOT NULL DEFAULT 0,
  `peak` int(10) unsigned NOT NULL DEFAULT 0,
  `peak_at` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `typecho_online_visitor` (
  `day` date NOT NULL,
  `sid` char(32) NOT NULL,
  PRIMARY KEY (`day`, `sid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `typecho_online_meta` (
  `k` varchar(32) NOT NULL,
  `v` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

3. 打开任意页面看控制台，确认 `/online.php?do=ping` 返回 `{"ok":1,...}`

**说明**

- 只支持 MySQL / MariaDB（用到了 `ON DUPLICATE KEY`、`DATE_SUB`）
- 身份判定：带 Typecho 登录 cookie → 博主；邮箱/网址命中友链 → 朋友；其余 → 游客
- 心跳 45 秒，90 秒无心跳视为离线；关闭页面会发 `do=leave` 立即下线
