<?php

use App\Core\Csrf;
use App\Services\Apparence;

use function App\Core\aria_erreur;
use function App\Core\date_fr;
use function App\Core\e;
use function App\Core\etoiles;
use function App\Core\message_erreur;

?>
<p><a href="/personnages" class="lien-discret">&larr; Tous les personnages</a></p>

<div class="fiche">
    <figure class="fiche-avatar">
        <img src="/avatar?id=<?= (int) $p['id'] ?>" alt="<?= e($p['nom'] . ' : ' . Apparence::description($p)) ?>" width="300" height="390" class="anime">
    </figure>

    <section class="fiche-infos" aria-labelledby="titre-perso">
        <h1 id="titre-perso"><?= e($p['nom']) ?></h1>
        <p>Créé par <strong><?= e($p['pseudo']) ?></strong> le <?= e(date_fr($p['cree_le'])) ?>
            <?php if ($p['note_moyenne'] !== null): ?> · <?= etoiles((float) $p['note_moyenne']) ?> (<?= count($avis) ?> avis)<?php endif; ?></p>
        <?php if (!($p['statut'] === 'valide' && $p['partage'])): ?>
            <p class="flash flash-info">Ce personnage n'est pas visible dans la galerie (non partagé ou en attente de validation).</p>
        <?php endif; ?>

        <h2>Apparence</h2>
        <dl class="caracteristiques">
            <dt>Genre</dt><dd><?= e(Apparence::GENRES[$p['genre']]) ?></dd>
            <dt>Visage</dt><dd><?= e(Apparence::FORMES['forme_visage'][$p['forme_visage']]) ?></dd>
            <dt>Yeux</dt><dd><?= e(Apparence::FORMES['forme_yeux'][$p['forme_yeux']]) ?>,
                <?= e(mb_strtolower(Apparence::PALETTES['couleur_yeux'][$p['couleur_yeux']])) ?></dd>
            <dt>Nez</dt><dd><?= e(Apparence::FORMES['forme_nez'][$p['forme_nez']]) ?></dd>
            <dt>Bouche</dt><dd><?= e(Apparence::FORMES['forme_bouche'][$p['forme_bouche']]) ?></dd>
            <dt>Coiffure</dt><dd><?= e(Apparence::FORMES['coiffure'][$p['coiffure']]) ?>,
                <?= e(mb_strtolower(Apparence::PALETTES['couleur_cheveux'][$p['couleur_cheveux']])) ?></dd>
            <dt>Peau</dt><dd><?= e(Apparence::PALETTES['couleur_peau'][$p['couleur_peau']]) ?></dd>
        </dl>

        <h2>Équipement</h2>
        <?php if ($equipement === []): ?>
            <p>Aucun équipement.</p>
        <?php else: ?>
            <ul class="equipement">
                <?php foreach ($equipement as $a): ?>
                    <li><span class="pastille" data-couleur="<?= e($a['couleur']) ?>" aria-hidden="true"></span>
                        <strong><?= e(Apparence::CATEGORIES[$a['categorie']]) ?> :</strong> <?= e($a['nom']) ?>
                        <?php if ($a['description'] !== ''): ?><span class="petit"> - <?= e($a['description']) ?></span><?php endif; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<section id="avis" aria-labelledby="titre-avis" class="section">
    <h2 id="titre-avis">Avis (<?= count($avis) ?>)</h2>
    <?php if ($avis === []): ?>
        <p>Aucun avis pour le moment.</p>
    <?php else: ?>
        <ul class="liste-avis">
            <?php foreach ($avis as $a): ?>
                <li class="carte">
                    <p><?= etoiles((int) $a['note']) ?> <strong><?= e($a['pseudo']) ?></strong>
                        <span class="petit">le <?= e(date_fr($a['cree_le'])) ?></span></p>
                    <p><?= nl2br(e($a['commentaire'])) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($peutNoter && $monAvis === null): ?>
        <form method="post" action="/personnage/avis" class="formulaire carte bloc-moyen">
            <h3>Donner mon avis</h3>
            <?= Csrf::champ() ?>
            <input type="hidden" name="personnage_id" value="<?= (int) $p['id'] ?>">
            <fieldset class="notation"<?= aria_erreur($erreurs, 'note') ?>>
                <legend>Note</legend>
                <?php for ($n = 1; $n <= 5; $n++): ?>
                    <input type="radio" id="note-<?= $n ?>" name="note" value="<?= $n ?>" required<?= (int) ($old['note'] ?? 0) === $n ? ' checked' : '' ?>>
                    <label for="note-<?= $n ?>"><?= $n ?><span class="sr-only"> sur 5</span></label>
                <?php endfor; ?>
            </fieldset>
            <?= message_erreur($erreurs, 'note') ?>
            <label for="commentaire">Commentaire</label>
            <textarea id="commentaire" name="commentaire" rows="4" required minlength="5" maxlength="1000"<?= aria_erreur($erreurs, 'commentaire') ?>><?= e($old['commentaire'] ?? '') ?></textarea>
            <?= message_erreur($erreurs, 'commentaire') ?>
            <button type="submit" class="bouton">Publier</button>
            <p class="petit">Votre avis sera visible après validation par notre équipe.</p>
        </form>
    <?php elseif ($monAvis !== null): ?>
        <p class="flash flash-info">Vous avez déjà donné votre avis sur ce personnage<?= $monAvis['statut'] === 'en_attente' ? ' : il est en attente de validation' : '' ?>.</p>
    <?php elseif (!$connecte): ?>
        <p><a href="/connexion">Connectez-vous</a> pour donner votre avis.</p>
    <?php endif; ?>
</section>
