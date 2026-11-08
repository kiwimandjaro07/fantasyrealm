<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Règles de validation côté serveur (la validation HTML5 du navigateur se contourne : seule celle-ci fait foi).
 */
final class Validator
{
    public static function email(string $email): bool
    {
        return strlen($email) <= 190 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** Pseudo : 3 à 30 caractères, lettres, chiffres, tiret, point et souligné. */
    public static function pseudo(string $pseudo): bool
    {
        return preg_match('/^[\p{L}\p{N}_.-]{3,30}$/u', $pseudo) === 1;
    }

    /**
     * Mot de passe conforme à l'énoncé et aux recommandations de la CNIL :
     * 12 caractères minimum, au moins une majuscule, une minuscule, un chiffre et un caractère spécial.
     */
    public static function motDePasse(string $mdp): bool
    {
        $longueur = mb_strlen($mdp);

        return $longueur >= 12 && $longueur <= 128
            && preg_match('/\p{Lu}/u', $mdp) === 1
            && preg_match('/\p{Ll}/u', $mdp) === 1
            && preg_match('/\d/', $mdp) === 1
            && preg_match('/[^\p{L}\d]/u', $mdp) === 1;
    }

    /** Nom de personnage : 2 à 40 caractères, lettres (accents compris), espaces, apostrophes et tirets. */
    public static function nomPersonnage(string $nom): bool
    {
        return preg_match("/^[\p{L}][\p{L}' -]{1,39}$/u", $nom) === 1 && !str_contains($nom, '  ');
    }

    public static function date(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $d !== false && $d->format('Y-m-d') === $date;
    }

    public static function texte(string $valeur, int $max, int $min = 1): bool
    {
        $l = mb_strlen(trim($valeur));

        return $l >= $min && $l <= $max;
    }

    /** Valeur appartenant à une liste autorisée (liste blanche). */
    public static function parmi(mixed $valeur, array $autorisees): bool
    {
        return is_string($valeur) && in_array($valeur, $autorisees, true);
    }

    public static function couleur(string $c): bool
    {
        return preg_match('/^#[0-9a-f]{6}$/', $c) === 1;
    }

    /** Entier positif reçu d'un formulaire, ou null. */
    public static function id(mixed $valeur): ?int
    {
        $v = filter_var($valeur, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $v === false ? null : $v;
    }
}
