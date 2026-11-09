/**
 * Button loading state (global).
 *
 * Semua tombol aksi form (submit) menampilkan spinner + disabled selama
 * proses berlangsung:
 *  - Form non-AJAX (POST/PUT biasa): tombol langsung dinonaktifkan saat
 *    submit sampai browser berpindah halaman. Jika server mengembalikan
 *    validasi (halaman dimuat ulang), state tombol otomatis bersih.
 *  - Form data-ajax : sudah ditangani ajax-actions.js.
 *  - Tombol reset   : ditangani ajax-filter.js (setButtonBusy saat fetch).
 *
 * Handler dipasang di fase bubble (document) sehingga handler inline
 * seperti onsubmit="if(!confirm()) return false" sempat membatalkan
 * submit terlebih dahulu (e.defaultPrevented → tombol tidak dinonaktifkan).
 */

const SPINNER_CLASS =
    'inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent';

export function setButtonBusy(btn, busy) {
    if (!btn) return;
    const existing = btn.querySelector('[data-btn-spinner]');

    if (busy) {
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        if (!existing) {
            const spinner = document.createElement('span');
            spinner.className = SPINNER_CLASS;
            spinner.dataset.btnSpinner = '';
            spinner.setAttribute('aria-hidden', 'true');
            spinner.style.marginRight = '0.375rem';
            btn.prepend(spinner);
        }
        return;
    }

    if (existing) {
        existing.remove();
        btn.disabled = false;
        btn.classList.remove('opacity-70', 'cursor-not-allowed');
    }
}

function isActionButton(el) {
    // Hanya tombol yang benar-benar mengirim form (bukan type="button").
    return !!el && el.tagName === 'BUTTON' && el.type !== 'button';
}

export function installGlobalButtonLoading() {
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        // data-ajax: ajax-actions.js yang mengatur tombolnya.
        if (form.hasAttribute('data-ajax')) return;
        // Submit dibatalkan handler lain (confirm/prompt inline).
        if (e.defaultPrevented) return;

        // e.submitter = tombol yang diklik (bisa null saat submit via Enter
        // pada browser lama → fallback ke tombol pertama dengan semantik
        // submit; tombol tanpa atribut type default-nya submit).
        // JANGAN pernah memilih tombol type="button" (mis. "Batal").
        const submitter = isActionButton(e.submitter)
            ? e.submitter
            : (Array.from(form.querySelectorAll('button')).find(isActionButton) || null);

        setButtonBusy(submitter, true);
    });
}
