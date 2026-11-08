<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\TentativeConnexion;
use App\Models\Utilisateur;
use App\Services\ActivityLog;
use App\Services\MailService;
use App\Services\MotDePasse;

use function App\Core\redirect;

/**
 * Création de compte, connexion, déconnexion, mot de passe oublié et changement de mot de passe.
 */
class AuthController
{
    /** Hash factice : vérifié quand l'e-mail n'existe pas, pour que le temps de réponse soit identique. */
    private const HASH_FACTICE = '$argon2id$v=19$m=65536,t=4,p=1$RmczUXpYcTZQY09NdE0zSg$fLPsRZPXk7SPkKYJPnGYHilwfmNVjdYLfOQ0P7EaM4k';

    public static function accueilDuRole(string $role): string
    {
        return ['joueur' => '/espace', 'employe' => '/gestion', 'admin' => '/gestion'][$role] ?? '/';
    }

    private static function erreurs(): array
    {
        $e = $_SESSION['_erreurs'] ?? [];
        unset($_SESSION['_erreurs']);

        return $e;
    }

    private static function retour(string $chemin, array $erreurs, array $saisie = []): never
    {
        Flash::garderSaisie($saisie);
        $_SESSION['_erreurs'] = $erreurs;
        Flash::ajouter('erreur', 'Le formulaire contient des erreurs.');
        redirect($chemin);
    }

    // ----- Inscription -----

    public function formulaireInscription(): void
    {
        if (Auth::utilisateur()) {
            redirect(self::accueilDuRole(Auth::utilisateur()['role']));
        }
        View::render('auth/inscription', ['title' => 'Créer un compte', 'old' => Flash::saisie(), 'erreurs' => self::erreurs()]);
    }

    public function inscription(): void
    {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $pseudo = trim((string) ($_POST['pseudo'] ?? ''));
        $mdp = (string) ($_POST['mot_de_passe'] ?? '');

        $erreurs = [];
        if (!Validator::email($email)) {
            $erreurs['email'] = 'Adresse e-mail invalide.';
        } elseif (Utilisateur::emailExiste($email)) {
            $erreurs['email'] = 'Cette adresse est déjà utilisée par un compte.';
        }
        if (!Validator::pseudo($pseudo)) {
            $erreurs['pseudo'] = '3 à 30 caractères : lettres, chiffres, point, tiret ou souligné.';
        } elseif (Utilisateur::pseudoExiste($pseudo)) {
            $erreurs['pseudo'] = 'Ce pseudo est déjà pris.';
        }
        if (!Validator::motDePasse($mdp)) {
            $erreurs['mot_de_passe'] = '12 caractères minimum, avec une majuscule, une minuscule, un chiffre et un caractère spécial.';
        } elseif ($mdp !== ($_POST['mot_de_passe_confirmation'] ?? null)) {
            $erreurs['mot_de_passe_confirmation'] = 'Les deux mots de passe ne correspondent pas.';
        }
        if (empty($_POST['cgu'])) {
            $erreurs['cgu'] = 'Vous devez accepter les conditions générales.';
        }
        if ($erreurs !== []) {
            self::retour('/inscription', $erreurs, $_POST);
        }

        try {
            $id = Utilisateur::creer($email, $pseudo, $mdp);
        } catch (\PDOException $e) {
            // Deux inscriptions simultanées avec le même e-mail : la contrainte UNIQUE de la base tranche
            if ($e->getCode() === '23000') {
                self::retour('/inscription', ['email' => 'Cette adresse ou ce pseudo vient d\'être utilisé.'], $_POST);
            }
            throw $e;
        }

        Auth::connecter($id);
        Flash::ajouter('succes', 'Bienvenue ' . $pseudo . ' ! Créez votre premier personnage.');
        redirect('/espace');
    }

    // ----- Connexion -----

    public function formulaireConnexion(): void
    {
        if (Auth::utilisateur()) {
            redirect(self::accueilDuRole(Auth::utilisateur()['role']));
        }
        View::render('auth/connexion', ['title' => 'Connexion', 'old' => Flash::saisie()]);
    }

    public function connexion(): void
    {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $mdp = (string) ($_POST['mot_de_passe'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if (TentativeConnexion::estBloque($email, $ip)) {
            Flash::ajouter('erreur', 'Trop de tentatives. Réessayez dans ' . TentativeConnexion::FENETRE_MINUTES . ' minutes.');
            Flash::garderSaisie(['email' => $email]);
            redirect('/connexion');
        }

        $u = Validator::email($email) ? Utilisateur::trouverPourConnexion($email) : null;
        $valide = password_verify($mdp, $u['mot_de_passe'] ?? self::HASH_FACTICE) && $u !== null;

        if (!$valide) {
            TentativeConnexion::enregistrerEchec($email, $ip);
            Flash::ajouter('erreur', 'E-mail ou mot de passe incorrect.'); // même message que l'e-mail existe ou non
            Flash::garderSaisie(['email' => $email]);
            redirect('/connexion');
        }
        if ($u['statut'] !== 'actif') {
            Flash::ajouter('erreur', 'Ce compte est suspendu. Contactez le support via la page Contact.');
            redirect('/connexion');
        }

        TentativeConnexion::effacer($email);
        Utilisateur::rehacherSiNecessaire($u, $mdp);
        Utilisateur::noterConnexion((int) $u['id']);
        Auth::connecter((int) $u['id']);
        ActivityLog::ajouter('compte.connexion', $u);

        if ($u['doit_changer_mdp']) {
            Flash::ajouter('info', 'Vous vous êtes connecté avec un mot de passe provisoire : choisissez-en un nouveau.');
            redirect('/espace/mot-de-passe');
        }
        Flash::ajouter('succes', 'Bonjour ' . $u['pseudo'] . ' !');
        redirect(self::accueilDuRole($u['role']));
    }

    public function deconnexion(): void
    {
        Auth::deconnecter();
        Auth::demarrerSession();
        Flash::ajouter('info', 'Vous êtes déconnecté.');
        redirect('/');
    }

    // ----- Mot de passe oublié -----

    public function formulaireOubli(): void
    {
        View::render('auth/oubli', ['title' => 'Mot de passe oublié', 'old' => Flash::saisie()]);
    }

    /**
     * Le joueur donne son e-mail ET son pseudo. S'ils correspondent, un mot de passe provisoire sécurisé
     * lui est envoyé par e-mail ; il devra le changer à sa prochaine connexion.
     * Le message affiché est le même dans tous les cas : on ne révèle pas si le compte existe.
     */
    public function oubli(): void
    {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $pseudo = trim((string) ($_POST['pseudo'] ?? ''));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if (TentativeConnexion::estBloque($email, $ip)) {
            Flash::ajouter('erreur', 'Trop de demandes. Réessayez plus tard.');
            redirect('/mot-de-passe-oublie');
        }
        // Chaque demande compte comme une tentative : limite les envois répétés
        TentativeConnexion::enregistrerEchec($email, $ip);

        $u = Validator::email($email) ? Utilisateur::trouverParPseudo($pseudo) : null;
        if ($u !== null && $u['email'] === $email && $u['statut'] === 'actif') {
            $provisoire = MotDePasse::generer();
            Utilisateur::changerMotDePasse((int) $u['id'], $provisoire, true);
            MailService::motDePasseProvisoire($u, $provisoire);
            ActivityLog::ajouter('compte.gestion', $u, null, ['action' => 'mot de passe réinitialisé']);
        }

        Flash::ajouter('info', 'Si ces informations correspondent à un compte, un e-mail contenant un mot de passe provisoire vient d\'être envoyé.');
        redirect('/connexion');
    }

    // ----- Changement de mot de passe (obligatoire après réinitialisation) -----

    public function formulaireChangement(): void
    {
        View::render('auth/changement', [
            'title'     => 'Changer mon mot de passe',
            'impose'    => (bool) Auth::utilisateur()['doit_changer_mdp'],
            'erreurs'   => self::erreurs(),
        ]);
    }

    public function changement(): void
    {
        $u = Auth::utilisateur();
        $actuel = (string) ($_POST['mot_de_passe_actuel'] ?? '');
        $nouveau = (string) ($_POST['mot_de_passe'] ?? '');

        $erreurs = [];
        if (!Utilisateur::verifierMotDePasse((int) $u['id'], $actuel)) {
            $erreurs['mot_de_passe_actuel'] = 'Mot de passe actuel incorrect.';
        }
        if (!Validator::motDePasse($nouveau)) {
            $erreurs['mot_de_passe'] = '12 caractères minimum, avec une majuscule, une minuscule, un chiffre et un caractère spécial.';
        } elseif ($nouveau === $actuel) {
            $erreurs['mot_de_passe'] = 'Le nouveau mot de passe doit être différent de l\'actuel.';
        } elseif ($nouveau !== ($_POST['mot_de_passe_confirmation'] ?? null)) {
            $erreurs['mot_de_passe_confirmation'] = 'Les deux mots de passe ne correspondent pas.';
        }
        if ($erreurs !== []) {
            self::retour('/espace/mot-de-passe', $erreurs);
        }

        Utilisateur::changerMotDePasse((int) $u['id'], $nouveau);
        Auth::connecter((int) $u['id']); // nouvel identifiant de session après un changement sensible
        Flash::ajouter('succes', 'Votre mot de passe a été modifié.');
        redirect(self::accueilDuRole($u['role']));
    }

    /** RGPD (droit à l'effacement) : le joueur supprime lui-même son compte et toutes ses données. */
    public function supprimerCompte(): void
    {
        $u = Auth::utilisateur();
        if (!Utilisateur::verifierMotDePasse((int) $u['id'], (string) ($_POST['mot_de_passe'] ?? ''))) {
            Flash::ajouter('erreur', 'Mot de passe incorrect : le compte n\'a pas été supprimé.');
            redirect('/espace');
        }
        Utilisateur::supprimer((int) $u['id'], 'joueur');
        ActivityLog::ajouter('compte.gestion', $u, null, ['action' => 'compte supprimé par son titulaire']);
        Auth::deconnecter();
        Auth::demarrerSession();
        Flash::ajouter('info', 'Votre compte et vos personnages ont été supprimés.');
        redirect('/');
    }
}
