// Captures d'écran de l'application en local (http://localhost:8090) pour les maquettes et le manuel.
// Usage : node docs/outils/captures.mjs   (l'application doit tourner : docker compose up -d)
import { mkdirSync } from 'node:fs';
import { lancer } from './cdp.mjs';

const BASE = process.env.BASE_URL ?? 'http://localhost:8090';
const DOSSIER = new URL('../sources/captures/', import.meta.url).pathname.replace(/^\/([A-Z]:)/, '$1');
mkdirSync(DOSSIER, { recursive: true });

const ECRANS = { bureau: [1280, 800, false], mobile: [390, 844, true] };

const page = await lancer();
const connexion = async (email, mdp) => {
    await page.aller(`${BASE}/connexion`);
    await page.soumettre({ email, mot_de_passe: mdp });
};
const capturer = async (nom, chemin, ecrans = ['bureau', 'mobile']) => {
    for (const e of ecrans) {
        await page.taille(...ECRANS[e]);
        await page.aller(BASE + chemin);
        await page.capture(`${DOSSIER}${nom}-${e}.png`);
        console.log(`✓ ${nom}-${e}.png`);
    }
};

// Visiteur
await capturer('accueil', '/');
await capturer('galerie', '/personnages');
await capturer('detail', '/personnage?id=1');
await capturer('contact', '/contact', ['bureau']);
await capturer('inscription', '/inscription', ['bureau']);

// Joueur
await page.taille(...ECRANS.bureau);
await connexion('lyra@exemple.fr', 'Joueur#Fantasy2026');
await capturer('espace', '/espace');
await capturer('atelier', '/espace/personnage?id=1');
await capturer('creation', '/espace/creer', ['bureau']);

// Employé puis administrateur (déconnexion via le formulaire du menu)
const deconnexion = async () => {
    await page.aller(`${BASE}/`);
    await page.soumettre({}, 'form[action="/deconnexion"]');
};
await deconnexion();
await page.taille(...ECRANS.bureau);
await connexion('jose@pixelverse-studios.local', 'Employe#Fantasy2026');
await capturer('gestion', '/gestion', ['bureau']);
await capturer('articles', '/gestion/accessoires', ['bureau']);
await deconnexion();
await connexion('admin@pixelverse-studios.local', 'Admin#Fantasy2026');
await capturer('employes', '/admin/employes', ['bureau']);
await capturer('logs', '/admin/logs', ['bureau']);
await deconnexion();

page.fermer();
