<?php

use App\Services\Apparence;

use function App\Core\date_fr;
use function App\Core\e;
use function App\Core\etoiles;

/** Lien de pagination qui conserve les filtres. */
$lienPage = static fn (int $n): string => '/personnages?' . http_build_query(array_filter($filtres) + ['page' => $n]);
?>
<header class="titre-page">
    <h1>Les personnages</h1>
    <p class="intro">Les créations partagées par la communauté de FantasyRealm Online.</p>
</header>

<form method="get" action="/personnages" class="filtres carte" role="search" aria-label="Filtrer les personnages">
    <div>
        <label for="genre">Genre</label>
        <select id="genre" name="genre">
            <option value="">Tous</option>
            <?php foreach (Apparence::GENRES as $code => $libelle): ?>
                <option value="<?= e($code) ?>"<?= $filtres['genre'] === $code ? ' selected' : '' ?>><?= e($libelle) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="du">Créé à partir du</label>
        <input type="date" id="du" name="du" value="<?= e($filtres['du']) ?>">
    </div>
    <div>
        <label for="au">Jusqu'au</label>
        <input type="date" id="au" name="au" value="<?= e($filtres['au']) ?>">
    </div>
    <div>
        <label for="pseudo">Pseudo du créateur</label>
        <input type="search" id="pseudo" name="pseudo" maxlength="30" value="<?= e($filtres['pseudo']) ?>">
    </div>
    <div class="filtres-actions">
        <button type="submit" class="bouton">Filtrer</button>
        <a href="/personnages" class="lien-discret">Réinitialiser</a>
    </div>
</form>

<p role="status"><?= $total ?> personnage<?= $total > 1 ? 's' : '' ?> trouvé<?= $total > 1 ? 's' : '' ?></p>

<?php if ($personnages === []): ?>
    <p class="carte">Aucun personnage ne correspond à ces critères.</p>
<?php else: ?>
    <ul class="galerie">
        <?php foreach ($personnages as $p): ?>
            <li class="carte-perso">
                <a href="/personnage?id=<?= (int) $p['id'] ?>">
                    <img src="/avatar?id=<?= (int) $p['id'] ?>" alt="" width="200" height="260" loading="lazy">
                    <span class="nom-perso"><?= e($p['nom']) ?></span>
                </a>
                <span class="petit">par <strong><?= e($p['pseudo']) ?></strong> · <?= e(Apparence::GENRES[$p['genre']]) ?></span>
                <span class="petit">créé le <?= e(date_fr($p['cree_le'])) ?></span>
                <?php if ($p['note_moyenne'] !== null): ?><span><?= etoiles((float) $p['note_moyenne']) ?></span><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <ul>
                <?php for ($n = 1; $n <= $pages; $n++): ?>
                    <li><a href="<?= e($lienPage($n)) ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>>
                        <span class="sr-only">Page </span><?= $n ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
