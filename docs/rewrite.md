# 伪静态配置

Typecho 漂亮链接（`/archives/1/`、`/category/default/`、`/online.html`）依赖两件事：

1. **Web 服务器把不存在的路径交给 `index.php`**
2. **PHP-FPM 收到 `PATH_INFO`**（`index.php/archives/1/` 里 `/archives/1/` 那一段）

只做第 1 步、漏掉第 2 步，典型症状是：

```
/index.php/archives/1/  →  200，但显示的是首页
其他路径                →  404
```

这不是主题的问题，主题输出的链接由你的路由表决定。

---

## 后台开关

先确认 Typecho 侧已经打开：

```
控制台 → 设置 → 基本设置 → 使用地址重写功能 → 开启 → 保存设置
```

保存时如果提示地址重写失败，先关掉，把下面的服务器配置写好再开。

---

## Nginx（根目录安装）

站点配置里加入：

```nginx
location / {
    index index.php;
    try_files $uri $uri/ /index.php$uri;
}

# 关键：正则要能匹配 index.php/xxx 这种带 PATH_INFO 的路径
location ~ \.php(/|$) {
    fastcgi_pass   unix:/tmp/php-cgi.sock;      # 按你实际的改，或 127.0.0.1:9000
    fastcgi_index  index.php;
    fastcgi_split_path_info ^(.+\.php)(/.*)$;
    fastcgi_param  PATH_INFO $fastcgi_path_info;
    fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include        fastcgi_params;
}
```

注意最后一段里的两行：

```nginx
fastcgi_split_path_info ^(.+\.php)(/.*)$;
fastcgi_param  PATH_INFO $fastcgi_path_info;
```

少了这两行，文章地址就会显示成首页。

---

## Nginx（子目录安装）

假设程序装在 `/blog/`，把所有根路径换成子目录：

```nginx
location /blog/ {
    try_files $uri $uri/ /blog/index.php$uri;
}

location ~ \.php(/|$) {
    fastcgi_pass   unix:/tmp/php-cgi.sock;
    fastcgi_index  index.php;
    fastcgi_split_path_info ^(.+\.php)(/.*)$;
    fastcgi_param  PATH_INFO $fastcgi_path_info;
    fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include        fastcgi_params;
}
```

多级目录同理，例如 `/123/32/23/`：

```nginx
location /123/32/23/ {
    try_files $uri $uri/ /123/32/23/index.php$uri;
}
```

---

## 宝塔面板

1. 站点 → **设置 → 配置文件**，确认 PHP 那段 `location` 是这种形式：

   ```nginx
   location ~ \.php(.*)$ {
   ```

   如果是 `location ~ \.php$`，改掉并补上：

   ```nginx
   fastcgi_split_path_info ^(.+\.php)(/.*)$;
   fastcgi_param PATH_INFO $fastcgi_path_info;
   ```

2. 站点 → **设置 → 伪静态**，按安装位置选一段粘贴。

   **根目录：**

   ```nginx
   location / {
       try_files $uri $uri/ /index.php$uri;
   }
   ```

   **`/blog/` 子目录：**

   ```nginx
   location /blog/ {
       try_files $uri $uri/ /blog/index.php$uri;
   }
   ```

3. 保存后重载 Nginx，再到 Typecho 后台打开「使用地址重写功能」。

---

## Apache

需要 `mod_rewrite`。站点根或子目录放 `.htaccess`：

**根目录：**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>
```

**`/blog/` 子目录**（把文件放在 `blog/.htaccess`）：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /blog/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>
```

同时确认 Apache 的 PHP 模块允许 PATH_INFO（`AcceptPathInfo On`）。

---

## 验证

配置重载后，逐项确认：

| 检查 | 期望 |
|---|---|
| `/archives/1/` | 显示文章，标题是文章标题，不是首页 |
| `/category/default/` | 分类列表 |
| `/online.html`（主题自动建的页面） | 「在线访客」 |
| `/stats.html` | 「站点统计」 |
| 一个不存在的地址 | 主题的 404 页面，HTTP 状态码 404 |

如果 `/archives/1/` 返回 200 但标题还是站点名，说明 PATH_INFO 仍然没传进去，回到上面 Nginx 那段检查 `fastcgi_split_path_info`。

---

## 与主题的关系

主题不写死任何路径：

- CSS / JS：按 `SCRIPT_NAME` 自动加安装目录，`/blog/usr/themes/qiqi77/...`
- 文章 / 分类 / 页面：从 Typecho 路由表读 `format` 生成
- 根安装返回 `/`，二级返回 `/a111/`，多级返回 `/123/32/23/`

所以装在哪一层都不用改主题，只要服务器把路径交对。
