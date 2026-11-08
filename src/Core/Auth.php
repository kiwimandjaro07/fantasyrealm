<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Utilisateur;

/**
 * Sessions et authentification.
 * La session ne contient que l'identifiant ; l'utilisateur est relu en base à chaque requête :
 * un compte suspendu ou supprimé perd donc immédiatement l'accès, même s'il était connecté.
 */
final class Auth
{
    private static ?array $utilisateur = null;
    private static bool $charge = false;

    public static function demarrerSession(): void
    {
        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        session_name('fantasyrealm_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,   // envoyé uniquement en HTTPS (production)
            'httponly' => true,     // illisible par JavaScript : limite le vol de session par XSS
            'samesite' => 'Lax',    // non envoyé par les formulaires d'autres sites (CSRF)
        ]);
        ini_set('session.use_strict_mode', '1');   // refuse un identifiant de session inventé
        ini_set('session.use_only_cookies', '1');

        session_start();

        // Expiration après 2 heures d'inactivité
        if (isset($_SESSION['_activite']) && time() - $_SESSION['_activite'] > 7200) {
            session_unset();
            session_regenerate_id(true);
        }
        $_SESSION['_activite'] = time();
    }

    public static function connecter(int $id): void
    {
        session_regenerate_id(true); // contre la fixation de session
        Csrf::renouveler();
        $_SESSION['utilisateur_id'] = $id;
        self::$charge = false;
    }

    public static function deconnecter(): void
    {
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'],
            'httponly' => $p['httponly'], 'samesite' => $p['samesite'],
        ]);
        session_destroy();
        self::$utilisateur = null;
        self::$charge = true;
    }

    /** Utilisateur connecté et actif, ou null. */
    public static function utilisateur(): ?array
    {
        if (!self::$charge) {
            self::$charge = true;
            $id = $_SESSION['utilisateur_id'] ?? null;
            $u = $id ? Utilisateur::trouver((int) $id) : null;
            if ($u !== null && $u['statut'] !== 'actif') {
                // Compte suspendu pendant la session : on déconnecte
                unset($_SESSION['utilisateur_id']);
                $u = null;
            }
            self::$utilisateur = $u;
        }

        return self::$utilisateur;
    }

    public static function id(): ?int
    {
        return isset(self::utilisateur()['id']) ? (int) self::utilisateur()['id'] : null;
    }

    /** Employé ou administrateur (l'admin peut tout faire comme un employé). */
    public static function estPersonnel(): bool
    {
        return in_array(self::utilisateur()['role'] ?? null, ['employe', 'admin'], true);
    }
}
