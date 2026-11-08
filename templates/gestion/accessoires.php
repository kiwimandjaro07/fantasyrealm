<?php

use App\Core\Csrf;
use App\Services\Apparence;

use function App\Core\aria_erreur;
use function App\Core\e;
use function App\Core\message_erreur;

?>
<h1>Bibliothèque d'articles</h1>
<?php require __DIR__ . '/_menu.php'; ?>

<div class="deux-colonnes">
    <section aria-labelledby="titre-liste" class="colonne-large">
        <h2 id="titre-liste">Articles (<?= count($accessoires) ?>)</h2>
        <div class="tableau-defilant">
            <table class="tableau">
                <thead><tr><th scope="col">Article</th><th scope="col">Catégorie</th><th scope="col">Portés</th><th scope="col">État</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($accessoires as $a): ?>
                    <tr<?= $a['actif'] ? '' : ' class="inactif"' ?>>
                        <td><span class="pastille" data-couleur="<?= e($a['couleur']) ?>" aria-hidden="true"></span>
                            <strong><?= e($a['nom']) ?></strong><br><span class="petit"><?= e($a['description']) ?></span></td>
                        <td><?= e(Apparence::CATEGORIES[$a['categorie']]) ?><br>
                            <span class="petit"><?= e(Apparence::VISUELS[$a['categorie']][$a['visuel']] ?? $a['visuel']) ?></span></td>
                        <td><?= (int) $a['nb_utilisations'] ?></td>
                        <td><?= $a['actif'] ? 'Proposé' : 'Désactivé' ?></td>
                        <td class="actions-tableau">
                            <form method="post" action="/gestion/accessoires/etat">
                                <?= Csrf::champ() ?>
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <input type="hidden" name="actif" value="<?= $a['actif'] ? '0' : '1' ?>">
                                <button type="submit" class="bouton bouton-petit bouton-secondaire">
                                    <?= $a['actif'] ? 'Désactiver' : 'Réactiver' ?><span class="sr-only"> <?= e($a['nom']) ?></span></button>
                            </form>
                            <form method="post" action="/gestion/accessoires/supprimer"
                                  data-confirmation="Supprimer « <?= e($a['nom']) ?> » ? Il sera retiré des <?= (int) $a['nb_utilisations'] ?> personnage(s) qui le portent.">
                                <?= Csrf::champ() ?>
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button type="submit" class="bouton bouton-petit bouton-danger">Supprimer<span class="sr-only"> <?= e($a['nom']) ?></span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section id="ajout" aria-labelledby="titre-ajout" class="carte">
        <h2 id="titre-ajout">Ajouter un article</h2>
        <form method="post" action="/gestion/accessoires" class="formulaire" id="form-article">
            <?= Csrf::champ() ?>
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" required minlength="2" maxlength="60" value="<?= e($old['nom'] ?? '') ?>"<?= aria_erreur($erreurs, 'nom') ?>>
            <?= message_erreur($erreurs, 'nom') ?>

            <label for="visuel">Catégorie et forme</label>
            <select id="visuel" name="visuel" required<?= aria_erreur($erreurs, 'visuel') ?>>
                <?php foreach (Apparence::VISUELS as $cat => $visuels): ?>
                    <optgroup label="<?= e(Apparence::CATEGORIES[$cat]) ?>">
                        <?php foreach ($visuels as $v => $lib): ?>
                            <option value="<?= e($v) ?>" data-categorie="<?= e($cat) ?>"<?= ($old['visuel'] ?? '') === $v ? ' selected' : '' ?>><?= e($lib) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <?= message_erreur($erreurs, 'visuel') ?>

            <label for="couleur">Couleur</label>
            <input type="color" id="couleur" name="couleur" value="<?= e($old['couleur'] ?? '#d4a63a') ?>"<?= aria_erreur($erreurs, 'couleur') ?>>
            <?= message_erreur($erreurs, 'couleur') ?>

            <label for="description">Description <span class="aide">(facultatif)</span></label>
            <input type="text" id="description" name="description" maxlength="255" value="<?= e($old['description'] ?? '') ?>">

            <button type="submit" class="bouton">Ajouter</button>
        </form>
    </section>
</div>
