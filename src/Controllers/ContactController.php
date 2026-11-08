<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Validator;
use App\Core\View;
use App\Models\MessageContact;
use App\Models\Utilisateur;
use App\Services\MailService;

use function App\Core\e;
use function App\Core\redirect;

/**
 * Formulaire de contact : e-mail, pseudo, détail de la demande.
 * - connecté : e-mail et pseudo pré-remplis ;
 * - le pseudo doit exister (vérifié côté serveur, et signalé dès la saisie en JavaScript) ;
 * - la demande est envoyée par e-mail à l'entreprise et conservée dans MongoDB.
 * Anti-spam : champ piège invisible, délai minimal de 3 s, 5 envois par session.
 */
class ContactController
{
    public function formulaire(): void
    {
        $_SESSION['_contact_affiche_le'] = time();
        $u = Auth::utilisateur();
        $erreurs = $_SESSION['_erreurs'] ?? [];
        unset($_SESSION['_erreurs']);

        View::render('pages/contact', [
            'title'    => 'Contact',
            'old'      => Flash::saisie() + ['email' => $u['email'] ?? '', 'pseudo' => $u['pseudo'] ?? ''],
            'connecte' => $u !== null,
            'erreurs'  => $erreurs,
        ]);
    }

    public function envoyer(): void
    {
        $u = Auth::utilisateur();
        // Connecté : on prend l'e-mail et le pseudo du compte, pas ceux du formulaire (qui pourraient être modifiés)
        $email = $u['email'] ?? mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $pseudo = $u['pseudo'] ?? trim((string) ($_POST['pseudo'] ?? ''));
        $demande = trim((string) ($_POST['demande'] ?? ''));

        $robot = ($_POST['site_web'] ?? '') !== ''
            || time() - ($_SESSION['_contact_affiche_le'] ?? 0) < 3
            || ($_SESSION['_contact_envois'] ?? 0) >= 5;
        if ($robot) {
            Flash::ajouter('succes', 'Merci, votre demande a bien été envoyée.'); // le robot n'apprend rien
            redirect('/contact');
        }

        $erreurs = [];
        if (!Validator::email($email)) {
            $erreurs['email'] = 'Adresse e-mail invalide.';
        }
        if (!Validator::pseudo($pseudo) || Utilisateur::trouverParPseudo($pseudo) === null) {
            $erreurs['pseudo'] = 'Ce pseudo n\'existe pas. Indiquez le pseudo de votre compte de jeu.';
        }
        if (!Validator::texte($demande, 3000, 10)) {
            $erreurs['demande'] = 'Votre demande doit contenir entre 10 et 3000 caractères.';
        }
        if ($erreurs !== []) {
            Flash::garderSaisie($_POST);
            $_SESSION['_erreurs'] = $erreurs;
            Flash::ajouter('erreur', 'Le formulaire contient des erreurs.');
            redirect('/contact');
        }

        $envoye = MailService::envoyer(
            $_ENV['MAIL_CONTACT'] ?? '',
            'Demande de contact de ' . $pseudo,
            '<p><strong>Pseudo :</strong> ' . e($pseudo) . '<br><strong>E-mail :</strong> ' . e($email) . '</p>'
            . '<p>' . nl2br(e($demande)) . '</p>',
            $email
        );
        try {
            MessageContact::enregistrer($email, $pseudo, $demande);
        } catch (\Throwable $ex) {
            error_log('[Contact] ' . $ex->getMessage());
        }

        if (!$envoye) {
            Flash::garderSaisie($_POST);
            Flash::ajouter('erreur', 'L\'envoi a échoué. Réessayez dans quelques minutes.');
            redirect('/contact');
        }
        $_SESSION['_contact_envois'] = ($_SESSION['_contact_envois'] ?? 0) + 1;
        Flash::ajouter('succes', 'Merci, votre demande a bien été envoyée. Notre équipe vous répondra par e-mail.');
        redirect('/contact');
    }

    /**
     * API JSON utilisée par le JavaScript du formulaire : GET /api/pseudo?pseudo=Lyra -> {"existe": true}.
     * Les pseudos sont déjà publics (galerie) : cette vérification ne révèle rien de confidentiel.
     */
    public function apiPseudo(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $pseudo = trim((string) ($_GET['pseudo'] ?? ''));
        echo json_encode(['existe' => Validator::pseudo($pseudo) && Utilisateur::trouverParPseudo($pseudo) !== null]);
    }
}
