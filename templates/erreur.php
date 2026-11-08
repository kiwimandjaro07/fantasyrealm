<?php

use function App\Core\e;

?>
<section class="carte bloc-etroit centre">
    <p class="code-erreur" aria-hidden="true"><?= (int) $code ?></p>
    <h1><?= e($title) ?></h1>
    <p><?= e($message !== '' ? $message : 'Cette page n\'existe pas, ou a disparu dans les brumes du royaume.') ?></p>
    <p><a class="bouton" href="/">Retour à l'accueil</a></p>
</section>
