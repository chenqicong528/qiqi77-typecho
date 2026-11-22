/**
 * admin.js — 主题设置页增强
 * 依赖：无（原生 DOM）
 * 职责：两栏布局 / 分组导航 / 搜索 / 滑块 / 开关 / 取色 / 图片预览 / 恢复默认 / 社交编辑 / 脏数据提醒
 */
(function () {
  'use strict';

  var form = document.querySelector('form[action*="options-theme"]')
    || document.getElementById('typecho-options')
    || document.querySelector('#typecho-wrapper form')
    || document.querySelector('form');
  if (!form) {
    return;
  }

  var options = Array.prototype.slice.call(form.querySelectorAll('.typecho-option'));
  if (!options.length) {
    return;
  }

  /* 需要滑块化的数值字段：名 => [最小, 最大, 单位] */
  var RANGES = {
    qq_radius: [0, 28, 'px'],
    qq_card_height: [160, 400, 'px'],
    qq_card_veil: [0, 100, '%'],
    qq_backdrop_dim: [0, 100, '%'],
    qq_topbar_alpha: [0, 100, '%'],
    qq_comment_page: [5, 50, ' 条'],
    qq_font_size: [13, 18, 'px', 0.5],
    qq_line_height: [1.5, 2.3, '', 0.05],
    qq_container_w: [960, 1440, 'px', 20],
    qq_card_gap: [8, 32, 'px', 1],
    qq_sidebar_w: [240, 360, 'px', 4],
    qq_topbar_h: [52, 80, 'px', 1],
    qq_shadow_alpha: [0, 100, '%', 1],
    qq_surface_alpha: [40, 100, '%', 1]
  };

  /* 取色器字段（白名单，别用后缀匹配，会把版权文案也当成颜色） */
  var COLOR_KEYS = ['qq_accent_color', 'qq_color_bg', 'qq_color_surface', 'qq_color_text', 'qq_color_muted'];

  /* 会自动出预览图的字段 */
  var IMAGE_KEYS = /(_url|_logo|_cover|_bg|_backdrop|_qr|_avatar|_reward|_favicon)$/;

  var SOCIALS = [
    ['qq', 'QQ'],
    ['email', '邮箱'],
    ['weibo', '微博'],
    ['bilibili', '哔哩哔哩'],
    ['github', 'GitHub'],
    ['twitter', 'Twitter'],
    ['telegram', 'Telegram'],
    ['zhihu', '知乎'],
    ['douyin', '抖音'],
    ['netease-music', '网易云音乐']
  ];

  /* 默认值表 */
  var DEFAULTS = {};
  try {
    DEFAULTS = JSON.parse((document.getElementById('qq-defaults') || {}).textContent || '{}') || {};
  } catch (e) {
    DEFAULTS = {};
  }

  function fieldOf(item) {
    return item.querySelector('input[name]:not([type="hidden"]), select[name], textarea[name]');
  }

  function markDirty() {
    wrap.dataset.dirty = '1';
  }

  /* ---------- 1. 按 JSON 分组表归档 ---------- */
  var GROUP_MAP = {};
  try {
    GROUP_MAP = JSON.parse((document.getElementById('qq-groups') || {}).textContent || '{}') || {};
  } catch (e) {
    GROUP_MAP = {};
  }

  var sections = [];
  Object.keys(GROUP_MAP).forEach(function (gid) {
    var g = GROUP_MAP[gid] || {};
    sections.push({ key: gid, title: g.title || gid, desc: g.desc || '', items: [] });
  });

  var byField = {};
  Object.keys(GROUP_MAP).forEach(function (gid, i) {
    (GROUP_MAP[gid].fields || []).forEach(function (name) {
      byField[name] = sections[i];
    });
  });

  var misc = null;
  options.forEach(function (item) {
    var input = fieldOf(item);
    var name = input ? input.name : '';
    var sec = byField[name];
    if (!sec) {
      if (!misc) {
        misc = { key: 'misc', title: '其他', desc: '', items: [] };
        sections.push(misc);
      }
      sec = misc;
    }
    sec.items.push(item);
  });

  sections = sections.filter(function (sec) {
    return sec.items.length > 0;
  });

  if (!sections.length) {
    return;
  }
  /* ---------- 2. 骨架 ---------- */
  var wrap = document.createElement('div');
  wrap.className = 'qq-set';

  var nav = document.createElement('aside');
  nav.className = 'qq-set__nav';

  var search = document.createElement('input');
  search.type = 'search';
  search.className = 'qq-set__search';
  search.placeholder = '搜索设置项…';
  nav.appendChild(search);

  var body = document.createElement('div');
  body.className = 'qq-set__body';

  wrap.appendChild(nav);
  wrap.appendChild(body);
  /* 锚点可能已经被移除，用第一个还在 DOM 里的字段做插入点 */
  var firstAlive = sections.length && sections[0].items.length ? sections[0].items[0] : null;
  if (firstAlive && firstAlive.parentNode) {
    firstAlive.parentNode.insertBefore(wrap, firstAlive);
  } else {
    form.appendChild(wrap);
  }

  var tabs = [];

  sections.forEach(function (sec, i) {
    var card = document.createElement('section');
    card.className = 'qq-set__card';
    card.id = 'qq-sec-' + sec.key;

    var head = document.createElement('header');
    head.className = 'qq-set__head';
    var h3 = document.createElement('h3');
    h3.textContent = sec.title;
    head.appendChild(h3);
    if (sec.desc) {
      var p = document.createElement('p');
      p.textContent = sec.desc;
      head.appendChild(p);
    }
    card.appendChild(head);

    sec.items.forEach(function (item) {
      /* 把「标签」留在左列，其余控件全部塞进右列容器，避免 grid 自动排布乱序 */
      var li = item.querySelector('li');
      if (li) {
        var label = li.querySelector('.typecho-label') || li.querySelector('label');
        var field = document.createElement('div');
        field.className = 'qq-field';
        Array.prototype.slice.call(li.children).forEach(function (ch) {
          if (ch !== label) {
            field.appendChild(ch);
          }
        });
        li.appendChild(field);
      }
      card.appendChild(item);
      var input = fieldOf(item);
      item.dataset.qqKey = input ? input.name : '';
    });

    body.appendChild(card);

    var tab = document.createElement('button');
    tab.type = 'button';
    tab.className = 'qq-set__tab' + (i === 0 ? ' is-on' : '');
    tab.textContent = sec.title;
    var count = document.createElement('em');
    count.textContent = sec.items.length;
    tab.appendChild(count);

    tab.addEventListener('click', function () {
      var top = card.getBoundingClientRect().top + window.pageYOffset - 76;
      window.scrollTo({ top: top, behavior: 'smooth' });
    });

    nav.appendChild(tab);
    tabs.push({ tab: tab, card: card });
  });

  /* ---------- 3. 字段增强 ---------- */
  function syncSwitch(sw, input) {
    sw.classList.toggle('is-on', input.value === 'on');
  }

  function makePreview(input) {
    if (!input || !IMAGE_KEYS.test(input.name)) {
      return;
    }
    var box = document.createElement('div');
    box.className = 'qq-preview';
    var img = document.createElement('img');
    img.alt = '';
    img.hidden = true;

    var tip = document.createElement('span');
    tip.textContent = '图片预览';

    box.appendChild(img);
    box.appendChild(tip);
    input.parentNode.insertBefore(box, input.nextSibling);

    var paint = function () {
      var v = (input.value || '').trim();
      var looksLikeImage = /^(https?:)?\/\//.test(v) || /^data:image\//.test(v)
        || /^\/.+\.(png|jpe?g|gif|webp|svg)(\?|$)/i.test(v);
      if (looksLikeImage) {
        img.src = v;
        img.hidden = false;
      } else {
        img.hidden = true;
        img.removeAttribute('src');
      }
    };
    input.addEventListener('input', paint);
    input.addEventListener('change', paint);
    paint();
  }

  function makeReset(item, input) {
    var key = input.name;
    if (!Object.prototype.hasOwnProperty.call(DEFAULTS, key)) {
      return;
    }
    var def = String(DEFAULTS[key] || '');
    var foot = document.createElement('div');
    foot.className = 'qq-row__foot';

    var label = document.createElement('span');
    label.className = 'qq-row__def';
    label.textContent = def === '' ? '默认：留空' : '默认：' + def;
    label.title = def;

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'qq-row__reset';
    btn.textContent = '恢复默认';
    btn.hidden = (input.value || '') === def;
    btn.addEventListener('click', function () {
      input.value = def;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      btn.hidden = true;
      markDirty();
      var sw = item.querySelector('.qq-sw');
      if (sw) {
        syncSwitch(sw, input);
      }
    });

    foot.appendChild(label);
    foot.appendChild(btn);
    /* 必须放进右列容器，否则会被网格排到标签那列 */
    var field = item.querySelector('.qq-field');
    (field || item.querySelector('li') || item).appendChild(foot);

    input.addEventListener('input', function () {
      btn.hidden = (input.value || '') === def;
    });
  }

  options.forEach(function (item) {
    var input = fieldOf(item);
    if (!input) {
      return;
    }
    var name = input.name;

    /* 滑块 */
    if (RANGES[name] && input.tagName === 'INPUT') {
      var cfg = RANGES[name];
      var box = document.createElement('div');
      box.className = 'qq-range';

      var range = document.createElement('input');
      range.type = 'range';
      range.min = cfg[0];
      range.max = cfg[1];
      range.step = cfg[3] || 1;
      range.value = parseFloat(input.value) || parseFloat(cfg[0]) || 0;

      var out = document.createElement('span');
      out.className = 'qq-range__val';
      out.textContent = range.value + cfg[2];

      var fmt = function (v) {
        return (cfg[3] && cfg[3] < 1) ? (Math.round(parseFloat(v) * 100) / 100).toString() : v;
      };

      range.addEventListener('input', function () {
        input.value = fmt(range.value);
        out.textContent = fmt(range.value) + cfg[2];
        input.dispatchEvent(new Event('change', { bubbles: true }));
        markDirty();
      });
      input.addEventListener('input', function () {
        range.value = input.value;
        out.textContent = input.value + cfg[2];
      });

      box.appendChild(range);
      box.appendChild(out);
      input.parentNode.insertBefore(box, input);
      input.classList.add('qq-hidden-input');
    }

    /* 取色 */
    if (COLOR_KEYS.indexOf(name) > -1 && input.tagName === 'INPUT') {
      var color = document.createElement('input');
      color.type = 'color';
      color.className = 'qq-color';
      color.value = /^#[0-9a-f]{6}$/i.test(input.value) ? input.value : '#ff4081';
      color.addEventListener('input', function () {
        input.value = color.value;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        markDirty();
      });
      input.addEventListener('input', function () {
        if (/^#[0-9a-f]{6}$/i.test(input.value)) {
          color.value = input.value;
        }
      });
      var colorRow = document.createElement('div');
      colorRow.className = 'qq-color-row';
      input.parentNode.insertBefore(colorRow, input);
      colorRow.appendChild(color);
      colorRow.appendChild(input);
    }

    /* on/off 下拉 → 拨动开关 */
    if (input.tagName === 'SELECT' && input.options.length === 2) {
      var vals = [input.options[0].value, input.options[1].value];
      if (vals.indexOf('on') > -1 && vals.indexOf('off') > -1) {
        var sw = document.createElement('button');
        sw.type = 'button';
        sw.className = 'qq-sw' + (input.value === 'on' ? ' is-on' : '');
        sw.setAttribute('aria-label', '开关');
        sw.innerHTML = '<i></i>';
        sw.addEventListener('click', function () {
          input.value = input.value === 'on' ? 'off' : 'on';
          syncSwitch(sw, input);
          input.dispatchEvent(new Event('change', { bubbles: true }));
          markDirty();
        });
        input.classList.add('qq-hidden-input');
        input.parentNode.insertBefore(sw, input);
      }
    }

    makePreview(input);
    makeReset(item, input);
  });

  /* ---------- 4. 社交链接可视化 ---------- */
  var socialBox = form.querySelector('textarea[name="qq_drawer_contacts"]');
  if (socialBox) {
    var data = {};
    try {
      data = JSON.parse(socialBox.value || '{}') || {};
    } catch (e) {
      data = {};
    }

    var panel = document.createElement('div');
    panel.className = 'qq-social';

    var phead = document.createElement('div');
    phead.className = 'qq-social__head';
    phead.innerHTML = '<b>社交链接</b><span>勾选右侧平台并填账号，列表会自动生成</span>';
    panel.appendChild(phead);

    SOCIALS.forEach(function (pair) {
      var key = pair[0];
      var row = document.createElement('label');
      row.className = 'qq-social__row';

      var check = document.createElement('input');
      check.type = 'checkbox';
      check.checked = Object.prototype.hasOwnProperty.call(data, key);

      var field = document.createElement('input');
      field.type = 'text';
      field.placeholder = key === 'qq' ? 'QQ 号' : (key === 'email' ? '邮箱地址' : '用户名 / ID');
      field.value = data[key] || '';
      field.disabled = !check.checked;

      var tag = document.createElement('span');
      tag.className = 'qq-social__name';
      tag.textContent = pair[1];

      var sync = function () {
        if (check.checked && field.value.trim() !== '') {
          data[key] = field.value.trim();
        } else {
          delete data[key];
        }
        socialBox.value = JSON.stringify(data);
        markDirty();
      };

      check.addEventListener('change', function () {
        field.disabled = !check.checked;
        if (check.checked) {
          field.focus();
        }
        sync();
      });
      field.addEventListener('input', sync);

      row.appendChild(check);
      row.appendChild(tag);
      row.appendChild(field);
      panel.appendChild(row);
    });

    /* 面板放在原始行"前面"，然后把原始行整行藏掉（面板不能留在行内，否则一起被隐藏） */
    var socialRow = socialBox.closest ? socialBox.closest('.typecho-option') : null;
    if (socialRow && socialRow.parentNode) {
      socialRow.parentNode.insertBefore(panel, socialRow);
      socialRow.classList.add('qq-row--hidden');
    } else {
      socialBox.parentNode.insertBefore(panel, socialBox.nextSibling);
    }
    socialBox.classList.add('qq-hidden-input');

    var tip = document.createElement('p');
    tip.className = 'qq-social__tip';
    tip.textContent = '勾选后填写账号即可，配置会自动生成，不用手写 JSON。';
    panel.appendChild(tip);
  }

  /* ---------- 5. 搜索 ---------- */
  search.addEventListener('input', function () {
    var q = (search.value || '').trim().toLowerCase();
    var visible = 0;

    sections.forEach(function (sec, i) {
      var card = tabs[i].card;
      var shown = 0;

      sec.items.forEach(function (item) {
        var text = (item.textContent || '').toLowerCase() + ' ' + (item.dataset.qqKey || '').toLowerCase();
        var hit = q === '' || text.indexOf(q) > -1;
        item.style.display = hit ? '' : 'none';
        if (hit) {
          shown++;
        }
      });

      card.style.display = shown ? '' : 'none';
      tabs[i].tab.style.display = shown ? '' : 'none';
      visible += shown;
    });

    var empty = document.getElementById('qq-set-empty');
    if (q !== '' && visible === 0) {
      if (!empty) {
        empty = document.createElement('p');
        empty.id = 'qq-set-empty';
        empty.className = 'qq-set__head';
        empty.textContent = '没有匹配的设置项，换个关键词试试。';
        body.appendChild(empty);
      }
    } else if (empty) {
      empty.parentNode.removeChild(empty);
    }
  });

  /* ---------- 6. 滚动定位导航 ---------- */
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) {
          return;
        }
        tabs.forEach(function (t) {
          t.tab.classList.toggle('is-on', t.card === en.target);
        });
      });
    }, { rootMargin: '-84px 0px -60% 0px', threshold: 0 });
    tabs.forEach(function (t) { io.observe(t.card); });
  }

  /* ---------- 7. 保存条 ---------- */
  var submit = form.querySelector('button[type="submit"], input[type="submit"]');
  if (submit) {
    var bar = document.createElement('div');
    bar.className = 'qq-set__save';

    var hint = document.createElement('span');
    hint.className = 'qq-set__hint';
    hint.textContent = '改完记得保存。留空的项会自动回落到默认值。';

    submit.parentNode.insertBefore(bar, submit);
    bar.appendChild(submit);
    bar.appendChild(hint);
    body.appendChild(bar);
  }

  /* ---------- 8. 离开提醒 ---------- */
  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);

  window.addEventListener('beforeunload', function (ev) {
    if (wrap.dataset.dirty === '1' && wrap.dataset.saved !== '1') {
      ev.preventDefault();
      ev.returnValue = '';
      return '';
    }
    return undefined;
  });

  form.addEventListener('submit', function () {
    wrap.dataset.saved = '1';
  });

  /* ---------- 9. 复制按钮（收录面板） ---------- */
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest ? ev.target.closest('[data-qq-copy]') : null;
    if (!btn) return;
    var text = btn.getAttribute('data-qq-copy') || '';
    var done = function () {
      var old = btn.textContent;
      btn.textContent = '已复制';
      setTimeout(function () { btn.textContent = old; }, 1400);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(function () {});
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); done(); } catch (e) {}
      document.body.removeChild(ta);
    }
  });
})();
