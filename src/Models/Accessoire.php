<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Bibliothèque d'articles de personnalisation (gérée par les employés).
 */
final class Accessoire
{
    public static function tous(): array
    {
        return Database::run(
            'SELECT a.*, (SELECT COUNT(*) FROM personnage_accessoires pa WHERE pa.accessoire_id = a.id) AS nb_utilisations
             FROM accessoires a ORDER BY FIELD(a.categorie, \'vetement\', \'armure\', \'arme\', \'accessoire\'), a.nom'
        )->fetchAll();
    }

    /** Articles proposés aux joueurs, regroupés par catégorie. @return array<string, list<array>> */
    public static function actifsParCategorie(): array
    {
        $groupes = [];
        foreach (Database::run('SELECT * FROM accessoires WHERE actif = TRUE ORDER BY nom')->fetchAll() as $a) {
            $groupes[$a['categorie']][] = $a;
        }

        return $groupes;
    }

    public static function trouverActif(int $id): ?array
    {
        $a = Database::run('SELECT * FROM accessoires WHERE id = :id AND actif = TRUE', ['id' => $id])->fetch();

        return $a ?: null;
    }

    public static function nomExiste(string $nom): bool
    {
        return (bool) Database::run('SELECT 1 FROM accessoires WHERE nom = :n', ['n' => $nom])->fetchColumn();
    }

    public static function creer(string $nom, string $categorie, string $visuel, string $couleur, string $description): int
    {
        Database::run(
            'INSERT INTO accessoires (nom, categorie, visuel, couleur, description) VALUES (:n, :c, :v, :col, :d)',
            ['n' => $nom, 'c' => $categorie, 'v' => $visuel, 'col' => $couleur, 'd' => $description]
        );

        return (int) Database::pdo()->lastInsertId();
    }

    /** Désactiver : l'article n'est plus proposé, mais les personnages qui le portent le gardent. */
    public static function changerActif(int $id, bool $actif): void
    {
        Database::run('UPDATE accessoires SET actif = :a WHERE id = :id', ['a' => (int) $actif, 'id' => $id]);
    }

    /** Supprimer : l'article est aussi retiré des personnages (ON DELETE CASCADE). */
    public static function supprimer(int $id): ?string
    {
        $nom = Database::run('SELECT nom FROM accessoires WHERE id = :id', ['id' => $id])->fetchColumn();
        Database::run('DELETE FROM accessoires WHERE id = :id', ['id' => $id]);

        return $nom === false ? null : $nom;
    }
}
