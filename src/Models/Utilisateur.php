<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Table utilisateurs (joueurs, employés, administrateur). Requêtes préparées uniquement.
 */
final class Utilisateur
{
    private const COLONNES = 'id, email, pseudo, role, statut, doit_changer_mdp, derniere_connexion, cree_le';

    public static function trouver(int $id): ?array
    {
        $u = Database::run('SELECT ' . self::COLONNES . ' FROM utilisateurs WHERE id = :id', ['id' => $id])->fetch();

        return $u ?: null;
    }

    /** Avec le hash du mot de passe : uniquement pour la connexion. */
    public static function trouverPourConnexion(string $email): ?array
    {
        $u = Database::run('SELECT * FROM utilisateurs WHERE email = :email', ['email' => $email])->fetch();

        return $u ?: null;
    }

    public static function trouverParPseudo(string $pseudo): ?array
    {
        $u = Database::run('SELECT ' . self::COLONNES . ' FROM utilisateurs WHERE pseudo = :pseudo', ['pseudo' => $pseudo])->fetch();

        return $u ?: null;
    }

    public static function emailExiste(string $email): bool
    {
        return (bool) Database::run('SELECT 1 FROM utilisateurs WHERE email = :e', ['e' => $email])->fetchColumn();
    }

    public static function pseudoExiste(string $pseudo): bool
    {
        return (bool) Database::run('SELECT 1 FROM utilisateurs WHERE pseudo = :p', ['p' => $pseudo])->fetchColumn();
    }

    public static function creer(string $email, string $pseudo, string $motDePasse, string $role = 'joueur'): int
    {
        Database::run(
            'INSERT INTO utilisateurs (email, pseudo, mot_de_passe, role) VALUES (:email, :pseudo, :mdp, :role)',
            ['email' => $email, 'pseudo' => $pseudo, 'mdp' => self::hacher($motDePasse), 'role' => $role]
        );

        return (int) Database::pdo()->lastInsertId();
    }

    /** argon2id : algorithme lent et salé recommandé pour les mots de passe (résiste aux attaques par dictionnaire). */
    public static function hacher(string $motDePasse): string
    {
        return password_hash($motDePasse, PASSWORD_ARGON2ID);
    }

    /** @param bool $provisoire vrai = mot de passe envoyé par e-mail, à changer à la prochaine connexion */
    public static function changerMotDePasse(int $id, string $motDePasse, bool $provisoire = false): void
    {
        Database::run(
            'UPDATE utilisateurs SET mot_de_passe = :mdp, doit_changer_mdp = :prov WHERE id = :id',
            ['mdp' => self::hacher($motDePasse), 'prov' => (int) $provisoire, 'id' => $id]
        );
    }

    public static function verifierMotDePasse(int $id, string $motDePasse): bool
    {
        $hash = Database::run('SELECT mot_de_passe FROM utilisateurs WHERE id = :id', ['id' => $id])->fetchColumn();

        return is_string($hash) && password_verify($motDePasse, $hash);
    }

    public static function noterConnexion(int $id): void
    {
        Database::run('UPDATE utilisateurs SET derniere_connexion = UTC_TIMESTAMP() WHERE id = :id', ['id' => $id]);
    }

    public static function rehacherSiNecessaire(array $u, string $motDePasse): void
    {
        if (password_needs_rehash($u['mot_de_passe'], PASSWORD_ARGON2ID)) {
            Database::run('UPDATE utilisateurs SET mot_de_passe = :m WHERE id = :id', ['m' => self::hacher($motDePasse), 'id' => $u['id']]);
        }
    }

    /** Liste par rôle, avec le nombre de personnages. */
    public static function parRole(string $role): array
    {
        return Database::run(
            'SELECT u.id, u.email, u.pseudo, u.role, u.statut, u.derniere_connexion, u.cree_le, COUNT(p.id) AS nb_personnages
             FROM utilisateurs u LEFT JOIN personnages p ON p.utilisateur_id = u.id
             WHERE u.role = :role GROUP BY u.id ORDER BY u.pseudo',
            ['role' => $role]
        )->fetchAll();
    }

    public static function changerStatut(int $id, string $statut, string $role): bool
    {
        return Database::run(
            'UPDATE utilisateurs SET statut = :s WHERE id = :id AND role = :role',
            ['s' => $statut === 'suspendu' ? 'suspendu' : 'actif', 'id' => $id, 'role' => $role]
        )->rowCount() === 1;
    }

    /**
     * Suppression d'un compte. Le rôle est imposé dans la requête : un employé ne peut supprimer
     * qu'un joueur, jamais l'administrateur. Les personnages et avis suivent (ON DELETE CASCADE).
     */
    public static function supprimer(int $id, string $role): bool
    {
        return Database::run('DELETE FROM utilisateurs WHERE id = :id AND role = :role', ['id' => $id, 'role' => $role])->rowCount() === 1;
    }
}
