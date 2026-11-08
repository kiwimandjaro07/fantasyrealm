<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Routeur : associe une méthode HTTP et un chemin à une action de contrôleur.
 *
 * Contrôles centralisés, faits AVANT d'appeler le contrôleur (impossible de les oublier) :
 * - toute requête POST doit porter un jeton CSRF valide (sinon 403) ;
 * - une route peut exiger un ou plusieurs rôles : visiteur -> page de connexion,
 *   connecté sans le bon rôle -> 403 ;
 * - un utilisateur dont le mot de passe a été réinitialisé est obligé de le changer
 *   avant d'accéder à son espace.
 */
class Router
{
    /** Pages accessibles même quand le changement de mot de passe est imposé. */
    private const PAGES_LIBRES_MDP = ['/espace/mot-de-passe', '/deconnexion'];

    /** @var array<string, array<string, array{action: array{0: class-string, 1: string}, roles: list<string>}>> */
    private array $routes = [];

    /** @param string|list<string> $roles */
    public function get(string $path, array $action, string|array $roles = []): void
    {
        $this->routes['GET'][$path] = ['action' => $action, 'roles' => (array) $roles];
    }

    /** @param string|list<string> $roles */
    public function post(string $path, array $action, string|array $roles = []): void
    {
        $this->routes['POST'][$path] = ['action' => $action, 'roles' => (array) $roles];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $route = $this->routes[$method][$path] ?? null;
        if ($route === null) {
            $autre = $method === 'GET' ? 'POST' : 'GET';
            isset($this->routes[$autre][$path])
                ? self::erreur(405, 'Méthode non autorisée')
                : self::erreur(404, 'Page introuvable');
            return;
        }

        if ($method === 'POST' && !Csrf::verifier($_POST['_csrf'] ?? null)) {
            self::erreur(403, 'Accès refusé', 'Le formulaire a expiré ou n\'est pas valide. Rechargez la page et réessayez.');
            return;
        }

        $utilisateur = Auth::utilisateur();

        if ($utilisateur !== null && $utilisateur['doit_changer_mdp'] && !in_array($path, self::PAGES_LIBRES_MDP, true)
            && $route['roles'] !== []) {
            Flash::ajouter('info', 'Votre mot de passe a été réinitialisé : choisissez-en un nouveau pour continuer.');
            redirect('/espace/mot-de-passe');
        }

        if ($route['roles'] !== []) {
            if ($utilisateur === null) {
                Flash::ajouter('info', 'Connectez-vous pour accéder à cette page.');
                redirect('/connexion');
            }
            if (!in_array($utilisateur['role'], $route['roles'], true)) {
                self::erreur(403, 'Accès refusé', 'Vous n\'avez pas les droits nécessaires pour cette page.');
                return;
            }
        }

        [$class, $methodName] = $route['action'];
        (new $class())->$methodName();
    }

    public static function erreur(int $code, string $titre, string $message = ''): void
    {
        http_response_code($code);
        View::render('erreur', ['title' => $titre, 'code' => $code, 'message' => $message]);
    }
}
