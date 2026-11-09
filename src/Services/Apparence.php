<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Options de personnalisation et dessin de l'avatar en SVG (image vectorielle : légère, nette à toutes
 * les tailles, générée côté serveur sans bibliothèque).
 *
 * Sécurité : le SVG est construit uniquement à partir de valeurs issues de listes fermées
 * (formes, palettes) et de couleurs validées au format #rrggbb. Aucune saisie libre n'y est insérée,
 * sauf le nom du personnage, échappé dans la balise <title>.
 */
final class Apparence
{
    public const GENRES = ['feminin' => 'Féminin', 'masculin' => 'Masculin', 'non_binaire' => 'Non binaire'];

    public const FORMES = [
        'forme_visage' => ['ovale' => 'Ovale', 'rond' => 'Rond', 'carre' => 'Carré', 'allonge' => 'Allongé'],
        'forme_yeux'   => ['amande' => 'En amande', 'ronds' => 'Ronds', 'plisses' => 'Plissés', 'grands' => 'Grands'],
        'forme_nez'    => ['droit' => 'Droit', 'retrousse' => 'Retroussé', 'aquilin' => 'Aquilin', 'large' => 'Large'],
        'forme_bouche' => ['fine' => 'Fine', 'pleine' => 'Pleine', 'souriante' => 'Souriante', 'severe' => 'Sévère'],
        'coiffure'     => ['courte' => 'Courte', 'longue' => 'Longue', 'tresse' => 'Tresse', 'chauve' => 'Crâne rasé', 'crete' => 'Crête'],
    ];

    public const PALETTES = [
        'couleur_peau' => [
            '#f5d6c1' => 'Porcelaine', '#e8b896' => 'Claire', '#c68e63' => 'Hâlée', '#8d5a3b' => 'Mate',
            '#5c3a26' => 'Ébène', '#9fbf8f' => 'Sylvestre (elfe)', '#a7b8d8' => 'Givre', '#d9d2e9' => 'Opaline',
        ],
        'couleur_cheveux' => [
            '#1f1a17' => 'Noir', '#5a3825' => 'Brun', '#8a5a33' => 'Châtain', '#b5482a' => 'Roux',
            '#e3c16f' => 'Blond', '#e9e6df' => 'Blanc', '#2d3e8c' => 'Bleu nuit', '#6b3fa0' => 'Violet',
        ],
        'couleur_yeux' => [
            '#5b3a1e' => 'Marron', '#8a6a2f' => 'Noisette', '#3f7d3a' => 'Vert', '#3b6ea8' => 'Bleu',
            '#7b8794' => 'Gris', '#c98a1b' => 'Ambre', '#7a3fb0' => 'Violet',
        ],
    ];

    public const CATEGORIES = ['vetement' => 'Vêtement', 'armure' => 'Armure', 'arme' => 'Arme', 'accessoire' => 'Accessoire'];

    /** Formes dessinables pour chaque catégorie d'article. */
    public const VISUELS = [
        'vetement'   => ['tunique' => 'Tunique', 'robe' => 'Robe longue', 'cape' => 'Cape'],
        'armure'     => ['plastron' => 'Plastron', 'cotte' => 'Cotte de mailles', 'epaulieres' => 'Épaulières'],
        'arme'       => ['epee' => 'Épée', 'hache' => 'Hache', 'arc' => 'Arc', 'baton' => 'Bâton de mage'],
        'accessoire' => ['couronne' => 'Couronne', 'amulette' => 'Amulette', 'bouclier' => 'Bouclier', 'ailes' => 'Ailes'],
    ];

    /** Apparence par défaut proposée à la création. */
    public const DEFAUT = [
        'genre' => 'feminin', 'forme_visage' => 'ovale', 'forme_yeux' => 'amande', 'forme_nez' => 'droit',
        'forme_bouche' => 'fine', 'coiffure' => 'longue', 'couleur_peau' => '#e8b896',
        'couleur_cheveux' => '#5a3825', 'couleur_yeux' => '#3f7d3a',
    ];

    /**
     * Vérifie et normalise les traits reçus d'un formulaire.
     * @return array{0: array<string, string>, 1: array<string, string>} [traits valides, erreurs]
     */
    public static function valider(array $saisie): array
    {
        $traits = [];
        $erreurs = [];

        $genre = $saisie['genre'] ?? null;
        is_string($genre) && isset(self::GENRES[$genre]) ? $traits['genre'] = $genre : $erreurs['genre'] = 'Choisissez un genre.';

        foreach (self::FORMES as $champ => $options) {
            $v = $saisie[$champ] ?? null;
            is_string($v) && isset($options[$v]) ? $traits[$champ] = $v : $erreurs[$champ] = 'Choix invalide.';
        }
        foreach (self::PALETTES as $champ => $options) {
            $v = is_string($saisie[$champ] ?? null) ? strtolower($saisie[$champ]) : null;
            $v !== null && isset($options[$v]) ? $traits[$champ] = $v : $erreurs[$champ] = 'Couleur invalide.';
        }

        return [$traits, $erreurs];
    }

    /** Description textuelle de l'apparence (pour le texte alternatif et le détail). */
    public static function description(array $p): string
    {
        return sprintf(
            '%s, visage %s, yeux %s %s, nez %s, bouche %s, coiffure %s %s, peau %s',
            self::GENRES[$p['genre']] ?? '',
            mb_strtolower(self::FORMES['forme_visage'][$p['forme_visage']] ?? ''),
            mb_strtolower(self::FORMES['forme_yeux'][$p['forme_yeux']] ?? ''),
            mb_strtolower(self::PALETTES['couleur_yeux'][$p['couleur_yeux']] ?? ''),
            mb_strtolower(self::FORMES['forme_nez'][$p['forme_nez']] ?? ''),
            mb_strtolower(self::FORMES['forme_bouche'][$p['forme_bouche']] ?? ''),
            mb_strtolower(self::FORMES['coiffure'][$p['coiffure']] ?? ''),
            mb_strtolower(self::PALETTES['couleur_cheveux'][$p['couleur_cheveux']] ?? ''),
            mb_strtolower(self::PALETTES['couleur_peau'][$p['couleur_peau']] ?? '')
        );
    }

    /** Peaux « féeriques » : le personnage reçoit des oreilles pointues d'elfe. */
    private const PEAUX_ELFIQUES = ['#9fbf8f', '#a7b8d8', '#d9d2e9'];

    /**
     * Morphologie selon le genre (demi-largeurs en unités du dessin).
     * Proportions réalistes : environ 6,5 têtes de haut, du sommet du crâne (y ≈ 27) aux pieds (y ≈ 244).
     */
    private const SILHOUETTES = [
        'masculin'    => ['epaules' => 31, 'taille' => 18, 'hanches' => 20, 'cuisse' => 9],
        'feminin'     => ['epaules' => 24, 'taille' => 14, 'hanches' => 24, 'cuisse' => 9.5],
        'non_binaire' => ['epaules' => 27, 'taille' => 16, 'hanches' => 21, 'cuisse' => 9],
    ];

    /** Étoiles du décor (positions fixes : chaque avatar a le même ciel). */
    private const ETOILES = [[18, 30, 1.2], [42, 18, 0.8], [70, 34, 1], [150, 22, 1.3], [176, 44, 0.9], [188, 16, 0.7],
        [24, 78, 0.8], [182, 90, 1.1], [12, 120, 0.7], [190, 140, 0.8], [36, 150, 0.6], [162, 64, 0.6]];

    /** La tête est dessinée dans un repère agrandi (centre 100,80), puis réduite et placée sur le cou. */
    private const REPERE_TETE = 'translate(100 46) scale(0.55) translate(-100 -80)';

    /**
     * Dessine l'avatar : décor, silhouette aux proportions humaines, visage, coiffure et équipement.
     * Les plans sont superposés du fond vers l'avant : ailes, cape, cheveux longs, jambes, buste, armure,
     * bras, tête, accessoires, arme, mains.
     *
     * @param array<string, string> $p traits (déjà validés)
     * @param list<array{categorie: string, visuel: string, couleur: string}> $equipement
     */
    public static function svg(array $p, array $equipement = [], string $titre = 'Personnage'): string
    {
        $peau = self::couleurSure($p['couleur_peau'] ?? '#e8b896');
        $cheveux = self::couleurSure($p['couleur_cheveux'] ?? '#5a3825');
        $yeux = self::couleurSure($p['couleur_yeux'] ?? '#3f7d3a');
        $genre = isset(self::SILHOUETTES[$p['genre'] ?? '']) ? $p['genre'] : 'non_binaire';
        $m = self::SILHOUETTES[$genre];
        $ombre = self::assombrir($peau, 0.22);
        $or = '#e0b74f';

        $equip = [];
        foreach ($equipement as $a) {
            if (isset(self::VISUELS[$a['categorie']][$a['visuel']])) {
                $equip[$a['categorie']] = ['visuel' => $a['visuel'], 'couleur' => self::couleurSure($a['couleur'])];
            }
        }
        $vetement = $equip['vetement']['visuel'] ?? null;
        $accessoire = $equip['accessoire']['visuel'] ?? null;

        // Couleurs des vêtements : la cape se porte par-dessus une tenue de base
        $tenue = ($vetement !== null && $vetement !== 'cape') ? $equip['vetement']['couleur'] : '#d8cdb2';
        $pantalon = $vetement === 'tunique' ? self::assombrir($tenue, 0.55) : '#4a3a2c';

        // Repères anatomiques
        $sh = $m['epaules'];
        $epauleG = [100 - $sh + 5, 81];
        $epauleD = [100 + $sh - 5, 81];
        $coudeG = [100 - $sh - 1, 116];
        $coudeD = [100 + $sh + 1, 116];
        $poignetG = [100 - $sh - 1, 146];
        $poignetD = [100 + $sh + 1, 146];
        $mainG = [100 - $sh - 1, 153];
        $mainD = [100 + $sh + 1, 153];

        $defs = [];
        $c = [];

        // ---------- Dégradés et filtres ----------
        $defs[] = '<linearGradient id="ciel" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0f0b26"/>'
            . '<stop offset="0.6" stop-color="#2c1f55"/><stop offset="1" stop-color="#4a3279"/></linearGradient>';
        $defs[] = '<radialGradient id="aura" cx="0.5" cy="0.45" r="0.5"><stop offset="0" stop-color="#8e74d6" stop-opacity="0.5"/>'
            . '<stop offset="1" stop-color="#8e74d6" stop-opacity="0"/></radialGradient>';
        $defs[] = '<filter id="lueur" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="3"/></filter>';
        $defs[] = sprintf('<linearGradient id="peau" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="%s"/>'
            . '<stop offset="0.45" stop-color="%s"/><stop offset="1" stop-color="%s"/></linearGradient>',
            self::eclaircir($peau, 0.12), $peau, $ombre);
        $defs[] = sprintf('<linearGradient id="cheveux" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="%s"/>'
            . '<stop offset="1" stop-color="%s"/></linearGradient>', self::eclaircir($cheveux, 0.25), self::assombrir($cheveux, 0.2));
        $defs[] = sprintf('<radialGradient id="iris" cx="0.4" cy="0.35" r="0.65"><stop offset="0" stop-color="%s"/>'
            . '<stop offset="1" stop-color="%s"/></radialGradient>', self::eclaircir($yeux, 0.35), self::assombrir($yeux, 0.25));
        $defs[] = sprintf('<linearGradient id="tissu" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="%s"/>'
            . '<stop offset="0.5" stop-color="%s"/><stop offset="1" stop-color="%s"/></linearGradient>',
            self::eclaircir($tenue, 0.12), $tenue, self::assombrir($tenue, 0.3));
        $defs[] = sprintf('<linearGradient id="jambe" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="%s"/>'
            . '<stop offset="1" stop-color="%s"/></linearGradient>', self::eclaircir($pantalon, 0.12), self::assombrir($pantalon, 0.3));

        // ---------- Décor : ciel de nuit, lune, étoiles, collines ----------
        $c[] = '<rect width="200" height="260" rx="16" fill="url(#ciel)"/>';
        foreach (self::ETOILES as [$x, $y, $r]) {
            $c[] = sprintf('<circle cx="%s" cy="%s" r="%s" fill="#fff6d8" opacity="0.85"/>', $x, $y, $r);
        }
        $c[] = '<path d="M166 18 l1.6 4.4 4.4 1.6 -4.4 1.6 -1.6 4.4 -1.6 -4.4 -4.4 -1.6 4.4 -1.6z" fill="#fff6d8"/>';
        $c[] = '<circle cx="30" cy="40" r="11" fill="#f3e6b4" opacity="0.95"/><circle cx="35" cy="36" r="10" fill="#1a1338"/>';
        $c[] = '<ellipse cx="100" cy="130" rx="80" ry="110" fill="url(#aura)"/>';
        $c[] = '<path d="M0 228 Q50 212 100 222 T200 216 V260 H0Z" fill="#1a1234"/>'
            . '<path d="M0 240 Q60 228 120 238 T200 234 V260 H0Z" fill="#130d29"/>';
        $c[] = '<ellipse cx="100" cy="244" rx="36" ry="5" fill="#000" opacity="0.45"/>';

        // ---------- Plans arrière : ailes, cape, cheveux longs ----------
        if ($accessoire === 'ailes') {
            $col = $equip['accessoire']['couleur'];
            $defs[] = sprintf('<linearGradient id="aile" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="%s" stop-opacity="0.9"/>'
                . '<stop offset="1" stop-color="%s" stop-opacity="0.3"/></linearGradient>', self::eclaircir($col, 0.3), $col);
            foreach (['', 'translate(200 0) scale(-1 1) '] as $miroir) {
                $c[] = '<g transform="' . $miroir . 'translate(100 92) scale(0.9) translate(-100 -142)">'
                    . '<path d="M88 132 C60 70 14 66 10 104 C8 128 30 138 52 140 C24 150 18 182 36 196 C54 206 74 178 88 152Z" '
                    . 'fill="url(#aile)" stroke="' . self::eclaircir($col, 0.5) . '" stroke-width="1.2"/>'
                    . '<path d="M86 136 C66 104 36 92 20 104 M86 140 C62 132 40 136 30 142 M86 148 C66 160 48 176 40 190" '
                    . 'stroke="' . self::eclaircir($col, 0.6) . '" stroke-width="0.8" fill="none" opacity="0.8"/></g>';
            }
            $c[] = '<circle cx="30" cy="70" r="1.4" fill="#fff"/><circle cx="168" cy="78" r="1.2" fill="#fff"/><circle cx="44" cy="132" r="1" fill="#fff"/>';
        }
        if ($vetement === 'cape') {
            $col = $equip['vetement']['couleur'];
            $c[] = sprintf('<path d="M%d 76 Q100 68 %d 76 C%d 140 %d 200 %d 244 Q100 238 %d 244 C%d 200 %d 140 %d 76Z" fill="%s"/>',
                100 - $sh - 1, 100 + $sh + 1, 100 + $sh + 8, 100 + $sh + 14, 100 + $sh + 20, 100 - $sh - 20, 100 - $sh - 14, 100 - $sh - 8,
                100 - $sh - 1, self::assombrir($col, 0.2));
            $c[] = sprintf('<path d="M%d 80 C%d 150 %d 210 %d 242" stroke="%s" stroke-width="4" fill="none" opacity="0.45"/>',
                100 + $sh + 2, 100 + $sh + 10, 100 + $sh + 14, 100 + $sh + 18, self::eclaircir($col, 0.25));
        }
        if (in_array($p['coiffure'] ?? '', ['longue', 'tresse'], true)) {
            $c[] = '<g transform="' . self::REPERE_TETE . '"><path d="M70 70 C60 110 58 150 64 176 Q100 186 136 176 '
                . 'C142 150 140 110 130 70Z" fill="url(#cheveux)"/></g>';
        }

        // ---------- Jambes et bottes ----------
        foreach ([-1, 1] as $sens) {
            $hanche = 100 + $sens * ($m['hanches'] - $m['cuisse']);
            $c[] = self::membre([[$hanche, 140, $m['cuisse']], [100 + $sens * 10, 166, $m['cuisse'] - 1.6], [100 + $sens * 9, 190, 5.6],
                [100 + $sens * 9, 204, 6], [100 + $sens * 8, 230, 4]],
                'url(#jambe)');
            // Botte de cuir
            $c[] = sprintf('<path d="M%s 210 L%s 236 Q%s 244 %s 244 L%s 244 Q%s 238 %s 210Z" fill="#3a281c"/>',
                100 + $sens * 2.5, 100 + $sens * 3, 100 + $sens * 3, 100 + $sens * 5, 100 + $sens * 19, 100 + $sens * 14, 100 + $sens * 14);
            $c[] = sprintf('<path d="M%s 212 L%s 212" stroke="#5a4030" stroke-width="2.5"/>', 100 + $sens * 2.5, 100 + $sens * 14);
        }

        // ---------- Cou et buste ----------
        $c[] = sprintf('<path d="M94 58 L94 74 Q100 78 106 74 L106 58Z" fill="%s"/><path d="M95 66 Q100 70 105 66" stroke="%s" stroke-width="1" fill="none" opacity="0.6"/>',
            $ombre, self::assombrir($peau, 0.35));
        $buste = sprintf('M95 72 Q%s 72 %s 80 C%s 96 %s 108 %s 122 C%s 130 %s 134 %s 144 L%s 144 C%s 134 %s 130 %s 122 C%s 108 %s 96 %s 80 Q%s 72 105 72Z',
            100 - $sh + 6, 100 - $sh, 100 - $sh - 1, 100 - $m['taille'] - 3, 100 - $m['taille'], 100 - $m['taille'], 100 - $m['hanches'], 100 - $m['hanches'],
            100 + $m['hanches'], 100 + $m['hanches'], 100 + $m['taille'], 100 + $m['taille'], 100 + $m['taille'] + 3, 100 + $sh + 1, 100 + $sh, 100 + $sh - 6);

        if ($vetement === 'robe') {
            $col = $equip['vetement']['couleur'];
            $c[] = sprintf('<path d="M95 72 Q%s 72 %s 80 C%s 96 %s 108 %s 122 C%s 160 %s 210 %s 244 Q100 248 %s 244 C%s 210 %s 160 %s 122 C%s 108 %s 96 %s 80 Q%s 72 105 72Z" fill="url(#tissu)"/>',
                100 - $sh + 6, 100 - $sh, 100 - $sh - 1, 100 - $m['taille'] - 3, 100 - $m['taille'], 100 - $m['hanches'] - 2, 100 - $m['hanches'] - 10,
                100 - $m['hanches'] - 18, 100 + $m['hanches'] + 18, 100 + $m['hanches'] + 10, 100 + $m['hanches'] + 2, 100 + $m['taille'],
                100 + $m['taille'] + 3, 100 + $sh + 1, 100 + $sh, 100 + $sh - 6);
            // Plis, liseré doré, broderies d'étoiles et ceinture
            $c[] = sprintf('<path d="M92 150 Q90 200 86 242 M108 150 Q110 200 114 242" stroke="%s" stroke-width="1.2" fill="none" opacity="0.5"/>', self::assombrir($col, 0.4));
            $c[] = sprintf('<path d="M%s 242 Q100 248 %s 242" stroke="%s" stroke-width="2.5" fill="none"/><path d="M100 78 L100 244" stroke="%s" stroke-width="1" opacity="0.55"/>',
                100 - $m['hanches'] - 17, 100 + $m['hanches'] + 17, $or, $or);
            foreach ([[88, 180], [113, 196], [94, 222], [110, 166]] as [$x, $y]) {
                $c[] = sprintf('<path d="M%d %d l1.2 3.3 3.3 1.2 -3.3 1.2 -1.2 3.3 -1.2 -3.3 -3.3 -1.2 3.3 -1.2z" fill="%s" opacity="0.9"/>', $x, $y - 4, $or);
            }
            $c[] = sprintf('<path d="M%s 120 Q100 126 %s 120 L%s 126 Q100 132 %s 126Z" fill="%s"/>',
                100 - $m['taille'] - 1, 100 + $m['taille'] + 1, 100 + $m['taille'] + 1, 100 - $m['taille'] - 1, $or);
        } else {
            $c[] = sprintf('<path d="%s" fill="url(#tissu)"/>', $buste);
            if ($vetement === 'tunique') {
                $col = $equip['vetement']['couleur'];
                // La tunique descend à mi-cuisse
                $c[] = sprintf('<path d="M%s 122 C%s 134 %s 150 %s 170 Q100 176 %s 170 C%s 150 %s 134 %s 122Z" fill="url(#tissu)"/>',
                    100 - $m['taille'], 100 - $m['hanches'] - 1, 100 - $m['hanches'] - 4, 100 - $m['hanches'] - 6,
                    100 + $m['hanches'] + 6, 100 + $m['hanches'] + 4, 100 + $m['hanches'] + 1, 100 + $m['taille']);
                $c[] = sprintf('<path d="M%s 168 Q100 174 %s 168" stroke="%s" stroke-width="2" fill="none"/><path d="M100 136 L100 172" stroke="%s" stroke-width="1" opacity="0.5"/>',
                    100 - $m['hanches'] - 5, 100 + $m['hanches'] + 5, self::eclaircir($col, 0.35), self::assombrir($col, 0.4));
            } else {
                // Tenue de base : chemise de lin, gilet de cuir
                $c[] = sprintf('<path d="M%s 80 C%s 100 %s 118 %s 144 L94 144 L96 78Z M%s 80 C%s 100 %s 118 %s 144 L106 144 L104 78Z" fill="#6b4a2f"/>',
                    100 - $sh + 5, 100 - $m['taille'] - 2, 100 - $m['taille'], 100 - $m['hanches'] + 1,
                    100 + $sh - 5, 100 + $m['taille'] + 2, 100 + $m['taille'], 100 + $m['hanches'] - 1);
                $c[] = '<path d="M98 86 l0 50 M102 86 l0 50" stroke="#3d2a1a" stroke-width="0.8" stroke-dasharray="2 3"/>';
            }
            // Ceinture et boucle
            $c[] = sprintf('<path d="M%s 122 Q100 127 %s 122 L%s 129 Q100 134 %s 129Z" fill="#3d2a1a"/><rect x="96" y="123" width="8" height="8" rx="1.5" fill="none" stroke="%s" stroke-width="1.6"/>',
                100 - $m['taille'] - 1, 100 + $m['taille'] + 1, 100 + $m['taille'] + 1.5, 100 - $m['taille'] - 1.5, $or);
        }
        // Encolure et modelé du buste (pectoraux ou poitrine suggérés par une ombre)
        $c[] = sprintf('<path d="M95 72 L100 86 L105 72" fill="%s"/><path d="M94 72 L100 87 L106 72" fill="none" stroke="%s" stroke-width="1.2"/>',
            $peau, self::assombrir($tenue, 0.35));
        $c[] = $genre === 'masculin'
            ? sprintf('<path d="M86 100 Q93 104 99 100 M101 100 Q107 104 114 100" stroke="%s" stroke-width="1" fill="none" opacity="0.45"/>', self::assombrir($tenue, 0.4))
            : sprintf('<path d="M86 98 Q92 106 99 100 M101 100 Q108 106 114 98" stroke="%s" stroke-width="1" fill="none" opacity="0.45"/>', self::assombrir($tenue, 0.4));

        // ---------- Armure ----------
        if (isset($equip['armure'])) {
            $col = $equip['armure']['couleur'];
            $fonce = self::assombrir($col, 0.45);
            $defs[] = sprintf('<linearGradient id="metal" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="%s"/>'
                . '<stop offset="0.5" stop-color="%s"/><stop offset="1" stop-color="%s"/></linearGradient>',
                self::eclaircir($col, 0.45), $col, self::assombrir($col, 0.35));
            switch ($equip['armure']['visuel']) {
                case 'plastron':
                    $c[] = sprintf('<path d="M%s 80 Q100 72 %s 80 C%s 98 %s 112 %s 124 Q100 132 %s 124 C%s 112 %s 98 %s 80Z" fill="url(#metal)" stroke="%s" stroke-width="1.2"/>',
                        100 - $sh + 4, 100 + $sh - 4, 100 + $sh - 4, 100 + $m['taille'] + 3, 100 + $m['taille'] + 1,
                        100 - $m['taille'] - 1, 100 - $m['taille'] - 3, 100 - $sh + 4, 100 - $sh + 4, $fonce);
                    $c[] = sprintf('<path d="M100 76 L100 130" stroke="%s" stroke-width="1.2"/><path d="M%s 102 Q100 110 %s 102" stroke="%s" fill="none" stroke-width="1"/>',
                        $fonce, 100 - $m['taille'] - 2, 100 + $m['taille'] + 2, $fonce);
                    foreach ([[100 - $sh + 8, 84], [100 + $sh - 8, 84], [100 - $m['taille'], 120], [100 + $m['taille'], 120]] as [$x, $y]) {
                        $c[] = sprintf('<circle cx="%s" cy="%s" r="1.3" fill="%s"/>', $x, $y, $or);
                    }
                    break;
                case 'cotte':
                    $defs[] = sprintf('<pattern id="mailles" width="4" height="3.4" patternUnits="userSpaceOnUse"><rect width="4" height="3.4" fill="%s"/>'
                        . '<circle cx="2" cy="1.7" r="1.45" fill="none" stroke="%s" stroke-width="0.6"/></pattern>', self::assombrir($col, 0.2), self::eclaircir($col, 0.3));
                    $c[] = sprintf('<path d="M%s 80 Q100 72 %s 80 C%s 100 %s 112 %s 122 C%s 134 %s 150 %s 160 Q100 164 %s 160 C%s 150 %s 134 %s 122 C%s 112 %s 100 %s 80Z" fill="url(#mailles)" stroke="%s" stroke-width="0.8"/>',
                        100 - $sh + 1, 100 + $sh - 1, 100 + $sh, 100 + $m['taille'] + 2, 100 + $m['taille'] + 1, 100 + $m['hanches'] + 1,
                        100 + $m['hanches'] + 3, 100 + $m['hanches'] + 4, 100 - $m['hanches'] - 4, 100 - $m['hanches'] - 3, 100 - $m['hanches'] - 1,
                        100 - $m['taille'] - 1, 100 - $m['taille'] - 2, 100 - $sh, 100 - $sh + 1, $fonce);
                    break;
                case 'epaulieres':
                    foreach ([-1, 1] as $sens) {
                        $x = 100 + $sens * ($sh - 1);
                        $c[] = sprintf('<path d="M%s 76 Q%s 70 %s 84 Q%s 94 %s 92 Q%s 84 %s 78Z" fill="url(#metal)" stroke="%s" stroke-width="1.2"/>',
                            $x - $sens * 10, $x + $sens * 8, $x + $sens * 12, $x + $sens * 10, $x + $sens * 2, $x - $sens * 6, $x - $sens * 10, $fonce);
                        $c[] = sprintf('<path d="M%s 84 Q%s 82 %s 90" stroke="%s" fill="none" stroke-width="1"/>', $x - $sens * 4, $x + $sens * 6, $x + $sens * 9, $fonce);
                        $c[] = sprintf('<path d="M%s 74 l%s -10 l%s 9Z M%s 77 l%s -8 l%s 8Z" fill="%s"/>',
                            $x - $sens * 2, $sens * 3, $sens * 4, $x + $sens * 5, $sens * 3, $sens * 3, self::eclaircir($col, 0.4));
                    }
                    break;
            }
        }

        // ---------- Bras : épaule, coude, poignet (manches) ----------
        $manche = $vetement === 'cape' || $vetement === null ? '#cfc4a8' : $equip['vetement']['couleur'];
        foreach ([[$epauleG, $coudeG, $poignetG], [$epauleD, $coudeD, $poignetD]] as [$e, $co, $po]) {
            $c[] = self::membre([[$e[0], $e[1], 6.4], [($e[0] + $co[0]) / 2, 98, 5.4], [$co[0], $co[1], 4.3], [($co[0] + $po[0]) / 2, 128, 4.4],
                [$po[0], $po[1], $vetement === 'robe' ? 6.5 : 3.4]],
                self::assombrir($manche, 0.06));
            $c[] = sprintf('<path d="M%s %s L%s %s" stroke="%s" stroke-width="0.8" opacity="0.5"/>', $co[0], $co[1] - 2, $co[0], $co[1] + 2, self::assombrir($manche, 0.4));
        }

        // ---------- Tête (repère agrandi, réduit à l'échelle du corps) ----------
        $c[] = '<g transform="' . self::REPERE_TETE . '">' . self::tete($p, $genre, $peau, $cheveux, $ombre) . '</g>';

        // ---------- Accessoires portés ----------
        if ($accessoire === 'couronne') {
            $col = $equip['accessoire']['couleur'];
            $c[] = '<g transform="' . self::REPERE_TETE . '">'
                . sprintf('<path d="M76 50 L74 30 L84 40 L92 24 L100 38 L108 24 L116 40 L126 30 L124 50 Q100 44 76 50Z" fill="%s" stroke="%s" stroke-width="1.5"/>',
                    $col, self::assombrir($col, 0.4))
                . '<circle cx="100" cy="44" r="3" fill="#d33a5a"/><circle cx="86" cy="46" r="2" fill="#3a7bd3"/><circle cx="114" cy="46" r="2" fill="#3a7bd3"/></g>';
        }
        if ($accessoire === 'amulette') {
            $col = $equip['accessoire']['couleur'];
            $c[] = sprintf('<path d="M95 70 Q100 86 105 70" stroke="%s" stroke-width="1" fill="none"/>', $or);
            $c[] = sprintf('<circle cx="100" cy="92" r="6" fill="%s" filter="url(#lueur)" opacity="0.8"/>', $col);
            $c[] = sprintf('<path d="M100 86 L104 92 L100 98 L96 92Z" fill="%s" stroke="%s" stroke-width="1"/>', $col, $or);
        }
        if ($accessoire === 'bouclier') {
            $col = $equip['accessoire']['couleur'];
            [$x, $y] = $mainG;
            $c[] = sprintf('<path d="M%s %s l17 -6 l17 6 q0 26 -17 38 q-17 -12 -17 -38Z" fill="%s" stroke="%s" stroke-width="2.2"/>', $x - 17, $y - 18, $col, $or);
            $c[] = sprintf('<path d="M%s %s l17 -6 l0 44 q-17 -12 -17 -38Z" fill="#fff" opacity="0.12"/>', $x - 17, $y - 18);
            $c[] = sprintf('<path d="M%s %s l2.4 6 6 2.4 -6 2.4 -2.4 6 -2.4 -6 -6 -2.4 6 -2.4z" fill="%s"/>', $x, $y - 12, $or);
        }

        // ---------- Arme (main droite) ----------
        if (isset($equip['arme'])) {
            $col = $equip['arme']['couleur'];
            [$x, $y] = $mainD;
            switch ($equip['arme']['visuel']) {
                case 'epee':
                    $c[] = sprintf('<path d="M%s %s L%s 66 L%s 58 L%s 66 L%s %s Z" fill="%s" stroke="%s" stroke-width="0.7"/>',
                        $x - 2.4, $y - 6, $x - 2.4, $x, $x + 2.4, $x + 2.4, $y - 6, self::eclaircir($col, 0.2), self::assombrir($col, 0.4));
                    $c[] = sprintf('<path d="M%s 70 L%s %s" stroke="#7fe0ff" stroke-width="1.4" filter="url(#lueur)"/><path d="M%s 70 L%s %s" stroke="#bff3ff" stroke-width="0.6"/>',
                        $x, $x, $y - 12, $x, $x, $y - 12);
                    $c[] = sprintf('<rect x="%s" y="%s" width="18" height="3.4" rx="1.7" fill="%s"/><rect x="%s" y="%s" width="3.4" height="12" fill="#4a2f1c"/>'
                        . '<circle cx="%s" cy="%s" r="2.4" fill="#d33a5a" stroke="%s"/>', $x - 9, $y - 7, $or, $x - 1.7, $y - 4, $x, $y + 9, $or);
                    break;
                case 'hache':
                    $c[] = sprintf('<path d="M%s %s L%s 86" stroke="#5a3a22" stroke-width="4" stroke-linecap="round"/>', $x, $y + 44, $x);
                    $c[] = sprintf('<path d="M%s 90 Q%s 82 %s 100 Q%s 112 %s 116 Q%s 106 %s 104Z M%s 90 Q%s 82 %s 100 Q%s 112 %s 116 Q%s 106 %s 104Z" fill="%s" stroke="%s" stroke-width="1"/>',
                        $x + 2, $x + 22, $x + 20, $x + 18, $x + 2, $x + 10, $x + 2, $x - 2, $x - 22, $x - 20, $x - 18, $x - 2, $x - 10, $x - 2,
                        self::eclaircir($col, 0.15), self::assombrir($col, 0.45));
                    $c[] = sprintf('<path d="M%s 86 l-3 -7 h6z" fill="%s"/>', $x, $or);
                    break;
                case 'arc':
                    $c[] = sprintf('<path d="M%s 96 Q%s 154 %s 212" stroke="%s" stroke-width="3.6" fill="none" stroke-linecap="round"/><path d="M%s 96 L%s 212" stroke="#efe6cf" stroke-width="0.8"/>',
                        $x + 3, $x + 30, $x + 3, $col, $x + 3, $x + 3);
                    $c[] = sprintf('<path d="M%s %s L%s %s" stroke="#b9a37a" stroke-width="1.3"/><path d="M%s %s l-5 -2.5 v5z" fill="#cfd8e3"/>'
                        . '<path d="M%s %s l3.5 -2.5 M%s %s l3.5 2.5" stroke="#e8e0f0" stroke-width="1.3"/>',
                        $x - 18, $y, $x + 32, $y, $x + 37, $y, $x - 18, $y, $x - 18, $y);
                    break;
                case 'baton':
                    $c[] = sprintf('<path d="M%s 240 L%s 58" stroke="#5a3a22" stroke-width="4" stroke-linecap="round"/>'
                        . '<path d="M%s 60 Q%s 48 %s 40 M%s 60 Q%s 48 %s 40" stroke="#5a3a22" stroke-width="2.4" fill="none" stroke-linecap="round"/>',
                        $x, $x, $x, $x - 8, $x - 3, $x, $x + 8, $x + 3);
                    $c[] = sprintf('<circle cx="%s" cy="47" r="12" fill="%s" filter="url(#lueur)" opacity="0.75"/>', $x, $col);
                    $c[] = sprintf('<circle cx="%s" cy="47" r="5.5" fill="%s" stroke="%s"/><circle cx="%s" cy="45" r="1.8" fill="#fff" opacity="0.8"/>',
                        $x, self::eclaircir($col, 0.2), self::eclaircir($col, 0.6), $x - 1.6);
                    $c[] = sprintf('<circle cx="%s" cy="32" r="1.1" fill="#fff"/><circle cx="%s" cy="54" r="0.9" fill="#fff"/><circle cx="%s" cy="40" r="0.8" fill="#fff"/>',
                        $x + 11, $x - 12, $x - 10);
                    break;
            }
        }

        // ---------- Mains (au premier plan : elles tiennent l'arme et le bouclier) ----------
        foreach ([[$mainG, -1], [$mainD, 1]] as [[$x, $y], $sens]) {
            $c[] = sprintf('<ellipse cx="%s" cy="%s" rx="3.8" ry="4.8" fill="%s"/><ellipse cx="%s" cy="%s" rx="1.4" ry="2.6" fill="%s" transform="rotate(%d %s %s)"/>',
                $x, $y - 1, $peau, $x - $sens * 3.4, $y - 3, $ombre, -$sens * 30, $x - $sens * 3.4, $y - 3);
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 260" role="img">'
            . '<title>' . htmlspecialchars($titre, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</title>'
            . '<defs>' . implode('', $defs) . '</defs>'
            . implode('', $c) . '</svg>';
    }

    /**
     * Membre (bras ou jambe) : contour lissé construit autour d'une ligne passant par les articulations,
     * avec une demi-largeur propre à chaque point (épaule large, poignet fin...).
     * @param list<array{0: float|int, 1: float|int, 2: float|int}> $points [x, y, demi-largeur]
     */
    private static function membre(array $points, string $couleur): string
    {
        $gauche = [];
        $droite = [];
        $n = count($points);
        foreach ($points as $i => [$x, $y, $w]) {
            // Direction locale du membre, puis perpendiculaire (normale) pour placer les deux bords
            [$ax, $ay] = $points[max(0, $i - 1)];
            [$bx, $by] = $points[min($n - 1, $i + 1)];
            $dx = $bx - $ax;
            $dy = $by - $ay;
            $l = sqrt($dx * $dx + $dy * $dy) ?: 1;
            $nx = -$dy / $l * $w;
            $ny = $dx / $l * $w;
            $gauche[] = [round($x + $nx, 1), round($y + $ny, 1)];
            $droite[] = [round($x - $nx, 1), round($y - $ny, 1)];
        }
        // Bords lissés (courbes passant par le milieu des segments) et extrémités arrondies (arcs)
        $cote = static function (array $pts): string {
            $d = '';
            for ($i = 1, $k = count($pts); $i < $k - 1; $i++) {
                $mx = round(($pts[$i][0] + $pts[$i + 1][0]) / 2, 1);
                $my = round(($pts[$i][1] + $pts[$i + 1][1]) / 2, 1);
                $d .= sprintf(' Q%s %s %s %s', $pts[$i][0], $pts[$i][1], $mx, $my);
            }
            $fin = $pts[count($pts) - 1];

            return $d . sprintf(' L%s %s', $fin[0], $fin[1]);
        };
        $droite = array_reverse($droite);
        $rFin = round($points[$n - 1][2], 1);
        $rDebut = round($points[0][2], 1);
        $d = sprintf('M%s %s', $gauche[0][0], $gauche[0][1]) . $cote($gauche)
            . sprintf(' A%s %s 0 0 0 %s %s', $rFin, $rFin, $droite[0][0], $droite[0][1]) . $cote($droite)
            . sprintf(' A%s %s 0 0 0 %s %sZ', $rDebut, $rDebut, $gauche[0][0], $gauche[0][1]);

        return sprintf('<path d="%s" fill="%s" stroke="%s" stroke-width="2" stroke-linejoin="round"/>', $d,
            $couleur, str_starts_with($couleur, 'url(') ? 'none' : $couleur);
    }

    /** Visage et coiffure, dessinés dans le repère agrandi de la tête (centre 100,80). */
    private static function tete(array $p, string $genre, string $peau, string $cheveux, string $ombre): string
    {
        $t = [];
        $trait = self::assombrir($peau, 0.45);

        // Oreilles : pointues pour les peaux elfiques
        $t[] = in_array($peau, self::PEAUX_ELFIQUES, true)
            ? sprintf('<path d="M74 80 L56 62 L72 92Z M126 80 L144 62 L128 92Z" fill="%s" stroke="%s" stroke-width="1"/>', $peau, $trait)
            : sprintf('<ellipse cx="73" cy="82" rx="5" ry="8" fill="%s"/><ellipse cx="127" cy="82" rx="5" ry="8" fill="%s"/>', $ombre, $ombre);
        $t[] = match ($p['forme_visage'] ?? 'ovale') {
            'rond'    => '<circle cx="100" cy="80" r="29" fill="url(#peau)"/>',
            'carre'   => '<path d="M72 56 Q72 48 82 48 L118 48 Q128 48 128 56 L128 92 Q128 110 100 112 Q72 110 72 92Z" fill="url(#peau)"/>',
            'allonge' => '<path d="M100 44 C120 44 126 60 125 80 C124 100 114 116 100 116 C86 116 76 100 75 80 C74 60 80 44 100 44Z" fill="url(#peau)"/>',
            default   => '<path d="M100 46 C122 46 128 62 127 80 C126 98 115 112 100 112 C85 112 74 98 73 80 C72 62 78 46 100 46Z" fill="url(#peau)"/>',
        };
        // Modelé : ombre sous les pommettes et sous le menton, joues
        $t[] = sprintf('<path d="M78 92 Q84 104 100 108 Q116 104 122 92" stroke="%s" stroke-width="3" fill="none" opacity="0.18"/>', $trait);
        $t[] = '<ellipse cx="86" cy="94" rx="5" ry="3" fill="#e0607a" opacity="0.2"/><ellipse cx="114" cy="94" rx="5" ry="3" fill="#e0607a" opacity="0.2"/>';

        $sourcil = ($p['coiffure'] ?? '') === 'chauve' ? $trait : self::assombrir($cheveux, 0.15);
        $t[] = sprintf('<path d="M81 70 Q88 66 94 69 M106 69 Q112 66 119 70" stroke="%s" stroke-width="2.4" fill="none" stroke-linecap="round"/>', $sourcil);

        foreach ([88, 112] as $x) {
            $t[] = match ($p['forme_yeux'] ?? 'amande') {
                'ronds'   => sprintf('<circle cx="%d" cy="79" r="5" fill="#fff"/><circle cx="%d" cy="79" r="3.4" fill="url(#iris)"/>'
                    . '<circle cx="%d" cy="79" r="1.5" fill="#120c06"/><circle cx="%s" cy="77.6" r="1" fill="#fff"/>', $x, $x, $x, $x + 1.2),
                'plisses' => sprintf('<path d="M%d 80 Q%d 76 %d 80" stroke="#2b1d12" stroke-width="2" fill="none" stroke-linecap="round"/>'
                    . '<circle cx="%d" cy="79.4" r="1.6" fill="url(#iris)"/>', $x - 6, $x, $x + 6, $x),
                'grands'  => sprintf('<ellipse cx="%d" cy="79" rx="6.5" ry="6" fill="#fff"/><circle cx="%d" cy="79.5" r="4.4" fill="url(#iris)"/>'
                    . '<circle cx="%d" cy="79.5" r="2" fill="#120c06"/><circle cx="%s" cy="77.4" r="1.4" fill="#fff"/>', $x, $x, $x, $x + 1.5),
                default   => sprintf('<path d="M%d 79 Q%d 74 %d 79 Q%d 83 %d 79Z" fill="#fff"/><circle cx="%d" cy="79" r="2.8" fill="url(#iris)"/>'
                    . '<circle cx="%d" cy="79" r="1.2" fill="#120c06"/><circle cx="%s" cy="78" r="0.8" fill="#fff"/>',
                    $x - 6.5, $x, $x + 6.5, $x, $x - 6.5, $x, $x, $x + 1),
            };
            // Paupière supérieure (trait), cils pour les personnages féminins
            if (($p['forme_yeux'] ?? '') !== 'plisses') {
                $t[] = sprintf('<path d="M%d 77.5 Q%d 73.5 %d 77.5" stroke="#2b1d12" stroke-width="1.3" fill="none"/>', $x - 6, $x, $x + 6);
                if ($genre === 'feminin') {
                    $t[] = sprintf('<path d="M%d 76 l-2 -2 M%d 75 l1 -2.5" stroke="#2b1d12" stroke-width="1" stroke-linecap="round"/>',
                        $x > 100 ? $x + 6 : $x - 6, $x > 100 ? $x + 3 : $x - 3);
                }
            }
        }

        $t[] = match ($p['forme_nez'] ?? 'droit') {
            'retrousse' => sprintf('<path d="M100 84 q-2 8 -4 9 q4 2 7 -0.5" stroke="%s" stroke-width="1.6" fill="none" stroke-linecap="round"/>', $trait),
            'aquilin'   => sprintf('<path d="M100 82 q6 7 2 13 l-5 0" stroke="%s" stroke-width="1.6" fill="none" stroke-linecap="round"/>', $trait),
            'large'     => sprintf('<path d="M96 88 q-5 7 1 8 h6 q6 -1 1 -8" stroke="%s" stroke-width="1.6" fill="none" stroke-linecap="round"/>', $trait),
            default     => sprintf('<path d="M100 83 L98 93 Q100 95 103 93" stroke="%s" stroke-width="1.6" fill="none" stroke-linecap="round"/>', $trait),
        };
        $levres = $genre === 'feminin' ? '#b8455a' : '#9a4a4e';
        $t[] = match ($p['forme_bouche'] ?? 'fine') {
            'pleine'    => sprintf('<path d="M92 102 Q96 99 100 101 Q104 99 108 102 Q100 109 92 102Z" fill="%s"/>', $levres),
            'souriante' => sprintf('<path d="M91 100 Q100 110 109 100 Q100 104 91 100Z" fill="%s"/><path d="M93 101 Q100 104 107 101" stroke="#fff" stroke-width="1.2" fill="none"/>', $levres),
            'severe'    => sprintf('<path d="M92 104 Q100 100 108 104" stroke="%s" stroke-width="2.2" fill="none" stroke-linecap="round"/>', self::assombrir($levres, 0.2)),
            default     => sprintf('<path d="M93 102 Q100 104 107 102" stroke="%s" stroke-width="2.2" fill="none" stroke-linecap="round"/>', $levres),
        };

        $reflet = self::eclaircir($cheveux, 0.45);
        $t[] = match ($p['coiffure'] ?? 'courte') {
            'chauve' => '<path d="M84 52 Q92 47 100 48" stroke="#fff" stroke-width="3" opacity="0.25" fill="none" stroke-linecap="round"/>',
            'crete'  => '<path d="M86 58 Q84 30 92 16 Q96 34 98 22 Q101 8 104 24 Q108 32 110 18 Q118 34 114 58 Q100 50 86 58Z" fill="url(#cheveux)"/>'
                . sprintf('<path d="M74 70 Q76 56 86 52 M126 70 Q124 56 114 52" stroke="%s" stroke-width="3" opacity="0.35" fill="none"/>', $cheveux),
            'tresse' => '<path d="M70 78 Q66 40 100 38 Q134 40 130 78 Q124 60 108 56 Q96 66 78 64 Q72 70 70 78Z" fill="url(#cheveux)"/>'
                . sprintf('<g fill="url(#cheveux)" stroke="%s" stroke-width="0.8">%s</g>', self::assombrir($cheveux, 0.3), implode('', array_map(
                    static fn (int $y) => sprintf('<ellipse cx="72" cy="%d" rx="6.5" ry="8"/>', $y),
                    [100, 113, 126, 139, 152, 165, 178]
                ))) . sprintf('<circle cx="72" cy="188" r="3.5" fill="%s"/>', $reflet),
            'longue' => '<path d="M70 86 Q64 40 100 38 Q136 40 130 86 Q126 62 112 54 Q98 64 84 60 Q74 68 70 86Z" fill="url(#cheveux)"/>',
            default  => '<path d="M71 76 Q68 40 100 38 Q132 40 129 76 Q126 60 116 56 L112 62 L106 55 L99 62 L93 55 L86 62 L82 56 Q74 62 71 76Z" fill="url(#cheveux)"/>',
        };
        if (($p['coiffure'] ?? '') !== 'chauve') {
            $t[] = sprintf('<path d="M84 46 Q96 40 112 44" stroke="%s" stroke-width="2" opacity="0.6" fill="none" stroke-linecap="round"/>', $reflet);
        }

        return implode('', $t);
    }

    /** N'accepte qu'une couleur #rrggbb (sinon gris) : rien d'autre ne peut entrer dans le SVG. */
    private static function couleurSure(string $c): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $c) === 1 ? strtolower($c) : '#888888';
    }

    /** Couleur plus foncée (contours, ombres). */
    public static function assombrir(string $hex, float $taux): string
    {
        $hex = self::couleurSure($hex);
        $rgb = array_map(static fn ($i) => (int) round(hexdec(substr($hex, $i, 2)) * (1 - $taux)), [1, 3, 5]);

        return vsprintf('#%02x%02x%02x', $rgb);
    }

    /** Couleur plus claire (reflets, lumières). */
    public static function eclaircir(string $hex, float $taux): string
    {
        $hex = self::couleurSure($hex);
        $rgb = array_map(static fn ($i) => (int) round(($v = hexdec(substr($hex, $i, 2))) + (255 - $v) * $taux), [1, 3, 5]);

        return vsprintf('#%02x%02x%02x', $rgb);
    }
}
