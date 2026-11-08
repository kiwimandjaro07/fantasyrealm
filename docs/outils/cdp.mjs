// Pilotage de Microsoft Edge (ou Chrome) en mode headless via le protocole DevTools (CDP),
// sans dépendance npm : Node 22+ fournit WebSocket et fetch.
// Sert à produire les captures d'écran (maquettes, manuel) et les PDF de la documentation.
import { spawn } from 'node:child_process';
import { mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const NAVIGATEURS = [
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
];

export async function lancer(port = 9333) {
    const { existsSync } = await import('node:fs');
    const exe = NAVIGATEURS.find((p) => existsSync(p));
    if (!exe) throw new Error('Edge ou Chrome introuvable');
    const profil = mkdtempSync(join(tmpdir(), 'fr-capture-'));
    const proc = spawn(exe, ['--headless=new', `--remote-debugging-port=${port}`, `--user-data-dir=${profil}`,
        '--no-first-run', '--hide-scrollbars', 'about:blank'], { stdio: 'ignore' });

    let cible;
    for (let i = 0; i < 50 && !cible; i++) {
        await new Promise((r) => setTimeout(r, 200));
        try {
            const liste = await (await fetch(`http://127.0.0.1:${port}/json`)).json();
            cible = liste.find((t) => t.type === 'page');
        } catch { /* le navigateur démarre */ }
    }
    if (!cible) throw new Error('Impossible de se connecter au navigateur');

    const ws = new WebSocket(cible.webSocketDebuggerUrl);
    await new Promise((r) => ws.addEventListener('open', r, { once: true }));
    let id = 0;
    const attentes = new Map();
    const ecouteurs = [];
    ws.addEventListener('message', (e) => {
        const msg = JSON.parse(e.data);
        if (msg.id && attentes.has(msg.id)) {
            const { ok, ko } = attentes.get(msg.id);
            attentes.delete(msg.id);
            msg.error ? ko(new Error(msg.error.message)) : ok(msg.result);
        } else if (msg.method) {
            ecouteurs.forEach((f) => f(msg));
        }
    });
    const envoyer = (method, params = {}) => new Promise((ok, ko) => {
        id += 1;
        attentes.set(id, { ok, ko });
        ws.send(JSON.stringify({ id, method, params }));
    });
    const evenement = (nom) => new Promise((r) => {
        const f = (msg) => { if (msg.method === nom) { ecouteurs.splice(ecouteurs.indexOf(f), 1); r(msg.params); } };
        ecouteurs.push(f);
    });

    await envoyer('Page.enable');
    await envoyer('Runtime.enable');

    const page = {
        envoyer,
        async taille(largeur, hauteur, mobile = false) {
            await envoyer('Emulation.setDeviceMetricsOverride', { width: largeur, height: hauteur, deviceScaleFactor: 1, mobile });
        },
        async aller(url) {
            const charge = evenement('Page.loadEventFired');
            await envoyer('Page.navigate', { url });
            await charge;
            await new Promise((r) => setTimeout(r, 400)); // images SVG et polices
        },
        async js(code) {
            const r = await envoyer('Runtime.evaluate', { expression: code, awaitPromise: true, returnByValue: true });
            return r.result?.value;
        },
        /** Remplit un formulaire puis l'envoie, et attend la page suivante. */
        async soumettre(valeurs, selecteurFormulaire = 'main form') {
            const charge = evenement('Page.loadEventFired');
            await this.js(`(() => { const f = document.querySelector(${JSON.stringify(selecteurFormulaire)});
                const v = ${JSON.stringify(valeurs)};
                for (const [n, val] of Object.entries(v)) { f.querySelector('[name="' + n + '"]').value = val; }
                f.submit(); })()`);
            await charge;
            await new Promise((r) => setTimeout(r, 300));
        },
        async capture(fichier, pleinePage = true) {
            const { writeFileSync } = await import('node:fs');
            const params = { format: 'png' };
            let restaurer = null;
            if (pleinePage) {
                // On agrandit la fenêtre à la hauteur de la page : les images en chargement différé (lazy) s'affichent
                const h = Math.min(await this.js('document.documentElement.scrollHeight'), 4000);
                const l = await this.js('window.innerWidth');
                const mobile = l < 640;
                await envoyer('Emulation.setDeviceMetricsOverride', { width: l, height: h, deviceScaleFactor: 1, mobile });
                await new Promise((r) => setTimeout(r, 800));
                restaurer = { width: l, height: mobile ? 844 : 800, deviceScaleFactor: 1, mobile };
            }
            const r = await envoyer('Page.captureScreenshot', params);
            if (restaurer) await envoyer('Emulation.setDeviceMetricsOverride', restaurer);
            writeFileSync(fichier, Buffer.from(r.data, 'base64'));
        },
        async pdf(fichier) {
            const { writeFileSync } = await import('node:fs');
            const r = await envoyer('Page.printToPDF', {
                printBackground: true, preferCSSPageSize: true, displayHeaderFooter: false,
            });
            writeFileSync(fichier, Buffer.from(r.data, 'base64'));
        },
        fermer() { ws.close(); proc.kill(); },
    };
    return page;
}
