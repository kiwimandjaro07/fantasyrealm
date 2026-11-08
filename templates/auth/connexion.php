<?php

use App\Core\Csrf;

use function App\Core\e;

?>
<section class="carte bloc-etroit">
    <h1>Connexion</h1>
    <form method="post" action="/connexion" class="formulaire">
        <?= Csrf::champ() ?>
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required maxlength="190" autocomplete="email" value="<?= e($old['email'] ?? '') ?>">

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="current-password">

        <button type="submit" class="bouton">Se connecter</button>
    </form>
    <p><a href="/mot-de-passe-oublie">Mot de passe oublié ?</a></p>
    <p>Pas encore de compte ? <a href="/inscription">Créez votre compte</a> et votre premier personnage.</p>
</section>
