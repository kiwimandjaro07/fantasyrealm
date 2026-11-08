<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

use function App\Core\e;

/**
 * Envoi des e-mails avec PHPMailer (SMTP). En local, Mailpit les intercepte : http://localhost:8026.
 */
final class MailService
{
    public static function envoyer(string $destinataire, string $sujet, string $html, ?string $repondreA = null): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? 'localhost';
            $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 1025);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            if (!empty($_ENV['MAIL_USER'])) {
                // Production : authentification et chiffrement TLS
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['MAIL_USER'];
                $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAuth = false;
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom($_ENV['MAIL_FROM'] ?? 'no-reply@fantasyrealm.local', 'FantasyRealm Online');
            $mail->addAddress($destinataire);
            if ($repondreA !== null) {
                $mail->addReplyTo($repondreA); // PHPMailer refuse les adresses invalides (pas d'injection d'en-tête)
            }
            $mail->isHTML(true);
            $mail->Subject = $sujet;
            $mail->Body = self::gabarit($sujet, $html);
            $mail->AltBody = html_entity_decode(strip_tags(str_replace(['<br>', '</p>'], "\n", $html)), ENT_QUOTES, 'UTF-8');

            return $mail->send();
        } catch (MailException) {
            error_log('[MailService] ' . $mail->ErrorInfo);

            return false;
        }
    }

    private static function gabarit(string $titre, string $contenu): string
    {
        return '<div style="font-family:Georgia,serif;max-width:600px;margin:auto;background:#f5ecd7;color:#1c1430;padding:24px;border-top:6px solid #d4a63a">'
            . '<h1 style="color:#3b2a66;font-size:22px">' . e($titre) . '</h1>'
            . '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.5">' . $contenu . '</div>'
            . '<p style="font-family:Arial,sans-serif;font-size:12px;color:#5a4f6e;margin-top:24px">FantasyRealm Online - PixelVerse Studios</p></div>';
    }

    public static function lien(string $chemin): string
    {
        return rtrim($_ENV['APP_URL'] ?? '', '/') . $chemin;
    }

    // ----- Messages de l'application (toute donnée variable est échappée avec e()) -----

    public static function nomValide(array $p): bool
    {
        return self::envoyer($p['email'], 'Votre personnage est validé', '<p>Bonjour ' . e($p['pseudo']) . ',</p>'
            . '<p>Le nom de votre personnage <strong>' . e($p['nom']) . '</strong> a été validé. Vous pouvez maintenant '
            . 'l\'équiper et le partager avec la communauté.</p><p><a href="' . e(self::lien('/espace')) . '">Accéder à mon espace</a></p>');
    }

    public static function nomRejete(array $p, string $motif): bool
    {
        return self::envoyer($p['email'], 'Votre personnage n\'a pas été accepté', '<p>Bonjour ' . e($p['pseudo']) . ',</p>'
            . '<p>Le nom de votre personnage <strong>' . e($p['nom']) . '</strong> n\'a pas été accepté et le personnage a été supprimé.</p>'
            . '<p><strong>Motif :</strong> ' . nl2br(e($motif)) . '</p><p>Vous pouvez créer un nouveau personnage depuis votre espace.</p>');
    }

    public static function nouvelAvis(array $a): bool
    {
        return self::envoyer($a['email'], 'Nouvel avis sur ' . $a['personnage'], '<p>Bonjour ' . e($a['proprietaire']) . ',</p>'
            . '<p>' . e($a['auteur']) . ' a donné la note de ' . (int) $a['note'] . '/5 à votre personnage <strong>'
            . e($a['personnage']) . '</strong>.</p><p><a href="' . e(self::lien('/personnage?id=' . (int) $a['personnage_id']))
            . '">Lire l\'avis</a></p>');
    }

    public static function motDePasseProvisoire(array $u, string $mdp): bool
    {
        return self::envoyer($u['email'], 'Votre nouveau mot de passe', '<p>Bonjour ' . e($u['pseudo']) . ',</p>'
            . '<p>Voici votre mot de passe provisoire : <strong style="font-family:monospace;font-size:18px">' . e($mdp) . '</strong></p>'
            . '<p>Il devra être changé dès votre prochaine connexion. Si vous n\'êtes pas à l\'origine de cette demande, '
            . 'connectez-vous avec ce mot de passe puis choisissez-en un nouveau, et prévenez le support.</p>'
            . '<p><a href="' . e(self::lien('/connexion')) . '">Se connecter</a></p>');
    }

    public static function compteEmploye(string $email, string $pseudo): bool
    {
        return self::envoyer($email, 'Votre compte employé FantasyRealm', '<p>Bonjour ' . e($pseudo) . ',</p>'
            . '<p>Un compte employé a été créé pour vous. L\'administrateur vous communiquera votre mot de passe de vive voix.</p>'
            . '<p><a href="' . e(self::lien('/connexion')) . '">Se connecter</a></p>');
    }
}
