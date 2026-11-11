import './bootstrap';
import './image-compress';

import Alpine from 'alpinejs';

import { initAjaxFilters } from './ajax-filter';
import { initAjaxActions } from './ajax-actions';
import { installGlobalButtonLoading } from './button-loading';
import { pageEditorComponent } from './page-editor';

window.Alpine = Alpine;

// Komponen page editor harus didaftarkan sebelum Alpine.start()
// agar elemen x-data="pageEditor()" sudah menemukannya saat init.
Alpine.data('pageEditor', pageEditorComponent);

Alpine.start();

// Kaitkan ulang form AJAX + filter setelah konten <main> di-refresh via AJAX.
const rewireRegions = () => {
    initAjaxFilters();
    initAjaxActions();
};
window.rewireRegions = rewireRegions;

rewireRegions();

// Spinner + disable pada semua tombol submit (form non-AJAX).
// Sekali per halaman — listener menempel di document, bukan di <main>.
installGlobalButtonLoading();
