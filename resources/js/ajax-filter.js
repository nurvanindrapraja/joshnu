/**
 * AJAX filter.
 *
 * Kontrak markup:
 *  - <form data-ajax-filter>        : form filter (GET). Semua field yang berubah
 *                                     langsung memicu fetch tanpa tombol "Terapkan".
 *  - <div data-ajax-results>        : area hasil (tabel/kartu + pagination) yang
 *                                     di-replace saat fetch selesai.
 *  - <a data-ajax-reset>            : link reset (opsional) — di-intercept.
 *
 *Pagination (link dengan path sama dengan form) juga di-intercept agar tetap
 * AJAX. Loading bar tipis muncul di atas viewport selama proses.
 */

import { setButtonBusy } from './button-loading';

const RESULTS_SELECTOR = '[data-ajax-results]';
const BAR_ID = 'ajax-loading-bar';

function ensureLoadingBar() {
    if (document.getElementById(BAR_ID)) return;
    const bar = document.createElement('div');
    bar.id = BAR_ID;
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);
}

function setLoading(on) {
    const bar = document.getElementById(BAR_ID);
    if (bar) bar.classList.toggle('is-active', on);
}

function formParams(form) {
    const params = new URLSearchParams();
    new FormData(form).forEach((value, key) => {
        if (typeof value === 'string' && value.trim() !== '') params.set(key, value);
    });
    // Jaga per_page (biasanya dari pagination) agar tidak hilang saat filter berubah.
    const current = new URL(window.location.href);
    if (current.searchParams.has('per_page') && !params.has('per_page')) {
        params.set('per_page', current.searchParams.get('per_page'));
    }
    return params;
}

// form.reset() hanya mengembalikan field ke atribut value="…" saat halaman
// dimuat, yang bisa berisi filter lama. Kosongkan paksa input teks.
function clearTextInputs(form) {
    form.querySelectorAll('input[type="text"], input[type="search"], input:not([type])')
        .forEach((el) => { el.value = ''; });
}

function wireForm(form) {
    const base = new URL(form.action || window.location.href, window.location.href);
    const basePath = base.origin + base.pathname;
    let active = 0;
    let seq = 0;
    let timer = null;

    const fetchRegion = (url, { push = false } = {}) => {
        const mySeq = ++seq;
        const region = document.querySelector(RESULTS_SELECTOR);
        if (!region) return Promise.resolve();
        active++;
        setLoading(true);
        const started = performance.now();

        return fetch(url, {
            headers: { 'X-Requested-With': 'fetch' },
            credentials: 'same-origin',
        })
            .then((res) => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then((html) => {
                if (mySeq !== seq) return; // respons tertinggal, abaikan
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const fresh = parsed.querySelector(RESULTS_SELECTOR);
                if (fresh) {
                    const holder = document.createElement('div');
                    holder.innerHTML = fresh.outerHTML;
                    const node = holder.firstElementChild;
                    region.replaceWith(node);
                    if (window.Alpine) window.Alpine.initTree(node);
                }
                history[push ? 'pushState' : 'replaceState'](null, '', url);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch(() => {
                if (mySeq === seq) window.location.href = url; // fallback full navigation
            })
            .finally(() => {
                active--;
                const elapsed = performance.now() - started;
                // Tahan sejenak agar bar tidak "kedip" saat respons sangat cepat.
                setTimeout(() => {
                    if (active <= 0) { active = 0; setLoading(false); }
                }, Math.max(0, 300 - elapsed));
            });
    };

    const run = () => {
        const params = formParams(form);
        const url = basePath + (params.toString() ? '?' + params : '');
        fetchRegion(url, { push: false });
    };

    // Cegah submit klasik (mis. tekan Enter di input teks).
    form.addEventListener('submit', (e) => e.preventDefault());

    // Select & date & number: langsung saat berubah.
    form
        .querySelectorAll('select, input[type="date"], input[type="number"], input[type="month"]')
        .forEach((el) => el.addEventListener('change', run));

    // Input teks: debounce.
    form
        .querySelectorAll('input[type="text"], input[type="search"], input:not([type])')
        .forEach((el) => {
            el.addEventListener('input', () => {
                if (timer) clearTimeout(timer);
                timer = setTimeout(run, 400);
            });
        });

    form._ajaxFilter = { basePath, fetch: (url, opts) => fetchRegion(url, opts) };
}

// Tombol reset (button) + pagination/reset link: intercept klik.
document.addEventListener('click', (e) => {
    const form = document.querySelector('form[data-ajax-filter]');
    if (!form || !form._ajaxFilter) return;

    const resetBtn = e.target.closest('button[data-ajax-reset]');
    if (resetBtn) {
        e.preventDefault();
        form.reset();
        clearTextInputs(form);
        setButtonBusy(resetBtn, true);
        form._ajaxFilter.fetch(form._ajaxFilter.basePath, { push: false })
            .finally(() => setButtonBusy(resetBtn, false));
        return;
    }

    const a = e.target.closest('a');
    if (!a || a.target === '_blank' || a.hasAttribute('download')) return;
    let url;
    try {
        url = new URL(a.href, window.location.href);
    } catch {
        return;
    }
    const { basePath, fetch: doFetch } = form._ajaxFilter;
    const isReset = a.hasAttribute('data-ajax-reset');
    const isSamePage = url.origin + url.pathname === basePath;
    if (!isReset && !isSamePage) return;
    e.preventDefault();
    if (isReset) {
        form.reset();
        clearTextInputs(form);
    }
    doFetch(isReset ? basePath : url.pathname + url.search, { push: !isReset });
});

// Tombol back/forward browser.
window.addEventListener('popstate', () => {
    const form = document.querySelector('form[data-ajax-filter]');
    if (form && form._ajaxFilter) {
        form._ajaxFilter.fetch(window.location.pathname + window.location.search);
    }
});

export function initAjaxFilters() {
    ensureLoadingBar();
    document.querySelectorAll('form[data-ajax-filter]').forEach((f) => {
        if (!f._ajaxFilter) wireForm(f);
    });
}
