<?php

declare(strict_types=1);

namespace App\Core;

use MongoDB\Client;
use MongoDB\Database as MongoDatabase;

/**
 * Connexion à MongoDB (base NoSQL orientée documents).
 *
 * Pourquoi MongoDB en plus de MySQL ? Les logs de personnalisation et les messages de contact
 * sont des données sans relations, écrites souvent, dont la structure varie selon l'événement
 * (un changement de nom n'a pas les mêmes informations qu'un ajout d'arme). Un document JSON s'y prête
 * mieux qu'une table au schéma fixe. Les données métier (comptes, personnages, accessoires, avis)
 * restent dans MySQL, qui garantit l'intégrité (clés étrangères, contraintes, transactions).
 */
final class Mongo
{
    private static ?MongoDatabase $db = null;

    public static function db(): MongoDatabase
    {
        if (self::$db === null) {
            $client = new Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017', [
                'serverSelectionTimeoutMS' => 2000, // si Mongo est arrêté, on n'attend pas 30 s
            ]);
            self::$db = $client->selectDatabase($_ENV['MONGO_DB'] ?? 'fantasyrealm');
        }

        return self::$db;
    }
}
