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

    /** Silhouette selon le genre : demi-largeurs des épaules, de la taille et des hanches. */
    private const SILHOUETTES = [
        'masculin'    => ['epaules' => 36, 'taille' => 25, 'hanches' => 25],
        'feminin'     => ['epaules' => 28, 'taille' => 19, 'hanches' => 27],
        'non_binaire' => ['epaules' => 32, 'taille' => 22, 'hanches' => 25],
    ];

    /** Étoiles du décor (positions fixes : chaque avatar a le même ciel). */
    private const ETOILES = [[18, 30, 1.2], [42, 18, 0.8], [70, 34, 1], [150, 22, 1.3], [176, 44, 0.9], [188, 16, 0.7],
        [24, 78, 0.8], [182, 90, 1.1], [12, 120, 0.7], [190, 140, 0.8], [36, 150, 0.6], [162, 64, 0.6]];

    /**
     * Dessine l'avatar : décor de nuit étoilée, silhouette, visage, coiffure et équipement.
     * Les plans sont superposés du fond vers l'avant (ailes, cape, cheveux longs, corps, armure, bras,
     * tête, armes, mains).
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
        $s = self::SILHOUETTES[$genre];
        $ombre = self::assombrir($peau, 0.22);
        $trait = self::assombrir($peau, 0.45);

        $equip = [];
        foreach ($equipement as $a) {
            if (isset(self::VISUELS[$a['categorie']][$a['visuel']])) {
                $equip[$a['categorie']] = ['visuel' => $a['visuel'], 'couleur' => self::couleurSure($a['couleur'])];
            }
        }
        $vetement = $equip['vetement']['visuel'] ?? null;
        $accessoire = $equip['accessoire']['visuel'] ?? null;

        // Repères du corps
        $gx = 100 - $s['epaules'];          // épaule gauche (à gauche de l'image)
        $dx = 100 + $s['epaules'];          // épaule droite
        $mainG = [$gx - 3, 190];
        $mainD = [$dx + 3, 190];

        $defs = [];
        $c = [];

        // ---------- Décor : ciel de nuit, lune, étoiles, colline ----------
        $defs[] = '<linearGradient id="ciel" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0f0b26"/>'
            . '<stop offset="0.6" stop-color="#2c1f55"/><stop offset="1" stop-color="#4a3279"/></linearGradient>';
        $defs[] = '<radialGradient id="aura" cx="0.5" cy="0.45" r="0.5"><stop offset="0" stop-color="#8e74d6" stop-opacity="0.55"/>'
            . '<stop offset="1" stop-color="#8e74d6" stop-opacity="0"/></radialGradient>';
        $defs[] = '<filter id="lueur" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="3"/></filter>';
        $defs[] = sprintf('<linearGradient id="peau" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="%s"/>'
            . '<stop offset="0.45" stop-color="%s"/><stop offset="1" stop-color="%s"/></linearGradient>',
            self::eclaircir($peau, 0.12), $peau, $ombre);
        $defs[] = sprintf('<linearGradient id="cheveux" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="%s"/>'
            . '<stop offset="1" stop-color="%s"/></linearGradient>', self::eclaircir($cheveux, 0.25), self::assombrir($cheveux, 0.2));
        $defs[] = sprintf('<radialGradient id="iris" cx="0.4" cy="0.35" r="0.65"><stop offset="0" stop-color="%s"/>'
            . '<stop offset="1" stop-color="%s"/></radialGradient>', self::eclaircir($yeux, 0.35), self::assombrir($yeux, 0.25));

        $c[] = '<rect width="200" height="260" rx="16" fill="url(#ciel)"/>';
        foreach (self::ETOILES as [$x, $y, $r]) {
            $c[] = sprintf('<circle cx="%s" cy="%s" r="%s" fill="#fff6d8" opacity="0.85"/>', $x, $y, $r);
        }
        $c[] = '<path d="M166 18 l1.6 4.4 4.4 1.6 -4.4 1.6 -1.6 4.4 -1.6 -4.4 -4.4 -1.6 4.4 -1.6z" fill="#fff6d8"/>'; // étoile scintillante
        $c[] = '<circle cx="30" cy="40" r="11" fill="#f3e6b4" opacity="0.95"/><circle cx="35" cy="36" r="10" fill="#1a1338"/>'; // croissant de lune
        $c[] = '<circle cx="100" cy="120" r="92" fill="url(#aura)"/>';
        $c[] = '<path d="M0 228 Q50 212 100 222 T200 216 V260 H0Z" fill="#1a1234"/>'
            . '<path d="M0 240 Q60 228 120 238 T200 234 V260 H0Z" fill="#130d29"/>';
        $c[] = '<ellipse cx="100" cy="242" rx="46" ry="6" fill="#000" opacity="0.45"/>';

        // ---------- Plans arrière ----------
        if ($accessoire === 'ailes') {
            $col = $equip['accessoire']['couleur'];
            $defs[] = sprintf('<linearGradient id="aile" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="%s" stop-opacity="0.9"/>'
                . '<stop offset="1" stop-color="%s" stop-opacity="0.35"/></linearGradient>', self::eclaircir($col, 0.3), $col);
            foreach (['', ' transform="translate(200 0) scale(-1 1)"'] as $miroir) {
                $c[] = '<g' . $miroir . '>'
                    . '<path d="M88 132 C60 70 14 66 10 104 C8 128 30 138 52 140 C24 150 18 182 36 196 C54 206 74 178 88 152Z" '
                    . 'fill="url(#aile)" stroke="' . self::eclaircir($col, 0.5) . '" stroke-width="1.2"/>'
                    . '<path d="M86 136 C66 104 36 92 20 104 M86 140 C62 132 40 136 30 142 M86 148 C66 160 48 176 40 190" '
                    . 'stroke="' . self::eclaircir($col, 0.6) . '" stroke-width="0.8" fill="none" opacity="0.8"/></g>';
            }
            $c[] = '<circle cx="30" cy="110" r="1.4" fill="#fff"/><circle cx="168" cy="118" r="1.2" fill="#fff"/>'
                . '<circle cx="44" cy="182" r="1" fill="#fff"/><circle cx="160" cy="176" r="1.3" fill="#fff"/>';
        }
        if ($vetement === 'cape') {
            $col = $equip['vetement']['couleur'];
            $c[] = sprintf('<path d="M%d 122 Q100 112 %d 122 L%d 244 Q100 236 %d 244Z" fill="%s"/>'
                . '<path d="M%d 124 L%d 244" stroke="%s" stroke-width="5" opacity="0.5"/>',
                $gx - 2, $dx + 2, $dx + 26, $gx - 26, self::assombrir($col, 0.15), $dx + 2, $dx + 22, self::eclaircir($col, 0.2));
        }
        if (in_array($p['coiffure'] ?? '', ['longue', 'tresse'], true)) {
            $c[] = '<path d="M70 70 C62 100 58 132 66 162 Q100 172 134 162 C142 132 138 100 130 70Z" fill="url(#cheveux)"/>';
        }

        // ---------- Jambes et bottes ----------
        $c[] = sprintf('<path d="M%d 194 L%d 228 L98 228 L99 194Z" fill="#4a3a2c"/><path d="M101 194 L102 228 L%d 228 L%d 194Z" fill="#3f3125"/>',
            100 - $s['hanches'] + 4, 100 - $s['hanches'] + 6, 100 + $s['hanches'] - 6, 100 + $s['hanches'] - 4);
        $c[] = sprintf('<path d="M%d 222 h%d q3 0 4 4 l1 12 h-%d z" fill="#2e2018"/><path d="M102 222 h%d q3 0 4 4 l1 12 h-%d z" fill="#261a13"/>',
            100 - $s['hanches'] + 4, $s['hanches'] - 6, $s['hanches'] - 1, $s['hanches'] - 6, $s['hanches'] - 1);

        // ---------- Buste et vêtement ----------
        $col = ($vetement !== null && $vetement !== 'cape') ? $equip['vetement']['couleur'] : '#d8cdb2';
        $defs[] = sprintf('<linearGradient id="tissu" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="%s"/>'
            . '<stop offset="0.5" stop-color="%s"/><stop offset="1" stop-color="%s"/></linearGradient>',
            self::eclaircir($col, 0.15), $col, self::assombrir($col, 0.25));
        $buste = sprintf('M%d 124 Q100 114 %d 124 L%d 180 L%d 196 L%d 196 L%d 180Z',
            $gx, $dx, 100 + $s['taille'], 100 + $s['hanches'], 100 - $s['hanches'], 100 - $s['taille']);
        $or = '#e0b74f';

        if ($vetement === 'robe') {
            $c[] = sprintf('<path d="M%d 124 Q100 114 %d 124 L%d 180 L%d 244 Q100 250 %d 244 L%d 180Z" fill="url(#tissu)"/>',
                $gx, $dx, 100 + $s['taille'], 100 + $s['hanches'] + 20, 100 - $s['hanches'] - 20, 100 - $s['taille']);
            $c[] = sprintf('<path d="M%d 242 Q100 248 %d 242" stroke="%s" stroke-width="3" fill="none"/>', 100 - $s['hanches'] - 19, 100 + $s['hanches'] + 19, $or);
            $c[] = sprintf('<path d="M100 132 L100 244" stroke="%s" stroke-width="1.5" opacity="0.6"/>', $or);
            foreach ([[84, 206], [116, 214], [92, 230], [112, 196]] as [$x, $y]) {
                $c[] = sprintf('<path d="M%d %d l1.5 4 4 1.5 -4 1.5 -1.5 4 -1.5 -4 -4 -1.5 4 -1.5z" fill="%s" opacity="0.9"/>', $x, $y - 5, $or);
            }
            $c[] = sprintf('<path d="M%d 178 Q100 184 %d 178 L%d 184 Q100 190 %d 184Z" fill="%s"/>',
                100 - $s['taille'], 100 + $s['taille'], 100 + $s['taille'] + 1, 100 - $s['taille'] - 1, $or);
        } elseif ($vetement === 'tunique') {
            $c[] = sprintf('<path d="M%d 124 Q100 114 %d 124 L%d 180 L%d 210 Q100 216 %d 210 L%d 180Z" fill="url(#tissu)"/>',
                $gx, $dx, 100 + $s['taille'], 100 + $s['hanches'] + 6, 100 - $s['hanches'] - 6, 100 - $s['taille']);
            $c[] = sprintf('<path d="M%d 208 Q100 214 %d 208" stroke="%s" stroke-width="2.5" fill="none"/>',
                100 - $s['hanches'] - 5, 100 + $s['hanches'] + 5, self::eclaircir($col, 0.35));
            $c[] = sprintf('<rect x="%d" y="176" width="%d" height="8" rx="2" fill="#5a3a22"/><rect x="95" y="175" width="10" height="10" rx="2" fill="none" stroke="%s" stroke-width="2"/>',
                100 - $s['taille'] - 2, 2 * $s['taille'] + 4, $or);
        } else {
            // Chemise de lin et gilet de cuir par défaut
            $c[] = sprintf('<path d="%s" fill="url(#tissu)"/>', $buste);
            $c[] = sprintf('<path d="M%d 128 L%d 196 L92 196 L94 130Z M%d 128 L%d 196 L108 196 L106 130Z" fill="#6b4a2f"/>',
                $gx + 8, 100 - $s['hanches'] + 2, $dx - 8, 100 + $s['hanches'] - 2);
            $c[] = sprintf('<rect x="%d" y="178" width="%d" height="6" fill="#3d2a1a"/>', 100 - $s['taille'] - 1, 2 * $s['taille'] + 2);
        }
        // Col
        $c[] = sprintf('<path d="M91 119 L100 133 L109 119" fill="none" stroke="%s" stroke-width="2"/>', self::assombrir($col, 0.35));

        // ---------- Armure ----------
        if (isset($equip['armure'])) {
            $col = $equip['armure']['couleur'];
            $defs[] = sprintf('<linearGradient id="metal" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="%s"/>'
                . '<stop offset="0.5" stop-color="%s"/><stop offset="1" stop-color="%s"/></linearGradient>',
                self::eclaircir($col, 0.45), $col, self::assombrir($col, 0.35));
            $fonce = self::assombrir($col, 0.45);
            switch ($equip['armure']['visuel']) {
                case 'plastron':
                    $c[] = sprintf('<path d="M%d 126 Q100 118 %d 126 L%d 176 Q100 190 %d 176Z" fill="url(#metal)" stroke="%s" stroke-width="1.5"/>',
                        $gx + 6, $dx - 6, 100 + $s['taille'] + 2, 100 - $s['taille'] - 2, $fonce);
                    $c[] = sprintf('<path d="M100 124 L100 184" stroke="%s" stroke-width="1.5"/><path d="M%d 150 Q100 158 %d 150" stroke="%s" fill="none"/>',
                        $fonce, 100 - $s['taille'] - 2, 100 + $s['taille'] + 2, $fonce);
                    foreach ([[$gx + 10, 132], [$dx - 10, 132], [100 - $s['taille'], 172], [100 + $s['taille'], 172]] as [$x, $y]) {
                        $c[] = sprintf('<circle cx="%d" cy="%d" r="1.6" fill="%s"/>', $x, $y, $or);
                    }
                    break;
                case 'cotte':
                    $defs[] = sprintf('<pattern id="mailles" width="5" height="4" patternUnits="userSpaceOnUse">'
                        . '<rect width="5" height="4" fill="%s"/><circle cx="2.5" cy="2" r="1.8" fill="none" stroke="%s" stroke-width="0.8"/></pattern>',
                        self::assombrir($col, 0.2), self::eclaircir($col, 0.3));
                    $c[] = sprintf('<path d="M%d 126 Q100 118 %d 126 L%d 180 L%d 204 Q100 208 %d 204 L%d 180Z" fill="url(#mailles)" stroke="%s"/>',
                        $gx + 2, $dx - 2, 100 + $s['taille'] + 2, 100 + $s['hanches'] + 3, 100 - $s['hanches'] - 3, 100 - $s['taille'] - 2, $fonce);
                    break;
                case 'epaulieres':
                    foreach ([[$gx, -1], [$dx, 1]] as [$x, $sens]) {
                        $c[] = sprintf('<path d="M%d 124 Q%d 112 %d 134 Q%d 140 %d 130Z" fill="url(#metal)" stroke="%s" stroke-width="1.5"/>',
                            $x - 12 * $sens, $x + 6 * $sens, $x + 16 * $sens, $x + 4 * $sens, $x - 12 * $sens, $fonce);
                        $c[] = sprintf('<path d="M%d 118 l%d -12 l%d 10Z M%d 122 l%d -10 l%d 9Z" fill="%s"/>',
                            $x, 4 * $sens, 4 * $sens, $x + 8 * $sens, 5 * $sens, 3 * $sens, self::eclaircir($col, 0.4));
                    }
                    break;
            }
        }

        // ---------- Bras (manches) ----------
        $manche = ($vetement !== null && $vetement !== 'cape') ? $equip['vetement']['couleur'] : '#cfc4a8';
        foreach ([[$gx + 4, $gx - 5, $mainG[0]], [$dx - 4, $dx + 5, $mainD[0]]] as [$x1, $x2, $x3]) {
            $c[] = sprintf('<path d="M%d 128 Q%d 156 %d 184" stroke="%s" stroke-width="12" stroke-linecap="round" fill="none"/>',
                $x1, $x2, $x3, self::assombrir($manche, 0.08));
        }

        // ---------- Tête ----------
        $c[] = sprintf('<path d="M92 100 L92 120 Q100 126 108 120 L108 100Z" fill="%s"/>', $ombre); // cou
        // Oreilles (pointues pour les peaux elfiques)
        if (in_array($peau, self::PEAUX_ELFIQUES, true)) {
            $c[] = sprintf('<path d="M74 80 L56 62 L72 92Z M126 80 L144 62 L128 92Z" fill="%s" stroke="%s" stroke-width="1"/>', $peau, $trait);
        } else {
            $c[] = sprintf('<ellipse cx="73" cy="82" rx="5" ry="8" fill="%s"/><ellipse cx="127" cy="82" rx="5" ry="8" fill="%s"/>', $ombre, $ombre);
        }
        $c[] = match ($p['forme_visage'] ?? 'ovale') {
            'rond'    => '<circle cx="100" cy="80" r="29" fill="url(#peau)"/>',
            'carre'   => '<path d="M72 56 Q72 48 82 48 L118 48 Q128 48 128 56 L128 92 Q128 110 100 112 Q72 110 72 92Z" fill="url(#peau)"/>',
            'allonge' => '<path d="M100 44 C120 44 126 60 125 80 C124 100 114 116 100 116 C86 116 76 100 75 80 C74 60 80 44 100 44Z" fill="url(#peau)"/>',
            default   => '<path d="M100 46 C122 46 128 62 127 80 C126 98 115 112 100 112 C85 112 74 98 73 80 C72 62 78 46 100 46Z" fill="url(#peau)"/>',
        };
        $c[] = '<ellipse cx="86" cy="94" rx="5" ry="3" fill="#e0607a" opacity="0.22"/><ellipse cx="114" cy="94" rx="5" ry="3" fill="#e0607a" opacity="0.22"/>';

        // Sourcils
        $sourcil = ($p['coiffure'] ?? '') === 'chauve' ? $trait : self::assombrir($cheveux, 0.15);
        $c[] = sprintf('<path d="M81 70 Q88 66 94 69 M106 69 Q112 66 119 70" stroke="%s" stroke-width="2.2" fill="none" stroke-linecap="round"/>', $sourcil);

        // Yeux
        foreach ([88, 112] as $x) {
            $c[] = match ($p['forme_yeux'] ?? 'amande') {
                'ronds'   => sprintf('<circle cx="%d" cy="79" r="5" fill="#fff"/><circle cx="%d" cy="79" r="3.4" fill="url(#iris)"/>'
                    . '<circle cx="%d" cy="79" r="1.5" fill="#120c06"/><circle cx="%s" cy="77.6" r="1" fill="#fff"/>', $x, $x, $x, $x + 1.2),
                'plisses' => sprintf('<path d="M%d 80 Q%d 76 %d 80" stroke="#2b1d12" stroke-width="2" fill="none" stroke-linecap="round"/>'
                    . '<circle cx="%d" cy="79.4" r="1.6" fill="url(#iris)"/>', $x - 6, $x, $x + 6, $x),
                'grands'  => sprintf('<ellipse cx="%d" cy="79" rx="6.5" ry="6" fill="#fff"/><circle cx="%d" cy="79.5" r="4.4" fill="url(#iris)"/>'
                    . '<circle cx="%d" cy="79.5" r="2" fill="#120c06"/><circle cx="%s" cy="77.4" r="1.4" fill="#fff"/><circle cx="%s" cy="81.4" r="0.7" fill="#fff"/>',
                    $x, $x, $x, $x + 1.5, $x - 1.4),
                default   => sprintf('<path d="M%d 79 Q%d 74 %d 79 Q%d 83 %d 79Z" fill="#fff"/><circle cx="%d" cy="79" r="2.8" fill="url(#iris)"/>'
                    . '<circle cx="%d" cy="79" r="1.2" fill="#120c06"/><circle cx="%s" cy="78" r="0.8" fill="#fff"/>',
                    $x - 6.5, $x, $x + 6.5, $x, $x - 6.5, $x, $x, $x + 1),
            };
            if ($genre === 'feminin' && ($p['forme_yeux'] ?? '') !== 'plisses') {
                $c[] = sprintf('<path d="M%d 76 l-2 -2 M%d 75 l1 -2.5" stroke="#2b1d12" stroke-width="1" stroke-linecap="round"/>',
                    $x > 100 ? $x + 6 : $x - 6, $x > 100 ? $x + 3 : $x - 3);
            }
        }

        // Nez et bouche
        $c[] = match ($p['forme_nez'] ?? 'droit') {
            'retrousse' => sprintf('<path d="M100 84 q-2 8 -4 9 q4 2 7 -0.5" stroke="%s" stroke-width="1.6" fill="none" stroke-linecap="round"/>', $trait),
            'aquilin'   => sprintf('<path d="M100 82 q6 7 2 13 l-5 0" stroke="%s" stroke-width="1.6" fill="none" stroke-linecap="round"/>', $trait),
            'large'     => sprintf('<path d="M96 88 q-5 7 1 8 h6 q6 -1 1 -8" stroke="%s" stroke-width="1.6" fill="none" stroke-linecap="round"/>', $trait),
            default     => sprintf('<path d="M100 83 L98 93 Q100 95 103 93" stroke="%s" stroke-width="1.6" fill="none" stroke-linecap="round"/>', $trait),
        };
        $levres = $genre === 'feminin' ? '#b8455a' : '#9a4a4e';
        $c[] = match ($p['forme_bouche'] ?? 'fine') {
            'pleine'    => sprintf('<path d="M92 102 Q96 99 100 101 Q104 99 108 102 Q100 109 92 102Z" fill="%s"/>', $levres),
            'souriante' => sprintf('<path d="M91 100 Q100 110 109 100 Q100 104 91 100Z" fill="%s"/><path d="M93 101 Q100 104 107 101" stroke="#fff" stroke-width="1.2" fill="none"/>', $levres),
            'severe'    => sprintf('<path d="M92 104 Q100 100 108 104" stroke="%s" stroke-width="2.2" fill="none" stroke-linecap="round"/>', self::assombrir($levres, 0.2)),
            default     => sprintf('<path d="M93 102 Q100 104 107 102" stroke="%s" stroke-width="2.2" fill="none" stroke-linecap="round"/>', $levres),
        };

        // Cheveux (devant)
        $reflet = self::eclaircir($cheveux, 0.45);
        $c[] = match ($p['coiffure'] ?? 'courte') {
            'chauve' => '<path d="M84 52 Q92 47 100 48" stroke="#fff" stroke-width="3" opacity="0.25" fill="none" stroke-linecap="round"/>',
            'crete'  => '<path d="M86 58 Q84 30 92 16 Q96 34 98 22 Q101 8 104 24 Q108 32 110 18 Q118 34 114 58 Q100 50 86 58Z" fill="url(#cheveux)"/>'
                . sprintf('<path d="M74 70 Q76 56 86 52 M126 70 Q124 56 114 52" stroke="%s" stroke-width="3" opacity="0.35" fill="none"/>', $cheveux),
            'tresse' => '<path d="M70 78 Q66 40 100 38 Q134 40 130 78 Q124 60 108 56 Q96 66 78 64 Q72 70 70 78Z" fill="url(#cheveux)"/>'
                . sprintf('<g fill="url(#cheveux)" stroke="%s" stroke-width="0.8">%s</g>', self::assombrir($cheveux, 0.3), implode('', array_map(
                    static fn (int $y) => sprintf('<ellipse cx="68" cy="%d" rx="6.5" ry="8"/>', $y),
                    [96, 109, 122, 135, 148, 161]
                ))) . sprintf('<circle cx="68" cy="170" r="3.5" fill="%s"/>', $reflet),
            'longue' => '<path d="M70 82 Q64 40 100 38 Q136 40 130 82 Q126 62 112 54 Q98 64 84 60 Q74 68 70 82Z" fill="url(#cheveux)"/>',
            default  => '<path d="M71 76 Q68 40 100 38 Q132 40 129 76 Q126 60 116 56 L112 62 L106 55 L99 62 L93 55 L86 62 L82 56 Q74 62 71 76Z" fill="url(#cheveux)"/>',
        };
        if (($p['coiffure'] ?? '') !== 'chauve') {
            $c[] = sprintf('<path d="M84 46 Q96 40 112 44" stroke="%s" stroke-width="2" opacity="0.6" fill="none" stroke-linecap="round"/>', $reflet);
        }

        // ---------- Accessoires portés ----------
        if ($accessoire === 'couronne') {
            $col = $equip['accessoire']['couleur'];
            $c[] = sprintf('<path d="M76 50 L74 30 L84 40 L92 24 L100 38 L108 24 L116 40 L126 30 L124 50 Q100 44 76 50Z" fill="%s" stroke="%s" stroke-width="1.5"/>',
                $col, self::assombrir($col, 0.4));
            $c[] = '<circle cx="100" cy="44" r="3" fill="#d33a5a"/><circle cx="86" cy="46" r="2" fill="#3a7bd3"/><circle cx="114" cy="46" r="2" fill="#3a7bd3"/>';
        }
        if ($accessoire === 'amulette') {
            $col = $equip['accessoire']['couleur'];
            $c[] = sprintf('<path d="M90 118 Q100 136 110 118" stroke="%s" stroke-width="1.5" fill="none"/>', $or);
            $c[] = sprintf('<circle cx="100" cy="140" r="9" fill="%s" filter="url(#lueur)" opacity="0.8"/>', $col);
            $c[] = sprintf('<path d="M100 132 L106 140 L100 148 L94 140Z" fill="%s" stroke="%s" stroke-width="1.5"/>', $col, $or);
        }
        if ($accessoire === 'bouclier') {
            $col = $equip['accessoire']['couleur'];
            [$x, $y] = $mainG;
            $c[] = sprintf('<path d="M%d %d l24 -8 l24 8 q0 34 -24 50 q-24 -16 -24 -50Z" fill="%s" stroke="%s" stroke-width="3"/>',
                $x - 24, $y - 26, $col, $or);
            $c[] = sprintf('<path d="M%d %d l24 -8 l0 58 q-24 -16 -24 -50Z" fill="#fff" opacity="0.12"/>', $x - 24, $y - 26);
            $c[] = sprintf('<path d="M%d %d l3 8 8 3 -8 3 -3 8 -3 -8 -8 -3 8 -3z" fill="%s"/>', $x, $y - 18, $or);
        }

        // ---------- Arme (main droite) ----------
        if (isset($equip['arme'])) {
            $col = $equip['arme']['couleur'];
            [$x, $y] = $mainD;
            switch ($equip['arme']['visuel']) {
                case 'epee':
                    $c[] = sprintf('<path d="M%d %d L%d 88 L%d 80 L%d 88 L%d %d Z" fill="%s" stroke="%s" stroke-width="0.8"/>',
                        $x - 3, $y - 8, $x - 3, $x, $x + 3, $x + 3, $y - 8, self::eclaircir($col, 0.2), self::assombrir($col, 0.4));
                    $c[] = sprintf('<path d="M%d 94 L%d %d" stroke="#7fe0ff" stroke-width="1.6" filter="url(#lueur)"/><path d="M%d 94 L%d %d" stroke="#bff3ff" stroke-width="0.7"/>',
                        $x, $x, $y - 14, $x, $x, $y - 14);
                    $c[] = sprintf('<rect x="%d" y="%d" width="22" height="4" rx="2" fill="%s"/><rect x="%d" y="%d" width="4" height="14" fill="#4a2f1c"/>'
                        . '<circle cx="%d" cy="%d" r="3" fill="#d33a5a" stroke="%s"/>', $x - 11, $y - 9, $or, $x - 2, $y - 5, $x, $y + 11, $or);
                    break;
                case 'hache':
                    $c[] = sprintf('<path d="M%d %d L%d 112" stroke="#5a3a22" stroke-width="5" stroke-linecap="round"/>', $x, $y + 40, $x);
                    $c[] = sprintf('<path d="M%d 116 Q%d 108 %d 128 Q%d 140 %d 144 Q%d 132 %d 130Z M%d 116 Q%d 108 %d 128 Q%d 140 %d 144 Q%d 132 %d 130Z" '
                        . 'fill="%s" stroke="%s" stroke-width="1.2"/>',
                        $x + 2, $x + 26, $x + 24, $x + 22, $x + 2, $x + 12, $x + 2,
                        $x - 2, $x - 26, $x - 24, $x - 22, $x - 2, $x - 12, $x - 2,
                        self::eclaircir($col, 0.15), self::assombrir($col, 0.45));
                    $c[] = sprintf('<path d="M%d 112 l-4 -8 h8z" fill="%s"/>', $x, $or);
                    break;
                case 'arc':
                    $c[] = sprintf('<path d="M%d 132 Q%d 190 %d 248" stroke="%s" stroke-width="4.5" fill="none" stroke-linecap="round"/>'
                        . '<path d="M%d 132 L%d 248" stroke="#efe6cf" stroke-width="1"/>',
                        $x + 4, $x + 34, $x + 4, $col, $x + 4, $x + 4);
                    $c[] = sprintf('<path d="M%d 186 L%d 186" stroke="#b9a37a" stroke-width="1.5"/><path d="M%d 186 l-6 -3 v6z" fill="#cfd8e3"/>'
                        . '<path d="M%d 186 l4 -3 M%d 186 l4 3" stroke="#e8e0f0" stroke-width="1.5"/>', $x - 22, $x + 36, $x + 42, $x - 22, $x - 22);
                    break;
                case 'baton':
                    $c[] = sprintf('<path d="M%d %d L%d 104" stroke="#5a3a22" stroke-width="5" stroke-linecap="round"/>'
                        . '<path d="M%d 106 Q%d 92 %d 84 M%d 106 Q%d 92 %d 84" stroke="#5a3a22" stroke-width="3" fill="none" stroke-linecap="round"/>',
                        $x, $y + 48, $x, $x, $x - 10, $x - 4, $x, $x + 10, $x + 4);
                    $c[] = sprintf('<circle cx="%d" cy="92" r="14" fill="%s" filter="url(#lueur)" opacity="0.75"/>', $x, $col);
                    $c[] = sprintf('<circle cx="%d" cy="92" r="7" fill="%s" stroke="%s"/><circle cx="%d" cy="89.5" r="2.2" fill="#fff" opacity="0.8"/>',
                        $x, self::eclaircir($col, 0.2), self::eclaircir($col, 0.6), $x - 2);
                    $c[] = sprintf('<circle cx="%d" cy="76" r="1.2" fill="#fff"/><circle cx="%d" cy="98" r="1" fill="#fff"/><circle cx="%d" cy="84" r="0.9" fill="#fff"/>',
                        $x + 12, $x - 14, $x - 12);
                    break;
            }
        }

        // ---------- Mains (au premier plan : elles tiennent l'arme et le bouclier) ----------
        foreach ([$mainG, $mainD] as [$x, $y]) {
            $c[] = sprintf('<circle cx="%d" cy="%d" r="6" fill="%s"/>', $x, $y - 4, $peau);
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 260" role="img">'
            . '<title>' . htmlspecialchars($titre, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</title>'
            . '<defs>' . implode('', $defs) . '</defs>'
            . implode('', $c) . '</svg>';
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
