/**
 * MODULE VIEW TOGGLE - Basculement entre affichage grille et liste
 * Gère le mode d'affichage pour le catalogue et les collections
 */

export function initViewToggle() {
  const btnGrid = document.getElementById('btnGrid');
  const btnList = document.getElementById('btnList');

  if (!btnGrid || !btnList) {
    // Pas sur une page avec view toggle
    console.log('Boutons Grid/List non trouvés (page sans toggle)');
    return;
  }

  console.log('🔍 Boutons trouvés:', { btnGrid, btnList });

  /**
   * Détermine la clé localStorage selon la page
   */
  function getStorageKey() {
    // Si on est sur la page collections
    if (window.location.pathname.includes('/collections')) {
      return 'collectionsViewMode';
    }
    // Sinon on est sur le catalogue
    return 'catalogViewMode';
  }

  /**
   * Applique le mode d'affichage (grid ou list)
   */
  function setViewMode(mode) {
    console.log('setViewMode appelé:', mode);
    
    const gridViews = document.querySelectorAll('.view-grid');
    const listViews = document.querySelectorAll('.view-list');

    console.log(' Éléments trouvés:', { 
      gridViews: gridViews.length, 
      listViews: listViews.length 
    });

    if (mode === 'list') {
      btnList.classList.add('active');
      btnGrid.classList.remove('active');
      gridViews.forEach((el) => el.classList.add('d-none'));
      listViews.forEach((el) => el.classList.remove('d-none'));
      localStorage.setItem(getStorageKey(), 'list');
    } else {
      btnGrid.classList.add('active');
      btnList.classList.remove('active');
      gridViews.forEach((el) => el.classList.remove('d-none'));
      listViews.forEach((el) => el.classList.add('d-none'));
      localStorage.setItem(getStorageKey(), 'grid');
    }
    
    console.log('Mode appliqué:', mode);
  }

  // Événements sur les boutons
  btnGrid.addEventListener('click', (e) => {
    console.log('Clic sur btnGrid');
    e.preventDefault();
    setViewMode('grid');
  });
  
  btnList.addEventListener('click', (e) => {
    console.log('Clic sur btnList');
    e.preventDefault();
    setViewMode('list');
  });

  // Restaurer le mode sauvegardé au chargement
  const savedMode = localStorage.getItem(getStorageKey());
  if (savedMode) {
    console.log('Mode sauvegardé trouvé:', savedMode);
    setViewMode(savedMode);
  } else {
    console.log(' Pas de mode sauvegardé, mode par défaut: grid');
  }

  console.log(' Module view-toggle initialisé');
}

export default initViewToggle;