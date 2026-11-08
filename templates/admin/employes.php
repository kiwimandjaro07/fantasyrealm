<?php

use App\Core\Csrf;

use function App\Core\aria_erreur;
use function App\Core\date_fr;
use function App\Core\e;
use function App\Core\message_erreur;

?>
<h1>Comptes employés</h1>
<?php require dirname(__DIR__) . '/gestion/_menu.php'; ?>

<div class="deux-colonnes">
    <section aria-labelledby="titre-employes" class="colonne-large">
        <h2 id="titre-employes">Employés (<?= count($employes) ?>)</h2>
        <?php if ($employes === []): ?>
            <p>Aucun employé.</p>
        <?php else: ?>
            <ul class="liste-moderation">
                <?php foreach ($employes as $em): ?>
                    <li class="carte<?= $em['statut'] === 'suspendu' ? ' inactif' : '' ?>">
                        <h3><?= e($em['pseudo']) ?> <span class="petit">(<?= e($em['email']) ?>)</span></h3>
                        <p class="petit"><?= $em['statut'] === 'actif' ? 'Actif' : 'Suspendu' ?> · dernière connexion :
                            <?= $em['derniere_connexion'] ? e(date_fr($em['derniere_connexion'], true)) : 'jamais' ?></p>
                        <div class="actions">
                            <form method="post" action="/admin/employes/statut" class="en-ligne">
                                <?= Csrf::champ() ?>
                                <input type="hidden" name="id" value="<?= (int) $em['id'] ?>">
                                <input type="hidden" name="statut" value="<?= $em['statut'] === 'actif' ? 'suspendu' : 'actif' ?>">
                                <button type="submit" class="bouton bouton-petit bouton-secondaire">
                                    <?= $em['statut'] === 'actif' ? 'Suspendre' : 'Réactiver' ?><span class="sr-only"> <?= e($em['pseudo']) ?></span></button>
                            </form>
                            <form method="post" action="/admin/employes/supprimer" class="en-ligne" data-confirmation="Supprimer le compte de <?= e($em['pseudo']) ?> ?">
                                <?= Csrf::champ() ?>
                                <input type="hidden" name="id" value="<?= (int) $em['id'] ?>">
                                <button type="submit" class="bouton bouton-petit bouton-danger">Supprimer<span class="sr-only"> <?= e($em['pseudo']) ?></span></button>
                            </form>
                        </div>
                        <details>
                            <summary>Modifier le mot de passe<span class="sr-only"> de <?= e($em['pseudo']) ?></span></summary>
                            <form method="post" action="/admin/employes/mot-de-passe" class="formulaire formulaire-ligne">
                                <?= Csrf::champ() ?>
                                <input type="hidden" name="id" value="<?= (int) $em['id'] ?>">
                                <label for="mdp-<?= (int) $em['id'] ?>">Nouveau mot de passe</label>
                                <input type="password" id="mdp-<?= (int) $em['id'] ?>" name="mot_de_passe" required minlength="12" autocomplete="new-password">
                                <button type="submit" class="bouton bouton-petit">Enregistrer</button>
                            </form>
                        </details>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section aria-labelledby="titre-nouvel" class="carte">
        <h2 id="titre-nouvel">Créer un compte employé</h2>
        <form method="post" action="/admin/employes" class="formulaire">
            <?= Csrf::champ() ?>
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required maxlength="190" value="<?= e($old['email'] ?? '') ?>"<?= aria_erreur($erreurs, 'email') ?>>
            <?= message_erreur($erreurs, 'email') ?>
            <label for="mot_de_passe">Mot de passe</label>
            <p class="aide" id="regles-mdp">12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.</p>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="12" maxlength="128"
                   autocomplete="new-password" aria-describedby="regles-mdp"<?= isset($erreurs['mot_de_passe']) ? ' aria-invalid="true"' : '' ?>>
            <?= message_erreur($erreurs, 'mot_de_passe') ?>
            <button type="submit" class="bouton">Créer le compte</button>
        </form>
    </section>
</div>
