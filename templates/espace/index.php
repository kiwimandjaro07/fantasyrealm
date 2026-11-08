<?php

use App\Core\Auth;
use App\Core\Csrf;

use function App\Core\date_fr;
use function App\Core\e;

?>
<header class="titre-page titre-avec-action">
    <div>
        <h1>Mon espace</h1>
        <p class="intro">Bonjour <?= e(Auth::utilisateur()['pseudo']) ?>, voici vos personnages.</p>
    </div>
    <a class="bouton" href="/espace/creer">Créer un personnage</a>
</header>

<?php if ($personnages === []): ?>
    <section class="carte centre">
        <h2>Aucun personnage pour l'instant</h2>
        <p>Créez votre premier héros : choisissez son nom, son genre et les traits de son visage.</p>
        <p><a class="bouton" href="/espace/creer">Créer mon premier personnage</a></p>
    </section>
<?php else: ?>
    <ul class="mes-personnages">
        <?php foreach ($personnages as $p): ?>
            <li class="carte perso-ligne">
                <img src="/avatar?id=<?= (int) $p['id'] ?>" alt="" width="120" height="156">
                <div class="perso-infos">
                    <h2><?= e($p['nom']) ?></h2>
                    <p>
                        <?php if ($p['statut'] === 'en_attente'): ?>
                            <span class="badge badge-attente">Nom en attente de validation</span>
                        <?php elseif ($p['partage']): ?>
                            <span class="badge badge-ok">Partagé</span>
                        <?php else: ?>
                            <span class="badge">Privé</span>
                        <?php endif; ?>
                        <span class="petit">Créé le <?= e(date_fr($p['cree_le'])) ?> · <?= (int) $p['nb_avis'] ?> avis</span>
                    </p>

                    <div class="actions">
                        <a class="bouton bouton-petit" href="/espace/personnage?id=<?= (int) $p['id'] ?>">
                            Modifier<span class="sr-only"> <?= e($p['nom']) ?></span></a>

                        <?php if ($p['statut'] === 'valide'): ?>
                            <form method="post" action="/espace/partager" class="en-ligne">
                                <?= Csrf::champ() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="bouton bouton-petit bouton-secondaire">
                                    <?= $p['partage'] ? 'Arrêt du partage' : 'Partager' ?><span class="sr-only"> <?= e($p['nom']) ?></span>
                                </button>
                            </form>
                            <?php if ($p['partage']): ?>
                                <a class="bouton bouton-petit bouton-secondaire" href="/personnage?id=<?= (int) $p['id'] ?>">
                                    Voir dans la galerie<span class="sr-only"> : <?= e($p['nom']) ?></span></a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <form method="post" action="/espace/supprimer" class="en-ligne" data-confirmation="Supprimer définitivement « <?= e($p['nom']) ?> » ?">
                            <?= Csrf::champ() ?>
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button type="submit" class="bouton bouton-petit bouton-danger">Supprimer<span class="sr-only"> <?= e($p['nom']) ?></span></button>
                        </form>
                    </div>

                    <details class="dupliquer">
                        <summary>Dupliquer<span class="sr-only"> <?= e($p['nom']) ?></span></summary>
                        <form method="post" action="/espace/dupliquer" class="formulaire formulaire-ligne">
                            <?= Csrf::champ() ?>
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <label for="copie-<?= (int) $p['id'] ?>">Nom de la copie <span class="aide">(unique)</span></label>
                            <input type="text" id="copie-<?= (int) $p['id'] ?>" name="nouveau_nom" required minlength="2" maxlength="40">
                            <button type="submit" class="bouton bouton-petit">Dupliquer</button>
                        </form>
                        <p class="petit">La copie garde l'apparence et l'équipement ; son nom sera validé par notre équipe.</p>
                    </details>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<section class="section compte" aria-labelledby="titre-compte">
    <h2 id="titre-compte">Mon compte</h2>
    <p><a href="/espace/mot-de-passe">Changer mon mot de passe</a></p>
    <details>
        <summary>Supprimer mon compte</summary>
        <form method="post" action="/espace/supprimer-compte" class="formulaire bloc-etroit"
              data-confirmation="Supprimer votre compte et tous vos personnages ? Cette action est définitive.">
            <?= Csrf::champ() ?>
            <p>Votre compte, vos personnages et vos avis seront supprimés définitivement.</p>
            <label for="mdp-suppression">Confirmez avec votre mot de passe</label>
            <input type="password" id="mdp-suppression" name="mot_de_passe" required autocomplete="current-password">
            <button type="submit" class="bouton bouton-danger">Supprimer mon compte</button>
        </form>
    </details>
</section>
