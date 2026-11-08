<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

/**
 * Pages publiques.
 */
class PageController
{
    public function accueil(): void
    {
        // Quelques chiffres et les derniers personnages partagés pour donner vie à l'accueil
        $chiffres = Database::run(
            "SELECT (SELECT COUNT(*) FROM personnages WHERE statut = 'valide' AND partage = TRUE) AS partages,
                    (SELECT COUNT(*) FROM accessoires WHERE actif = TRUE) AS articles,
                    (SELECT COUNT(*) FROM utilisateurs WHERE role = 'joueur' AND statut = 'actif') AS joueurs"
        )->fetch();
        $derniers = Database::run(
            "SELECT p.id, p.nom, u.pseudo FROM personnages p JOIN utilisateurs u ON u.id = p.utilisateur_id
             WHERE p.statut = 'valide' AND p.partage = TRUE AND u.statut = 'actif' ORDER BY p.valide_le DESC LIMIT 3"
        )->fetchAll();

        View::render('pages/accueil', ['title' => 'Accueil', 'chiffres' => $chiffres, 'derniers' => $derniers]);
    }

    public function mentionsLegales(): void
    {
        View::render('pages/mentions-legales', ['title' => 'Mentions légales']);
    }

    public function conditionsGenerales(): void
    {
        View::render('pages/conditions-generales', ['title' => 'Conditions générales de vente et d\'utilisation']);
    }

    public function confidentialite(): void
    {
        View::render('pages/confidentialite', ['title' => 'Politique de confidentialité']);
    }
}
