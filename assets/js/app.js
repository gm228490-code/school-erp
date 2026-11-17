/* School ERP - client side helpers */
(function () {
  'use strict';
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  const T = (window.APP && APP.i18n) || {};
  const route = (r, params = {}) => APP.base + '/index.php?' + new URLSearchParams(Object.assign({ r }, params)).toString();

  /* ---------- Toasts ---------- */
  window.toast = function (msg, type = 'success') {
    const area = $('#toastArea');
    if (!area) return alert(msg);
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${type} border-0`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    el.querySelector('.toast-body').textContent = msg;
    area.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 3500 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  };

  /* ---------- fetch wrapper (CSRF + JSON) ---------- */
  window.api = async function (url, opts = {}) {
    opts.headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': APP.csrf }, opts.headers || {});
    const res = await fetch(url, opts);
    const ct = res.headers.get('content-type') || '';
    if (ct.includes('application/json')) {
      const data = await res.json();
      if (res.status === 401) { location.reload(); }
      return data;
    }
    return { ok: res.ok, html: await res.text() };
  };

  /* ---------- Sidebar ---------- */
  $$('[data-toggle-sidebar]').forEach(b => b.addEventListener('click', () => $('#sidebar').classList.toggle('open')));
  const active = $('.sidebar .nav-item.active');
  if (active) active.scrollIntoView({ block: 'center' });

  /* ---------- Theme ---------- */
  const tt = $('#themeToggle');
  if (tt) tt.addEventListener('click', () => {
    const html = document.documentElement;
    const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-bs-theme', next);
    document.cookie = 'theme=' + next + ';path=/;max-age=31536000;samesite=lax';
    tt.innerHTML = next === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
    const fd = new FormData(); fd.append('theme', next);
    api(route('lang/theme'), { method: 'POST', body: fd });
  });

  /* ---------- Confirm dialogs ---------- */
  document.addEventListener('submit', e => {
    const f = e.target;
    if (f.dataset.confirm && !f.dataset.confirmed) {
      if (!confirm(f.dataset.confirm)) { e.preventDefault(); return; }
    }
  }, true);
  document.addEventListener('click', e => {
    const a = e.target.closest('a[data-confirm]');
    if (a && !confirm(a.dataset.confirm)) e.preventDefault();
  });

  /* ---------- AJAX modal ---------- */
  const modalEl = $('#ajaxModal');
  const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
  window.openModal = async function (url, size) {
    if (!modal) return (location.href = url);
    const dlg = $('.modal-dialog', modalEl);
    dlg.className = 'modal-dialog modal-dialog-scrollable ' + (size || 'modal-lg');
    $('.modal-content', modalEl).innerHTML = '<div class="p-5 text-center"><div class="spinner-border text-primary"></div></div>';
    modal.show();
    const r = await api(url);
    if (r.html !== undefined) {
      $('.modal-content', modalEl).innerHTML = r.html;
      initDynamic(modalEl);
    } else {
      modal.hide();
      toast(r.message || T.error, 'danger');
    }
  };
  document.addEventListener('click', e => {
    const a = e.target.closest('[data-modal]');
    if (!a) return;
    e.preventDefault();
    openModal(a.dataset.modal || a.getAttribute('href'), a.dataset.size);
  });

  /* ---------- AJAX forms ---------- */
  document.addEventListener('submit', async e => {
    const f = e.target;
    if (!f.matches('form[data-ajax]')) return;
    e.preventDefault();
    if (f.dataset.confirm && !confirm(f.dataset.confirm)) return;
    $$('.is-invalid', f).forEach(i => i.classList.remove('is-invalid'));
    const btn = f.querySelector('[type=submit]');
    const btnHtml = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
    try {
      const r = await api(f.action, { method: 'POST', body: new FormData(f) });
      if (r.ok) {
        if (r.sticky) alert(r.message); else toast(r.message || T.saved);
        if (r.redirect) { location.href = r.redirect; return; }
        if (modal && f.closest('#ajaxModal')) modal.hide();
        if (f.dataset.ajax !== 'stay') setTimeout(() => location.reload(), 350);
      } else {
        toast(r.message || T.error, 'danger');
        Object.entries(r.errors || {}).forEach(([k, v]) => {
          const inp = f.querySelector(`[name="${CSS.escape(k)}"]`);
          if (inp) inp.classList.add('is-invalid');
          const fb = f.querySelector(`[data-error-for="${CSS.escape(k)}"]`);
          if (fb) fb.textContent = v;
        });
      }
    } catch (err) {
      toast(T.error, 'danger');
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = btnHtml; }
    }
  });

  /* ---------- AJAX delete / action buttons ---------- */
  document.addEventListener('click', async e => {
    const b = e.target.closest('[data-post]');
    if (!b) return;
    e.preventDefault();
    if (!confirm(b.dataset.confirmText || T.confirm)) return;
    const fd = new FormData();
    Object.entries(b.dataset).forEach(([k, v]) => { if (k.startsWith('field')) fd.append(k.slice(5).toLowerCase(), v); });
    const r = await api(b.dataset.post, { method: 'POST', body: fd });
    if (r.ok) {
      if (r.sticky) { alert(r.message); if (r.reload) location.reload(); return; }
      toast(r.message || T.deleted);
      if (r.redirect) location.href = r.redirect;
      else {
        const row = b.closest('tr');
        if (row && b.dataset.removeRow !== undefined) row.remove(); else setTimeout(() => location.reload(), 350);
      }
    } else toast(r.message || T.error, 'danger');
  });

  /* ---------- Dynamic widgets (run on page + modal content) ---------- */
  function initDynamic(root = document) {
    // Class -> Section dependent selects: <select data-sections-for="#classSelect">
    $$('select[data-sections-for]', root).forEach(sel => {
      const cls = root.querySelector(sel.dataset.sectionsFor) || document.querySelector(sel.dataset.sectionsFor);
      if (!cls || cls.dataset.bound) return;
      cls.dataset.bound = 1;
      cls.addEventListener('change', async () => {
        const keep = sel.dataset.keep || '';
        sel.innerHTML = `<option value="">${sel.dataset.placeholder || '-- Section --'}</option>`;
        if (!cls.value) return;
        const r = await api(route('ajax/sections', { class_id: cls.value }));
        (r.items || []).forEach(it => { const o = new Option(it.name, it.id); if (String(it.id) === keep) o.selected = true; sel.add(o); });
      });
    });
    // Class -> Subject dependent selects
    $$('select[data-subjects-for]', root).forEach(sel => {
      const cls = root.querySelector(sel.dataset.subjectsFor) || document.querySelector(sel.dataset.subjectsFor);
      if (!cls || cls.dataset.boundSub) return;
      cls.dataset.boundSub = 1;
      cls.addEventListener('change', async () => {
        sel.innerHTML = '<option value="">-- Subject --</option>';
        if (!cls.value) return;
        const r = await api(route('ajax/subjects', { class_id: cls.value }));
        (r.items || []).forEach(it => sel.add(new Option(it.name, it.id)));
      });
    });
    // Image preview
    $$('input[type=file][data-preview]', root).forEach(inp => inp.addEventListener('change', () => {
      const f = inp.files[0];
      if (!f) return;
      let img = inp.parentElement.querySelector('img.img-thumbnail');
      if (!img) { img = document.createElement('img'); img.className = 'img-thumbnail mt-2'; img.style.maxHeight = '80px'; inp.after(img); }
      img.src = URL.createObjectURL(f);
    }));
    // Student autocomplete: <input data-student-search data-target="#student_id">
    $$('input[data-student-search]', root).forEach(inp => {
      if (inp.dataset.bound) return; inp.dataset.bound = 1;
      const list = document.createElement('div');
      list.className = 'list-group position-absolute w-100 shadow student-search-list';
      list.style.zIndex = 1060;
      list.style.maxHeight = '340px';
      list.style.overflowY = 'auto';
      list.setAttribute('role', 'listbox');
      inp.parentElement.style.position = 'relative';
      inp.parentElement.appendChild(list);
      inp.setAttribute('autocomplete', 'off');
      let timer, items = [], active = -1, seq = 0, pendingEnter = false;
      const close = () => { list.innerHTML = ''; items = []; active = -1; };
      const choose = it => {
        const target = document.querySelector(inp.dataset.target);
        if (target) target.value = it.id;
        inp.value = `${it.name} (${it.reg_no})`;
        close();
        if (inp.dataset.go) location.href = inp.dataset.go.replace('__ID__', it.id);
      };
      const highlight = i => {
        const btns = $$('.list-group-item-action', list);
        btns.forEach((b, k) => b.classList.toggle('active', k === i));
        active = i;
        if (btns[i]) btns[i].scrollIntoView({ block: 'nearest' });
      };
      const render = data => {
        list.innerHTML = '';
        items = data;
        active = -1;
        if (!data.length) {
          const empty = document.createElement('div');
          empty.className = 'list-group-item small text-muted';
          empty.textContent = (T.notFound || 'No student found') + ': "' + inp.value.trim() + '"';
          list.appendChild(empty);
          return;
        }
        data.forEach((it, i) => {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'list-group-item list-group-item-action py-2';
          const top = document.createElement('div');
          top.className = 'd-flex justify-content-between gap-2';
          const nm = document.createElement('span'); nm.className = 'fw-semibold'; nm.textContent = it.name;
          const rg = document.createElement('span'); rg.className = 'small text-muted text-nowrap'; rg.textContent = it.reg_no;
          top.append(nm, rg);
          const sub = document.createElement('div');
          sub.className = 'small text-muted';
          sub.textContent = [it.class, it.roll_no ? 'Roll ' + it.roll_no : '', it.father_name ? 'Father: ' + it.father_name : ''].filter(Boolean).join(' · ');
          b.append(top, sub);
          if (it.due_text) { const d = document.createElement('div'); d.className = 'small text-danger fw-semibold'; d.textContent = 'Due: ' + it.due_text; b.append(d); }
          b.addEventListener('mousedown', e => e.preventDefault()); // keep focus in the input
          b.addEventListener('click', () => choose(it));
          b.addEventListener('mouseenter', () => highlight(i));
          list.appendChild(b);
        });
        if (data.length === 1 || pendingEnter) highlight(0);
        if (pendingEnter) { pendingEnter = false; choose(data[0]); }
      };
      const search = async () => {
        const q = inp.value.trim();
        if (q.length < 1) { close(); return; }
        const my = ++seq;
        const r = await api(route('ajax/students', { q }));
        if (my !== seq) return; // ignore out-of-date responses
        render(r.items || []);
      };
      inp.addEventListener('input', () => { clearTimeout(timer); pendingEnter = false; timer = setTimeout(search, 200); });
      inp.addEventListener('focus', () => { if (inp.value.trim() && !items.length && !inp.dataset.chosen) search(); });
      inp.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown' && items.length) { e.preventDefault(); highlight(Math.min(items.length - 1, active + 1)); }
        else if (e.key === 'ArrowUp' && items.length) { e.preventDefault(); highlight(Math.max(0, active - 1)); }
        else if (e.key === 'Escape') { close(); }
        else if (e.key === 'Enter') {
          e.preventDefault();
          if (items.length) { choose(items[active >= 0 ? active : 0]); }
          else if (inp.value.trim()) { pendingEnter = true; clearTimeout(timer); search(); } // results not loaded yet: open first match when they arrive
        }
      });
      document.addEventListener('click', ev => { if (!inp.parentElement.contains(ev.target)) close(); });
    });
    // Check-all
    $$('[data-check-all]', root).forEach(c => c.addEventListener('change', () => {
      $$(c.dataset.checkAll).forEach(x => { x.checked = c.checked; });
    }));
    // Auto-submit filters
    $$('[data-autosubmit]', root).forEach(el => el.addEventListener('change', () => el.form && el.form.submit()));
  }
  window.initDynamic = initDynamic;
  initDynamic();

  /* ---------- Notifications ---------- */
  const nb = $('#notifBtn');
  async function loadNotifs() {
    const r = await api(route('notifications/feed'));
    const list = $('#notifList');
    if (!list || !r.ok) return;
    const cnt = $('#notifCount');
    cnt.textContent = r.unread > 99 ? '99+' : r.unread;
    cnt.classList.toggle('d-none', !r.unread);
    list.innerHTML = '';
    if (!r.items.length) { list.innerHTML = `<div class="p-3 text-muted small">${T.none}</div>`; return; }
    r.items.forEach(n => {
      const a = document.createElement('a');
      a.href = route('notifications/open', { id: n.id });
      a.className = 'notif-item' + (n.is_read ? '' : ' unread');
      const t = document.createElement('div'); t.className = 'fw-semibold'; t.textContent = n.title;
      const m = document.createElement('div'); m.className = 'text-muted text-truncate'; m.textContent = n.message || '';
      const d = document.createElement('div'); d.className = 'text-muted'; d.style.fontSize = '.7rem'; d.textContent = n.ago;
      a.append(t, m, d);
      list.appendChild(a);
    });
  }
  if (nb) {
    nb.addEventListener('show.bs.dropdown', loadNotifs);
    setInterval(async () => {
      const r = await api(route('notifications/count'));
      if (r.ok) { const c = $('#notifCount'); c.textContent = r.unread > 99 ? '99+' : r.unread; c.classList.toggle('d-none', !r.unread); }
    }, 60000);
  }

  /* ---------- Chart defaults ---------- */
  if (window.Chart) {
    const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.color = dark ? '#95a1bb' : '#6b7489';
    Chart.defaults.borderColor = dark ? '#22304a' : '#e5e9f2';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.layout.padding = { left: 6, right: 6 };
    // Re-measure axis labels once the web font has loaded
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => Object.values(Chart.instances || {}).forEach(c => c.update('none')));
  }
})();
