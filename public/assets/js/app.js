/**
 * FantasyRealm - JavaScript de l'interface (natif, sans bibliothèque).
 * Tout est en amélioration progressive : sans JavaScript, chaque page reste utilisable et le serveur
 * refait tous les contrôles. Aucun script en ligne (interdit par la Content-Security-Policy).
 */
(() => {
    'use strict';

    /* ---------- Menu mobile ---------- */
    const boutonMenu = document.querySelector('.bouton-menu');
    const menu = document.getElementById('menu-principal');
    if (boutonMenu && menu) {
        boutonMenu.hidden = false;
        document.documentElement.classList.add('js');
        boutonMenu.addEventListener('click', () => {
            const ouvert = boutonMenu.getAttribute('aria-expanded') === 'true';
            boutonMenu.setAttribute('aria-expanded', String(!ouvert));
            menu.classList.toggle('menu-ouvert', !ouvert);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && boutonMenu.getAttribute('aria-expanded') === 'true') {
                boutonMenu.setAttribute('aria-expanded', 'false');
                menu.classList.remove('menu-ouvert');
                boutonMenu.focus();
            }
        });
    }

    /* ---------- Confirmation des actions destructrices ---------- */
    document.querySelectorAll('form[data-confirmation]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirmation)) e.preventDefault();
        });
    });

    /* ---------- Pastilles de couleur (pas d'attribut style en HTML à cause de la CSP) ---------- */
    document.querySelectorAll('[data-couleur]').forEach((el) => {
        if (/^#[0-9a-f]{6}$/i.test(el.dataset.couleur)) el.style.backgroundColor = el.dataset.couleur;
    });

    /* ---------- Robustesse du mot de passe et confirmation ---------- */
    const REGLES = {
        longueur: (v) => v.length >= 12,
        majuscule: (v) => /\p{Lu}/u.test(v),
        minuscule: (v) => /\p{Ll}/u.test(v),
        chiffre: (v) => /\d/.test(v),
        special: (v) => /[^\p{L}\d]/u.test(v),
    };
    document.querySelectorAll('input[data-force]').forEach((champ) => {
        const liste = document.getElementById('force-mdp');
        champ.addEventListener('input', () => {
            if (liste) liste.hidden = false;
            let manquantes = 0;
            Object.entries(REGLES).forEach(([nom, test]) => {
                const ok = test(champ.value);
                if (!ok) manquantes++;
                const li = liste?.querySelector(`[data-regle="${nom}"]`);
                if (li) {
                    li.classList.toggle('ok', ok);
                    li.dataset.etat = ok ? 'respectée' : 'manquante';
                }
            });
            // Message intégré à la validation HTML5 : le formulaire ne part pas tant que la règle n'est pas respectée
            champ.setCustomValidity(manquantes ? 'Le mot de passe ne respecte pas toutes les règles.' : '');
        });
    });
    document.querySelectorAll('input[data-confirme]').forEach((confirmation) => {
        const original = document.getElementById(confirmation.dataset.confirme);
        const verifier = () => confirmation.setCustomValidity(
            confirmation.value && confirmation.value !== original.value ? 'Les deux mots de passe ne correspondent pas.' : ''
        );
        confirmation.addEventListener('input', verifier);
        original.addEventListener('input', verifier);
    });

    /* ---------- Aperçu en direct du personnage ---------- */
    const apercu = document.getElementById('apercu');
    if (apercu) {
        let minuterie;
        const champs = document.querySelectorAll('[data-apercu]');
        const actualiser = () => {
            const params = new URLSearchParams();
            champs.forEach((c) => {
                if ((c.type === 'radio' && !c.checked) || c.value === '') return;
                params.set(c.name, c.value);
            });
            // L'image est générée par le serveur à partir des seules valeurs autorisées
            apercu.src = `/apercu?${params}`;
        };
        champs.forEach((c) => c.addEventListener('change', () => {
            clearTimeout(minuterie);
            minuterie = setTimeout(actualiser, 120);
        }));

        // Le personnage « en action » est animé ; l'animation peut être mise en pause (critère RGAA 13.8)
        const boutonAction = document.getElementById('bouton-action');
        if (boutonAction && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            boutonAction.hidden = false;
            boutonAction.addEventListener('click', () => {
                const enPause = apercu.classList.toggle('en-pause');
                boutonAction.setAttribute('aria-pressed', String(!enPause));
                boutonAction.textContent = enPause ? 'Voir en action' : 'Mettre en pause l\'animation';
            });
        }
    }

    /* ---------- Contact : vérification du pseudo pendant la saisie ---------- */
    const pseudo = document.querySelector('#form-contact #pseudo');
    const statutPseudo = document.getElementById('pseudo-statut');
    if (pseudo && statutPseudo && !pseudo.readOnly) {
        pseudo.addEventListener('change', async () => {
            const valeur = pseudo.value.trim();
            if (valeur.length < 3) return;
            try {
                const rep = await fetch(`/api/pseudo?pseudo=${encodeURIComponent(valeur)}`, { headers: { Accept: 'application/json' } });
                const { existe } = await rep.json();
                statutPseudo.textContent = existe ? 'Pseudo trouvé.' : 'Ce pseudo n\'existe pas.';
                pseudo.setCustomValidity(existe ? '' : 'Ce pseudo n\'existe pas.');
                pseudo.setAttribute('aria-invalid', String(!existe));
            } catch {
                statutPseudo.textContent = ''; // en cas d'erreur réseau, le serveur vérifiera à l'envoi
            }
        });
    }
})();
