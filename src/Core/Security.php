<?php

declare(strict_types=1);

namespace App\Core;

/**
 * En-têtes HTTP de sécurité, envoyés avec chaque réponse.
 */
final class Security
{
    /**
     * Refuse les données qui ne sont pas de l'UTF-8 valide (un navigateur n'en envoie jamais : c'est le signe
     * d'une requête forgée). Évite des erreurs SQL et des contournements de validation.
     */
    public static function entreesValides(array $donnees): bool
    {
        foreach ($donnees as $cle => $valeur) {
            if (!mb_check_encoding((string) $cle, 'UTF-8')) {
                return false;
            }
            if (is_array($valeur) ? !self::entreesValides($valeur) : !mb_check_encoding((string) $valeur, 'UTF-8')) {
                return false;
            }
        }

        return true;
    }

    public static function envoyerEntetes(): void
    {
        // CSP : le navigateur n'exécute que les scripts et styles servis par notre propre domaine.
        // Aucun script en ligne n'est autorisé : une injection <script> serait bloquée même si elle passait l'échappement.
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; "
            . "connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
        header('X-Content-Type-Options: nosniff');          // pas de devinette du type de fichier
        header('X-Frame-Options: DENY');                    // le site ne peut pas être affiché dans une iframe (clickjacking)
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        if (($_SERVER['HTTPS'] ?? '') === 'on') {
            // HSTS : le navigateur n'utilisera plus que HTTPS pour ce site pendant un an
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
