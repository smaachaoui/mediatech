/**
 * MODULE : Sélection multiple pour déplacer des éléments
 * Gère les checkboxes et le formulaire de déplacement en masse
 */

export function initBulkMove() {
  const form = document.getElementById('bulkMoveForm');
  if (!form) return; // Pas sur la bonne page

  const checkboxes = document.querySelectorAll('.item-checkbox');
  const selectedCountEl = document.getElementById('selectedCount');
  const bulkMoveBtn = document.getElementById('bulkMoveBtn');
  const selectAllBtn = document.getElementById('selectAllBtn');
  const deselectAllBtn = document.getElementById('deselectAllBtn');

  /**
   * Met à jour le compteur et l'état du bouton
   */
  function updateSelectionUI() {
    const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
    const totalCount = checkboxes.length;
    
    selectedCountEl.textContent = checkedCount;
    bulkMoveBtn.disabled = checkedCount === 0;

    // Gérer l'affichage des boutons select/deselect all
    if (checkedCount === totalCount && totalCount > 0) {
      selectAllBtn.style.display = 'none';
      deselectAllBtn.style.display = 'inline-block';
    } else {
      selectAllBtn.style.display = 'inline-block';
      deselectAllBtn.style.display = 'none';
    }
  }

  /**
   * Sélectionner tous les éléments
   */
  function selectAll() {
    checkboxes.forEach(cb => cb.checked = true);
    updateSelectionUI();
  }

  /**
   * Désélectionner tous les éléments
   */
  function deselectAll() {
    checkboxes.forEach(cb => cb.checked = false);
    updateSelectionUI();
  }

  /**
   * Validation avant soumission
   */
  function validateSubmission(e) {
    const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
    
    if (checkedCount === 0) {
      e.preventDefault();
      alert('Veuillez sélectionner au moins un élément.');
      return;
    }

    const collectionSelect = document.getElementById('bulkCollectionSelect');
    if (!collectionSelect.value) {
      e.preventDefault();
      alert('Veuillez sélectionner une collection de destination.');
      return;
    }

    // Confirmation avant déplacement
    const confirmation = confirm(
      `Déplacer ${checkedCount} élément${checkedCount > 1 ? 's' : ''} vers la collection sélectionnée ?`
    );
    
    if (!confirmation) {
      e.preventDefault();
    }
  }

  // Événements
  selectAllBtn.addEventListener('click', selectAll);
  deselectAllBtn.addEventListener('click', deselectAll);
  checkboxes.forEach(cb => cb.addEventListener('change', updateSelectionUI));
  form.addEventListener('submit', validateSubmission);

  // Initialisation
  updateSelectionUI();

  console.log(' Le module de sélection multiple est initialisé');
}

export default initBulkMove;