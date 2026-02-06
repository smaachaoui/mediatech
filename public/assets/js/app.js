/**
 * Je charge les modules JavaScript de l'application MediaTech.
 */

// Import du module loader
import { initLoader } from './modules/loader.js';
import { initToasts } from './modules/toasts.js';
import { activateTabFromHash } from './modules/tabs.js';
import { initBulkMove } from './modules/bulk-move.js';
import { initHomeScroll } from './modules/home_scroll.js';



function initApp() {
  initLoader();
  initToasts();
  activateTabFromHash();
  initBulkMove();
  initHomeScroll();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}
