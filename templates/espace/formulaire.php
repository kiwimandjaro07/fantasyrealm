<?php

use App\Core\Csrf;
use App\Services\Apparence;

use function App\Core\aria_erreur;
use function App\Core\e;
use function App\Core\message_erreur;

$creation = $mode === 'creation';
$id = $creation ? 0 : (int) $personnage['id'];
// Paramètres de l'aperçu initial (mêmes noms que les champs du formulaire)
$traitsApercu = array_intersect_key($p, array_flip(['genre', ...array_keys(Apparence::FORMES), ...array_keys(Apparence::PALETTES)]));
foreach ($equipement ?? [] as $cat => $a) {
    $traitsApercu[$cat] = $a['id'];
}
?>
<p><a href="/espace" class="lien-discret">&larr; Mon espace</a></p>

<header class="titre-page">
    <h1><?= $creation ? 'Créer un personnage' : 'Modifier « ' . e($personnage['nom']) . ' »' ?></h1>
    <?php if (!$creation && $personnage['statut'] === 'en_attente'): ?>
        <p class="flash flash-info">Le nom de ce personnage est en attente de validation : vous pourrez l'équiper et le partager une fois validé.</p>
    <?php endif; ?>
</header>

<div class="atelier">
    <!-- Aperçu en direct : l'image est régénérée par le serveur à chaque changement (assets/js/app.js) -->
    <figure class="atelier-apercu" aria-live="polite">
        <img id="apercu" src="/apercu?<?= e(http_build_query($traitsApercu)) ?>" alt="Aperçu du personnage" width="300" height="390" class="anime">
        <figcaption>
            <button type="button" class="bouton bouton-petit bouton-secondaire" id="bouton-action" hidden aria-pressed="true">Mettre en pause l'animation</button>
        </figcaption>
    </figure>

    <div class="atelier-formulaires">
        <form method="post" action="<?= $creation ? '/espace/creer' : '/espace/personnage' ?>" class="formulaire carte" id="form-apparence">
            <?= Csrf::champ() ?>
            <?php if (!$creation): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

            <label for="nom">Nom du personnage <span class="aide">(unique, validé par notre équipe)</span></label>
            <input type="text" id="nom" name="nom" required minlength="2" maxlength="40" value="<?= e($p['nom'] ?? '') ?>"<?= aria_erreur($erreurs, 'nom') ?>>
            <?= message_erreur($erreurs, 'nom') ?>

            <fieldset>
                <legend>Genre</legend>
                <div class="choix-ligne">
                    <?php foreach (Apparence::GENRES as $code => $libelle): ?>
                        <span class="case">
                            <input type="radio" id="genre-<?= e($code) ?>" name="genre" value="<?= e($code) ?>" data-apercu<?= $p['genre'] === $code ? ' checked' : '' ?> required>
                            <label for="genre-<?= e($code) ?>"><?= e($libelle) ?></label>
                        </span>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset>
                <legend>Traits du visage</legend>
                <div class="grille-champs">
                    <?php foreach (Apparence::FORMES as $champ => $options): ?>
                        <div>
                            <label for="<?= e($champ) ?>"><?= e(['forme_visage' => 'Visage', 'forme_yeux' => 'Yeux', 'forme_nez' => 'Nez',
                                    'forme_bouche' => 'Bouche', 'coiffure' => 'Coiffure'][$champ]) ?></label>
                            <select id="<?= e($champ) ?>" name="<?= e($champ) ?>" data-apercu<?= aria_erreur($erreurs, $champ) ?>>
                                <?php foreach ($options as $val => $lib): ?>
                                    <option value="<?= e($val) ?>"<?= $p[$champ] === $val ? ' selected' : '' ?>><?= e($lib) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <?php foreach (Apparence::PALETTES as $champ => $couleurs): ?>
                <fieldset class="palette">
                    <legend><?= e(['couleur_peau' => 'Couleur de peau', 'couleur_cheveux' => 'Couleur des cheveux', 'couleur_yeux' => 'Couleur des yeux'][$champ]) ?></legend>
                    <div class="nuancier">
                        <?php foreach ($couleurs as $hex => $nom): ?>
                            <span class="nuance">
                                <input type="radio" id="<?= e($champ . substr($hex, 1)) ?>" name="<?= e($champ) ?>" value="<?= e($hex) ?>"
                                       data-apercu<?= $p[$champ] === $hex ? ' checked' : '' ?> required>
                                <label for="<?= e($champ . substr($hex, 1)) ?>" title="<?= e($nom) ?>">
                                    <span class="pastille" data-couleur="<?= e($hex) ?>" aria-hidden="true"></span>
                                    <span class="sr-only"><?= e($nom) ?></span>
                                </label>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            <?php endforeach; ?>

            <button type="submit" class="bouton"><?= $creation ? 'Créer le personnage' : 'Enregistrer l\'apparence' ?></button>
            <?php if (!$creation): ?>
                <p class="petit">Changer le nom renvoie le personnage en validation et arrête son partage.</p>
            <?php endif; ?>
        </form>

        <?php if (!$creation): ?>
            <form method="post" action="/espace/equiper" class="formulaire carte" id="equipement">
                <h2>Équipement</h2>
                <?php if ($personnage['statut'] !== 'valide'): ?>
                    <p class="flash flash-info">Disponible une fois le nom validé.</p>
                <?php else: ?>
                    <?= Csrf::champ() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="grille-champs">
                        <?php foreach (Apparence::CATEGORIES as $cat => $libelle): ?>
                            <div>
                                <label for="eq-<?= e($cat) ?>"><?= e($libelle) ?></label>
                                <select id="eq-<?= e($cat) ?>" name="<?= e($cat) ?>" data-apercu>
                                    <option value="">Aucun</option>
                                    <?php foreach ($catalogue[$cat] ?? [] as $a): ?>
                                        <option value="<?= (int) $a['id'] ?>"<?= (int) ($equipement[$cat]['id'] ?? 0) === (int) $a['id'] ? ' selected' : '' ?>>
                                            <?= e($a['nom']) ?></option>
                                    <?php endforeach; ?>
                                    <?php if (isset($equipement[$cat]) && !$equipement[$cat]['actif']): ?>
                                        <option value="" disabled>(<?= e($equipement[$cat]['nom']) ?> : retiré de la boutique)</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="bouton">Enregistrer l'équipement</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>
