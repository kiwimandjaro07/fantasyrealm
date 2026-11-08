<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;

use function App\Core\e;

$u = Auth::utilisateur();
$chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$actif = static fn (string $href): string => ($href === $chemin || ($href !== '/' && str_starts_with($chemin, $href . '/')))
    ? ' aria-current="page"' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'FantasyRealm') ?> - FantasyRealm Online</title>
    <meta name="description" content="Créez, personnalisez et partagez vos personnages de FantasyRealm Online, le MMORPG de PixelVerse Studios.">
    <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="/assets/js/app.js" defer></script>
</head>
<body>
<a class="lien-evitement" href="#contenu">Aller au contenu</a>

<header class="entete">
    <div class="conteneur entete-interieur">
        <a class="logo" href="/" aria-label="FantasyRealm Online, accueil">
            <img src="/assets/img/logo.svg" alt="" width="40" height="40">
            <span>Fantasy<strong>Realm</strong></span>
        </a>

        <button class="bouton-menu" type="button" aria-expanded="false" aria-controls="menu-principal" hidden>Menu</button>

        <nav id="menu-principal" class="menu" aria-label="Menu principal">
            <ul>
                <li><a href="/"<?= $actif('/') ?>>Accueil</a></li>
                <li><a href="/personnages"<?= $actif('/personnages') ?>>Personnages</a></li>
                <li><a href="/contact"<?= $actif('/contact') ?>>Contact</a></li>
                <?php if ($u === null): ?>
                    <li><a class="menu-bouton" href="/connexion"<?= $actif('/connexion') ?>>Connexion</a></li>
                <?php else: ?>
                    <?php if ($u['role'] === 'joueur'): ?>
                        <li><a href="/espace"<?= $actif('/espace') ?>>Mon espace</a></li>
                    <?php else: ?>
                        <li><a href="/gestion"<?= $actif('/gestion') ?>><?= $u['role'] === 'admin' ? 'Administration' : 'Espace employé' ?></a></li>
                    <?php endif; ?>
                    <li>
                        <form method="post" action="/deconnexion" class="en-ligne">
                            <?= Csrf::champ() ?>
                            <button type="submit" class="menu-bouton">Déconnexion<span class="sr-only"> de <?= e($u['pseudo']) ?></span></button>
                        </form>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main id="contenu" class="conteneur" tabindex="-1">
    <?php foreach (Flash::lire() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>" role="<?= $f['type'] === 'erreur' ? 'alert' : 'status' ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="pied">
    <div class="conteneur pied-interieur">
        <p class="logo-pied">Fantasy<strong>Realm</strong> Online</p>
        <ul class="pied-liens">
            <li><a href="/mentions-legales">Mentions légales</a></li>
            <li><a href="/conditions-generales">Conditions générales de vente</a></li>
            <li><a href="/confidentialite">Confidentialité</a></li>
            <li><a href="/contact">Contact</a></li>
        </ul>
        <p class="petit">&copy; <?= date('Y') ?> PixelVerse Studios - projet de formation (entreprise fictive)</p>
    </div>
</footer>
</body>
</html>
