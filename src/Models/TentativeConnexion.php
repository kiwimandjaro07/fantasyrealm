<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Limitation des tentatives de connexion (protection contre les attaques par force brute) :
 * au-delà de 5 échecs pour un même e-mail, ou de 20 échecs pour une même adresse IP,
 * en 15 minutes, la connexion est refusée temporairement.
 */
final class TentativeConnexion
{
    public const MAX_PAR_EMAIL = 5;
    public const MAX_PAR_IP = 20;
    public const FENETRE_MINUTES = 15;

    public static function estBloque(string $email, string $ip): bool
    {
        $ligne = Database::run(
            'SELECT
                SUM(email = :email) AS par_email,
                SUM(ip = :ip)       AS par_ip
             FROM tentatives_connexion
             WHERE tente_le > UTC_TIMESTAMP() - INTERVAL ' . self::FENETRE_MINUTES . ' MINUTE
               AND (email = :email2 OR ip = :ip2)',
            ['email' => $email, 'ip' => $ip, 'email2' => $email, 'ip2' => $ip]
        )->fetch();

        return (int) $ligne['par_email'] >= self::MAX_PAR_EMAIL || (int) $ligne['par_ip'] >= self::MAX_PAR_IP;
    }

    public static function enregistrerEchec(string $email, string $ip): void
    {
        Database::run('INSERT INTO tentatives_connexion (email, ip) VALUES (:email, :ip)', ['email' => $email, 'ip' => $ip]);
        // Ménage : les tentatives anciennes ne servent plus
        Database::run('DELETE FROM tentatives_connexion WHERE tente_le < UTC_TIMESTAMP() - INTERVAL 1 DAY');
    }

    /** Après une connexion réussie, les échecs de cet e-mail sont effacés. */
    public static function effacer(string $email): void
    {
        Database::run('DELETE FROM tentatives_connexion WHERE email = :email', ['email' => $email]);
    }
}
