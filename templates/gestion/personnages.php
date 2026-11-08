<?php

use App\Core\Csrf;
use App\Services\Apparence;

use function App\Core\date_fr;
use function App\Core\e;

?>
<h1>Tous les personnages</h1>
<?php require __DIR__ . '/_menu.php'; ?>

<div class="tableau-defilant">
    <table class="tableau">
        <thead><tr><th scope="col">Personnage</th><th scope="col">Joueur</th><th scope="col">Genre</th><th scope="col">Statut</th><th scope="col">Créé le</th><th scope="col">Action</th></tr></thead>
        <tbody>
        <?php foreach ($personnages as $p): ?>
            <tr>
                <td><a href="/personnage?id=<?= (int) $p['id'] ?>"><?= e($p['nom']) ?></a></td>
                <td><?= e($p['pseudo']) ?></td>
                <td><?= e(Apparence::GENRES[$p['genre']]) ?></td>
                <td><?= $p['statut'] === 'en_attente' ? 'En attente' : ($p['partage'] ? 'Validé, partagé' : 'Validé, privé') ?></td>
                <td><?= e(date_fr($p['cree_le'])) ?></td>
                <td>
                    <form method="post" action="/gestion/personnages/supprimer" data-confirmation="Supprimer définitivement « <?= e($p['nom']) ?> » ?">
                        <?= Csrf::champ() ?>
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button type="submit" class="bouton bouton-petit bouton-danger">Supprimer<span class="sr-only"> <?= e($p['nom']) ?></span></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
