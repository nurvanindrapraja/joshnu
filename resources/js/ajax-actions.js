/**
 * AJAX actions.
 *
 * Kontrak markup:
 *  - <form data-ajax>            : form dikirim via fetch (POST/PUT/DELETE,
 *                                  multipart/form-data tetap didukung).
 *  - <form data-ajax data-ajax-redirect> : setelah sukses, navigasi penuh ke
 *                                  data.redirect dari respons JSON (login/register).
 *  - <form data-ajax data-no-refresh>  : setelah sukses, toast + reset form,
 *                                  TANPA refresh <main> (mis. subscribe di footer).
 *  - <form data-ajax data-prompt="..."> : tampilkan window.prompt sebelum kirim;
 *                                         hasil disimpan ke input hidden "reason".
 *
 * Sukses (200 + {ok, message})   : toast slide-in pojok kanan atas, lalu konten
 *                                  <main> di-refresh (tanpa reload penuh) — atau
 *                                  navigasi penuh jika data-ajax-redirect.
 * Validasi gagal (422 + {errors}): toast error + pesan error inline per field.
 * Login 422 + {unverified}       : event 'auth:unverified' (UI login menangani).
 * CSRF kedaluwarsa (419)         : reload halaman.
 */

const TOAST_ROOT_ID = 'ajax-toast-root';
const TOAST_STYLE_ID = 'ajax-toast-styles';
const TOAST_DURATION = 4500;

/* ------------------------------ toast ------------------------------ */

function ensureToastStyles() {
    if (document.getElementById(TOAST_STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = TOAST_STYLE_ID;
    style.textContent = `
#ajax-toast-root{position:fixed;top:1.25rem;right:1.25rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;max-width:min(92vw,24rem);}
.ajax-toast{display:flex;align-items:flex-start;gap:.625rem;min-width:18rem;padding:.75rem 1rem;border-radius:.75rem;box-shadow:0 10px 25px -5px rgb(0 0 0/.15),0 8px 10px -6px rgb(0 0 0/.1);color:#fff;font-size:.875rem;transform:translateX(calc(100% + 1.5rem));opacity:0;transition:transform .35s cubic-bezier(.21,1.02,.73,1),opacity .35s ease;pointer-events:none;}
.ajax-toast.is-shown{transform:translateX(0);opacity:1;pointer-events:auto;}
.ajax-toast-success{background:#15803d;}
.ajax-toast-error{background:#b91c1c;}
.ajax-toast .ajax-toast-msg{flex:1;line-height:1.4;}
.ajax-toast .ajax-toast-close{background:none;border:0;color:rgba(255,255,255,.85);cursor:pointer;padding:0;line-height:1;font-size:1.1rem;}
`;
    document.head.appendChild(style);
}

/* --------------------------- progress bar --------------------------- */

let progressTimer = null;

function ensureProgressStyles() {
    if (document.getElementById('ajax-progress-styles')) return;
    const style = document.createElement('style');
    style.id = 'ajax-progress-styles';
    style.textContent = `
#ajax-progress-bar{position:fixed;top:0;left:0;height:3px;width:0;background:var(--primary,#1b6e5a);z-index:10000;pointer-events:none;transition:width .25s ease;}
#ajax-progress-bar.is-done{width:100%;opacity:0;transition:width .2s ease,opacity .45s ease .2s;}
`;
    document.head.appendChild(style);
}

function startProgressBar() {
    ensureProgressStyles();
    let bar = document.getElementById('ajax-progress-bar');
    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'ajax-progress-bar';
        bar.setAttribute('aria-hidden', 'true');
        document.body.appendChild(bar);
    }
    bar.classList.remove('is-done');
    bar.style.width = '0';
    // animasi bertahap (indeterminate) sampai proses selesai
    requestAnimationFrame(() => { bar.style.width = '25%'; });
    let width = 25;
    progressTimer = setInterval(() => {
        width = Math.min(width + Math.random() * 10, 80);
        bar.style.width = width + '%';
    }, 200);
}

function stopProgressBar() {
    const bar = document.getElementById('ajax-progress-bar');
    if (!bar) return;
    if (progressTimer) { clearInterval(progressTimer); progressTimer = null; }
    bar.classList.add('is-done');
    setTimeout(() => { if (bar.isConnected) bar.remove(); }, 800);
}

function toastRoot() {
    let root = document.getElementById(TOAST_ROOT_ID);
    if (!root) {
        root = document.createElement('div');
        root.id = TOAST_ROOT_ID;
        root.setAttribute('aria-live', 'polite');
        document.body.appendChild(root);
    }
    return root;
}

function escapeHtml(value) {
    const d = document.createElement('div');
    d.textContent = value == null ? '' : String(value);
    return d.innerHTML;
}

function dismissToast(el) {
    if (el.dataset.leaving) return;
    el.dataset.leaving = '1';
    el.classList.remove('is-shown');
    setTimeout(() => el.remove(), 380);
}

export function showToast(message, type = 'success', title = null) {
    ensureToastStyles();
    const root = toastRoot();

    const el = document.createElement('div');
    el.className = 'ajax-toast ajax-toast-' + type;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = `
        <svg class="shrink-0 mt-0.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            ${type === 'success'
                ? '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>'
                : '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>'}
        </svg>
        <div class="ajax-toast-msg">${title ? `<p class="font-semibold">${escapeHtml(title)}</p>` : ''}<p>${escapeHtml(message)}</p></div>
        <button type="button" class="ajax-toast-close" aria-label="Tutup">&times;</button>`;

    el.querySelector('.ajax-toast-close').addEventListener('click', () => dismissToast(el));
    root.appendChild(el);
    // dua rAF agar transisi slide-in terpicu setelah elemen ter-render
    requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('is-shown')));
    setTimeout(() => dismissToast(el), TOAST_DURATION);
}

/* --------------------------- refresh page --------------------------- */

async function refreshContent() {
    const res = await fetch(window.location.href, {
        headers: { 'X-Requested-With': 'fetch' },
        credentials: 'same-origin',
    });
    if (!res.ok) return;

    const html = await res.text();
    const parsed = new DOMParser().parseFromString(html, 'text/html');
    const freshMain = parsed.querySelector('main');
    const oldMain = document.querySelector('main');
    if (!freshMain || !oldMain) return;

    const holder = document.createElement('div');
    holder.innerHTML = freshMain.outerHTML;
    const node = holder.firstElementChild;
    oldMain.replaceWith(node);

    if (parsed.title) document.title = parsed.title.textContent;
    if (window.Alpine) window.Alpine.initTree(node);
    if (typeof window.rewireRegions === 'function') window.rewireRegions();
}

/* -------------------------- field errors ---------------------------- */

function fillFieldErrors(form, errors) {
    form.querySelectorAll('[data-ajax-field-error]').forEach((el) => el.remove());
    for (const [name, messages] of Object.entries(errors)) {
        const field = form.querySelector(`[name="${CSS.escape(name)}"]`);
        if (!field) continue;
        const wrap = field.closest('.grid, label') || field;
        const p = document.createElement('p');
        p.className = 'mt-1 text-sm text-red-600';
        p.dataset.ajaxFieldError = '';
        p.textContent = (Array.isArray(messages) ? messages : [messages]).join(' ');
        wrap.after(p);
    }
}

/* ------------------------------ submit ------------------------------ */

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

async function submitForm(form) {
    const method = (form.method || 'get').toUpperCase();
    const body = new FormData(form);

    const res = await fetch(form.action, {
        method,
        body,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
    });

    const isJson = (res.headers.get('content-type') || '').includes('application/json');
    const data = isJson ? await res.json().catch(() => null) : null;

    return { res, data };
}

function wire(form) {
    if (form._ajaxWired) return;
    form._ajaxWired = true;

    form.addEventListener('submit', async (e) => {
        // Dibatalkan handler lain (mis. onsubmit="return confirm(...)")
        if (e.defaultPrevented) return;
        e.preventDefault();
        if (form._ajaxBusy) return;

        if (form.hasAttribute('data-prompt')) {
            const answer = window.prompt(form.getAttribute('data-prompt'));
            if (answer === null) return; // dibatalkan
            const hidden = form.querySelector('input[type="hidden"][name="reason"]');
            if (hidden) hidden.value = answer;
        }

        form._ajaxBusy = true;
        startProgressBar();
        const btn = form.querySelector('button');
        let spinner = null;
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-70');
            // progres: spinner kecil di dalam tombol selama proses
            spinner = document.createElement('span');
            spinner.className = 'inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent';
            spinner.style.marginRight = '0.375rem';
            btn.prepend(spinner);
        }

        let redirectTarget = null;

        try {
            const { res, data } = await submitForm(form);

            if (res.ok) {
                if (form.hasAttribute('data-ajax-redirect')) {
                    // login/register: navigasi penuh ke URL dari server
                    // (tombol tetap disabled sampai browser pindah halaman)
                    showToast(data?.message || 'Berhasil.', 'success');
                    redirectTarget = data?.redirect || '/';
                    window.setTimeout(() => { window.location.href = redirectTarget; }, 700);
                    return;
                }
                showToast(data?.message || data?.data?.message || 'Berhasil.', 'success');
                if (form.hasAttribute('data-no-refresh')) {
                    // mis. subscribe di footer: cukup toast + reset form
                    form.reset();
                    return;
                }
                await refreshContent();
                return;
            }

            if (res.status === 422) {
                showToast(
                    data?.message || 'Data belum valid. Silakan periksa isian Anda.',
                    'error',
                    data?.unverified ? 'Email belum diverifikasi' : 'Validasi'
                );
                if (data?.unverified) {
                    window.dispatchEvent(new CustomEvent('auth:unverified', { detail: data }));
                }
                if (data?.errors) fillFieldErrors(form, data.errors);
                return;
            }

            if (res.status === 419) {
                window.location.reload();
                return;
            }

            showToast(data?.message || 'Terjadi kesalahan. Silakan coba lagi.', 'error', 'Gagal');
        } catch {
            showToast('Koneksi bermasalah. Silakan coba lagi.', 'error', 'Gagal');
        } finally {
            stopProgressBar();
            if (spinner) spinner.remove();
            // tombol diaktifkan kembali kecuali halaman sedang dinavigasi
            if (btn && !redirectTarget) { btn.disabled = false; btn.classList.remove('opacity-70'); }
            form._ajaxBusy = false;
        }
    });
}

export function initAjaxActions(root = document) {
    root.querySelectorAll('form[data-ajax]').forEach(wire);
}
