<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Mongo;
use MongoDB\BSON\UTCDateTime;

/**
 * Logs des modifications apportées aux personnages (et des actions sensibles), collection MongoDB "logs".
 *
 * Exemple de document :
 * { type: "personnage.equipement", acteur: { id: 3, pseudo: "Lyra" }, personnage: { id: 7, nom: "Aelwen" },
 *   details: { changements: ["arme : aucun → Épée runique"] }, ip: "172.18.0.1", cree_le: ISODate(...) }
 *
 * Le document garde le nom et le pseudo tels qu'ils étaient au moment de l'action : l'historique reste lisible
 * même si le personnage est renommé ou supprimé ensuite.
 */
final class ActivityLog
{
    public const TYPES = [
        'personnage.creation'    => 'Création de personnage',
        'personnage.apparence'   => 'Modification d\'apparence',
        'personnage.nom'         => 'Changement de nom',
        'personnage.equipement'  => 'Équipement modifié',
        'personnage.partage'     => 'Partage',
        'personnage.duplication' => 'Duplication',
        'personnage.suppression' => 'Suppression de personnage',
        'personnage.validation'  => 'Nom validé',
        'personnage.rejet'       => 'Nom rejeté',
        'avis.depot'             => 'Avis déposé',
        'avis.moderation'        => 'Avis modéré',
        'accessoire.gestion'     => 'Gestion des articles',
        'compte.gestion'         => 'Gestion des comptes',
        'compte.connexion'       => 'Connexion',
    ];

    /**
     * @param array{id: int, pseudo: string}|null $acteur
     * @param array{id: int, nom: string}|null $personnage
     */
    public static function ajouter(string $type, ?array $acteur, ?array $personnage = null, array $details = []): void
    {
        try {
            Mongo::db()->selectCollection('logs')->insertOne([
                'type'       => $type,
                'acteur'     => $acteur === null ? null : ['id' => (int) $acteur['id'], 'pseudo' => $acteur['pseudo']],
                'personnage' => $personnage === null ? null : ['id' => (int) $personnage['id'], 'nom' => $personnage['nom']],
                'details'    => $details,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'cree_le'    => new UTCDateTime(),
            ]);
        } catch (\Throwable $e) {
            // Le journal ne doit jamais bloquer l'action du joueur : en cas de panne, on trace l'erreur côté serveur
            error_log('[ActivityLog] ' . $e->getMessage());
        }
    }

    /**
     * Recherche dans les logs. Les filtres sont passés comme valeurs dans un document de requête,
     * jamais comme opérateurs : pas d'injection NoSQL possible (ex. {"$ne": null}).
     *
     * @param array{type?: string, personnage?: string, pseudo?: string} $filtres
     */
    public static function rechercher(array $filtres, int $limite = 100): array
    {
        $requete = [];
        if (!empty($filtres['type']) && isset(self::TYPES[$filtres['type']])) {
            $requete['type'] = $filtres['type'];
        }
        if (!empty($filtres['personnage'])) {
            // preg_quote : la saisie est cherchée telle quelle, sans être interprétée comme une expression régulière
            $requete['personnage.nom'] = new \MongoDB\BSON\Regex('^' . preg_quote((string) $filtres['personnage']), 'i');
        }
        if (!empty($filtres['pseudo'])) {
            $requete['acteur.pseudo'] = (string) $filtres['pseudo'];
        }

        $curseur = Mongo::db()->selectCollection('logs')->find($requete, ['sort' => ['cree_le' => -1], 'limit' => $limite]);
        $resultat = [];
        foreach ($curseur as $doc) {
            $resultat[] = [
                'type'       => $doc['type'],
                'acteur'     => $doc['acteur'] ? (array) $doc['acteur'] : null,
                'personnage' => $doc['personnage'] ? (array) $doc['personnage'] : null,
                'details'    => json_decode(json_encode($doc['details']), true) ?? [],
                'cree_le'    => $doc['cree_le']->toDateTime()->setTimezone(new \DateTimeZone('Europe/Paris')),
            ];
        }

        return $resultat;
    }

    /** Nombre d'événements par type (agrégation MongoDB). */
    public static function statistiques(): array
    {
        $stats = [];
        foreach (Mongo::db()->selectCollection('logs')->aggregate([
            ['$group' => ['_id' => '$type', 'total' => ['$sum' => 1]]],
            ['$sort' => ['total' => -1]],
        ]) as $ligne) {
            $stats[$ligne['_id']] = $ligne['total'];
        }

        return $stats;
    }
}
