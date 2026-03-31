import './bootstrap';

import searchableSelect from './components/searchable-select.js';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// after: import Alpine from 'alpinejs'
Alpine.data('searchableSelect', searchableSelect);