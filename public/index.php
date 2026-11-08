<?php
/**
 * Point d'entrée unique de l'application (front controller) : toutes les requêtes arrivent ici (.htaccess).
 */

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\EspaceController;
use App\Controllers\GalerieController;
use App\Controllers\GestionController;
use App\Controllers\PageController;
use App\Core\Auth;
use App\Core\Router;
use App\Core\Security;

define('ROOT', dirname(__DIR__));

require ROOT . '/vendor/autoload.php';

// Variables d'environnement : les secrets (mots de passe des bases, SMTP) ne sont pas dans le code
Dotenv\Dotenv::createImmutable(ROOT)->safeLoad();
date_default_timezone_set('Europe/Paris');

$dev = ($_ENV['APP_ENV'] ?? 'prod') === 'dev';
ini_set('display_errors', $dev ? '1' : '0');   // jamais d'erreur détaillée en production
error_reporting(E_ALL);
if (!$dev) {
    set_exception_handler(static function (Throwable $e): void {
        error_log((string) $e);
        Router::erreur(500, 'Erreur', 'Une erreur est survenue. Réessayez dans quelques instants.');
    });
}

Security::envoyerEntetes();
Auth::demarrerSession();

if (!Security::entreesValides($_GET) || !Security::entreesValides($_POST)) {
    Router::erreur(400, 'Requête invalide', 'Les données envoyées ne sont pas valides.');
    exit;
}

$joueur = 'joueur';
$personnel = ['employe', 'admin'];
$admin = 'admin';

$r = new Router();

// Pages publiques
$r->get('/', [PageController::class, 'accueil']);
$r->get('/mentions-legales', [PageController::class, 'mentionsLegales']);
$r->get('/conditions-generales', [PageController::class, 'conditionsGenerales']);
$r->get('/confidentialite', [PageController::class, 'confidentialite']);
$r->get('/contact', [ContactController::class, 'formulaire']);
$r->post('/contact', [ContactController::class, 'envoyer']);
$r->get('/api/pseudo', [ContactController::class, 'apiPseudo']);

// Galerie des personnages partagés
$r->get('/personnages', [GalerieController::class, 'liste']);
$r->get('/personnage', [GalerieController::class, 'detail']);
$r->get('/avatar', [GalerieController::class, 'avatar']);
$r->post('/personnage/avis', [GalerieController::class, 'deposerAvis'], $joueur);

// Comptes
$r->get('/inscription', [AuthController::class, 'formulaireInscription']);
$r->post('/inscription', [AuthController::class, 'inscription']);
$r->get('/connexion', [AuthController::class, 'formulaireConnexion']);
$r->post('/connexion', [AuthController::class, 'connexion']);
$r->post('/deconnexion', [AuthController::class, 'deconnexion']);
$r->get('/mot-de-passe-oublie', [AuthController::class, 'formulaireOubli']);
$r->post('/mot-de-passe-oublie', [AuthController::class, 'oubli']);
$r->get('/espace/mot-de-passe', [AuthController::class, 'formulaireChangement'], [$joueur, ...$personnel]);
$r->post('/espace/mot-de-passe', [AuthController::class, 'changement'], [$joueur, ...$personnel]);
$r->post('/espace/supprimer-compte', [AuthController::class, 'supprimerCompte'], $joueur);

// Espace joueur
$r->get('/espace', [EspaceController::class, 'index'], $joueur);
$r->get('/espace/creer', [EspaceController::class, 'formulaireCreation'], $joueur);
$r->post('/espace/creer', [EspaceController::class, 'creer'], $joueur);
$r->get('/espace/personnage', [EspaceController::class, 'formulaireModification'], $joueur);
$r->post('/espace/personnage', [EspaceController::class, 'modifier'], $joueur);
$r->post('/espace/equiper', [EspaceController::class, 'equiper'], $joueur);
$r->post('/espace/partager', [EspaceController::class, 'partager'], $joueur);
$r->post('/espace/dupliquer', [EspaceController::class, 'dupliquer'], $joueur);
$r->post('/espace/supprimer', [EspaceController::class, 'supprimer'], $joueur);
$r->get('/apercu', [EspaceController::class, 'apercu'], $joueur);

// Espace employé (accessible aussi à l'administrateur)
$r->get('/gestion', [GestionController::class, 'index'], $personnel);
$r->post('/gestion/personnage/valider', [GestionController::class, 'validerPersonnage'], $personnel);
$r->post('/gestion/personnage/rejeter', [GestionController::class, 'rejeterPersonnage'], $personnel);
$r->post('/gestion/avis', [GestionController::class, 'modererAvis'], $personnel);
$r->get('/gestion/accessoires', [GestionController::class, 'accessoires'], $personnel);
$r->post('/gestion/accessoires', [GestionController::class, 'creerAccessoire'], $personnel);
$r->post('/gestion/accessoires/etat', [GestionController::class, 'etatAccessoire'], $personnel);
$r->post('/gestion/accessoires/supprimer', [GestionController::class, 'supprimerAccessoire'], $personnel);
$r->get('/gestion/personnages', [GestionController::class, 'personnages'], $personnel);
$r->post('/gestion/personnages/supprimer', [GestionController::class, 'supprimerPersonnage'], $personnel);
$r->get('/gestion/joueurs', [GestionController::class, 'joueurs'], $personnel);
$r->post('/gestion/joueurs/statut', [GestionController::class, 'statutJoueur'], $personnel);
$r->post('/gestion/joueurs/supprimer', [GestionController::class, 'supprimerJoueur'], $personnel);

// Espace administrateur
$r->get('/admin/employes', [AdminController::class, 'employes'], $admin);
$r->post('/admin/employes', [AdminController::class, 'creerEmploye'], $admin);
$r->post('/admin/employes/mot-de-passe', [AdminController::class, 'motDePasseEmploye'], $admin);
$r->post('/admin/employes/statut', [AdminController::class, 'statutEmploye'], $admin);
$r->post('/admin/employes/supprimer', [AdminController::class, 'supprimerEmploye'], $admin);
$r->get('/admin/logs', [AdminController::class, 'logs'], $admin);

$r->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
