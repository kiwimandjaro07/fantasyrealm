<?php

use App\Core\Auth;

$cheminGestion = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$liens = [
    '/gestion'              => 'À valider',
    '/gestion/accessoires'  => 'Articles',
    '/gestion/personnages'  => 'Personnages',
    '/gestion/joueurs'      => 'Joueurs',
];
if ((Auth::utilisateur()['role'] ?? '') === 'admin') {
    $liens['/admin/employes'] = 'Employés';
    $liens['/admin/logs'] = 'Logs';
}
?>
<nav class="menu-gestion" aria-label="Menu de gestion">
    <ul>
        <?php foreach ($liens as $href => $libelle): ?>
            <li><a href="<?= $href ?>"<?= $href === $cheminGestion ? ' aria-current="page"' : '' ?>><?= $libelle ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
