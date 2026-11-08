<?php

use function App\Core\e;

?>
<section class="hero">
    <div class="hero-texte">
        <p class="surtitre">FantasyRealm Online</p>
        <h1>Donnez vie au héros <span class="or">qui vous ressemble</span></h1>
        <p class="intro">Le nouveau système de gestion de personnages vous permet de créer, personnaliser, équiper et partager
            vos avatars, et de les voir en action avant de valider vos choix.</p>
        <p class="actions">
            <a class="bouton" href="/inscription">Créer mon personnage</a>
            <a class="bouton bouton-secondaire" href="/personnages">Voir la galerie</a>
        </p>
    </div>
    <?php if ($derniers !== []): ?>
        <div class="hero-avatars" aria-hidden="true">
            <?php foreach ($derniers as $p): ?>
                <img src="/avatar?id=<?= (int) $p['id'] ?>" alt="" width="140" height="182">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section aria-labelledby="titre-studio" class="section">
    <h2 id="titre-studio">PixelVerse Studios</h2>
    <p>Studio indépendant de développement de jeux vidéo, PixelVerse Studios est reconnu pour ses jeux de rôle en ligne
        massivement multijoueurs. Notre ambition : offrir aux joueurs des mondes riches dans lesquels chacun écrit sa propre
        légende. Avec plus de <?= (int) $chiffres['joueurs'] ?> aventuriers inscrits sur ce portail, la communauté
        de FantasyRealm ne cesse de grandir.</p>
</section>

<section aria-labelledby="titre-avantages" class="section">
    <h2 id="titre-avantages">Pourquoi gérer vos personnages ici ?</h2>
    <ul class="cartes">
        <li class="carte">
            <h3>Une personnalisation poussée</h3>
            <p>Forme du visage, des yeux, du nez et de la bouche, coiffure, couleurs de peau, de cheveux et d'yeux : composez un héros unique.</p>
        </li>
        <li class="carte">
            <h3>Un équipement complet</h3>
            <p><?= (int) $chiffres['articles'] ?> articles disponibles : vêtements, armures, armes et accessoires, enrichis régulièrement par notre équipe.</p>
        </li>
        <li class="carte">
            <h3>L'aperçu en direct</h3>
            <p>Visualisez votre personnage en action à chaque changement, avant de confirmer vos choix.</p>
        </li>
        <li class="carte">
            <h3>Une communauté</h3>
            <p>Partagez vos créations dans la galerie (<?= (int) $chiffres['partages'] ?> personnages partagés), recevez des avis et inspirez-vous des autres joueurs.</p>
        </li>
    </ul>
</section>

<section aria-labelledby="titre-jeu" class="section section-jeu">
    <h2 id="titre-jeu">FantasyRealm Online</h2>
    <p>Le MMORPG de PixelVerse Studios vous plonge dans un monde fantastique où elfes, nains, mages et guerriers combattent
        des monstres et accomplissent des quêtes épiques. Les personnages créés sur ce portail vous accompagnent dans
        l'aventure : leur apparence et leur équipement sont ceux que vous avez choisis ici.</p>
    <?php if ($derniers !== []): ?>
        <h3>Dernières créations partagées</h3>
        <ul class="galerie galerie-mini">
            <?php foreach ($derniers as $p): ?>
                <li class="carte-perso">
                    <a href="/personnage?id=<?= (int) $p['id'] ?>">
                        <img src="/avatar?id=<?= (int) $p['id'] ?>" alt="" width="200" height="260" loading="lazy">
                        <span class="nom-perso"><?= e($p['nom']) ?></span>
                    </a>
                    <span class="petit">par <?= e($p['pseudo']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
