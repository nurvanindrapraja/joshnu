import './bootstrap';
import './image-compress';

import Alpine from 'alpinejs';

import { initAjaxFilters } from './ajax-filter';
import { initAjaxActions } from './ajax-actions';
import { installGlobalButtonLoading } from './button-loading';

window.Alpine = Alpine;

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
