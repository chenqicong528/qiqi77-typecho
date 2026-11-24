# 静态资源走 CDN（unpkg / jsDelivr）

主题的 `assets/` 目录一共 4.2 MB。想让它从公共 CDN 出，先说清楚一件事：

**unpkg 没有上传入口。** 它是个只读代理，请求 `https://unpkg.com/<包名>@<版本>/<路径>` 时，它去 npm registry 拉那个包的 tarball 解出来给你。所以「上 unpkg」= 「发一个 npm 包」，没有第二条路。

---

## 一、三条路怎么选

| 方式 | 地址形态 | 要不要发 npm | 国内速度 | 适合 |
|---|---|---|---|---|
| **unpkg** | `https://unpkg.com/qiqi77-typecho@1.0.0/assets/...` | 要 | 慢，时常连不上 | 海外站、给国外用户看。**代码路径已通，但本机没有 npm 账号，未实际发布验证** |
| **jsDelivr（npm）** | `https://cdn.jsdelivr.net/npm/qiqi77-typecho@1.0.0/assets/...` | 要 | 一般，有国内节点 | 兼顾国内外 |
| **jsDelivr（GitHub）** | `https://cdn.jsdelivr.net/gh/chenqicong528/qiqi77-typecho@v1.0.0/assets/...` | **不要** | 一般 | **已实测 118/118 可用**，不想折腾 npm 就走这条 |
| **BootCDN** | 各家库的官方地址 | 不要 | 快 | 只换第三方库，主题自己的 css/js 还走本地 |

主题后台「静态资源来源」下拉里已经内置了三档：`本地文件` / `BootCDN` / `npm CDN（unpkg / jsDelivr）`。第三档就是这份文档讲的东西。

> 如果你的访客主要在国内，直说：**unpkg 不是好选择**。想要 CDN 又不想被墙，用 BootCDN 那档（它只替换 mdui/pjax/highlight 这些公共库），或者自己套一层国内对象存储 + 回源。

---

## 二、发 npm 包（一次性配置）

### 1. 确认包名没被占

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://registry.npmjs.org/qiqi77-typecho
# 404 = 没被占，可以用
# 200 = 已有人用，改名，比如 @你的用户名/qiqi77-typecho
```

`qiqi77-typecho` 目前是空号，可以直接用。

### 2. 登录 npm

```bash
npm login
# 交互式输入用户名 / 密码 / 邮箱 / 一次性验证码
npm whoami          # 回显用户名即登录成功
```

没账号先去 <https://www.npmjs.com/signup> 注册。开了两步验证的话，`npm publish` 会要 OTP。

### 3. 仓库根的 `package.json`

已经放在仓库里了，内容就是这段：

```json
{
  "name": "qiqi77-typecho",
  "version": "1.0.0",
  "description": "柒葱 Typecho 主题的静态资源包",
  "license": "MIT",
  "files": ["assets/"],
  "unpkg": "assets/css/app.css",
  "jsdelivr": "assets/css/app.css",
  "publishConfig": { "access": "public", "registry": "https://registry.npmjs.org/" }
}
```

关键只有一行：`"files": ["assets/"]`。npm 只会打包 `assets/` 下的东西，PHP 源码不进 tarball。想确认打包内容，跑：

```bash
npm pack --dry-run
```

会列出即将上传的文件清单和总大小。**这一步必看**，防止把 `config.inc.php`、数据库、备份包一起传上去。

### 4. 发布

```bash
cd release/qiqi77-typecho
npm publish
```

第一次发布会占掉包名。之后每次改版本号才能再发。

---

## 三、每次更新资源的流程

npm 不允许覆盖已发布版本。改完 `assets/` 之后：

```bash
# 1. 升版本号（补丁号 +1 就够，除非有大改）
npm version patch --no-git-tag-version     # 1.0.0 → 1.0.1

# 2. 看一眼文件清单
npm pack --dry-run

# 3. 发
npm publish

# 4. 回主题后台，把「CDN 版本号」改成 1.0.1
```

`npm version patch` 会同时改 `package.json` 的 `version`。**主题后台的版本号必须跟着改**，两边不一致就会 404 —— 这是最常见的翻车点。

也可以写成脚本，仓库里带了：`tools/publish-npm.sh`。

```bash
bash tools/publish-npm.sh patch    # 或 minor / major
```

脚本干的事：检查登录 → 检查工作区干净 → 升版本 → `npm pack --dry-run` → `npm publish` → 打印新版本的 CDN 地址。任何一步失败就退出，不会带着脏状态发布。

---

## 四、URL 规则

```
https://unpkg.com/qiqi77-typecho@1.0.0/assets/css/app.css
                    └──── 包名 ────┘└版本┘└── 包内路径 ──┘
```

版本位可以写：

| 写法 | 含义 | 用不用 |
|---|---|---|
| `@1.0.0` | 精确版本 | **用这个** |
| `@^1.0.0` | 1.x 最新 | 能用，但发新版后缓存要等 |
| `@latest` | 最新 | 别用，缓存不可控 |
| 不写 | 等同 latest | 别用 |

unpkg 的响应头带 `Cache-Control: public, max-age=31536000`（一年）。它认版本号做失效，所以**只有换版本号才能让访客拿到新文件**。这就是上面为什么强调后台版本号要跟着改。

jsDelivr 把 `unpkg.com` 换成 `cdn.jsdelivr.net/npm` 即可，路径结构完全一样：

```
https://cdn.jsdelivr.net/npm/qiqi77-typecho@1.0.0/assets/css/app.css
```

不用发 npm 的话，jsDelivr 还能直接读 GitHub tag：

```
https://cdn.jsdelivr.net/gh/chenqicong528/qiqi77-typecho@v1.0.0/assets/css/app.css
```

注意这形式走的是 GitHub 源，tag 名带 `v`，和 npm 的 `1.0.0` 不一样。

---

## 五、在主题里打开

后台 → 设置外观 → 高级：

- **静态资源来源**：选 `npm CDN（unpkg / jsDelivr）`
- **CDN 包地址**：默认 `https://unpkg.com/qiqi77-typecho`，换 jsDelivr 就填 `https://cdn.jsdelivr.net/npm/qiqi77-typecho`
- **CDN 版本号**：填 npm 上实际存在的版本

保存后刷新前台，`view-source` 里应该看到：

```html
<link rel="stylesheet" href="https://unpkg.com/qiqi77-typecho@1.0.0/assets/css/app.css">
<script src="https://unpkg.com/qiqi77-typecho@1.0.0/assets/js/app.js"></script>
```

代码侧对应 `src/Options.php` 的 `Options::asset()`。两个约束写死在里面：

1. **本地没有的文件不替换成 CDN 地址。** 先 `is_file()` 判断，本地不存在就照旧输出本地路径 —— CDN 上更不可能有，换过去只是多一条 404。
2. **CDN 地址不加 `?v=mtime`。** 本地模式加这个是靠文件修改时间破缓存；CDN 靠版本号，加查询串只会让缓存命中率变差。

---

## 六、验证

仓库带了检查脚本，对每个资源发一次 HEAD：

```bash
node tools/cdn-check.js https://unpkg.com/qiqi77-typecho 1.0.0
```

输出：

```
包   : qiqi77-typecho
版本 : 1.0.0
文件 : 118 个
目标 : https://unpkg.com/qiqi77-typecho@1.0.0/assets

[200] css/app.css                            12.4 KB
[200] js/app.js                              31.0 KB
[!!!] js/foo.js                                —     <- 有问题
...
通过 117 / 118
```

有 404 就说明：版本号填错、文件没进 `files`、或者 npm 上还没发这一版。脚本退出码非 0，可以直接串进 CI。

第三个参数可以换目录，用来测别的路径：

```bash
node tools/cdn-check.js https://cdn.jsdelivr.net/gh/chenqicong528/qiqi77-typecho v1.0.0 docs/img
```

手工抽查也够：

```bash
curl -sI https://unpkg.com/qiqi77-typecho@1.0.0/assets/css/app.css | head -5
```

要看到 `HTTP/2 200` 和 `content-type: text/css`。

浏览器侧打开 DevTools → Network，筛 `unpkg`，确认：
- 所有请求都是 200，没有回落
- `content-type` 对得上（css 不能被当成 `text/plain`，否则浏览器拒绝解析）
- 控制台没有 CORS 报错。unpkg 和 jsDelivr 都返回 `access-control-allow-origin: *`，字体跨域不会有问题

### 已经实测过的结果

用 jsDelivr 读 GitHub tag 的形式（对应表格里第三行，不需要发 npm）：

```bash
node tools/cdn-check.js https://cdn.jsdelivr.net/gh/chenqicong528/qiqi77-typecho v1.0.0 assets
```

```
通过 118 / 118
全部命中。
```

再在一台真实的 Typecho 1.3 + PHP 8.3 站点上切到 CDN 模式跑了一遍：

| 检查项 | 结果 |
|---|---|
| `Options.php` 语法（`token_get_all` + `TOKEN_PARSE`） | 通过 |
| 首页 HTTP 状态 | 200，无 500 |
| 生成的资源地址 | 12 个，格式全部正确 |
| 12 个 CDN 地址逐个请求 | 12 / 12 = 200 |
| `app.css` 本地与 CDN 的 md5 | 一致（73587 字节） |
| 无头浏览器渲染 | 卡片、封面、毛玻璃、字体全部正常，无样式丢失 |
| 切回 `local` 后 | 0 个 CDN 引用，回到本地路径 |

**这一步暴露了一个必须知道的事**：第一次截图时页面是裸 HTML —— 因为无头浏览器没走代理，直连 jsDelivr 拿不到 CSS。换成走代理后立刻正常。这说明 CDN 挂了页面就完全没样式，不会降级。国内访客遇到这种情况的概率不低，这也是为什么默认值必须是 `local`。

顺带清掉了一个被 git 跟踪的备份文件 `assets/css/sidebar-old.css.bak`。它没被任何 CSS 引用，留在仓库里只会被 `files: ["assets/"]` 一起打进 npm 包。

---

## 七、CSS 里的相对路径

`app.css` 里如果有 `url(../images/xxx.png)`，从 CDN 加载后浏览器会去解析 `https://unpkg.com/qiqi77-typecho@1.0.0/assets/images/xxx.png` —— 只要 `assets/images/` 也在包里就没问题，不用改 CSS。

当前 `assets/css/` 里唯一的 `url()` 是 `iconfont.min.css` 的 base64 内嵌 woff2，不依赖外部文件。`assets/fonts/` 下 61 个 KaTeX 字体目前没有任何 CSS 引用，属于历史遗留，跟着包一起发但不会被请求。

---

## 八、踩坑清单

**发布报 `403 Forbidden`**
包名被占，或账号没权限。查 `npm view qiqi77-typecho`，有输出说明被占了。

**发布报 `402 Payment Required`**
想发到 scope 下的私有包但没付费。`publishConfig.access` 已经是 `public`，正常不会遇到。

**unpkg 一直 404，npm 上明明有**
unpkg 有几分钟同步延迟，等 2–5 分钟。还不行就看是不是版本号写错——`https://unpkg.com/qiqi77-typecho/package.json` 能打开就说明包在，问题出在版本或路径。

**前台样式全掉，DevTools 一片红**
先看 Network 里失败的 URL。八成是版本号对不上，或者 `assets/` 没进 `files` 字段。

**改完资源，访客还是看到旧样式**
版本号没升。CDN 缓存一年，唯一的失效手段就是换版本号。

**国内打开慢**
这不是 bug。unpkg 的 CDN 节点在境外。切回 `BootCDN` 那档，或者用国内对象存储自己托管一份。
