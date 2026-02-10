/**
 * MODULE FORM AUTO SUBMIT - Soumission automatique des formulaires
 * Gère les selects qui déclenchent automatiquement la soumission du formulaire
 */

export function initFormAutoSubmit() {
  // Sélectionne tous les éléments avec l'attribut data-auto-submit
  const autoSubmitElements = document.querySelectorAll('[data-auto-submit="true"]');

  if (autoSubmitElements.length === 0) {
    // Pas d'éléments auto-submit sur cette page
    return;
  }

  autoSubmitElements.forEach((element) => {
    element.addEventListener('change', function() {
      // Trouve le formulaire parent
      const form = this.closest('form');
      
      if (form) {
        form.submit();
      }
    });
  });

  console.log(`✓ Module form-auto-submit initialisé (${autoSubmitElements.length} élément(s))`);
}

export default initFormAutoSubmit;