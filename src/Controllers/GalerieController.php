<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Router;
use App\Core\Validator;
use App\Core\View;
use App\Models\Avis;
use App\Models\Personnage;
use App\Services\ActivityLog;
use App\Services\Apparence;

use function App\Core\redirect;

/**
 * Page globale des personnages partagés, détail d'un personnage, image de l'avatar et dépôt d'avis.
 */
class GalerieController
{
    public function liste(): void
    {
        // Les filtres viennent de l'URL (GET) : chacun est validé, une valeur invalide est simplement ignorée
        $filtres = [
            'genre'  => Validator::parmi($_GET['genre'] ?? null, array_keys(Apparence::GENRES)) ? $_GET['genre'] : '',
            'du'     => Validator::date((string) ($_GET['du'] ?? '')) ? $_GET['du'] : '',
            'au'     => Validator::date((string) ($_GET['au'] ?? '')) ? $_GET['au'] : '',
            'pseudo' => mb_substr(trim((string) ($_GET['pseudo'] ?? '')), 0, 30),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $resultat = Personnage::galerie($filtres, $page);

        View::render('galerie/liste', [
            'title'       => 'Les personnages',
            'filtres'     => $filtres,
            'personnages' => $resultat['personnages'],
            'total'       => $resultat['total'],
            'page'        => $page,
            'pages'       => (int) max(1, ceil($resultat['total'] / 12)),
        ]);
    }

    /** Un personnage est visible par tous s'il est partagé ; sinon seulement par son propriétaire et le personnel. */
    private static function visible(?array $p): bool
    {
        if ($p === null) {
            return false;
        }
        if ($p['statut'] === 'valide' && $p['partage']) {
            return true;
        }

        return Auth::id() === (int) $p['utilisateur_id'] || Auth::estPersonnel();
    }

    public function detail(): void
    {
        $p = Personnage::trouver(Validator::id($_GET['id'] ?? null) ?? 0);
        if (!self::visible($p)) {
            Router::erreur(404, 'Personnage introuvable', 'Ce personnage n\'existe pas ou n\'est pas partagé.');
            return;
        }

        $u = Auth::utilisateur();
        $erreurs = $_SESSION['_erreurs'] ?? [];
        unset($_SESSION['_erreurs']);

        View::render('galerie/detail', [
            'title'       => $p['nom'],
            'p'           => $p,
            'equipement'  => Personnage::equipement((int) $p['id']),
            'avis'        => Avis::validesDuPersonnage((int) $p['id']),
            'monAvis'     => $u ? Avis::deLAuteur((int) $p['id'], (int) $u['id']) : null,
            'peutNoter'   => ($u['role'] ?? null) === 'joueur' && (int) $u['id'] !== (int) $p['utilisateur_id']
                && $p['statut'] === 'valide' && $p['partage'],
            'connecte'    => $u !== null,
            'erreurs'     => $erreurs,
            'old'         => Flash::saisie(),
        ]);
    }

    /** Image SVG de l'avatar : GET /avatar?id=12 (utilisée dans des balises <img>). */
    public function avatar(): void
    {
        $p = Personnage::trouver(Validator::id($_GET['id'] ?? null) ?? 0);
        if (!self::visible($p)) {
            http_response_code(404);
            return;
        }
        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Cache-Control: private, max-age=60');
        echo Apparence::svg($p, Personnage::equipement((int) $p['id']), $p['nom']);
    }

    public function deposerAvis(): void
    {
        $u = Auth::utilisateur();
        $id = Validator::id($_POST['personnage_id'] ?? null) ?? 0;
        $p = Personnage::trouver($id);
        $retour = '/personnage?id=' . $id;

        if ($p === null || !($p['statut'] === 'valide' && $p['partage']) || (int) $p['utilisateur_id'] === (int) $u['id']) {
            Flash::ajouter('erreur', 'Vous ne pouvez pas donner d\'avis sur ce personnage.');
            redirect($p ? $retour : '/personnages');
        }
        if (Avis::deLAuteur($id, (int) $u['id']) !== null) {
            Flash::ajouter('erreur', 'Vous avez déjà donné votre avis sur ce personnage.');
            redirect($retour);
        }

        $note = filter_var($_POST['note'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        $commentaire = trim((string) ($_POST['commentaire'] ?? ''));
        $erreurs = [];
        if ($note === false) {
            $erreurs['note'] = 'Choisissez une note de 1 à 5.';
        }
        if (!Validator::texte($commentaire, 1000, 5)) {
            $erreurs['commentaire'] = 'Le commentaire doit contenir entre 5 et 1000 caractères.';
        }
        if ($erreurs !== []) {
            Flash::garderSaisie($_POST);
            $_SESSION['_erreurs'] = $erreurs;
            redirect($retour . '#avis');
        }

        Avis::creer($id, (int) $u['id'], $note, $commentaire);
        ActivityLog::ajouter('avis.depot', $u, $p, ['note' => $note]);
        Flash::ajouter('succes', 'Merci ! Votre avis sera visible après validation par notre équipe.');
        redirect($retour);
    }
}
