/**
 * EYETWIN — ACCESSIBILITY JS v1
 * WCAG 2.1 AA — Screen readers, keyboard nav, ARIA live
 * À charger APRÈS jQuery et Bootstrap
 */
(function () {
    'use strict';

    /* ══════════════════════════════════════════
       1. DÉTECTION MODE CLAVIER vs SOURIS
       Désactive le cursor canvas si clavier utilisé
    ══════════════════════════════════════════ */
    let isKeyboard = false;
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
            isKeyboard = true;
            document.body.classList.add('et-keyboard-mode');
            // Réactiver le curseur natif pour navigation clavier
            const canvas = document.getElementById('et-cursor-canvas');
            if (canvas) canvas.style.display = 'none';
            document.body.style.cursor = 'auto';
            document.querySelectorAll('*, a, button, [role="button"]').forEach(el => {
                el.style.cursor = '';
            });
        }
    });
    document.addEventListener('mousedown', () => {
        if (isKeyboard) {
            isKeyboard = false;
            document.body.classList.remove('et-keyboard-mode');
            const canvas = document.getElementById('et-cursor-canvas');
            if (canvas) canvas.style.display = '';
        }
    });

    /* ══════════════════════════════════════════
       2. ARIA LIVE — Annonces screen reader
    ══════════════════════════════════════════ */
    window.etAnnounce = function (message, priority = 'polite') {
        const id = priority === 'assertive' ? 'et-aria-assertive' : 'et-aria-polite';
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = '';
        setTimeout(() => { el.textContent = message; }, 80);
    };

    /* ══════════════════════════════════════════
       3. NAVIGATION CLAVIER — Dropdown cursor panel
    ══════════════════════════════════════════ */
    const panel = document.getElementById('et-cursor-panel');
    const panelBtn = document.getElementById('et-cursor-btn');
    const opts = document.querySelectorAll('.et-cursor-opt');

    if (panel && opts.length) {
        // Rendre les options focusables
        opts.forEach((opt, i) => {
            opt.setAttribute('tabindex', '0');
            opt.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    opt.click();
                }
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    opts[Math.min(i + 1, opts.length - 1)].focus();
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    opts[Math.max(i - 1, 0)].focus();
                }
                if (e.key === 'Escape') {
                    panel.classList.remove('open');
                    panelBtn.focus();
                }
            });
        });

        // Focus dans le panel à l'ouverture
        if (panelBtn) {
            panelBtn.addEventListener('click', () => {
                if (panel.classList.contains('open')) {
                    setTimeout(() => opts[0]?.focus(), 50);
                }
            });
        }
    }

    /* ══════════════════════════════════════════
       4. NOTIFICATIONS — Mark read accessible
    ══════════════════════════════════════════ */
    document.querySelectorAll('.notif-item form button[type="submit"]').forEach(btn => {
        btn.addEventListener('click', () => {
            etAnnounce('Notification marquée comme lue', 'polite');
        });
    });

    /* ══════════════════════════════════════════
       5. FLASH MESSAGES — Auto-dismiss + annonce
    ══════════════════════════════════════════ */
    document.querySelectorAll('.alert').forEach(alert => {
        const msg = alert.textContent.trim();
        if (msg) etAnnounce(msg, alert.classList.contains('alert-danger') ? 'assertive' : 'polite');

        // Auto-dismiss après 8s (avec annonce)
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.transition = 'opacity .4s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 400);
            }
        }, 8000);
    });

    /* ══════════════════════════════════════════
       6. DROPDOWN BOOTSTRAP — Sync aria-expanded
    ══════════════════════════════════════════ */
    document.querySelectorAll('[data-toggle="dropdown"]').forEach(trigger => {
        trigger.addEventListener('click', function () {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', String(!expanded));
        });
        // Bootstrap ferme le dropdown : sync
        $(trigger).closest('.dropdown').on('hide.bs.dropdown', () => {
            trigger.setAttribute('aria-expanded', 'false');
        });
        $(trigger).closest('.dropdown').on('show.bs.dropdown', () => {
            trigger.setAttribute('aria-expanded', 'true');
        });
    });

    /* ══════════════════════════════════════════
       7. PROFILE DROPDOWN — Focus trap
    ══════════════════════════════════════════ */
    const profileMenu = document.querySelector('.et-profile-menu');
    if (profileMenu) {
        const profileTrigger = document.getElementById('etProfileDropdown');

        profileMenu.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                $(profileTrigger).dropdown('hide');
                profileTrigger.focus();
            }
        });

        // Navigation flèches dans le menu profil
        const menuItems = profileMenu.querySelectorAll('.et-pd-item');
        menuItems.forEach((item, i) => {
            item.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    menuItems[Math.min(i + 1, menuItems.length - 1)].focus();
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (i === 0) profileTrigger?.focus();
                    else menuItems[i - 1].focus();
                }
            });
        });
    }

    /* ══════════════════════════════════════════
       8. SCROLL REVEAL — Respecter prefers-reduced-motion
    ══════════════════════════════════════════ */
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
        // Rendre tous les éléments visibles immédiatement
        document.querySelectorAll('[style*="opacity: 0"]').forEach(el => {
            el.style.opacity = '1';
            el.style.transform = 'none';
            el.style.transition = 'none';
        });
    }

    /* ══════════════════════════════════════════
       9. PAGE LOADER — Accessible
    ══════════════════════════════════════════ */
    const loader = document.getElementById('page-loader');
    if (loader) {
        // Annoncer la fin du chargement
        document.body.setAttribute('aria-busy', 'true');
        setTimeout(() => {
            document.body.setAttribute('aria-busy', 'false');
            etAnnounce('Page EyeTwin chargée et prête', 'polite');
        }, 1200); // correspond à l'animation loader-out
    }

    /* ══════════════════════════════════════════
       10. COINS PILL — Accessible balance display
    ══════════════════════════════════════════ */
    const coinsPill = document.querySelector('.nav-coins-pill');
    if (coinsPill) {
        const val = coinsPill.textContent.trim().replace(/[^0-9]/g, '');
        coinsPill.setAttribute('aria-label', `Solde de pièces : ${val}`);
    }

    /* ══════════════════════════════════════════
       11. ICONES DÉCORATIVES — aria-hidden auto
    ══════════════════════════════════════════ */
    // Ajouter aria-hidden sur les icônes Font Awesome décoratives
    document.querySelectorAll('i.fas, i.fab, i.far').forEach(icon => {
        if (!icon.getAttribute('aria-label') && !icon.getAttribute('aria-labelledby')) {
            icon.setAttribute('aria-hidden', 'true');
        }
    });

    /* ══════════════════════════════════════════
       12. NOTIFICATION BELL — Aria-label dynamique
    ══════════════════════════════════════════ */
    const bellTrigger = document.getElementById('notifDropdown');
    const badge = document.querySelector('.nav-bell .badge');
    if (bellTrigger) {
        const count = badge ? badge.textContent.trim() : '0';
        bellTrigger.setAttribute('aria-label',
            count > 0
                ? `Notifications : ${count} non lues`
                : 'Notifications : aucune nouvelle'
        );
    }

    /* ══════════════════════════════════════════
       13. LIVE DOT — Texte alternatif
    ══════════════════════════════════════════ */
    document.querySelectorAll('.nav-live-dot').forEach(dot => {
        dot.setAttribute('aria-hidden', 'true');
    });
    const liveLink = document.querySelector('.nav-live-link');
    if (liveLink && !liveLink.getAttribute('aria-label')) {
        liveLink.setAttribute('aria-label', 'Live — Streams en direct');
    }

    /* ══════════════════════════════════════════
       14. FOOTER SOCIAL ICONS — Labels
    ══════════════════════════════════════════ */
    const socialLabels = {
        'fa-facebook-f': 'Facebook EyeTwin',
        'fa-twitter':    'Twitter EyeTwin',
        'fa-instagram':  'Instagram EyeTwin',
        'fa-discord':    'Discord EyeTwin',
    };
    document.querySelectorAll('.single_social_icon').forEach(link => {
        const icon = link.querySelector('i');
        if (!icon) return;
        for (const [cls, label] of Object.entries(socialLabels)) {
            if (icon.classList.contains(cls)) {
                link.setAttribute('aria-label', label);
                break;
            }
        }
    });

    /* ══════════════════════════════════════════
       15. HUD CORNERS — Décoratifs, masqués
    ══════════════════════════════════════════ */
    document.querySelectorAll('.hud-corner').forEach(el => {
        el.setAttribute('aria-hidden', 'true');
    });

})();
