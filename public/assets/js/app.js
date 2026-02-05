/**
 * Je charge les modules JavaScript de l'application MediaTech.
 */

import { initGlobalLoader } from './modules/loader.js';
import { initToasts } from './modules/toasts.js';
import { activateTabFromHash } from './modules/tabs.js';
import { initHomeScroll } from './modules/home_scroll.js';

function initApp() {
  initGlobalLoader();
  initToasts();
  activateTabFromHash();
  initHomeScroll();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}
