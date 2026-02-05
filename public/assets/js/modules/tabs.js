/**
 * Je gère l'activation d'un onglet Bootstrap depuis l'URL (hash).
 */

function getBootstrapTab() {
  return window.bootstrap && window.bootstrap.Tab ? window.bootstrap.Tab : null;
}

export function activateTabFromHash() {
  const Tab = getBootstrapTab();
  if (!Tab) return;

  const hash = window.location.hash;
  if (!hash) return;

  const tabButton = document.querySelector('[data-bs-target="' + hash + '"]');
  if (!tabButton) return;

  const tab = new Tab(tabButton);
  tab.show();
}
