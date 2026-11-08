<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Génération d'un mot de passe provisoire (mot de passe oublié).
 * random_int() utilise le générateur cryptographiquement sûr du système (contrairement à rand()).
 */
final class MotDePasse
{
    private const MAJUSCULES = 'ABCDEFGHJKLMNPQRSTUVWXYZ';   // sans I ni O, faciles à confondre
    private const MINUSCULES = 'abcdefghijkmnopqrstuvwxyz';  // sans l
    private const CHIFFRES = '23456789';                      // sans 0 ni 1
    private const SPECIAUX = '!#$%&*+-=?@';

    public static function generer(int $longueur = 16): string
    {
        $familles = [self::MAJUSCULES, self::MINUSCULES, self::CHIFFRES, self::SPECIAUX];
        $tous = implode('', $familles);

        // Au moins un caractère de chaque famille, puis du hasard pour compléter
        $caracteres = array_map(static fn (string $f) => $f[random_int(0, strlen($f) - 1)], $familles);
        while (count($caracteres) < $longueur) {
            $caracteres[] = $tous[random_int(0, strlen($tous) - 1)];
        }

        // Mélange de Fisher-Yates avec random_int (shuffle() n'est pas cryptographiquement sûr)
        for ($i = count($caracteres) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$caracteres[$i], $caracteres[$j]] = [$caracteres[$j], $caracteres[$i]];
        }

        return implode('', $caracteres);
    }
}
