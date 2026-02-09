/**
 * Je charge les modules JavaScript de l'application MediaTech.
 */

// Import des modules existants
import { initLoader } from './modules/loader.js';
import { initToasts } from './modules/toasts.js';
import { activateTabFromHash } from './modules/tabs.js';
import { initBulkMove } from './modules/bulk-move.js';
import { initHomeScroll } from './modules/home_scroll.js';

// Import des nouveaux modules (extraction du JS inline)
import { initViewToggle } from './modules/view-toggle.js';
import { initGenreSelector } from './modules/genre-selector.js';
import { initFormAutoSubmit } from './modules/form-auto-submit.js';

function initApp() {
  // Modules existants
  initLoader();
  initToasts();
  activateTabFromHash();
  initBulkMove();
  initHomeScroll();
  
  // Nouveaux modules
  initViewToggle();
  initGenreSelector();
  initFormAutoSubmit();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}