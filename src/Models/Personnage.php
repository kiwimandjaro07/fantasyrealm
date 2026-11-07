<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Personnages et leur équipement.
 */
final class Personnage
{
    public const TRAITS = ['genre', 'forme_visage', 'forme_yeux', 'forme_nez', 'forme_bouche', 'coiffure',
        'couleur_peau', 'couleur_cheveux', 'couleur_yeux'];

    public static function trouver(int $id): ?array
    {
        $p = Database::run(
            'SELECT p.*, u.pseudo, u.email AS email_proprietaire,
                    (SELECT ROUND(AVG(a.note), 1) FROM avis a WHERE a.personnage_id = p.id AND a.statut = \'valide\') AS note_moyenne
             FROM personnages p JOIN utilisateurs u ON u.id = p.utilisateur_id
             WHERE p.id = :id',
            ['id' => $id]
        )->fetch();

        return $p ?: null;
    }

    /** Personnage appartenant à un utilisateur précis (sinon null) : un joueur n'agit que sur les siens. */
    public static function trouverDuProprietaire(int $id, int $utilisateurId): ?array
    {
        $p = self::trouver($id);

        return ($p !== null && (int) $p['utilisateur_id'] === $utilisateurId) ? $p : null;
    }

    public static function nomExiste(string $nom, ?int $saufId = null): bool
    {
        return (bool) Database::run(
            'SELECT 1 FROM personnages WHERE nom = :nom AND id <> :id',
            ['nom' => $nom, 'id' => $saufId ?? 0]
        )->fetchColumn();
    }

    public static function duJoueur(int $utilisateurId): array
    {
        return Database::run(
            'SELECT p.*, (SELECT COUNT(*) FROM avis a WHERE a.personnage_id = p.id AND a.statut = \'valide\') AS nb_avis
             FROM personnages p WHERE p.utilisateur_id = :u ORDER BY p.cree_le DESC',
            ['u' => $utilisateurId]
        )->fetchAll();
    }

    /** @param array<string, string> $traits */
    public static function creer(int $utilisateurId, string $nom, array $traits): int
    {
        $colonnes = implode(', ', self::TRAITS);
        $valeurs = implode(', ', array_map(static fn ($t) => ':' . $t, self::TRAITS));
        Database::run(
            "INSERT INTO personnages (utilisateur_id, nom, $colonnes) VALUES (:u, :nom, $valeurs)",
            ['u' => $utilisateurId, 'nom' => $nom] + array_intersect_key($traits, array_flip(self::TRAITS))
        );

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Modification de la configuration. Si le nom change, il doit être revalidé par un employé :
     * le personnage repasse "en attente" et n'est plus partagé.
     * (Les noms de colonnes viennent de la constante TRAITS, jamais de la saisie.)
     */
    public static function modifier(int $id, string $nom, array $traits, bool $nomChange): void
    {
        $set = implode(', ', array_map(static fn ($t) => "$t = :$t", self::TRAITS));
        $revalidation = $nomChange ? ", statut = 'en_attente', partage = FALSE, valide_le = NULL" : '';
        Database::run(
            "UPDATE personnages SET nom = :nom, $set, modifie_le = UTC_TIMESTAMP() $revalidation WHERE id = :id",
            ['id' => $id, 'nom' => $nom] + array_intersect_key($traits, array_flip(self::TRAITS))
        );
    }

    public static function changerPartage(int $id, bool $partage): bool
    {
        // La condition statut = 'valide' empêche de partager un personnage non validé
        return Database::run(
            "UPDATE personnages SET partage = :p WHERE id = :id AND (statut = 'valide' OR :p2 = 0)",
            ['p' => (int) $partage, 'p2' => (int) $partage, 'id' => $id]
        )->rowCount() === 1;
    }

    public static function supprimer(int $id): void
    {
        Database::run('DELETE FROM personnages WHERE id = :id', ['id' => $id]);
    }

    /**
     * Duplication : mêmes traits et même équipement, nouveau nom (unique) à faire valider.
     * Transaction : le personnage et son équipement sont copiés ensemble ou pas du tout.
     */
    public static function dupliquer(int $id, string $nouveauNom): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $colonnes = implode(', ', self::TRAITS);
            Database::run(
                "INSERT INTO personnages (utilisateur_id, nom, $colonnes)
                 SELECT utilisateur_id, :nom, $colonnes FROM personnages WHERE id = :id",
                ['nom' => $nouveauNom, 'id' => $id]
            );
            $copie = (int) $pdo->lastInsertId();
            Database::run(
                'INSERT INTO personnage_accessoires (personnage_id, accessoire_id, categorie)
                 SELECT :copie, accessoire_id, categorie FROM personnage_accessoires WHERE personnage_id = :id',
                ['copie' => $copie, 'id' => $id]
            );
            $pdo->commit();

            return $copie;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Validation du nom par un employé (seulement si le personnage est en attente). */
    public static function valider(int $id): bool
    {
        return Database::run(
            "UPDATE personnages SET statut = 'valide', valide_le = UTC_TIMESTAMP() WHERE id = :id AND statut = 'en_attente'",
            ['id' => $id]
        )->rowCount() === 1;
    }

    public static function enAttente(): array
    {
        return Database::run(
            "SELECT p.*, u.pseudo, u.email FROM personnages p JOIN utilisateurs u ON u.id = p.utilisateur_id
             WHERE p.statut = 'en_attente' ORDER BY p.modifie_le IS NULL DESC, p.cree_le"
        )->fetchAll();
    }

    /** Tous les personnages, pour l'espace employé. */
    public static function tous(): array
    {
        return Database::run(
            'SELECT p.id, p.nom, p.genre, p.statut, p.partage, p.cree_le, u.pseudo
             FROM personnages p JOIN utilisateurs u ON u.id = p.utilisateur_id ORDER BY p.cree_le DESC'
        )->fetchAll();
    }

    /**
     * Galerie publique : personnages validés ET partagés, filtrés.
     * Les filtres sont ajoutés à la requête sous forme de paramètres liés, jamais concaténés.
     *
     * @param array{genre?: string, du?: string, au?: string, pseudo?: string} $filtres
     * @return array{personnages: list<array>, total: int}
     */
    public static function galerie(array $filtres, int $page, int $parPage = 12): array
    {
        $where = ["p.statut = 'valide'", 'p.partage = TRUE', "u.statut = 'actif'"];
        $params = [];

        if (!empty($filtres['genre'])) {
            $where[] = 'p.genre = :genre';
            $params['genre'] = $filtres['genre'];
        }
        if (!empty($filtres['du'])) {
            $where[] = 'p.cree_le >= :du';
            $params['du'] = $filtres['du'] . ' 00:00:00';
        }
        if (!empty($filtres['au'])) {
            $where[] = 'p.cree_le < :au + INTERVAL 1 DAY';
            $params['au'] = $filtres['au'];
        }
        if (!empty($filtres['pseudo'])) {
            // Recherche "commence par" : les caractères spéciaux de LIKE (% et _) sont neutralisés
            $where[] = "u.pseudo LIKE :pseudo ESCAPE '\\\\'";
            $params['pseudo'] = addcslashes($filtres['pseudo'], '%_\\') . '%';
        }

        $sqlWhere = implode(' AND ', $where);
        $total = (int) Database::run(
            "SELECT COUNT(*) FROM personnages p JOIN utilisateurs u ON u.id = p.utilisateur_id WHERE $sqlWhere",
            $params
        )->fetchColumn();

        $offset = max(0, ($page - 1) * $parPage);
        $personnages = Database::run(
            "SELECT p.id, p.nom, p.genre, p.cree_le, u.pseudo,
                    (SELECT ROUND(AVG(a.note), 1) FROM avis a WHERE a.personnage_id = p.id AND a.statut = 'valide') AS note_moyenne
             FROM personnages p JOIN utilisateurs u ON u.id = p.utilisateur_id
             WHERE $sqlWhere ORDER BY p.cree_le DESC LIMIT $parPage OFFSET $offset",
            $params
        )->fetchAll();

        return ['personnages' => $personnages, 'total' => $total];
    }

    // ----- Équipement -----

    /** @return list<array> accessoires équipés */
    public static function equipement(int $id): array
    {
        return Database::run(
            'SELECT a.* FROM personnage_accessoires pa JOIN accessoires a ON a.id = pa.accessoire_id
             WHERE pa.personnage_id = :id ORDER BY FIELD(a.categorie, \'vetement\', \'armure\', \'arme\', \'accessoire\')',
            ['id' => $id]
        )->fetchAll();
    }

    /**
     * Remplace l'équipement : un article au plus par catégorie, uniquement parmi les articles actifs.
     * @param array<string, int|null> $choix [categorie => accessoire_id ou null]
     * @return list<string> libellés des changements (pour les logs)
     */
    public static function equiper(int $id, array $choix): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $avant = array_column(self::equipement($id), 'nom', 'categorie');
            Database::run('DELETE FROM personnage_accessoires WHERE personnage_id = :id', ['id' => $id]);
            $apres = [];
            foreach ($choix as $categorie => $accessoireId) {
                if ($accessoireId === null) {
                    continue;
                }
                $a = Accessoire::trouverActif($accessoireId);
                if ($a === null || $a['categorie'] !== $categorie) {
                    continue; // article inconnu, désactivé ou de la mauvaise catégorie : ignoré
                }
                Database::run(
                    'INSERT INTO personnage_accessoires (personnage_id, accessoire_id, categorie) VALUES (:p, :a, :c)',
                    ['p' => $id, 'a' => $a['id'], 'c' => $categorie]
                );
                $apres[$categorie] = $a['nom'];
            }
            Database::run('UPDATE personnages SET modifie_le = UTC_TIMESTAMP() WHERE id = :id', ['id' => $id]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $changements = [];
        foreach (array_unique([...array_keys($avant), ...array_keys($apres)]) as $cat) {
            if (($avant[$cat] ?? null) !== ($apres[$cat] ?? null)) {
                $changements[] = sprintf('%s : %s → %s', $cat, $avant[$cat] ?? 'aucun', $apres[$cat] ?? 'aucun');
            }
        }

        return $changements;
    }
}
