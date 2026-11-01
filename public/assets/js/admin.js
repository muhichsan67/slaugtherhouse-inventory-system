/*! Nimbus Admin – helpers. Requires Bootstrap 5 bundle. */
(function () {
  'use strict';
  const root = document.documentElement, body = document.body;
  const Admin = window.Admin = {};
  const store = { get: k => { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} } };

  window.appFormatNumber = function (value, decimals) {
    if (value === null || value === '') return '';
    const number = Number(value);
    if (!Number.isFinite(number)) return String(value);
    return new Intl.NumberFormat('id-ID', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(number);
  };

  window.appToScaledInteger = function (value, decimals) {
    if (value === null || value === '') return 0n;
    const number = Number(value);
    if (!Number.isFinite(number) || number < 0) return null;
    const [whole, fraction = ''] = number.toFixed(decimals).split('.');
    return BigInt(whole) * (10n ** BigInt(decimals)) +
      BigInt((fraction + '0'.repeat(decimals)).slice(0, decimals) || '0');
  };

  window.appFormatScaledInteger = function (value, decimals) {
    const negative = value < 0n;
    const absolute = (negative ? -value : value).toString().padStart(decimals + 1, '0');
    const whole = decimals ? absolute.slice(0, -decimals) : absolute;
    const fraction = decimals ? absolute.slice(-decimals) : '';
    const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return (negative ? '-' : '') + grouped + (decimals ? ',' + fraction : '');
  };

  window.appUpdateOrderTotals = function (rows, quantityTarget, amountTarget) {
    let totalQuantity = 0n;
    let totalAmount = 0n;

    rows.querySelectorAll('tr').forEach(function (row) {
      const quantity = window.appToScaledInteger(row.querySelector('[name$="[qty]"]').value, 3);
      const price = window.appToScaledInteger(row.querySelector('[name$="[unit_price]"]').value, 2);
      const subtotal = quantity === null || price === null
        ? 0n
        : (quantity * price + 500n) / 1000n;
      totalQuantity += quantity ?? 0n;
      totalAmount += subtotal;
      row.querySelector('.line-subtotal').textContent = window.appFormatScaledInteger(subtotal, 2);
    });

    quantityTarget.textContent = window.appFormatScaledInteger(totalQuantity, 3);
    amountTarget.textContent = window.appFormatScaledInteger(totalAmount, 2);
  };

  window.appFormatDateTime = function (value) {
    if (!value) return '';
    const timestamp = String(value);
    const normalized = /^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?$/.test(timestamp)
      ? timestamp.replace(' ', 'T') + 'Z'
      : timestamp;
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) return String(value);
    return new Intl.DateTimeFormat('id-ID', {
      timeZone: 'Asia/Jakarta',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      hourCycle: 'h23'
    }).format(date);
  };

  window.appFormatDate = function (value) {
    if (!value) return '';
    const date = String(value).match(/^\d{4}-\d{2}-\d{2}/);
    return date ? date[0] : String(value);
  };

  const validationMessage = function (control) {
    const validity = control.validity;
    const label = control.labels && control.labels.length
      ? control.labels[0].textContent.trim()
      : 'This field';

    if (validity.valueMissing) return label + ' is required.';
    if (validity.typeMismatch) return 'Enter a valid ' + (control.type === 'email' ? 'email address.' : 'value.');
    if (validity.badInput) return 'Enter a valid number.';
    if (validity.tooShort) return 'Enter at least ' + control.minLength + ' characters.';
    if (validity.tooLong) return 'Use no more than ' + control.maxLength + ' characters.';
    if (validity.rangeUnderflow) return 'Enter a value greater than or equal to ' + control.min + '.';
    if (validity.rangeOverflow) return 'Enter a value less than or equal to ' + control.max + '.';
    if (validity.stepMismatch) return 'Use increments of ' + control.step + '.';
    if (validity.patternMismatch) return control.dataset.validationMessage || 'Use the required format.';
    if (validity.customError) return control.validationMessage;
    return 'Enter a valid value.';
  };

  const feedbackFor = function (control) {
    let feedback = control.dataset.clientValidationFeedbackId
      ? document.getElementById(control.dataset.clientValidationFeedbackId)
      : null;
    if (!feedback) {
      feedback = control.parentElement.querySelector('.invalid-feedback');
    }

    if (!feedback) {
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback';
      const inputGroup = control.closest('.input-group');
      (inputGroup || control).insertAdjacentElement('afterend', feedback);
    }

    feedback.dataset.clientValidationFeedback = '';
    if (!feedback.id) {
      feedback.id = (control.id || control.name || 'field').replace(/[^a-zA-Z0-9_-]/g, '-') + '-client-feedback';
    }
    control.dataset.clientValidationFeedbackId = feedback.id;

    const describedBy = new Set((control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
    describedBy.add(feedback.id);
    control.setAttribute('aria-describedby', Array.from(describedBy).join(' '));
    return feedback;
  };

  const validateControl = function (control) {
    if (!control.willValidate) return control.checkValidity();

    const valid = control.checkValidity();
    control.classList.toggle('is-invalid', !valid);
    control.setAttribute('aria-invalid', String(!valid));
    const hasValue = control.type === 'checkbox' || control.type === 'radio'
      ? control.checked
      : control.value !== '';
    control.classList.toggle('is-valid', valid && hasValue);

    const feedback = feedbackFor(control);
    feedback.textContent = valid ? '' : validationMessage(control);
    feedback.classList.toggle('d-block', !valid);
    feedback.classList.toggle('d-none', valid);
    return valid;
  };

  document.querySelectorAll('form').forEach(function (form) {
    form.noValidate = true;
    let validationAttempted = false;
    form.querySelectorAll('.is-invalid').forEach(function (control) {
      control.setAttribute('aria-invalid', 'true');
    });
    const startDate = form.querySelector('input[name="start_date"]');
    const endDate = form.querySelector('input[name="end_date"]');
    const syncDateRange = function () {
      if (!startDate || !endDate) return;
      endDate.min = startDate.value;
      endDate.setCustomValidity(
        startDate.value && endDate.value && endDate.value < startDate.value
          ? 'The end date must be on or after the start date.'
          : ''
      );
      if (validationAttempted || endDate.classList.contains('is-invalid')) {
        validateControl(endDate);
      }
    };

    const updateSummary = function () {
      const summary = form.querySelector('[data-client-validation-summary]');
      if (summary) summary.hidden = form.checkValidity();
    };

    syncDateRange();
    if (startDate && endDate) {
      startDate.addEventListener('input', syncDateRange);
      startDate.addEventListener('change', syncDateRange);
      endDate.addEventListener('input', syncDateRange);
      endDate.addEventListener('change', syncDateRange);
    }

    form.addEventListener('input', function (event) {
      if (validationAttempted) {
        validateControl(event.target);
        updateSummary();
      } else if (event.target.classList.contains('is-invalid')) {
        validateControl(event.target);
        updateSummary();
      }
    });
    form.addEventListener('change', function (event) {
      if (validationAttempted || event.target.classList.contains('is-invalid')) {
        validateControl(event.target);
        updateSummary();
      }
    });
    form.addEventListener('submit', function (event) {
      syncDateRange();
      const controls = Array.from(form.elements).filter(function (control) {
        return control.willValidate;
      });
      const invalidControls = controls.filter(function (control) {
        return !validateControl(control);
      });

      if (invalidControls.length === 0) {
        return;
      }

      event.preventDefault();
      validationAttempted = true;
      let summary = form.querySelector('[data-client-validation-summary]');
      if (!summary) {
        summary = document.createElement('div');
        summary.className = 'alert alert-danger';
        summary.setAttribute('role', 'alert');
        summary.setAttribute('aria-live', 'polite');
        summary.dataset.clientValidationSummary = '';
        form.prepend(summary);
      }

      summary.textContent = 'Please correct the highlighted fields before continuing.';
      invalidControls[0].focus();
      invalidControls.forEach(validateControl);
    });
  });

  /* ---------- Theme (light / dark) ---------- */
  Admin.setTheme = function (t) {
    root.setAttribute('data-bs-theme', t); store.set('nimbus-theme', t);
    document.querySelectorAll('[data-theme-icon]').forEach(i => i.className = 'bi ' + (t === 'dark' ? 'bi-sun' : 'bi-moon-stars'));
    document.dispatchEvent(new Event('themechange'));
  };
  document.querySelectorAll('[data-theme-toggle]').forEach(b => b.addEventListener('click', () => Admin.setTheme(root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark')));
  document.querySelectorAll('[data-theme-icon]').forEach(i => i.className = 'bi ' + (root.getAttribute('data-bs-theme') === 'dark' ? 'bi-sun' : 'bi-moon-stars'));

  /* ---------- Sidebar ---------- */
  document.querySelectorAll('[data-sidebar-toggle]').forEach(b => b.addEventListener('click', () =>
    body.classList.toggle(window.innerWidth >= 992 ? 'sidebar-collapsed' : 'sidebar-open')));
  const bd = document.querySelector('.sidebar-backdrop');
  if (bd) bd.addEventListener('click', () => body.classList.remove('sidebar-open'));

  /* ---------- Toasts ---------- */
  const ICONS = { success: 'check-circle-fill', danger: 'x-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill', primary: 'bell-fill', secondary: 'bell-fill', dark: 'bell-fill' };
  Admin.toast = function (message, type, opts) {
    type = type || 'primary'; opts = opts || {};
    let c = document.getElementById('toastContainer');
    if (!c) { c = document.createElement('div'); c.id = 'toastContainer'; c.className = 'toast-container position-fixed top-0 end-0 p-3'; document.body.appendChild(c); }
    const el = document.createElement('div');
    el.className = 'toast align-items-center text-bg-' + type + ' border-0'; el.setAttribute('role', 'alert');
    const light = ['warning', 'info', 'light'].includes(type);
    el.innerHTML = '<div class="d-flex"><div class="toast-body d-flex gap-2 align-items-start"><i class="bi bi-' + (ICONS[type] || ICONS.primary) + ' mt-1"></i><div>' +
      (opts.title ? '<strong class="d-block">' + opts.title + '</strong>' : '') + message + '</div></div>' +
      '<button type="button" class="btn-close ' + (light ? '' : 'btn-close-white') + ' me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    c.appendChild(el);
    el.addEventListener('hidden.bs.toast', () => el.remove());
    const t = new bootstrap.Toast(el, { delay: opts.delay === undefined ? 4000 : opts.delay, autohide: opts.delay !== 0 });
    t.show(); return t;
  };
  window.showToast = Admin.toast;
  Admin.toastPosition = function (cls) { const c = document.getElementById('toastContainer'); if (c) c.className = 'toast-container position-fixed p-3 ' + cls; };
  document.addEventListener('click', function (e) {          // <button data-toast="success" data-toast-message="Saved!">
    const b = e.target.closest('[data-toast]'); if (!b) return;
    Admin.toast(b.dataset.toastMessage || 'Hello!', b.dataset.toast, { title: b.dataset.toastTitle });
  });

  /* ---------- Charts (Chart.js) – re-built on theme change ---------- */
  Admin.colors = ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
  const reg = [];
  function build(e) {
    if (e.inst) e.inst.destroy();
    const s = getComputedStyle(root);
    Chart.defaults.color = s.getPropertyValue('--bs-secondary-color').trim() || '#6b7280';
    Chart.defaults.borderColor = s.getPropertyValue('--bs-border-color').trim() || '#e5e7eb';
    Chart.defaults.font.family = "Inter, system-ui, sans-serif";
    e.inst = new Chart(document.getElementById(e.id), e.cfg());
  }
  Admin.chart = function (id, cfgFn) {
    if (!window.Chart || !document.getElementById(id)) return;
    const e = { id: id, cfg: cfgFn, inst: null }; reg.push(e); build(e); return e;
  };
  document.addEventListener('themechange', () => reg.forEach(build));

  /* ---------- Misc helpers ---------- */
  Admin.copy = function (text) {
    const done = () => Admin.toast('Copied: <code>' + text.replace(/</g, '&lt;') + '</code>', 'success', { delay: 2000 });
    if (navigator.clipboard) navigator.clipboard.writeText(text).then(done); else { const t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done(); }
  };
  Admin.fmtSize = b => b < 1024 ? b + ' B' : b < 1048576 ? (b / 1024).toFixed(1) + ' KB' : (b / 1048576).toFixed(1) + ' MB';

  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => new bootstrap.Popover(el));
  document.querySelectorAll('[data-year]').forEach(el => el.textContent = new Date().getFullYear());

  /* Show/hide password: <button data-toggle-password="#pwd"> */
  document.addEventListener('click', function (e) {
    const b = e.target.closest('[data-toggle-password]'); if (!b) return;
    const i = document.querySelector(b.dataset.togglePassword); i.type = i.type === 'password' ? 'text' : 'password';
    const ic = b.querySelector('i'); if (ic) ic.className = 'bi ' + (i.type === 'password' ? 'bi-eye' : 'bi-eye-slash');
  });

  /* Bootstrap validation: <form class="needs-validation" novalidate>
     Add data-demo to stop the real submit (remove it in Laravel). data-redirect="url" redirects after demo submit. */
  document.querySelectorAll('form.needs-validation').forEach(f => f.addEventListener('submit', function (e) {
    if (!f.checkValidity()) { e.preventDefault(); e.stopPropagation(); f.classList.add('was-validated'); Admin.toast('Please fix the highlighted fields.', 'danger'); return; }
    f.classList.add('was-validated');
    if (f.hasAttribute('data-demo')) {
      e.preventDefault(); Admin.toast('Form is valid – submitted (demo).', 'success');
      if (f.dataset.redirect) setTimeout(() => location.href = f.dataset.redirect, 900);
    }
  }));

  /* ---------- Drag & drop upload ----------
     <div class="dropzone" data-dropzone data-list="#fileList" data-upload-url="/upload" data-max-size="5" data-accept="image/*,.pdf">
     Without data-upload-url the upload is simulated. With it, files are POSTed (field "file") with X-CSRF-TOKEN from <meta name="csrf-token">. */
  document.querySelectorAll('[data-dropzone]').forEach(function (zone) {
    const input = zone.querySelector('input[type=file]'), list = document.querySelector(zone.dataset.list);
    const url = zone.dataset.uploadUrl, maxMB = +zone.dataset.maxSize || 10;
    const csrf = (document.querySelector('meta[name=csrf-token]') || {}).content;
    const accept = (zone.dataset.accept || '').split(',').map(s => s.trim()).filter(Boolean);
    const ok = f => !accept.length || accept.some(a => a.startsWith('.') ? f.name.toLowerCase().endsWith(a) : a.endsWith('/*') ? f.type.startsWith(a.slice(0, -1)) : f.type === a);
    zone.addEventListener('click', e => { if (e.target !== input) input.click(); });
    ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('dragover'); }));
    ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('dragover'); }));
    zone.addEventListener('drop', e => handle(e.dataTransfer.files));
    input.addEventListener('change', () => { handle(input.files); input.value = ''; });
    function handle(files) {
      [].forEach.call(files, function (f) {
        if (!ok(f)) return Admin.toast(f.name + ': file type not allowed', 'danger');
        if (f.size > maxMB * 1048576) return Admin.toast(f.name + ' is larger than ' + maxMB + ' MB', 'danger');
        add(f);
      });
    }
    function add(f) {
      const row = document.createElement('div'); row.className = 'file-item';
      const thumb = f.type.startsWith('image/') ? '<img class="file-thumb" src="' + URL.createObjectURL(f) + '" alt="">' : '<div class="file-thumb"><i class="bi bi-file-earmark-text"></i></div>';
      row.innerHTML = thumb + '<div class="flex-grow-1 min-w-0"><div class="d-flex justify-content-between"><span class="fw-semibold text-truncate"></span><small class="text-secondary status">0%</small></div>' +
        '<div class="progress mt-1" style="height:6px"><div class="progress-bar" style="width:0"></div></div><small class="text-secondary">' + Admin.fmtSize(f.size) + '</small></div>' +
        '<button type="button" class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-x-lg"></i></button>';
      row.querySelector('.fw-semibold').textContent = f.name;
      list.appendChild(row);
      const bar = row.querySelector('.progress-bar'), st = row.querySelector('.status'); let xhr, timer;
      const finish = () => { bar.style.width = '100%'; bar.classList.add('bg-success'); st.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Done'; Admin.toast(f.name + ' uploaded', 'success', { delay: 2500 }); };
      row.querySelector('button').addEventListener('click', () => { if (xhr) xhr.abort(); clearInterval(timer); row.remove(); });
      if (!url) { let p = 0; timer = setInterval(() => { p = Math.min(100, p + Math.random() * 18); bar.style.width = p + '%'; st.textContent = Math.round(p) + '%'; if (p >= 100) { clearInterval(timer); finish(); } }, 250); return; }
      xhr = new XMLHttpRequest(); const fd = new FormData(); fd.append('file', f);
      xhr.open('POST', url); if (csrf) xhr.setRequestHeader('X-CSRF-TOKEN', csrf); xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.onprogress = e => { if (e.lengthComputable) { const p = Math.round(e.loaded / e.total * 100); bar.style.width = p + '%'; st.textContent = p + '%'; } };
      xhr.onload = () => xhr.status >= 200 && xhr.status < 300 ? finish() : (bar.classList.add('bg-danger'), st.textContent = 'Failed', Admin.toast(f.name + ' failed (' + xhr.status + ')', 'danger'));
      xhr.onerror = () => { bar.classList.add('bg-danger'); st.textContent = 'Error'; };
      xhr.send(fd);
    }
  });

  /* ---------- Select2 ----------
     <select class="form-select select2" data-placeholder="Choose..." data-allow-clear="true" [multiple] [data-tags="true"]> */
  if (window.jQuery && jQuery.fn.select2) {
    jQuery('select.select2').each(function () {
      const $e = jQuery(this), m = $e.closest('.modal');
      $e.select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: m.length ? m : jQuery(document.body) });
    });
  }

  /* ---------- Confirmation dialog ----------
     JS:   Admin.confirm({title, message, confirmText, cancelText, type:'danger|warning|primary|success', icon}).then(ok => { if (ok) ... })
     HTML: <button data-confirm="Delete this item?" data-confirm-title="Delete" data-confirm-text="Yes, delete" data-confirm-type="danger">
           works on buttons, links (<a href>) and whole forms (<form data-confirm="...">). The action only runs after the user confirms. */
  Admin.confirm = function (o) {
    o = o || {};
    return new Promise(function (resolve) {
      let m = document.getElementById('adminConfirm');
      if (!m) {
        m = document.createElement('div'); m.id = 'adminConfirm'; m.className = 'modal fade'; m.tabIndex = -1;
        m.innerHTML = '<div class="modal-dialog modal-dialog-centered" style="max-width:380px"><div class="modal-content text-center p-4">' +
          '<div class="stat-icon mx-auto mb-3" data-ic style="width:64px;height:64px;font-size:1.9rem;border-radius:50%"></div>' +
          '<h5 class="mb-1" data-t></h5><p class="text-secondary mb-4" data-m></p>' +
          '<div class="d-flex gap-2 justify-content-center"><button type="button" class="btn btn-light border px-4" data-cancel></button><button type="button" class="btn px-4" data-ok></button></div></div></div>';
        document.body.appendChild(m);
      }
      const type = o.type || 'danger';
      const icon = o.icon || ({ danger: 'trash', warning: 'exclamation-triangle', success: 'check-circle', primary: 'question-circle' }[type] || 'question-circle');
      const ic = m.querySelector('[data-ic]'), ok = m.querySelector('[data-ok]'), cancel = m.querySelector('[data-cancel]');
      ic.className = 'stat-icon mx-auto mb-3 bg-' + type + '-subtle text-' + type; ic.innerHTML = '<i class="bi bi-' + icon + '"></i>';
      m.querySelector('[data-t]').textContent = o.title || 'Are you sure?';
      m.querySelector('[data-m]').textContent = o.message || 'Are you sure you want to do this action? This cannot be undone.';
      ok.className = 'btn px-4 btn-' + type; ok.textContent = o.confirmText || 'Yes, continue';
      cancel.textContent = o.cancelText || 'Cancel';
      const inst = bootstrap.Modal.getOrCreateInstance(m); let result = false;
      ok.onclick = function () { result = true; inst.hide(); };
      cancel.onclick = function () { inst.hide(); };
      m.addEventListener('hidden.bs.modal', function () { resolve(result); }, { once: true });
      m.addEventListener('shown.bs.modal', function () { ok.focus(); }, { once: true });
      inst.show();
    });
  };
  Admin.confirmDelete = function (name) {
    return Admin.confirm({ title: 'Delete ' + (name || 'item') + '?', message: 'Are you sure you want to delete ' + (name ? '"' + name + '"' : 'this item') + '? This action cannot be undone.', confirmText: 'Yes, delete', type: 'danger' });
  };
  function confirmOpts(el) {
    const d = el.dataset;
    return { message: d.confirm || undefined, title: d.confirmTitle, confirmText: d.confirmText, cancelText: d.confirmCancel, type: d.confirmType, icon: d.confirmIcon };
  }
  document.addEventListener('click', function (e) {          // buttons / links (capture so it runs before other click handlers)
    const el = e.target.closest('[data-confirm]:not(form)'); if (!el || el.dataset.confirmed) return;
    e.preventDefault(); e.stopImmediatePropagation();
    Admin.confirm(confirmOpts(el)).then(function (yes) {
      if (!yes) return;
      el.dataset.confirmed = '1'; el.click(); setTimeout(function () { delete el.dataset.confirmed; }, 0);
    });
  }, true);
  document.addEventListener('submit', function (e) {         // <form data-confirm="...">
    const f = e.target; if (!f.matches || !f.matches('form[data-confirm]') || f.dataset.confirmed) return;
    e.preventDefault(); e.stopImmediatePropagation();
    Admin.confirm(confirmOpts(f)).then(function (yes) {
      if (!yes) return;
      f.dataset.confirmed = '1'; f.requestSubmit ? f.requestSubmit(e.submitter) : f.submit();
    });
  }, true);

  /* ---------- Printable documents ----------
     <div class="doc-sheet" data-doc data-currency="USD" data-locale="en-US" data-tax="10" data-discount="50" data-shipping="25" data-paid="0">
       <tr data-qty="2" data-price="1200"> ... <td class="js-price"></td><td class="js-line"></td></tr>
       totals: [data-sub] [data-disc] [data-taxamt] [data-ship] [data-total] [data-paid-amt] [data-balance]
     Change data-currency="IDR" data-locale="id-ID" for Rupiah. In Laravel, just render the numbers server-side instead. */
  document.querySelectorAll('[data-doc]').forEach(function (doc) {
    const fmt = new Intl.NumberFormat(doc.dataset.locale || 'en-US', { style: 'currency', currency: doc.dataset.currency || 'USD' }).format;
    const set = (sel, v) => doc.querySelectorAll(sel).forEach(el => el.textContent = fmt(v));
    let sub = 0;
    doc.querySelectorAll('tr[data-qty]').forEach(function (tr) {
      const line = (+tr.dataset.qty) * (+tr.dataset.price); sub += line;
      const p = tr.querySelector('.js-price'), l = tr.querySelector('.js-line');
      if (p) p.textContent = fmt(+tr.dataset.price); if (l) l.textContent = fmt(line);
    });
    const disc = +(doc.dataset.discount || 0), ship = +(doc.dataset.shipping || 0), paid = +(doc.dataset.paid || 0);
    const tax = (sub - disc) * (+(doc.dataset.tax || 0)) / 100, total = sub - disc + tax + ship;
    set('[data-sub]', sub); set('[data-disc]', -disc); set('[data-taxamt]', tax); set('[data-ship]', ship);
    set('[data-total]', total); set('[data-paid-amt]', -paid); set('[data-balance]', total - paid);
    doc.querySelectorAll('[data-tax-rate]').forEach(el => el.textContent = (doc.dataset.tax || 0) + '%');
  });
  document.addEventListener('click', function (e) {           // <button data-pdf="#docSheet" data-filename="INV-0042.pdf">
    const b = e.target.closest('[data-pdf]'); if (!b) return;
    if (!window.html2pdf) return Admin.toast('PDF library not loaded (check your internet connection).', 'danger');
    const el = document.querySelector(b.dataset.pdf); window.scrollTo(0, 0);
    Admin.toast('Generating PDF...', 'info', { delay: 1500 });
    html2pdf().set({ margin: 0, filename: b.dataset.filename || 'document.pdf', image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true, scrollY: 0 }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }, pagebreak: { mode: ['css', 'legacy'] } })
      .from(el).save().then(() => Admin.toast('PDF downloaded.', 'success'));
  });
})();
