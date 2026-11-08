<?php

use App\Core\Csrf;

use function App\Core\aria_erreur;
use function App\Core\message_erreur;

?>
<section class="carte bloc-etroit">
    <h1>Changer mon mot de passe</h1>
    <?php if ($impose): ?>
        <p class="flash flash-info">Vous utilisez un mot de passe provisoire : choisissez un nouveau mot de passe pour accéder à votre espace.</p>
    <?php endif; ?>
    <form method="post" action="/espace/mot-de-passe" class="formulaire">
        <?= Csrf::champ() ?>
        <label for="mot_de_passe_actuel">Mot de passe actuel<?= $impose ? ' (provisoire, reçu par e-mail)' : '' ?></label>
        <input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel" required autocomplete="current-password"<?= aria_erreur($erreurs, 'mot_de_passe_actuel') ?>>
        <?= message_erreur($erreurs, 'mot_de_passe_actuel') ?>

        <label for="mot_de_passe">Nouveau mot de passe</label>
        <p class="aide" id="regles-mdp">12 caractères minimum, dont une majuscule, une minuscule, un chiffre et un caractère spécial.</p>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="12" maxlength="128"
               autocomplete="new-password" aria-describedby="regles-mdp" data-force<?= isset($erreurs['mot_de_passe']) ? ' aria-invalid="true"' : '' ?>>
        <ul class="force-mdp" id="force-mdp" aria-live="polite" hidden>
            <li data-regle="longueur">12 caractères</li>
            <li data-regle="majuscule">une majuscule</li>
            <li data-regle="minuscule">une minuscule</li>
            <li data-regle="chiffre">un chiffre</li>
            <li data-regle="special">un caractère spécial</li>
        </ul>
        <?= message_erreur($erreurs, 'mot_de_passe') ?>

        <label for="mot_de_passe_confirmation">Confirmation</label>
        <input type="password" id="mot_de_passe_confirmation" name="mot_de_passe_confirmation" required minlength="12"
               maxlength="128" autocomplete="new-password" data-confirme="mot_de_passe"<?= aria_erreur($erreurs, 'mot_de_passe_confirmation') ?>>
        <?= message_erreur($erreurs, 'mot_de_passe_confirmation') ?>

        <button type="submit" class="bouton">Enregistrer</button>
    </form>
</section>
