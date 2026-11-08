<?php

use App\Core\Csrf;

use function App\Core\aria_erreur;
use function App\Core\e;
use function App\Core\message_erreur;

?>
<section class="carte bloc-moyen">
    <h1>Contact</h1>
    <p>Une question sur votre compte ou vos personnages ? Notre équipe vous répond par e-mail.</p>
    <p class="petit">Tous les champs sont obligatoires.</p>

    <form method="post" action="/contact" class="formulaire" id="form-contact">
        <?= Csrf::champ() ?>

        <label for="email">Votre e-mail</label>
        <input type="email" id="email" name="email" required maxlength="190" autocomplete="email"
               value="<?= e($old['email'] ?? '') ?>"<?= $connecte ? ' readonly' : '' ?><?= aria_erreur($erreurs, 'email') ?>>
        <?= message_erreur($erreurs, 'email') ?>

        <label for="pseudo">Votre pseudo <span class="aide">(celui de votre compte de jeu)</span></label>
        <input type="text" id="pseudo" name="pseudo" required minlength="3" maxlength="30" autocomplete="username"
               value="<?= e($old['pseudo'] ?? '') ?>"<?= $connecte ? ' readonly' : '' ?><?= aria_erreur($erreurs, 'pseudo') ?>>
        <p class="aide" id="pseudo-statut" role="status"></p>
        <?= message_erreur($erreurs, 'pseudo') ?>

        <label for="demande">Détail de la demande</label>
        <textarea id="demande" name="demande" rows="7" required minlength="10" maxlength="3000"<?= aria_erreur($erreurs, 'demande') ?>><?= e($old['demande'] ?? '') ?></textarea>
        <?= message_erreur($erreurs, 'demande') ?>

        <!-- Champ piège anti-robot : invisible pour les humains et ignoré par les lecteurs d'écran -->
        <div class="piege" aria-hidden="true">
            <label for="site_web">Ne pas remplir</label>
            <input type="text" id="site_web" name="site_web" tabindex="-1" autocomplete="off">
        </div>

        <button type="submit" class="bouton">Envoyer</button>
    </form>
</section>
