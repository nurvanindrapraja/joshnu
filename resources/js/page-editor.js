/**
 * Komponen Alpine "pageEditor" — editor HTML TinyMCE (self-hosted) untuk form
 * Page di admin.
 *
 * Dipanggil: x-data="pageEditor('id-textarea', 'nama-modal')"
 *
 * Catatan penting:
 *  - TinyMCE diinisialisasi saat modal PERTAMA KALI dibuka (elemen harus sudah
 *    visible; inisiasi pada elemen display:none menghasilkan iframe rusak).
 *  - sync() dipanggil dari x-on:submit form SEBELUM handler AJAX membaca
 *    FormData, sehingga isi editor tersalin ke textarea lebih dulu.
 *  - this.$el di dalam handler x-on = elemen tempat handler terpasang,
 *    BUKAN root x-data — jadi semua pencarian DOM memakai id eksplisit.
 */
import tinymce from 'tinymce/tinymce';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/models/dom';
import 'tinymce/skins/ui/oxide/skin.min.css';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/table';
import 'tinymce/plugins/code';
import idLang from './tinymce-id-lang';

// Terjemahan Indonesia (harus sebelum inisiasi editor pertama).
tinymce.addI18n('id', idLang);

function initEditor(textareaId) {
    if (tinymce.get(textareaId)) return;
    tinymce.init({
        selector: '#' + textareaId,
        license_key: 'gpl', // self-hosted versi GPL (tanpa lisensi komersial)
        language: 'id',
        height: 320,
        // CSS skin sudah di-bundle Vite (import 'tinymce/skins/...') —
        // matikan injeksi <link> runtime yang ber-404 di lingkungan bundle.
        skin: false,
        menubar: false,
        branding: false,
        promotion: false,
        plugins: 'advlist autolink lists link table code',
        toolbar:
            'undo redo | blocks | bold italic underline strikethrough | ' +
            'bullist numlist | link table | code fullscreen',
        // CSS konten bawaan TinyMCE tidak tersedia di bundle; atur via content_style.
        content_css: false,
        content_style:
            'body { font-family: ui-sans-serif, system-ui, sans-serif; font-size: 14px; color: #1f2937; line-height: 1.6; padding: 8px; } ' +
            'h1 { font-size: 1.5em; font-weight: 700; margin: .6em 0 .3em; } ' +
            'h2 { font-size: 1.3em; font-weight: 700; margin: .6em 0 .3em; } ' +
            'h3 { font-size: 1.1em; font-weight: 600; margin: .5em 0 .3em; } ' +
            'p { margin: .5em 0; } ul { list-style: disc; padding-left: 1.4em; } ol { list-style: decimal; padding-left: 1.4em; } ' +
            'a { color: #1b6e5a; } table { border-collapse: collapse; margin: .5em 0; } th, td { border: 1px solid #d1d5db; padding: 4px 8px; }',
        rel_list: [
            { value: '', text: 'Tidak ada' },
            { value: 'noopener', text: 'noopener (disarankan)' },
        ],
    });
}

export function pageEditorComponent(textareaId, modalName) {
    return {
        init() {
            // Inisiasi (sekali) saat modal terbuka.
            window.addEventListener('open-modal', (e) => {
                if (e.detail !== modalName) return;
                // Tunda 1 tick agar x-show modal sempat menerapkan display.
                setTimeout(() => initEditor(textareaId), 50);
            });

            // "Nama Page" (disabled) terisi otomatis mengikuti "Judul" —
            // user cukup mengetik judul, tidak perlu mengisi dua kali.
            // Nilai terkirim dari input hidden name="name" (input tampilannya
            // disabled sehingga tidak ikut terkirim). Sinkron hanya saat user
            // mengetik (event input) agar saat edit, slug lama tetap terjaga
            // selama judul tidak diubah.
            const titleEl = this.$el.querySelector('input[name="title"]');
            const namePreview = this.$el.querySelector('input[x-ref="namePreview"]');
            const nameHidden = this.$el.querySelector('input[name="name"][type="hidden"]');
            if (titleEl && namePreview && nameHidden) {
                titleEl.addEventListener('input', () => {
                    const v = titleEl.value.trim();
                    namePreview.value = v;
                    nameHidden.value = v;
                });
            }
        },
        // Salin isi editor ke textarea sebelum form (AJAX/natif) dikirim.
        sync() {
            const ed = tinymce.get(textareaId);
            if (ed) ed.save();
        },
    };
}
