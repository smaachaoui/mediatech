/**
 * Je gère l'affichage des messages flash Bootstrap (toasts).
 */

function getBootstrapToast() {
  return window.bootstrap && window.bootstrap.Toast ? window.bootstrap.Toast : null;
}

export function initToasts() {
  const Toast = getBootstrapToast();
  if (!Toast) return;

  const toastElements = document.querySelectorAll('.toast');
  toastElements.forEach((toastEl) => {
    const toast = new Toast(toastEl, {
      autohide: true,
      delay: 5000,
    });

    toast.show();
  });
}
