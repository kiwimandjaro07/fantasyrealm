<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\Accessoire;
use App\Models\Personnage;
use App\Services\ActivityLog;
use App\Services\Apparence;

use function App\Core\redirect;

/**
 * Espace joueur : créer, modifier, équiper, partager, dupliquer et supprimer ses personnages.
 * Chaque action vérifie que le personnage appartient bien au joueur connecté.
 */
class EspaceController
{
    private static function erreurs(): array
    {
        $e = $_SESSION['_erreurs'] ?? [];
        unset($_SESSION['_erreurs']);

        return $e;
    }

    /** Personnage du joueur connecté désigné par le formulaire, sinon retour à l'espace. */
    private static function monPersonnage(mixed $id): array
    {
        $p = Personnage::trouverDuProprietaire(Validator::id($id) ?? 0, (int) Auth::id());
        if ($p === null) {
            Flash::ajouter('erreur', 'Personnage introuvable.');
            redirect('/espace');
        }

        return $p;
    }

    public function index(): void
    {
        View::render('espace/index', [
            'title'       => 'Mon espace',
            'personnages' => Personnage::duJoueur((int) Auth::id()),
        ]);
    }

    public function formulaireCreation(): void
    {
        View::render('espace/formulaire', [
            'title'   => 'Créer un personnage',
            'p'       => Flash::saisie() + Apparence::DEFAUT + ['nom' => ''],
            'erreurs' => self::erreurs(),
            'mode'    => 'creation',
        ]);
    }

    /** Vérifie le nom et les traits ; renvoie [nom, traits, erreurs]. */
    private static function lireFormulaire(?int $saufId = null): array
    {
        $nom = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['nom'] ?? '')) ?? '');
        [$traits, $erreurs] = Apparence::valider($_POST);
        if (!Validator::nomPersonnage($nom)) {
            $erreurs['nom'] = '2 à 40 caractères : lettres, espaces, apostrophes et tirets.';
        } elseif (Personnage::nomExiste($nom, $saufId)) {
            $erreurs['nom'] = 'Ce nom est déjà utilisé par un autre personnage.';
        }

        return [$nom, $traits, $erreurs];
    }

    public function creer(): void
    {
        [$nom, $traits, $erreurs] = self::lireFormulaire();
        if ($erreurs !== []) {
            Flash::garderSaisie($_POST);
            $_SESSION['_erreurs'] = $erreurs;
            Flash::ajouter('erreur', 'Le formulaire contient des erreurs.');
            redirect('/espace/creer');
        }

        $id = Personnage::creer((int) Auth::id(), $nom, $traits);
        ActivityLog::ajouter('personnage.creation', Auth::utilisateur(), ['id' => $id, 'nom' => $nom],
            ['apparence' => Apparence::description($traits)]);
        Flash::ajouter('succes', "« $nom » est créé ! Son nom doit être validé par notre équipe avant de pouvoir l'équiper et le partager.");
        redirect('/espace/personnage?id=' . $id);
    }

    public function formulaireModification(): void
    {
        $p = self::monPersonnage($_GET['id'] ?? null);
        $saisie = Flash::saisie();

        View::render('espace/formulaire', [
            'title'       => 'Modifier ' . $p['nom'],
            'p'           => $saisie + $p,
            'personnage'  => $p,
            'erreurs'     => self::erreurs(),
            'mode'        => 'modification',
            'equipement'  => array_column(Personnage::equipement((int) $p['id']), null, 'categorie'),
            'catalogue'   => Accessoire::actifsParCategorie(),
        ]);
    }

    public function modifier(): void
    {
        $p = self::monPersonnage($_POST['id'] ?? null);
        [$nom, $traits, $erreurs] = self::lireFormulaire((int) $p['id']);
        $retour = '/espace/personnage?id=' . $p['id'];
        if ($erreurs !== []) {
            Flash::garderSaisie($_POST);
            $_SESSION['_erreurs'] = $erreurs;
            Flash::ajouter('erreur', 'Le formulaire contient des erreurs.');
            redirect($retour);
        }

        $nomChange = $nom !== $p['nom'];
        Personnage::modifier((int) $p['id'], $nom, $traits, $nomChange);

        // Log détaillé : seulement les traits réellement modifiés
        $changements = [];
        foreach ($traits as $cle => $valeur) {
            if ($p[$cle] !== $valeur) {
                $changements[] = "$cle : {$p[$cle]} → $valeur";
            }
        }
        if ($changements !== []) {
            ActivityLog::ajouter('personnage.apparence', Auth::utilisateur(), ['id' => $p['id'], 'nom' => $nom], ['changements' => $changements]);
        }
        if ($nomChange) {
            ActivityLog::ajouter('personnage.nom', Auth::utilisateur(), ['id' => $p['id'], 'nom' => $nom], ['ancien_nom' => $p['nom']]);
            Flash::ajouter('info', 'Le nouveau nom doit être validé par notre équipe : le personnage n\'est plus partagé en attendant.');
        }
        Flash::ajouter('succes', 'Personnage enregistré.');
        redirect($retour);
    }

    public function equiper(): void
    {
        $p = self::monPersonnage($_POST['id'] ?? null);
        if ($p['statut'] !== 'valide') {
            Flash::ajouter('erreur', 'Le personnage doit être validé avant de pouvoir être équipé.');
            redirect('/espace/personnage?id=' . $p['id']);
        }

        $choix = [];
        foreach (array_keys(Apparence::CATEGORIES) as $categorie) {
            $choix[$categorie] = Validator::id($_POST[$categorie] ?? null); // null = aucun article
        }
        $changements = Personnage::equiper((int) $p['id'], $choix);
        if ($changements !== []) {
            ActivityLog::ajouter('personnage.equipement', Auth::utilisateur(), $p, ['changements' => $changements]);
        }
        Flash::ajouter('succes', 'Équipement enregistré.');
        redirect('/espace/personnage?id=' . $p['id'] . '#equipement');
    }

    public function partager(): void
    {
        $p = self::monPersonnage($_POST['id'] ?? null);
        $partager = !$p['partage'];
        if (!Personnage::changerPartage((int) $p['id'], $partager)) {
            Flash::ajouter('erreur', 'Seul un personnage validé peut être partagé.');
            redirect('/espace');
        }
        ActivityLog::ajouter('personnage.partage', Auth::utilisateur(), $p, ['partage' => $partager]);
        Flash::ajouter('succes', $partager ? "« {$p['nom']} » est visible dans la galerie." : "« {$p['nom']} » n'est plus partagé.");
        redirect('/espace');
    }

    public function dupliquer(): void
    {
        $p = self::monPersonnage($_POST['id'] ?? null);
        $nom = trim(preg_replace('/\s+/u', ' ', (string) ($_POST['nouveau_nom'] ?? '')) ?? '');
        if (!Validator::nomPersonnage($nom) || Personnage::nomExiste($nom)) {
            Flash::ajouter('erreur', 'Pour dupliquer, choisissez un nom valide qui n\'est pas déjà utilisé.');
            redirect('/espace');
        }

        $copie = Personnage::dupliquer((int) $p['id'], $nom);
        ActivityLog::ajouter('personnage.duplication', Auth::utilisateur(), ['id' => $copie, 'nom' => $nom], ['origine' => $p['nom']]);
        Flash::ajouter('succes', "« {$p['nom']} » a été dupliqué en « $nom » (nom à valider par notre équipe).");
        redirect('/espace');
    }

    public function supprimer(): void
    {
        $p = self::monPersonnage($_POST['id'] ?? null);
        Personnage::supprimer((int) $p['id']);
        ActivityLog::ajouter('personnage.suppression', Auth::utilisateur(), $p, ['par' => 'propriétaire']);
        Flash::ajouter('succes', "« {$p['nom']} » a été supprimé.");
        redirect('/espace');
    }

    /**
     * Aperçu en direct pendant la personnalisation : GET /apercu?genre=...&vetement=3...
     * Les traits sont validés par liste blanche ; un trait invalide reprend la valeur par défaut.
     */
    public function apercu(): void
    {
        [$traits] = Apparence::valider($_GET);
        $traits += Apparence::DEFAUT;

        $equipement = [];
        foreach (array_keys(Apparence::CATEGORIES) as $categorie) {
            $id = Validator::id($_GET[$categorie] ?? null);
            $a = $id ? Accessoire::trouverActif($id) : null;
            if ($a !== null && $a['categorie'] === $categorie) {
                $equipement[] = $a;
            }
        }

        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Cache-Control: no-store');
        echo Apparence::svg($traits, $equipement, 'Aperçu du personnage');
    }
}
