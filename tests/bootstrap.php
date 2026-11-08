<?php

declare(strict_types=1);

// Les tests unitaires ne touchent ni à MySQL ni à MongoDB : seules les classes "pures" sont testées.
require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('Europe/Paris');
