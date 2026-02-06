/**
 * LOADER GLOBAL - Gestion automatique du loader de chargement
 * Module ES6
 */

export function initLoader() {
  // Référence au loader
  const loader = document.getElementById('globalLoader');
  
  if (!loader) {
    console.warn('Le loader global (#globalLoader) est introuvable dans le DOM.');
    return;
  }

  // Délai minimum d'affichage du loader (pour éviter les flashs)
  const MIN_DISPLAY_TIME = 800;
  let loaderStartTime = null;

  /**
   * Affiche le loader
   */
  function showLoader() {
    loaderStartTime = Date.now();
    loader.classList.add('is-active');
    loader.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  /**
   * Masque le loader (avec délai minimum)
   */
  function hideLoader() {
    const elapsed = Date.now() - (loaderStartTime || 0);
    const remainingTime = Math.max(0, MIN_DISPLAY_TIME - elapsed);

    setTimeout(() => {
      loader.classList.remove('is-active');
      loader.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }, remainingTime);
  }

  /**
   * Vérifie si un lien doit déclencher le loader
   */
  function shouldShowLoaderForLink(link) {
    const href = link.getAttribute('href');
    
    if (!href || href === '#' || href.startsWith('#')) {
      return false;
    }

    if (link.hasAttribute('data-no-loader')) {
      return false;
    }

    if (link.getAttribute('target') === '_blank') {
      return false;
    }

    if (link.hasAttribute('download')) {
      return false;
    }

    // Vérifier si c'est un lien externe
    try {
      const linkUrl = new URL(href, window.location.origin);
      if (linkUrl.origin !== window.location.origin) {
        return false;
      }
    } catch (e) {
      return false;
    }

    return true;
  }

  /**
   * Vérifie si un formulaire doit déclencher le loader
   */
  function shouldShowLoaderForForm(form) {
    if (form.hasAttribute('data-no-loader')) {
      return false;
    }

    if (form.getAttribute('target') === '_blank') {
      return false;
    }

    return true;
  }

  /**
   * Gestion des clics sur les liens
   */
  document.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    
    if (link && shouldShowLoaderForLink(link)) {
      showLoader();
    }
  }, true);

  /**
   * Gestion de la soumission des formulaires
   */
  document.addEventListener('submit', function(e) {
    const form = e.target;
    
    if (form && shouldShowLoaderForForm(form)) {
      showLoader();
    }
  }, true);

  /**
   * Masquer le loader quand la page est complètement chargée
   */
  window.addEventListener('load', function() {
    hideLoader();
  });

  /**
   * Masquer le loader si la page devient visible
   */
  window.addEventListener('pageshow', function(e) {
    if (e.persisted) {
      hideLoader();
    }
  });

  /**
   * Afficher le loader lors de la navigation avec popstate
   */
  window.addEventListener('popstate', function() {
    showLoader();
  });

  /**
   * Masquer le loader si on quitte la page et qu'on revient
   */
  document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
      setTimeout(hideLoader, 100);
    }
  });

  /**
   * Sécurité : masquer le loader après 30 secondes max
   */
  let safetyTimeout;
  const originalShowLoader = showLoader;
  showLoader = function() {
    originalShowLoader();
    clearTimeout(safetyTimeout);
    safetyTimeout = setTimeout(() => {
      console.warn('Le loader est affiché depuis plus de 30 secondes, masquage forcé.');
      hideLoader();
    }, 30000);
  };

  // Masquer le loader au chargement initial
  hideLoader();

  console.log('Loader global initialisé avec succès');
}

// Export par défaut
export default initLoader;