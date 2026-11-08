<?php

use App\Core\Csrf;

use function App\Core\date_fr;
use function App\Core\e;

?>
<h1>Comptes joueurs</h1>
<?php require __DIR__ . '/_menu.php'; ?>

<div class="tableau-defilant">
    <table class="tableau">
        <thead><tr><th scope="col">Pseudo</th><th scope="col">E-mail</th><th scope="col">Personnages</th><th scope="col">Dernière connexion</th><th scope="col">Statut</th><th scope="col">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($joueurs as $j): ?>
            <tr<?= $j['statut'] === 'suspendu' ? ' class="inactif"' : '' ?>>
                <td><strong><?= e($j['pseudo']) ?></strong></td>
                <td><?= e($j['email']) ?></td>
                <td><?= (int) $j['nb_personnages'] ?></td>
                <td><?= $j['derniere_connexion'] ? e(date_fr($j['derniere_connexion'], true)) : 'Jamais' ?></td>
                <td><?= $j['statut'] === 'actif' ? 'Actif' : 'Suspendu' ?></td>
                <td class="actions-tableau">
                    <form method="post" action="/gestion/joueurs/statut">
                        <?= Csrf::champ() ?>
                        <input type="hidden" name="id" value="<?= (int) $j['id'] ?>">
                        <input type="hidden" name="statut" value="<?= $j['statut'] === 'actif' ? 'suspendu' : 'actif' ?>">
                        <button type="submit" class="bouton bouton-petit bouton-secondaire">
                            <?= $j['statut'] === 'actif' ? 'Suspendre' : 'Réactiver' ?><span class="sr-only"> <?= e($j['pseudo']) ?></span></button>
                    </form>
                    <form method="post" action="/gestion/joueurs/supprimer"
                          data-confirmation="Supprimer le compte de <?= e($j['pseudo']) ?> et ses <?= (int) $j['nb_personnages'] ?> personnage(s) ?">
                        <?= Csrf::champ() ?>
                        <input type="hidden" name="id" value="<?= (int) $j['id'] ?>">
                        <button type="submit" class="bouton bouton-petit bouton-danger">Supprimer<span class="sr-only"> <?= e($j['pseudo']) ?></span></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
