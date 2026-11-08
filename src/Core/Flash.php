<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Messages "flash" : affichés une seule fois, sur la page suivante (après une redirection).
 * Exemple : « Votre personnage a été créé. »
 */
final class Flash
{
    /** @param 'succes'|'erreur'|'info' $type */
    public static function ajouter(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return list<array{type: string, message: string}> */
    public static function lire(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $messages;
    }

    /** Conserve la saisie d'un formulaire refusé pour la réafficher (sauf mots de passe). */
    public static function garderSaisie(array $donnees): void
    {
        // Les mots de passe ne sont jamais conservés
        $donnees = array_filter($donnees, static fn ($cle) => !str_contains((string) $cle, 'mot_de_passe') && $cle !== '_csrf',
            ARRAY_FILTER_USE_KEY);
        $_SESSION['_old'] = $donnees;
    }

    /** @return array<string, mixed> */
    public static function saisie(): array
    {
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);

        return $old;
    }
}
