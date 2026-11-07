<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Connexion unique à MySQL via PDO (patron Singleton : une seule connexion par requête HTTP).
 *
 * Choix de sécurité :
 * - ERRMODE_EXCEPTION : toute erreur SQL lève une exception (pas d'échec silencieux) ;
 * - EMULATE_PREPARES = false : les requêtes préparées sont réellement envoyées au serveur MySQL,
 *   les valeurs voyagent séparément du SQL, ce qui bloque l'injection SQL ;
 * - utf8mb4 : jeu de caractères complet, cohérent avec le schéma.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_PORT'] ?? '3306',
                $_ENV['DB_NAME'] ?? 'fantasyrealm'
            );

            self::$pdo = new PDO($dsn, $_ENV['DB_USER'] ?? '', $_ENV['DB_PASSWORD'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Les dates sont stockées en UTC et converties à l'affichage
            self::$pdo->exec("SET time_zone = '+00:00'");
        }

        return self::$pdo;
    }

    /** Raccourci : prépare, exécute et renvoie la requête. */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }
}
