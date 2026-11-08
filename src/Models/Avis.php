<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Avis des joueurs (note + commentaire), visibles après validation par un employé.
 */
final class Avis
{
    public static function validesDuPersonnage(int $personnageId): array
    {
        return Database::run(
            "SELECT a.note, a.commentaire, a.cree_le, u.pseudo FROM avis a JOIN utilisateurs u ON u.id = a.utilisateur_id
             WHERE a.personnage_id = :p AND a.statut = 'valide' ORDER BY a.cree_le DESC",
            ['p' => $personnageId]
        )->fetchAll();
    }

    /** Avis déjà déposé par ce joueur sur ce personnage (quel que soit son statut). */
    public static function deLAuteur(int $personnageId, int $utilisateurId): ?array
    {
        $a = Database::run(
            'SELECT * FROM avis WHERE personnage_id = :p AND utilisateur_id = :u',
            ['p' => $personnageId, 'u' => $utilisateurId]
        )->fetch();

        return $a ?: null;
    }

    public static function creer(int $personnageId, int $utilisateurId, int $note, string $commentaire): int
    {
        Database::run(
            'INSERT INTO avis (personnage_id, utilisateur_id, note, commentaire) VALUES (:p, :u, :n, :c)',
            ['p' => $personnageId, 'u' => $utilisateurId, 'n' => $note, 'c' => $commentaire]
        );

        return (int) Database::pdo()->lastInsertId();
    }

    public static function enAttente(): array
    {
        return Database::run(
            "SELECT a.*, u.pseudo, p.nom AS personnage FROM avis a
             JOIN utilisateurs u ON u.id = a.utilisateur_id JOIN personnages p ON p.id = a.personnage_id
             WHERE a.statut = 'en_attente' ORDER BY a.cree_le"
        )->fetchAll();
    }

    /** Modération : seul un avis en attente peut changer de statut. */
    public static function moderer(int $id, bool $valide): bool
    {
        return Database::run(
            "UPDATE avis SET statut = :s, modere_le = UTC_TIMESTAMP() WHERE id = :id AND statut = 'en_attente'",
            ['s' => $valide ? 'valide' : 'refuse', 'id' => $id]
        )->rowCount() === 1;
    }

    /** Avis + personnage + e-mail du propriétaire, pour la notification. */
    public static function pourNotification(int $id): ?array
    {
        $a = Database::run(
            'SELECT a.id, a.note, a.personnage_id, p.nom AS personnage, u.email, u.pseudo AS proprietaire, auteur.pseudo AS auteur
             FROM avis a JOIN personnages p ON p.id = a.personnage_id
             JOIN utilisateurs u ON u.id = p.utilisateur_id JOIN utilisateurs auteur ON auteur.id = a.utilisateur_id
             WHERE a.id = :id',
            ['id' => $id]
        )->fetch();

        return $a ?: null;
    }
}
