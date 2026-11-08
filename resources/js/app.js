import './bootstrap';
import './image-compress';

import Alpine from 'alpinejs';

import { initAjaxFilters } from './ajax-filter';
import { initAjaxActions } from './ajax-actions';

window.Alpine = Alpine;

Alpine.start();

// Kaitkan ulang form AJAX + filter setelah konten <main> di-refresh via AJAX.
const rewireRegions = () => {
    initAjaxFilters();
    initAjaxActions();
};
window.rewireRegions = rewireRegions;

rewireRegions();
