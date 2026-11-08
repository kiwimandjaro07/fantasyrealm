// Conversion des documents HTML de docs/sources en PDF dans docs/.
// Usage : node docs/outils/pdf.mjs [nom-du-document]
import { pathToFileURL, fileURLToPath } from 'node:url';
import { join, dirname } from 'node:path';
import { lancer } from './cdp.mjs';

const ICI = dirname(fileURLToPath(import.meta.url));
const SOURCES = join(ICI, '..', 'sources');
const SORTIE = join(ICI, '..');
const DOCUMENTS = ['charte-graphique', 'manuel-utilisation', 'gestion-de-projet', 'documentation-technique'];

const choix = process.argv[2] ? [process.argv[2]] : DOCUMENTS;
const page = await lancer(9334);
for (const nom of choix) {
    await page.aller(pathToFileURL(join(SOURCES, `${nom}.html`)).href);
    // Les diagrammes Mermaid sont dessinés par JavaScript : on attend le signal de fin de rendu
    for (let i = 0; i < 100 && !(await page.js('window.__pret === true')); i++) {
        await new Promise((r) => setTimeout(r, 200));
    }
    await page.pdf(join(SORTIE, `${nom}.pdf`));
    console.log(`✓ docs/${nom}.pdf`);
}
page.fermer();
