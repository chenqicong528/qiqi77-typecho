#!/usr/bin/env bash
# 用途：把 assets/ 发到 npm，供 unpkg / jsDelivr 引用
# 用法：bash tools/publish-npm.sh [patch|minor|major] [-y]
#   -y  跳过确认，直接发
# 依赖：node、npm，且已 npm login
set -euo pipefail

cd "$(dirname "$0")/.."

LEVEL="${1:-patch}"
AUTO_NO="${2:-}"

case "$LEVEL" in
    patch|minor|major) ;;
    *) echo "版本级别只能是 patch / minor / major，收到：$LEVEL"; exit 1 ;;
esac

command -v node >/dev/null 2>&1 || { echo "找不到 node"; exit 1; }
command -v npm  >/dev/null 2>&1 || { echo "找不到 npm"; exit 1; }

# 1. 登录状态
if ! npm whoami >/dev/null 2>&1; then
    echo "npm 未登录。先跑：npm login"
    exit 1
fi
USER_NAME="$(npm whoami)"
echo "npm 账号：$USER_NAME"

# 2. 包名归属（已存在时必须是自己的）
NAME="$(node -p "require('./package.json').name")"
OWNER="$(npm view "$NAME" maintainers --json 2>/dev/null | node -e "
let s='';process.stdin.on('data',d=>s+=d).on('end',()=>{
  try{const a=JSON.parse(s);console.log(Array.isArray(a)?a.map(x=>x.name).join(','):'');}
  catch(e){console.log('');}
});" || true)"

if [ -n "$OWNER" ]; then
    case ",$OWNER," in
        *",$USER_NAME,"*) echo "包 $NAME 已存在，维护者包含你，继续" ;;
        *) echo "包 $NAME 已被 $OWNER 占用，换名字"; exit 1 ;;
    esac
else
    echo "包 $NAME 尚未发布，本次将占用包名"
fi

# 3. 工作区状态：脏的话发出去的内容和 git 里的对不上
if [ -d .git ] && [ -n "$(git status --porcelain)" ]; then
    echo "工作区有未提交改动，npm 包会包含它们。建议先 commit。"
    if [ "$AUTO_NO" != "-y" ]; then
        printf "继续？[y/N] "
        read -r ans
        [ "$ans" = "y" ] || [ "$ans" = "Y" ] || exit 1
    fi
fi

# 4. 升版本
npm version "$LEVEL" --no-git-tag-version >/dev/null
VER="$(node -p "require('./package.json').version")"
echo "新版本：$VER"

# 5. 打包含内容预览
echo
npm pack --dry-run 2>&1 | tail -n 20
echo

if [ "$AUTO_NO" != "-y" ]; then
    printf "确认发布 %s@%s？[y/N] " "$NAME" "$VER"
    read -r ans
    [ "$ans" = "y" ] || [ "$ans" = "Y" ] || {
        echo "已取消，版本号回滚到上一版"
        npm version "$(node -p "
const v=require('./package.json').version.split('.').map(Number);
if('$LEVEL'==='major')v[0]-=1;else if('$LEVEL'==='minor')v[1]-=1;else v[2]-=1;
console.log(v.join('.'));")" --no-git-tag-version >/dev/null
        exit 1
    }
fi

# 6. 发布
npm publish

# 7. 收尾提示
echo
echo "已发布 $NAME@$VER"
echo "  unpkg     https://unpkg.com/$NAME@$VER/assets/css/app.css"
echo "  jsDelivr  https://cdn.jsdelivr.net/npm/$NAME@$VER/assets/css/app.css"
echo
echo "下一步："
echo "  1. 后台 → 设置外观 → 高级 → CDN 版本号 改成 $VER"
echo "  2. 等 2-5 分钟同步，跑 php tools/cdn-check.php https://unpkg.com/$NAME $VER"
echo "  3. git add package.json && git commit -m 'chore: npm $VER'"
