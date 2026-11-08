<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\Accessoire;
use App\Models\Avis;
use App\Models\Personnage;
use App\Models\Utilisateur;
use App\Services\ActivityLog;
use App\Services\Apparence;
use App\Services\MailService;

use function App\Core\redirect;

/**
 * Espace employé (aussi accessible à l'administrateur) : modération des noms et des avis,
 * bibliothèque d'articles, suppression de personnages, suspension et suppression de comptes joueurs.
 */
class GestionController
{
    public function index(): void
    {
        View::render('gestion/index', [
            'title'       => 'Espace employé',
            'personnages' => Personnage::enAttente(),
            'avis'        => Avis::enAttente(),
        ]);
    }

    public function validerPersonnage(): void
    {
        $p = Personnage::trouver(Validator::id($_POST['id'] ?? null) ?? 0);
        if ($p === null || !Personnage::valider((int) $p['id'])) {
            Flash::ajouter('erreur', 'Ce personnage a déjà été traité.');
            redirect('/gestion');
        }
        MailService::nomValide(['email' => $p['email_proprietaire'], 'pseudo' => $p['pseudo'], 'nom' => $p['nom']]);
        ActivityLog::ajouter('personnage.validation', Auth::utilisateur(), $p);
        Flash::ajouter('succes', "Le nom « {$p['nom']} » est validé. Un e-mail a été envoyé à {$p['pseudo']}.");
        redirect('/gestion');
    }

    /** Rejet : motif obligatoire, e-mail au propriétaire, puis suppression définitive du personnage. */
    public function rejeterPersonnage(): void
    {
        $p = Personnage::trouver(Validator::id($_POST['id'] ?? null) ?? 0);
        $motif = trim((string) ($_POST['motif'] ?? ''));
        if ($p === null || $p['statut'] !== 'en_attente') {
            Flash::ajouter('erreur', 'Ce personnage a déjà été traité.');
            redirect('/gestion');
        }
        if (!Validator::texte($motif, 500, 5)) {
            Flash::ajouter('erreur', "Indiquez le motif du rejet de « {$p['nom']} » (5 à 500 caractères).");
            redirect('/gestion#personnage-' . $p['id']);
        }

        MailService::nomRejete(['email' => $p['email_proprietaire'], 'pseudo' => $p['pseudo'], 'nom' => $p['nom']], $motif);
        Personnage::supprimer((int) $p['id']);
        ActivityLog::ajouter('personnage.rejet', Auth::utilisateur(), $p, ['motif' => $motif, 'proprietaire' => $p['pseudo']]);
        Flash::ajouter('succes', "« {$p['nom']} » est rejeté et supprimé. {$p['pseudo']} a reçu le motif par e-mail.");
        redirect('/gestion');
    }

    public function modererAvis(): void
    {
        $id = Validator::id($_POST['id'] ?? null) ?? 0;
        $valider = ($_POST['decision'] ?? '') === 'valider';
        if (!Avis::moderer($id, $valider)) {
            Flash::ajouter('erreur', 'Cet avis a déjà été traité.');
            redirect('/gestion#avis');
        }
        $a = Avis::pourNotification($id);
        if ($valider && $a !== null) {
            MailService::nouvelAvis($a); // le propriétaire est prévenu quand l'avis devient visible
        }
        ActivityLog::ajouter('avis.moderation', Auth::utilisateur(),
            $a ? ['id' => $a['personnage_id'], 'nom' => $a['personnage']] : null,
            ['avis_id' => $id, 'decision' => $valider ? 'validé' : 'refusé']);
        Flash::ajouter('succes', $valider ? 'Avis publié, le propriétaire du personnage a été prévenu.' : 'Avis refusé.');
        redirect('/gestion#avis');
    }

    // ----- Bibliothèque d'articles -----

    public function accessoires(): void
    {
        $erreurs = $_SESSION['_erreurs'] ?? [];
        unset($_SESSION['_erreurs']);
        View::render('gestion/accessoires', [
            'title'       => 'Bibliothèque d\'articles',
            'accessoires' => Accessoire::tous(),
            'erreurs'     => $erreurs,
            'old'         => Flash::saisie(),
        ]);
    }

    public function creerAccessoire(): void
    {
        $nom = trim((string) ($_POST['nom'] ?? ''));
        $visuel = (string) ($_POST['visuel'] ?? '');
        // La catégorie se déduit de la forme choisie (chaque forme appartient à une seule catégorie)
        $categorie = '';
        foreach (Apparence::VISUELS as $cat => $visuels) {
            if (isset($visuels[$visuel])) {
                $categorie = $cat;
            }
        }
        $couleur = strtolower((string) ($_POST['couleur'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        $erreurs = [];
        if (!Validator::texte($nom, 60, 2)) {
            $erreurs['nom'] = 'Nom de 2 à 60 caractères.';
        } elseif (Accessoire::nomExiste($nom)) {
            $erreurs['nom'] = 'Un article porte déjà ce nom.';
        }
        if (!isset(Apparence::VISUELS[$categorie][$visuel])) {
            $erreurs['visuel'] = 'Choisissez une forme correspondant à la catégorie.';
        }
        if (!Validator::couleur($couleur)) {
            $erreurs['couleur'] = 'Couleur invalide.';
        }
        if (mb_strlen($description) > 255) {
            $erreurs['description'] = '255 caractères maximum.';
        }
        if ($erreurs !== []) {
            Flash::garderSaisie($_POST);
            $_SESSION['_erreurs'] = $erreurs;
            Flash::ajouter('erreur', 'L\'article n\'a pas été ajouté.');
            redirect('/gestion/accessoires#ajout');
        }

        Accessoire::creer($nom, $categorie, $visuel, $couleur, $description);
        ActivityLog::ajouter('accessoire.gestion', Auth::utilisateur(), null, ['action' => 'ajout', 'article' => $nom]);
        Flash::ajouter('succes', "« $nom » est ajouté à la bibliothèque.");
        redirect('/gestion/accessoires');
    }

    public function etatAccessoire(): void
    {
        $id = Validator::id($_POST['id'] ?? null) ?? 0;
        $actif = ($_POST['actif'] ?? '') === '1';
        Accessoire::changerActif($id, $actif);
        ActivityLog::ajouter('accessoire.gestion', Auth::utilisateur(), null, ['action' => $actif ? 'réactivation' : 'désactivation', 'article_id' => $id]);
        Flash::ajouter('succes', $actif ? 'Article réactivé.' : 'Article désactivé : il n\'est plus proposé aux joueurs.');
        redirect('/gestion/accessoires');
    }

    public function supprimerAccessoire(): void
    {
        $nom = Accessoire::supprimer(Validator::id($_POST['id'] ?? null) ?? 0);
        if ($nom !== null) {
            ActivityLog::ajouter('accessoire.gestion', Auth::utilisateur(), null, ['action' => 'suppression', 'article' => $nom]);
            Flash::ajouter('succes', "« $nom » est supprimé et retiré des personnages qui le portaient.");
        }
        redirect('/gestion/accessoires');
    }

    // ----- Personnages et comptes joueurs -----

    public function personnages(): void
    {
        View::render('gestion/personnages', ['title' => 'Tous les personnages', 'personnages' => Personnage::tous()]);
    }

    public function supprimerPersonnage(): void
    {
        $p = Personnage::trouver(Validator::id($_POST['id'] ?? null) ?? 0);
        if ($p !== null) {
            Personnage::supprimer((int) $p['id']);
            ActivityLog::ajouter('personnage.suppression', Auth::utilisateur(), $p, ['par' => 'personnel', 'proprietaire' => $p['pseudo']]);
            Flash::ajouter('succes', "« {$p['nom']} » a été supprimé.");
        }
        redirect('/gestion/personnages');
    }

    public function joueurs(): void
    {
        View::render('gestion/joueurs', ['title' => 'Comptes joueurs', 'joueurs' => Utilisateur::parRole('joueur')]);
    }

    public function statutJoueur(): void
    {
        $id = Validator::id($_POST['id'] ?? null) ?? 0;
        $statut = ($_POST['statut'] ?? '') === 'suspendu' ? 'suspendu' : 'actif';
        $u = Utilisateur::trouver($id);
        // Le rôle 'joueur' est imposé dans la requête SQL : impossible de suspendre un employé ou l'admin ici
        if ($u !== null && Utilisateur::changerStatut($id, $statut, 'joueur')) {
            ActivityLog::ajouter('compte.gestion', Auth::utilisateur(), null, ['action' => $statut === 'suspendu' ? 'suspension' : 'réactivation', 'compte' => $u['pseudo']]);
            Flash::ajouter('succes', $statut === 'suspendu' ? "Le compte de {$u['pseudo']} est suspendu." : "Le compte de {$u['pseudo']} est réactivé.");
        }
        redirect('/gestion/joueurs');
    }

    public function supprimerJoueur(): void
    {
        $id = Validator::id($_POST['id'] ?? null) ?? 0;
        $u = Utilisateur::trouver($id);
        if ($u !== null && Utilisateur::supprimer($id, 'joueur')) {
            ActivityLog::ajouter('compte.gestion', Auth::utilisateur(), null, ['action' => 'suppression', 'compte' => $u['pseudo']]);
            Flash::ajouter('succes', "Le compte de {$u['pseudo']} et ses personnages ont été supprimés.");
        }
        redirect('/gestion/joueurs');
    }
}
