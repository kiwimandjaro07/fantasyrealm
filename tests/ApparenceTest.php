<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Validator;
use App\Services\Apparence;
use App\Services\MotDePasse;
use PHPUnit\Framework\TestCase;

final class ApparenceTest extends TestCase
{
    public function testLesValeursParDefautSontValides(): void
    {
        [$traits, $erreurs] = Apparence::valider(Apparence::DEFAUT);
        self::assertSame([], $erreurs);
        self::assertSame(Apparence::DEFAUT['coiffure'], $traits['coiffure']);
    }

    public function testValeursHorsListeRefusees(): void
    {
        $saisie = ['forme_visage' => 'triangle', 'couleur_peau' => '#123456', 'genre' => ['feminin']] + Apparence::DEFAUT;
        [$traits, $erreurs] = Apparence::valider($saisie);
        self::assertArrayHasKey('forme_visage', $erreurs);
        self::assertArrayHasKey('couleur_peau', $erreurs);     // couleur valide mais absente de la palette
        self::assertArrayHasKey('genre', $erreurs);            // un tableau au lieu d'une chaîne
        self::assertArrayNotHasKey('forme_visage', $traits);
    }

    public function testCouleurEnMajusculesAcceptee(): void
    {
        [$traits, $erreurs] = Apparence::valider(['couleur_yeux' => '#3F7D3A'] + Apparence::DEFAUT);
        self::assertSame([], $erreurs);
        self::assertSame('#3f7d3a', $traits['couleur_yeux']);
    }

    public function testLeSvgEstValideEtLeTitreEchappe(): void
    {
        $svg = Apparence::svg(Apparence::DEFAUT, [], 'Nom <script>alert(1)</script> & co');
        self::assertStringContainsString('&lt;script&gt;', $svg);
        self::assertStringNotContainsString('<script>', $svg);

        $doc = new \DOMDocument();
        self::assertTrue($doc->loadXML($svg), 'Le SVG produit doit être un XML bien formé');
    }

    public function testUneCouleurMalveillanteNEntrePasDansLeSvg(): void
    {
        $equipement = [['categorie' => 'arme', 'visuel' => 'epee', 'couleur' => '"/><script>alert(1)</script>']];
        $svg = Apparence::svg(Apparence::DEFAUT, $equipement);
        self::assertStringNotContainsString('<script', $svg);
        self::assertStringContainsString('#888888', $svg); // remplacée par la couleur de secours
    }

    public function testTousLesEquipementsSeDessinent(): void
    {
        foreach (Apparence::VISUELS as $categorie => $visuels) {
            foreach (array_keys($visuels) as $visuel) {
                $svg = Apparence::svg(Apparence::DEFAUT, [['categorie' => $categorie, 'visuel' => $visuel, 'couleur' => '#d4a63a']]);
                self::assertTrue((new \DOMDocument())->loadXML($svg), "Rendu invalide pour $visuel");
            }
        }
    }

    public function testAssombrir(): void
    {
        self::assertSame('#000000', Apparence::assombrir('#ffffff', 1.0));
        self::assertSame('#808080', Apparence::assombrir('#ffffff', 0.4980));
    }

    public function testMotDePasseProvisoireConforme(): void
    {
        $vus = [];
        for ($i = 0; $i < 50; $i++) {
            $mdp = MotDePasse::generer();
            self::assertSame(16, strlen($mdp));
            self::assertTrue(Validator::motDePasse($mdp), "Mot de passe généré non conforme : $mdp");
            $vus[$mdp] = true;
        }
        self::assertCount(50, $vus, 'Les mots de passe générés doivent être tous différents');
    }
}
