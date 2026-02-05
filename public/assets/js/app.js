/**
 * app.js
 * Script principal de l'application MediaTech.
 * Gère le loader global et les messages flash (toasts).
 */

(function () {
    'use strict';

    /**
     * ========================================
     * LOADER GLOBAL
     * ========================================
     */

    const Loader = {
        element: null,
        isShowing: false,

        /**
         * J'initialise le loader en récupérant l'élément DOM.
         */
        init: function () {
            this.element = document.getElementById('global-loader');
        },

        /**
         * J'affiche le loader avec une animation.
         * @param {string} message - Message optionnel à afficher.
         */
        show: function (message) {
            if (!this.element || this.isShowing) return;

            this.isShowing = true;

            const textElement = this.element.querySelector('.loader-text');
            if (textElement) {
                textElement.textContent = message || 'Chargement en cours...';
            }

            this.element.style.display = 'flex';

            // Force reflow pour l'animation
            this.element.offsetHeight;

            this.element.classList.add('show');
        },

        /**
         * Je masque le loader avec une animation.
         */
        hide: function () {
            if (!this.element) return;

            this.isShowing = false;
            this.element.classList.remove('show');

            setTimeout(() => {
                this.element.style.display = 'none';
            }, 200);
        }
    };

    /**
     * ========================================
     * TOASTS (MESSAGES FLASH)
     * ========================================
     */

    const Toasts = {
        container: null,

        /**
         * J'initialise les toasts existants dans le DOM.
         */
        init: function () {
            this.container = document.querySelector('.toast-container');
            if (!this.container) return;

            const toastElements = document.querySelectorAll('.toast');
            toastElements.forEach(function (toastEl) {
                if (typeof bootstrap === 'undefined' || !bootstrap.Toast) return;

                const toast = new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 5000
                });

                toast.show();
            });
        },

        /**
         * Je crée et affiche un nouveau toast dynamiquement.
         * @param {string} type - Type du message (success, danger, warning, info).
         * @param {string} message - Message à afficher.
         * @param {string} link - Lien optionnel.
         * @param {string} linkLabel - Libellé du lien (défaut: "Voir").
         */
        create: function (type, message, link, linkLabel) {
            if (!this.container) {
                this.container = document.querySelector('.toast-container');
            }
            if (!this.container) return;
            if (typeof bootstrap === 'undefined' || !bootstrap.Toast) return;

            const icons = {
                success:
                    '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>',
                danger:
                    '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z"/></svg>',
                warning:
                    '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/></svg>',
                info:
                    '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/></svg>'
            };

            const colors = {
                success: 'text-bg-success',
                danger: 'text-bg-danger',
                warning: 'text-bg-warning',
                info: 'text-bg-info'
            };

            linkLabel = linkLabel || 'Voir';

            let linkHtml = '';
            if (link) {
                linkHtml =
                    '<a href="' +
                    link +
                    '" class="text-white fw-semibold text-decoration-underline mt-1 d-inline-block">' +
                    linkLabel +
                    '</a>';
            }

            const toastHtml = `
                <div class="toast align-items-center ${colors[type] || 'text-bg-secondary'} border-0"
                     role="alert"
                     aria-live="assertive"
                     aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-start gap-2">
                            <span class="flex-shrink-0">${icons[type] || ''}</span>
                            <div>
                                <div>${message}</div>
                                ${linkHtml}
                            </div>
                        </div>
                        <button type="button"
                                class="btn-close btn-close-white me-2 m-auto"
                                data-bs-dismiss="toast"
                                aria-label="Fermer"></button>
                    </div>
                </div>
            `;

            this.container.insertAdjacentHTML('beforeend', toastHtml);

            const newToast = this.container.lastElementChild;
            const toast = new bootstrap.Toast(newToast, {
                autohide: true,
                delay: 5000
            });

            toast.show();
        }
    };

    /**
     * ========================================
     * MESSAGES CONTEXTUELS POUR LE LOADER
     * ========================================
     */

    const LoaderMessages = {
        /**
         * Je détermine le message à afficher selon le contexte du formulaire.
         * @param {HTMLFormElement} form - Le formulaire soumis.
         * @returns {string} - Le message approprié.
         */
        getFormMessage: function (form) {
            const action = form.getAttribute('action') || '';
            const formId = form.getAttribute('id') || '';

            // Connexion
            if (action.includes('login') || formId.includes('login')) {
                return 'Connexion en cours...';
            }

            // Inscription
            if (action.includes('register') || formId.includes('register')) {
                return 'Création du compte...';
            }

            // Réinitialisation mot de passe
            if (action.includes('reset-password') || action.includes('reset_password')) {
                return 'Traitement en cours...';
            }

            // Contact
            if (action.includes('contact')) {
                return 'Envoi du message...';
            }

            // Profil
            if (action.includes('profile')) {
                return 'Mise à jour du profil...';
            }

            // Collection
            if (action.includes('collection')) {
                return 'Mise à jour de la collection...';
            }

            // Commentaire
            if (action.includes('comment')) {
                return 'Publication du commentaire...';
            }

            // Recherche
            if (action.includes('search') || action.includes('catalog')) {
                return 'Recherche en cours...';
            }

            // Ajout média
            if (action.includes('media') && action.includes('add')) {
                return 'Ajout en cours...';
            }

            // Wishlist
            if (action.includes('wishlist')) {
                return 'Mise à jour de la liste...';
            }

            // Admin
            if (action.includes('admin')) {
                return 'Traitement administrateur...';
            }

            // Défaut
            return 'Traitement en cours...';
        },

        /**
         * Je détermine le message à afficher selon le lien cliqué.
         * @param {HTMLAnchorElement} link - Le lien cliqué.
         * @returns {string} - Le message approprié.
         */
        getLinkMessage: function (link) {
            const href = link.getAttribute('href') || '';

            // Pages spécifiques
            if (href.includes('login')) {
                return 'Accès à la connexion...';
            }
            if (href.includes('register')) {
                return 'Accès à l\'inscription...';
            }
            if (href.includes('profile')) {
                return 'Chargement du profil...';
            }
            if (href.includes('admin')) {
                return 'Accès à l\'administration...';
            }
            if (href.includes('catalog')) {
                return 'Chargement du catalogue...';
            }
            if (href.includes('collection')) {
                return 'Chargement de la collection...';
            }
            if (href.includes('book') || href.includes('movie')) {
                return 'Chargement du média...';
            }

            return 'Chargement...';
        }
    };

    /**
     * ========================================
     * GESTIONNAIRE D'ÉVÉNEMENTS
     * ========================================
     */

    const EventHandlers = {
        /**
         * J'attache les événements du loader à TOUS les formulaires.
         */
        bindForms: function () {
            document.querySelectorAll('form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    // Ignorer les formulaires avec la classe no-loader
                    if (form.classList.contains('no-loader')) return;

                    // Ignorer si le formulaire est invalide
                    if (!form.checkValidity()) return;

                    // Désactiver le bouton submit pour éviter les doubles soumissions
                    const submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                    }

                    // Afficher le loader avec message contextuel
                    const message = LoaderMessages.getFormMessage(form);
                    Loader.show(message);
                });
            });
        },

        /**
         * J'attache les événements du loader à TOUS les liens de navigation.
         * Exceptions : liens externes, ancres, nouvels onglets, liens avec no-loader.
         */
        bindLinks: function () {
            document.addEventListener('click', function (e) {
                const link = e.target.closest('a');
                if (!link) return;

                // Ignorer si le lien a la classe no-loader
                if (link.classList.contains('no-loader')) return;

                // Ignorer les liens qui ouvrent dans un nouvel onglet
                if (link.target === '_blank') return;

                // Ignorer les liens javascript:
                const href = link.getAttribute('href') || '';
                if (href.startsWith('javascript:')) return;

                // Ignorer les ancres pures
                if (href.startsWith('#')) return;

                // Ignorer les liens vides
                if (href === '' || href === '#') return;

                // Ignorer les liens avec data-bs-toggle (modales, dropdowns, tabs, etc.)
                if (link.hasAttribute('data-bs-toggle')) return;

                // Ignorer les liens externes
                if (href.startsWith('http') && !href.includes(window.location.hostname)) return;

                // Ignorer les liens mailto et tel
                if (href.startsWith('mailto:') || href.startsWith('tel:')) return;

                // Afficher le loader avec message contextuel
                const message = LoaderMessages.getLinkMessage(link);
                Loader.show(message);
            });
        },

        /**
         * J'attache les événements pour les modales avec loader.
         */
        bindModals: function () {
            // Cacher le loader quand une modale s'ouvre
            document.addEventListener('shown.bs.modal', function () {
                Loader.hide();
            });

            // Gérer la soumission des formulaires dans les modales
            document.querySelectorAll('.modal form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    if (!form.checkValidity()) return;

                    const submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                    }

                    // Fermer la modale
                    const modal = form.closest('.modal');
                    if (modal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) {
                            bsModal.hide();
                        }
                    }

                    const message = LoaderMessages.getFormMessage(form);
                    Loader.show(message);
                });
            });
        },

        /**
         * Je gère l'activation de l'onglet si un hash est présent dans l'URL.
         * Ex: /profile?section=collections#tab-unlisted
         */
        handleTabFromHash: function () {
            const hash = window.location.hash;
            if (!hash) return;

            // Chercher le bouton de l'onglet correspondant
            const tabButton = document.querySelector('[data-bs-target="' + hash + '"]');
            if (tabButton && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                const tab = new bootstrap.Tab(tabButton);
                tab.show();
            }
        }
    };

    /**
     * ========================================
     * INITIALISATION
     * ========================================
     */

    document.addEventListener('DOMContentLoaded', function () {
        Loader.init();
        Toasts.init();

        EventHandlers.bindForms();
        EventHandlers.bindLinks();
        EventHandlers.bindModals();
        EventHandlers.handleTabFromHash();

        // S'assurer que le loader est caché au chargement initial
        Loader.hide();
    });

    // Cacher le loader quand la page est complètement chargée
    window.addEventListener('load', function () {
        Loader.hide();
    });

    // Gérer le retour arrière du navigateur (bouton back)
    window.addEventListener('pageshow', function (event) {
        // Si la page vient du cache (back/forward)
        if (event.persisted) {
            Loader.hide();

            // Réactiver tous les boutons submit désactivés
            document.querySelectorAll('button[type="submit"]:disabled').forEach(function (btn) {
                btn.disabled = false;
            });
        }
    });

    // Cacher le loader si l'utilisateur quitte la page
    window.addEventListener('pagehide', function () {
        Loader.hide();
    });

    // Exposer les objets pour un usage externe
    window.MediaTech = {
        Loader: Loader,
        Toasts: Toasts
    };
})();