<?php

declare(strict_types=1);

namespace App\Core;

/** Échappement HTML : à utiliser pour TOUTE donnée affichée (protection XSS). */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirection 303 : après un POST, le navigateur recharge la page cible en GET. */
function redirect(string $chemin): never
{
    header('Location: ' . $chemin, true, 303);
    exit;
}

/** Date/heure stockée en UTC, affichée à l'heure de Paris. */
function date_fr(?string $utc, bool $avecHeure = false): string
{
    if ($utc === null || $utc === '') {
        return '';
    }
    $d = (new \DateTimeImmutable($utc, new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('Europe/Paris'));

    return $d->format($avecHeure ? 'd/m/Y à H:i' : 'd/m/Y');
}

/** Accessibilité des formulaires : champ en erreur signalé et relié à son message. */
function aria_erreur(array $erreurs, string $champ): string
{
    return isset($erreurs[$champ]) ? ' aria-invalid="true" aria-describedby="erreur-' . e($champ) . '"' : '';
}

function message_erreur(array $erreurs, string $champ): string
{
    return isset($erreurs[$champ])
        ? '<p class="erreur-champ" id="erreur-' . e($champ) . '">' . e($erreurs[$champ]) . '</p>'
        : '';
}

/** Note sur 5 sous forme d'étoiles, avec un texte pour les lecteurs d'écran. */
function etoiles(int|float $note): string
{
    $n = (int) round($note);

    return '<span class="etoiles" aria-hidden="true">' . str_repeat('★', $n) . str_repeat('☆', 5 - $n) . '</span>'
        . '<span class="sr-only">' . e(number_format((float) $note, 1, ',', '')) . ' sur 5</span>';
}
