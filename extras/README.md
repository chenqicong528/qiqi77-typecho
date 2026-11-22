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
