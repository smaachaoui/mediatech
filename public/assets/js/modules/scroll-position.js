/**
 * MODULE SCROLL POSITION - Sauvegarde et restaure la position du scroll
 * Évite de remonter en haut de la page après soumission de formulaire
 */

export function initScrollPosition() {
  const SCROLL_KEY = 'catalogScrollPosition';

  /**
   * Sauvegarde la position du scroll avant la soumission
   */
  function saveScrollPosition() {
    sessionStorage.setItem(SCROLL_KEY, window.scrollY.toString());
  }

  /**
   * Restaure la position du scroll après le chargement de la page
   */
  function restoreScrollPosition() {
    const savedPosition = sessionStorage.getItem(SCROLL_KEY);
    
    if (savedPosition !== null) {
      // Petit délai pour s'assurer que le DOM est complètement chargé
      setTimeout(() => {
        window.scrollTo(0, parseInt(savedPosition, 10));
        sessionStorage.removeItem(SCROLL_KEY);
      }, 100);
    }
  }

  /**
   * Attache les événements sur tous les formulaires de filtres
   */
  function attachFormListeners() {
    const filterForms = document.querySelectorAll('form#filterForm');
    
    filterForms.forEach((form) => {
      form.addEventListener('submit', saveScrollPosition);
    });
  }

  // Restaure la position au chargement de la page
  restoreScrollPosition();

  // Attache les listeners sur les formulaires
  attachFormListeners();

  console.log('✓ Module scroll-position initialisé');
}

export default initScrollPosition;