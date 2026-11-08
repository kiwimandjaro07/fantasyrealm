<?php

use App\Core\Csrf;

?>
<section class="carte bloc-etroit">
    <h1>Mot de passe oublié</h1>
    <p>Indiquez l'e-mail et le pseudo de votre compte. Vous recevrez un mot de passe provisoire, à changer dès votre connexion.</p>
    <form method="post" action="/mot-de-passe-oublie" class="formulaire">
        <?= Csrf::champ() ?>
        <label for="email">E-mail du compte</label>
        <input type="email" id="email" name="email" required maxlength="190" autocomplete="email">

        <label for="pseudo">Pseudo</label>
        <input type="text" id="pseudo" name="pseudo" required maxlength="30" autocomplete="username">

        <button type="submit" class="bouton">Recevoir un nouveau mot de passe</button>
    </form>
    <p><a href="/connexion">Retour à la connexion</a></p>
</section>
