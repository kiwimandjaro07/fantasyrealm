<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\Utilisateur;
use App\Services\ActivityLog;
use App\Services\MailService;

use function App\Core\redirect;

/**
 * Espace administrateur : comptes employés et logs de personnalisation.
 * Le compte administrateur est créé par le script SQL : aucune route ne permet d'en créer un.
 */
class AdminController
{
    public function employes(): void
    {
        $erreurs = $_SESSION['_erreurs'] ?? [];
        unset($_SESSION['_erreurs']);
        View::render('admin/employes', [
            'title'    => 'Comptes employés',
            'employes' => Utilisateur::parRole('employe'),
            'erreurs'  => $erreurs,
            'old'      => Flash::saisie(),
        ]);
    }

    public function creerEmploye(): void
    {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $mdp = (string) ($_POST['mot_de_passe'] ?? '');

        $erreurs = [];
        if (!Validator::email($email)) {
            $erreurs['email'] = 'Adresse e-mail invalide.';
        } elseif (Utilisateur::emailExiste($email)) {
            $erreurs['email'] = 'Cette adresse est déjà utilisée.';
        }
        if (!Validator::motDePasse($mdp)) {
            $erreurs['mot_de_passe'] = '12 caractères minimum, avec une majuscule, une minuscule, un chiffre et un caractère spécial.';
        }
        if ($erreurs !== []) {
            Flash::garderSaisie($_POST);
            $_SESSION['_erreurs'] = $erreurs;
            Flash::ajouter('erreur', 'Le compte n\'a pas été créé.');
            redirect('/admin/employes');
        }

        // L'énoncé ne demande qu'un e-mail et un mot de passe : le pseudo est déduit de l'e-mail (unique)
        $base = preg_replace('/[^\p{L}\p{N}_.-]/u', '', explode('@', $email)[0]) ?: 'employe';
        $pseudo = mb_substr($base, 0, 24);
        for ($i = 2; Utilisateur::pseudoExiste($pseudo); $i++) {
            $pseudo = mb_substr($base, 0, 24) . '-' . $i;
        }

        Utilisateur::creer($email, $pseudo, $mdp, 'employe');
        MailService::compteEmploye($email, $pseudo);
        ActivityLog::ajouter('compte.gestion', Auth::utilisateur(), null, ['action' => 'création employé', 'compte' => $pseudo]);
        Flash::ajouter('succes', "Compte employé créé pour $email (pseudo : $pseudo).");
        redirect('/admin/employes');
    }

    public function motDePasseEmploye(): void
    {
        $id = Validator::id($_POST['id'] ?? null) ?? 0;
        $mdp = (string) ($_POST['mot_de_passe'] ?? '');
        $u = Utilisateur::trouver($id);
        if ($u === null || $u['role'] !== 'employe') {
            redirect('/admin/employes');
        }
        if (!Validator::motDePasse($mdp)) {
            Flash::ajouter('erreur', 'Mot de passe trop faible : 12 caractères, majuscule, minuscule, chiffre et caractère spécial.');
            redirect('/admin/employes');
        }
        Utilisateur::changerMotDePasse($id, $mdp);
        ActivityLog::ajouter('compte.gestion', Auth::utilisateur(), null, ['action' => 'mot de passe employé modifié', 'compte' => $u['pseudo']]);
        Flash::ajouter('succes', "Le mot de passe de {$u['pseudo']} a été modifié.");
        redirect('/admin/employes');
    }

    public function statutEmploye(): void
    {
        $id = Validator::id($_POST['id'] ?? null) ?? 0;
        $statut = ($_POST['statut'] ?? '') === 'suspendu' ? 'suspendu' : 'actif';
        $u = Utilisateur::trouver($id);
        if ($u !== null && Utilisateur::changerStatut($id, $statut, 'employe')) {
            ActivityLog::ajouter('compte.gestion', Auth::utilisateur(), null, ['action' => $statut === 'suspendu' ? 'suspension employé' : 'réactivation employé', 'compte' => $u['pseudo']]);
            Flash::ajouter('succes', $statut === 'suspendu' ? "Le compte de {$u['pseudo']} est suspendu." : "Le compte de {$u['pseudo']} est réactivé.");
        }
        redirect('/admin/employes');
    }

    public function supprimerEmploye(): void
    {
        $id = Validator::id($_POST['id'] ?? null) ?? 0;
        $u = Utilisateur::trouver($id);
        if ($u !== null && Utilisateur::supprimer($id, 'employe')) {
            ActivityLog::ajouter('compte.gestion', Auth::utilisateur(), null, ['action' => 'suppression employé', 'compte' => $u['pseudo']]);
            Flash::ajouter('succes', "Le compte de {$u['pseudo']} est supprimé.");
        }
        redirect('/admin/employes');
    }

    public function logs(): void
    {
        $filtres = [
            'type'       => is_string($_GET['type'] ?? null) ? $_GET['type'] : '',
            'personnage' => mb_substr(trim((string) ($_GET['personnage'] ?? '')), 0, 40),
            'pseudo'     => mb_substr(trim((string) ($_GET['pseudo'] ?? '')), 0, 30),
        ];
        try {
            [$logs, $stats, $erreur] = [ActivityLog::rechercher($filtres), ActivityLog::statistiques(), null];
        } catch (\Throwable $e) {
            error_log('[Logs] ' . $e->getMessage());
            [$logs, $stats, $erreur] = [[], [], 'La base MongoDB est injoignable.'];
        }

        View::render('admin/logs', [
            'title'   => 'Logs de personnalisation',
            'logs'    => $logs,
            'stats'   => $stats,
            'types'   => ActivityLog::TYPES,
            'filtres' => $filtres,
            'erreur'  => $erreur,
        ]);
    }
}
