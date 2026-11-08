<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Protection CSRF (Cross-Site Request Forgery).
 *
 * Un site malveillant pourrait faire soumettre un formulaire à notre site par le navigateur
 * d'un utilisateur connecté (ses cookies partent avec la requête). Pour l'empêcher, chaque
 * formulaire contient un jeton secret aléatoire, stocké en session : l'autre site ne peut pas
 * le connaître, donc sa requête est refusée.
 */
final class Csrf
{
    private const CLE = '_csrf_token';

    /** Jeton de la session (créé au premier appel). */
    public static function jeton(): string
    {
        if (empty($_SESSION[self::CLE])) {
            // 32 octets aléatoires cryptographiquement sûrs, affichés en hexadécimal
            $_SESSION[self::CLE] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CLE];
    }

    /** Champ caché à placer dans chaque formulaire POST. */
    public static function champ(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::jeton()) . '">';
    }

    public static function verifier(mixed $jetonRecu): bool
    {
        $attendu = $_SESSION[self::CLE] ?? '';

        // hash_equals : comparaison en temps constant (pas de fuite d'information par le temps de réponse)
        return is_string($jetonRecu) && $attendu !== '' && hash_equals($attendu, $jetonRecu);
    }

    /** À appeler après la connexion : nouveau jeton pour la nouvelle session. */
    public static function renouveler(): void
    {
        unset($_SESSION[self::CLE]);
    }
}
