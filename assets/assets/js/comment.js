/**
 * comment.js — 评论提交（AJAX）与 QQ 号补全
 */
(function () {
  'use strict';

  var form = document.getElementById('qq-comment-form');
  if (!form) return;

  var author = form.querySelector('[name="author"]');
  var mail = form.querySelector('[name="mail"]');

  /* QQ 号：点「获取」再拉头像 / 昵称 / 邮箱，避免边打字边发请求 */
  var preview = form.querySelector("[data-qq-avatar]");
  var qqBox = form.querySelector("[name=\"qq\"]");
  var qqGo = form.querySelector("[data-qq-fetch]");

  function avatarPreview(qq) {
    if (!preview) return;
    if (!/^[1-9]\d{4,10}$/.test(qq)) {
      preview.classList.remove("is-on");
      return;
    }
    var img = preview.querySelector("img");
    if (img) {
      preview.classList.add("is-on");
      img.onerror = function () { preview.classList.remove("is-on"); };
      img.src = "https://q1.qlogo.cn/g?b=qq&nk=" + qq + "&s=100";
    }
  }

  /* 写入后闪一下，让人看见值确实变了 */
  function flash(el) {
    if (!el) return;
    el.classList.remove("is-flash");
    void el.offsetWidth;
    el.classList.add("is-flash");
    setTimeout(function () { el.classList.remove("is-flash"); }, 900);
  }

  function fetchQQ() {
    if (!qqBox) return;
    var qq = qqBox.value.replace(/\D/g, "").slice(0, 11);
    qqBox.value = qq;

    if (!/^[1-9]\d{4,10}$/.test(qq)) {
      if (qqGo) qqGo.textContent = "号码有误";
      setTimeout(function () { if (qqGo) qqGo.textContent = "获取"; }, 1500);
      return;
    }

    avatarPreview(qq);

    /* 主动点「获取」= 按这个号重填，直接覆盖旧值 */
    if (mail) {
      mail.value = qq + "@qq.com";
      flash(mail);
    }
    if (author) {
      author.value = qq;
      flash(author);
    }

    if (qqGo) {
      qqGo.textContent = "已填入";
      setTimeout(function () { if (qqGo) qqGo.textContent = "获取"; }, 1600);
    }
  }  if (qqBox) {
    qqBox.addEventListener("input", function () {
      qqBox.value = qqBox.value.replace(/\D/g, "").slice(0, 11);
    });
    // 回车等同于点获取
    qqBox.addEventListener("keydown", function (e) {
      if (e.key === "Enter") { e.preventDefault(); fetchQQ(); }
    });
    // 回填过的号码，打开时直接把头像亮出来
    if (qqBox.value.trim()) avatarPreview(qqBox.value.trim());
  }
  if (qqGo) qqGo.addEventListener("click", fetchQQ);

  form.addEventListener('submit', function (e) {
    if (!window.fetch) return;
    e.preventDefault();

    var btn = form.querySelector('button[type="submit"]');
    var text = form.querySelector('[name="text"]');
    if (text && !text.value.trim()) {
      text.focus();
      return;
    }
    if (btn) { btn.disabled = true; btn.textContent = '发送中…'; }

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return fetch(location.pathname + location.search, { credentials: 'same-origin' });
      })
      .then(function (res) { return res.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var fresh = doc.getElementById('comments');
        var mine = document.getElementById('comments');
        if (fresh && mine) {
          mine.innerHTML = fresh.innerHTML;
          mine.scrollIntoView({ behavior: 'smooth', block: 'start' });
          document.dispatchEvent(new CustomEvent('qq:comments-updated'));
        } else {
          location.reload();
        }
      })
      .catch(function () {
        form.submit();
      })
      .finally(function () {
        if (btn) { btn.disabled = false; btn.textContent = '发表评论'; }
      });
  });
})();
