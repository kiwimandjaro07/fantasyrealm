<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Affichage des vues : une vue est insérée dans le gabarit commun templates/layout.php.
 */
class View
{
    public static function render(string $template, array $data = []): void
    {
        // Les clés du tableau deviennent des variables dans la vue ($title, ...)
        extract($data, EXTR_SKIP);

        ob_start();
        require ROOT . '/templates/' . $template . '.php';
        $content = ob_get_clean();

        require ROOT . '/templates/layout.php';
    }
}
