<?php

use function App\Core\e;

?>
<h1>Logs de personnalisation</h1>
<?php require dirname(__DIR__) . '/gestion/_menu.php'; ?>

<p>Historique des modifications (noms, apparence, équipement) et des actions de modération, lu dans la collection
    MongoDB <code>logs</code>. Les 100 événements les plus récents sont affichés.</p>

<?php if ($erreur): ?>
    <p class="flash flash-erreur" role="alert"><?= e($erreur) ?></p>
<?php else: ?>
    <form method="get" action="/admin/logs" class="filtres carte" role="search" aria-label="Filtrer les logs">
        <div>
            <label for="type">Type</label>
            <select id="type" name="type">
                <option value="">Tous</option>
                <?php foreach ($types as $code => $libelle): ?>
                    <option value="<?= e($code) ?>"<?= $filtres['type'] === $code ? ' selected' : '' ?>><?= e($libelle) ?> (<?= (int) ($stats[$code] ?? 0) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="personnage">Nom du personnage</label>
            <input type="search" id="personnage" name="personnage" value="<?= e($filtres['personnage']) ?>">
        </div>
        <div>
            <label for="pseudo">Pseudo de l'auteur</label>
            <input type="search" id="pseudo" name="pseudo" value="<?= e($filtres['pseudo']) ?>">
        </div>
        <div class="filtres-actions">
            <button type="submit" class="bouton">Filtrer</button>
            <a href="/admin/logs" class="lien-discret">Réinitialiser</a>
        </div>
    </form>

    <div class="tableau-defilant">
        <table class="tableau tableau-logs">
            <thead><tr><th scope="col">Date</th><th scope="col">Événement</th><th scope="col">Auteur</th><th scope="col">Personnage</th><th scope="col">Détails</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= e($l['cree_le']->format('d/m/Y H:i:s')) ?></td>
                    <td><?= e($types[$l['type']] ?? $l['type']) ?></td>
                    <td><?= e($l['acteur']['pseudo'] ?? '-') ?></td>
                    <td><?= e($l['personnage']['nom'] ?? '-') ?></td>
                    <td>
                        <?php foreach ($l['details'] as $cle => $valeur): ?>
                            <?php if (is_array($valeur)): ?>
                                <ul class="liste-details"><?php foreach ($valeur as $v): ?><li><?= e((string) $v) ?></li><?php endforeach; ?></ul>
                            <?php else: ?>
                                <span class="petit"><?= e((string) $cle) ?> :</span> <?= e(is_bool($valeur) ? ($valeur ? 'oui' : 'non') : (string) $valeur) ?><br>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($logs === []): ?>
                <tr><td colspan="5">Aucun événement.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
