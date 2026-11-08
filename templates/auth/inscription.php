<?php

use App\Core\Csrf;

use function App\Core\aria_erreur;
use function App\Core\e;
use function App\Core\message_erreur;

?>
<section class="carte bloc-etroit">
    <h1>Créer un compte</h1>
    <p class="petit">Tous les champs sont obligatoires.</p>
    <form method="post" action="/inscription" class="formulaire">
        <?= Csrf::champ() ?>

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required maxlength="190" autocomplete="email"
               value="<?= e($old['email'] ?? '') ?>"<?= aria_erreur($erreurs, 'email') ?>>
        <?= message_erreur($erreurs, 'email') ?>

        <label for="pseudo">Pseudo <span class="aide">(public, 3 à 30 caractères)</span></label>
        <input type="text" id="pseudo" name="pseudo" required minlength="3" maxlength="30" pattern="[\p{L}\p{N}_.\-]{3,30}"
               autocomplete="username" value="<?= e($old['pseudo'] ?? '') ?>"<?= aria_erreur($erreurs, 'pseudo') ?>>
        <?= message_erreur($erreurs, 'pseudo') ?>

        <label for="mot_de_passe">Mot de passe</label>
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

        <label for="mot_de_passe_confirmation">Confirmation du mot de passe</label>
        <input type="password" id="mot_de_passe_confirmation" name="mot_de_passe_confirmation" required minlength="12"
               maxlength="128" autocomplete="new-password" data-confirme="mot_de_passe"<?= aria_erreur($erreurs, 'mot_de_passe_confirmation') ?>>
        <?= message_erreur($erreurs, 'mot_de_passe_confirmation') ?>

        <div class="case">
            <input type="checkbox" id="cgu" name="cgu" value="1" required<?= aria_erreur($erreurs, 'cgu') ?>>
            <label for="cgu">J'accepte les <a href="/conditions-generales">conditions générales</a> et la
                <a href="/confidentialite">politique de confidentialité</a></label>
        </div>
        <?= message_erreur($erreurs, 'cgu') ?>

        <button type="submit" class="bouton">Créer mon compte</button>
    </form>
    <p>Déjà inscrit ? <a href="/connexion">Connectez-vous</a>.</p>
</section>
