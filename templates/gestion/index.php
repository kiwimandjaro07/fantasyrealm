<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Services\Apparence;

use function App\Core\date_fr;
use function App\Core\e;
use function App\Core\etoiles;

?>
<h1><?= Auth::utilisateur()['role'] === 'admin' ? 'Administration' : 'Espace employé' ?></h1>
<?php require __DIR__ . '/_menu.php'; ?>

<section aria-labelledby="titre-noms" class="section">
    <h2 id="titre-noms">Noms de personnages à valider (<?= count($personnages) ?>)</h2>
    <?php if ($personnages === []): ?>
        <p>Aucun nom en attente.</p>
    <?php else: ?>
        <ul class="liste-moderation">
            <?php foreach ($personnages as $p): ?>
                <li class="carte moderation" id="personnage-<?= (int) $p['id'] ?>">
                    <img src="/avatar?id=<?= (int) $p['id'] ?>" alt="<?= e(Apparence::description($p)) ?>" width="100" height="130">
                    <div>
                        <h3><?= e($p['nom']) ?></h3>
                        <p class="petit">Proposé par <strong><?= e($p['pseudo']) ?></strong>
                            le <?= e(date_fr($p['modifie_le'] ?? $p['cree_le'], true)) ?><?= $p['modifie_le'] ? ' (nom modifié)' : '' ?></p>
                        <form method="post" action="/gestion/personnage/valider" class="en-ligne">
                            <?= Csrf::champ() ?>
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button type="submit" class="bouton bouton-petit">Approuver<span class="sr-only"> le nom <?= e($p['nom']) ?></span></button>
                        </form>
                        <details>
                            <summary>Rejeter<span class="sr-only"> le nom <?= e($p['nom']) ?></span></summary>
                            <form method="post" action="/gestion/personnage/rejeter" class="formulaire"
                                  data-confirmation="Rejeter « <?= e($p['nom']) ?> » ? Le personnage sera supprimé définitivement.">
                                <?= Csrf::champ() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <label for="motif-<?= (int) $p['id'] ?>">Motif du rejet <span class="aide">(obligatoire, envoyé au joueur)</span></label>
                                <textarea id="motif-<?= (int) $p['id'] ?>" name="motif" rows="2" required minlength="5" maxlength="500"></textarea>
                                <button type="submit" class="bouton bouton-petit bouton-danger">Rejeter et supprimer</button>
                            </form>
                        </details>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section id="avis" aria-labelledby="titre-avis" class="section">
    <h2 id="titre-avis">Avis à modérer (<?= count($avis) ?>)</h2>
    <?php if ($avis === []): ?>
        <p>Aucun avis en attente.</p>
    <?php else: ?>
        <ul class="liste-moderation">
            <?php foreach ($avis as $a): ?>
                <li class="carte">
                    <p><?= etoiles((int) $a['note']) ?> par <strong><?= e($a['pseudo']) ?></strong> sur
                        <a href="/personnage?id=<?= (int) $a['personnage_id'] ?>"><?= e($a['personnage']) ?></a>
                        <span class="petit">le <?= e(date_fr($a['cree_le'], true)) ?></span></p>
                    <blockquote><?= nl2br(e($a['commentaire'])) ?></blockquote>
                    <form method="post" action="/gestion/avis" class="actions">
                        <?= Csrf::champ() ?>
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button type="submit" name="decision" value="valider" class="bouton bouton-petit">Approuver<span class="sr-only"> l'avis de <?= e($a['pseudo']) ?></span></button>
                        <button type="submit" name="decision" value="refuser" class="bouton bouton-petit bouton-danger">Refuser<span class="sr-only"> l'avis de <?= e($a['pseudo']) ?></span></button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
