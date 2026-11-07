<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Mongo;
use MongoDB\BSON\UTCDateTime;

/**
 * Demandes envoyées par le formulaire de contact : conservées dans la collection MongoDB "messages"
 * (en plus de l'e-mail envoyé à l'entreprise), pour garder une trace même si l'e-mail se perd.
 */
final class MessageContact
{
    public static function enregistrer(string $email, string $pseudo, string $demande): string
    {
        $resultat = Mongo::db()->selectCollection('messages')->insertOne([
            'email'   => $email,
            'pseudo'  => $pseudo,
            'demande' => $demande,
            'cree_le' => new UTCDateTime(),
        ]);

        return (string) $resultat->getInsertedId();
    }
}
