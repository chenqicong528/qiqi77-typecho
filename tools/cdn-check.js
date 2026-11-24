#!/usr/bin/env node
/**
 * 用途：检查 assets/ 下每个文件在 CDN 上是否真能取到
 * 用法：node tools/cdn-check.js [CDN前缀] [版本号] [资源目录名]
 *   例：node tools/cdn-check.js https://unpkg.com/qiqi77-typecho 1.0.0
 *       node tools/cdn-check.js https://cdn.jsdelivr.net/npm/qiqi77-typecho 1.0.0
 * 依赖：Node 18+（全局 fetch）
 * 退出码：0 全通过 / 1 有失败
 */
'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');

/* ---------- 参数 ---------- */

const spec = JSON.parse(fs.readFileSync(path.join(ROOT, 'package.json'), 'utf8'));
const name = spec.name || 'qiqi77-typecho';

const cdn = (process.argv[2] || `https://unpkg.com/${name}`).replace(/\/+$/, '');
const ver = (process.argv[3] || spec.version || '').replace(/^@/, '');
const dir = (process.argv[4] || 'assets').replace(/^\/+|\/+$/g, '');

if (!ver) {
  console.error('缺少版本号：npm 上还没发过，或 package.json 没有 version');
  process.exit(1);
}

const baseDir = path.join(ROOT, dir);
if (!fs.existsSync(baseDir)) {
  console.error(`找不到 ${dir} 目录：${baseDir}`);
  process.exit(1);
}

/* ---------- 收集文件 ---------- */

function walk(d) {
  const out = [];
  for (const ent of fs.readdirSync(d, { withFileTypes: true })) {
    const p = path.join(d, ent.name);
    if (ent.isDirectory()) {
      out.push(...walk(p));
    } else if (ent.isFile()) {
      // 备份文件不会进 npm 包，别拿它当失败
      if (/\.(bak|orig|rej|swp|tmp)$/i.test(ent.name) || /\.bak-/i.test(ent.name)) continue;
      out.push({
        rel: path.relative(baseDir, p).split(path.sep).join('/'),
        size: fs.statSync(p).size,
      });
    }
  }
  return out.sort((a, b) => a.rel.localeCompare(b.rel));
}

const files = walk(baseDir);
if (files.length === 0) {
  console.error(`${dir} 目录是空的`);
  process.exit(1);
}

console.log(`包   : ${name}`);
console.log(`版本 : ${ver}`);
console.log(`文件 : ${files.length} 个`);
console.log(`目标 : ${cdn}@${ver}/${dir}`);
console.log('');

/* ---------- 并发 HEAD ---------- */

const CONCURRENCY = 8;
const results = [];

async function head(url) {
  const ac = new AbortController();
  const timer = setTimeout(() => ac.abort(), 20000);
  try {
    const res = await fetch(url, { method: 'HEAD', signal: ac.signal, redirect: 'follow' });
    return { code: res.status, type: res.headers.get('content-type') || '' };
  } catch (e) {
    return { code: 0, type: '', err: e.name === 'AbortError' ? '超时' : String(e.message || e) };
  } finally {
    clearTimeout(timer);
  }
}

async function run() {
  let cursor = 0;

  async function worker() {
    while (cursor < files.length) {
      const f = files[cursor++];
      const url = `${cdn}@${ver}/${dir}/${f.rel}`;
      results.push({ ...f, url, ...(await head(url)) });
    }
  }

  await Promise.all(Array.from({ length: Math.min(CONCURRENCY, files.length) }, worker));
}

function human(n) {
  return n >= 1048576 ? `${(n / 1048576).toFixed(1)} MB` : `${(n / 1024).toFixed(1)} KB`;
}

(async () => {
  await run();
  results.sort((a, b) => a.rel.localeCompare(b.rel));

  let ok = 0;
  const bad = [];

  for (const r of results) {
    if (r.code === 200) {
      ok++;
      console.log(`[200] ${r.rel.padEnd(38)} ${human(r.size).padStart(9)}`);
    } else {
      bad.push(r);
      const why = r.code === 0 ? `连接失败 ${r.err}` : `HTTP ${r.code}`;
      console.log(`[!!!] ${r.rel.padEnd(38)} ${human(r.size).padStart(9)}  <- ${why}`);
    }
  }

  console.log(`\n通过 ${ok} / ${results.length}`);

  if (ok === results.length) {
    console.log('全部命中。前台可以切到 CDN 了。');
    process.exit(0);
  }

  console.log('\n排查顺序：');
  console.log(`  1. 包在不在：curl -sI ${cdn}/package.json | head -1`);
  console.log('  2. 版本对不对：npm view ' + name + ' versions');
  console.log('  3. 文件进没进包：npm pack --dry-run 看清单里有没有 ' + dir + '/');
  console.log('  4. 刚发完就查：npm 到 CDN 有 2-5 分钟同步延迟，等一会再跑');

  // 类型不对也算问题：css 被当 text/plain，浏览器会拒绝解析
  const wrongType = bad.filter((r) => r.code === 200);
  if (wrongType.length) {
    console.log('\n以下文件状态码正常但内容类型可疑：');
    for (const r of wrongType) console.log(`  ${r.rel} -> ${r.type || '(空)'}`);
  }

  process.exit(1);
})();
