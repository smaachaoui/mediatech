/**
 * Je gère le loader global de l'application.
 */

const DEFAULT_MESSAGE = 'Chargement…';

function getLoaderEl() {
  return document.getElementById('global-loader');
}

function setMessage(el, message) {
  const text = el.querySelector('.global-loader__text');
  if (text) {
    text.textContent = message || DEFAULT_MESSAGE;
  }
}

export function showLoader(message) {
  const el = getLoaderEl();
  if (!el) return;

  setMessage(el, message);
  el.classList.add('is-active');
  el.setAttribute('aria-hidden', 'false');
}

export function hideLoader() {
  const el = getLoaderEl();
  if (!el) return;

  el.classList.remove('is-active');
  el.setAttribute('aria-hidden', 'true');
}

function isModifiedClick(event) {
  return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
}

function isExternalLink(a) {
  try {
    const url = new URL(a.href, window.location.href);
    return url.origin !== window.location.origin;
  } catch (e) {
    return true;
  }
}

function shouldIgnoreLink(a) {
  const href = a.getAttribute('href') || '';

  if (!href || href === '#') return true;
  if (href.startsWith('#')) return true;
  if (href.startsWith('javascript:')) return true;
  if (href.startsWith('mailto:') || href.startsWith('tel:')) return true;
  if ((a.getAttribute('target') || '').toLowerCase() === '_blank') return true;
  if (a.hasAttribute('download')) return true;
  if (a.hasAttribute('data-bs-toggle')) return true;
  if (a.getAttribute('data-no-loader') === '1') return true;
  if (a.classList.contains('no-loader')) return true;
  if (isExternalLink(a)) return true;

  return false;
}

function getFormMessage(form) {
  const action = (form.getAttribute('action') || '').toLowerCase();
  const formId = (form.getAttribute('id') || '').toLowerCase();

  if (action.includes('login') || formId.includes('login')) return 'Connexion en cours…';
  if (action.includes('register') || formId.includes('register')) return 'Création du compte…';
  if (action.includes('reset-password') || action.includes('reset_password')) return 'Traitement en cours…';
  if (action.includes('contact')) return 'Envoi du message…';
  if (action.includes('profile')) return 'Mise à jour du profil…';
  if (action.includes('collection')) return 'Mise à jour de la collection…';
  if (action.includes('comment')) return 'Publication du commentaire…';
  if (action.includes('search') || action.includes('catalog')) return 'Recherche en cours…';
  if (action.includes('media') && action.includes('add')) return 'Ajout en cours…';
  if (action.includes('wishlist')) return 'Mise à jour de la liste…';
  if (action.includes('admin')) return 'Traitement administrateur…';

  return 'Traitement en cours…';
}

function getLinkMessage(a) {
  const href = (a.getAttribute('href') || '').toLowerCase();

  if (href.includes('login')) return 'Accès à la connexion…';
  if (href.includes('register')) return 'Accès à l\'inscription…';
  if (href.includes('profile')) return 'Chargement du profil…';
  if (href.includes('admin')) return 'Accès à l\'administration…';
  if (href.includes('catalog')) return 'Chargement du catalogue…';
  if (href.includes('collection')) return 'Chargement de la collection…';
  if (href.includes('book') || href.includes('movie')) return 'Chargement du média…';

  return DEFAULT_MESSAGE;
}

export function initGlobalLoader() {
  window.addEventListener('pageshow', () => hideLoader());

  document.addEventListener(
    'submit',
    (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) return;

      if (form.getAttribute('data-no-loader') === '1' || form.classList.contains('no-loader')) return;
      if (!form.checkValidity()) return;

      const submitBtn = form.querySelector('[type="submit"]');
      if (submitBtn instanceof HTMLButtonElement || submitBtn instanceof HTMLInputElement) {
        submitBtn.disabled = true;
      }

      showLoader(getFormMessage(form));
    },
    true
  );

  document.addEventListener(
    'click',
    (event) => {
      if (isModifiedClick(event)) return;

      const target = event.target;
      if (!(target instanceof Element)) return;

      const a = target.closest('a');
      if (!a) return;
      if (shouldIgnoreLink(a)) return;

      showLoader(getLinkMessage(a));
    },
    true
  );

  document.addEventListener('shown.bs.modal', () => hideLoader());
}
