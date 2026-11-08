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

    /**
     * Dessine l'avatar.
     * @param array<string, string> $p traits (déjà validés)
     * @param list<array{categorie: string, visuel: string, couleur: string}> $equipement
     */
    public static function svg(array $p, array $equipement = [], string $titre = 'Personnage'): string
    {
        $peau = self::couleurSure($p['couleur_peau'] ?? '#e8b896');
        $cheveux = self::couleurSure($p['couleur_cheveux'] ?? '#5a3825');
        $yeux = self::couleurSure($p['couleur_yeux'] ?? '#3f7d3a');
        $trait = self::assombrir($peau, 0.35);

        $equip = [];
        foreach ($equipement as $a) {
            if (isset(self::VISUELS[$a['categorie']][$a['visuel']])) {
                $equip[$a['categorie']] = ['visuel' => $a['visuel'], 'couleur' => self::couleurSure($a['couleur'])];
            }
        }

        $c = [];
        $c[] = '<defs><radialGradient id="halo" cx="50%" cy="40%" r="60%">'
            . '<stop offset="0" stop-color="#4b3a78"/><stop offset="1" stop-color="#1c1430"/></radialGradient></defs>';
        $c[] = '<rect width="200" height="260" rx="16" fill="url(#halo)"/>';
        $c[] = '<ellipse cx="100" cy="246" rx="58" ry="8" fill="#000" opacity="0.35"/>';

        // Plans arrière : ailes, cape, cheveux longs
        if (($equip['accessoire']['visuel'] ?? '') === 'ailes') {
            $col = $equip['accessoire']['couleur'];
            $c[] = sprintf('<path d="M78 140 C30 90 10 150 24 200 C40 175 55 170 78 168Z M122 140 C170 90 190 150 176 200 '
                . 'C160 175 145 170 122 168Z" fill="%s" opacity="0.85" stroke="%s" stroke-width="2"/>', $col, self::assombrir($col, 0.3));
        }
        if (($equip['vetement']['visuel'] ?? '') === 'cape') {
            $col = $equip['vetement']['couleur'];
            $c[] = sprintf('<path d="M66 126 L44 244 L156 244 L134 126Z" fill="%s" stroke="%s" stroke-width="2"/>', $col, self::assombrir($col, 0.3));
        }
        if (in_array($p['coiffure'] ?? '', ['longue', 'tresse'], true)) {
            $c[] = sprintf('<path d="M64 76 C60 120 62 150 70 166 L130 166 C138 150 140 120 136 76Z" fill="%s"/>', $cheveux);
        }

        // Corps : silhouette selon le genre
        $epaules = ['masculin' => [56, 144], 'feminin' => [64, 136], 'non_binaire' => [60, 140]][$p['genre'] ?? 'feminin'] ?? [60, 140];
        $tenue = $equip['vetement']['couleur'] ?? '#cfc6b0';
        if (($equip['vetement']['visuel'] ?? '') === 'cape') {
            $tenue = '#cfc6b0';
        }
        $c[] = sprintf('<rect x="92" y="108" width="16" height="18" fill="%s"/>', $peau); // cou
        $bas = ($equip['vetement']['visuel'] ?? '') === 'robe' ? 'L150 244 L50 244' : 'L134 236 L66 236';
        $c[] = sprintf('<path d="M%d 132 Q100 118 %d 132 %s Z" fill="%s" stroke="%s" stroke-width="2"/>',
            $epaules[0], $epaules[1], $bas, $tenue, self::assombrir($tenue, 0.3));
        if (($equip['vetement']['visuel'] ?? '') === 'tunique') {
            $c[] = sprintf('<rect x="66" y="196" width="68" height="8" fill="%s"/>', self::assombrir($tenue, 0.4)); // ceinture
        }
        // Bras
        $c[] = sprintf('<path d="M%d 134 L%d 200" stroke="%s" stroke-width="14" stroke-linecap="round"/>', $epaules[0] + 4, $epaules[0] - 6, $tenue);
        $c[] = sprintf('<path d="M%d 134 L%d 200" stroke="%s" stroke-width="14" stroke-linecap="round"/>', $epaules[1] - 4, $epaules[1] + 6, $tenue);
        $c[] = sprintf('<circle cx="%d" cy="204" r="7" fill="%s"/><circle cx="%d" cy="204" r="7" fill="%s"/>',
            $epaules[0] - 6, $peau, $epaules[1] + 6, $peau);

        // Armure
        if (isset($equip['armure'])) {
            $col = $equip['armure']['couleur'];
            $fonce = self::assombrir($col, 0.35);
            $c[] = match ($equip['armure']['visuel']) {
                'plastron' => sprintf('<path d="M72 134 Q100 124 128 134 L124 190 Q100 200 76 190Z" fill="%s" stroke="%s" stroke-width="2"/>'
                    . '<path d="M100 132 L100 192" stroke="%s" stroke-width="2"/>', $col, $fonce, $fonce),
                'cotte' => sprintf('<path d="M70 134 Q100 124 130 134 L132 212 L68 212Z" fill="%s" stroke="%s" stroke-width="2" '
                    . 'stroke-dasharray="3 2"/>', $col, $fonce),
                'epaulieres' => sprintf('<ellipse cx="%d" cy="134" rx="16" ry="10" fill="%s" stroke="%s" stroke-width="2"/>'
                    . '<ellipse cx="%d" cy="134" rx="16" ry="10" fill="%s" stroke="%s" stroke-width="2"/>',
                    $epaules[0] + 4, $col, $fonce, $epaules[1] - 4, $col, $fonce),
            };
        }

        // Tête
        $c[] = match ($p['forme_visage'] ?? 'ovale') {
            'rond'    => sprintf('<circle cx="100" cy="80" r="33" fill="%s"/>', $peau),
            'carre'   => sprintf('<rect x="68" y="46" width="64" height="68" rx="14" fill="%s"/>', $peau),
            'allonge' => sprintf('<ellipse cx="100" cy="80" rx="26" ry="39" fill="%s"/>', $peau),
            default   => sprintf('<ellipse cx="100" cy="80" rx="30" ry="36" fill="%s"/>', $peau),
        };

        // Yeux
        foreach ([88, 112] as $x) {
            $c[] = match ($p['forme_yeux'] ?? 'amande') {
                'ronds'   => sprintf('<circle cx="%d" cy="78" r="5" fill="#fff"/><circle cx="%d" cy="78" r="3" fill="%s"/>', $x, $x, $yeux),
                'plisses' => sprintf('<path d="M%d 79 q6 -4 12 0" stroke="#2b1d12" stroke-width="2" fill="none"/>'
                    . '<circle cx="%d" cy="78" r="1.8" fill="%s"/>', $x - 6, $x, $yeux),
                'grands'  => sprintf('<ellipse cx="%d" cy="78" rx="7" ry="6" fill="#fff"/><circle cx="%d" cy="78" r="4" fill="%s"/>'
                    . '<circle cx="%d" cy="76" r="1.3" fill="#fff"/>', $x, $x, $yeux, $x + 1),
                default   => sprintf('<ellipse cx="%d" cy="78" rx="6" ry="3.5" fill="#fff"/><circle cx="%d" cy="78" r="2.6" fill="%s"/>', $x, $x, $yeux),
            };
        }

        // Nez et bouche
        $c[] = match ($p['forme_nez'] ?? 'droit') {
            'retrousse' => sprintf('<path d="M100 82 q-2 10 -4 11 q4 2 7 0" stroke="%s" stroke-width="2" fill="none"/>', $trait),
            'aquilin'   => sprintf('<path d="M100 80 q6 8 2 15 l-5 0" stroke="%s" stroke-width="2" fill="none"/>', $trait),
            'large'     => sprintf('<path d="M97 84 q-7 10 0 11 h6 q7 -1 0 -11" stroke="%s" stroke-width="2" fill="none"/>', $trait),
            default     => sprintf('<path d="M100 82 L98 94 L103 94" stroke="%s" stroke-width="2" fill="none"/>', $trait),
        };
        $c[] = match ($p['forme_bouche'] ?? 'fine') {
            'pleine'    => '<ellipse cx="100" cy="103" rx="8" ry="3.5" fill="#b5555a"/>',
            'souriante' => '<path d="M90 100 q10 9 20 0" stroke="#8c3b3f" stroke-width="2.5" fill="none" stroke-linecap="round"/>',
            'severe'    => '<path d="M91 104 q9 -4 18 0" stroke="#6e2f32" stroke-width="2.5" fill="none" stroke-linecap="round"/>',
            default     => '<path d="M92 102 h16" stroke="#8c3b3f" stroke-width="2.5" stroke-linecap="round"/>',
        };

        // Cheveux (devant)
        $frange = '<path d="M68 74 Q66 38 100 38 Q134 38 132 74 Q122 54 100 54 Q78 54 68 74Z" fill="%s"/>';
        $c[] = match ($p['coiffure'] ?? 'courte') {
            'chauve' => sprintf('<ellipse cx="90" cy="54" rx="8" ry="4" fill="#fff" opacity="0.25"/>'),
            'crete'  => sprintf('<path d="M88 50 L92 22 L97 46 L101 18 L105 46 L110 24 L113 50Z" fill="%s"/>', $cheveux),
            'tresse' => sprintf($frange, $cheveux) . sprintf('<g fill="%s">%s</g>', $cheveux, implode('', array_map(
                static fn (int $y) => sprintf('<ellipse cx="131" cy="%d" rx="7" ry="9"/>', $y),
                [100, 114, 128, 142, 156]
            ))),
            default  => sprintf($frange, $cheveux),
        };

        // Accessoires portés
        $acc = $equip['accessoire'] ?? null;
        if ($acc !== null) {
            $fonce = self::assombrir($acc['couleur'], 0.35);
            $c[] = match ($acc['visuel']) {
                'couronne' => sprintf('<path d="M74 46 L78 24 L89 38 L100 20 L111 38 L122 24 L126 46Z" fill="%s" stroke="%s" stroke-width="2"/>',
                    $acc['couleur'], $fonce),
                'amulette' => sprintf('<path d="M88 122 Q100 140 112 122" stroke="%s" stroke-width="2" fill="none"/>'
                    . '<circle cx="100" cy="142" r="7" fill="%s" stroke="%s" stroke-width="2"/>', $fonce, $acc['couleur'], $fonce),
                'bouclier' => sprintf('<path d="M%d 168 l22 -8 l22 8 q0 34 -22 46 q-22 -12 -22 -46Z" fill="%s" stroke="%s" stroke-width="3"/>',
                    $epaules[0] - 30, $acc['couleur'], $fonce),
                default => '',
            };
        }

        // Arme tenue dans la main droite (à droite de l'image)
        if (isset($equip['arme'])) {
            $col = $equip['arme']['couleur'];
            $fonce = self::assombrir($col, 0.4);
            $x = $epaules[1] + 6;
            $c[] = match ($equip['arme']['visuel']) {
                'epee'  => sprintf('<path d="M%d 210 L%d 120" stroke="%s" stroke-width="6" stroke-linecap="round"/>'
                    . '<rect x="%d" y="196" width="24" height="5" fill="%s"/><rect x="%d" y="201" width="5" height="16" fill="#5a3825"/>',
                    $x, $x, $col, $x - 12, $fonce, $x - 2),
                'hache' => sprintf('<path d="M%d 226 L%d 130" stroke="#6b4a2b" stroke-width="5" stroke-linecap="round"/>'
                    . '<path d="M%d 134 q22 4 20 26 q-12 -6 -20 -4Z" fill="%s" stroke="%s" stroke-width="2"/>', $x, $x, $x, $col, $fonce),
                'arc'   => sprintf('<path d="M%d 120 Q%d 170 %d 220" stroke="%s" stroke-width="5" fill="none" stroke-linecap="round"/>'
                    . '<path d="M%d 120 L%d 220" stroke="#e8e2d0" stroke-width="1.5"/>', $x, $x + 34, $x, $col, $x, $x),
                'baton' => sprintf('<path d="M%d 236 L%d 116" stroke="#6b4a2b" stroke-width="6" stroke-linecap="round"/>'
                    . '<circle cx="%d" cy="110" r="10" fill="%s" stroke="%s" stroke-width="2"/>'
                    . '<circle cx="%d" cy="110" r="16" fill="%s" opacity="0.25"/>', $x, $x, $x, $col, $fonce, $x, $col),
            };
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 260" role="img">'
            . '<title>' . htmlspecialchars($titre, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</title>'
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
}
