<?php

namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailerService
{
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPassword;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        // Récupération depuis les variables d'environnement
        $this->smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $this->smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
        $this->smtpUser = getenv('SMTP_USER') ?: '';
        $this->smtpPassword = getenv('SMTP_PASSWORD') ?: '';
        $this->fromEmail = getenv('MAIL_FROM') ?: 'noreply@ecoride.fr';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'EcoRide';
    }

    /**
     * Envoie un email de contact
     * 
     * @param array $data Données du formulaire (name, email, subject, message)
     * @return bool True si envoyé, False sinon
     */
    public function sendContactEmail(array $data): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';

            // Expéditeur et destinataire
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress(getenv('CONTACT_EMAIL') ?: 'contact@ecoride.fr');
            $mail->addReplyTo($data['email'], $data['name']);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = 'EcoRide - ' . ($data['subject'] ?? 'Nouveau message de contact');
            $mail->Body = $this->getEmailTemplate($data);
            $mail->AltBody = $this->getPlainTextEmail($data);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erreur d'envoi email: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Template HTML de l'email
     */
    private function getEmailTemplate(array $data): string
    {
        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
                    .field { margin-bottom: 15px; padding: 10px; background: white; border-radius: 3px; }
                    .label { font-weight: bold; color: #4CAF50; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2 style='margin: 0;'>🚗 Nouveau message de contact EcoRide</h2>
                    </div>
                    <div class='content'>
                        <div class='field'>
                            <span class='label'>👤 Nom :</span> " . htmlspecialchars($data['name']) . "
                        </div>
                        <div class='field'>
                            <span class='label'>📧 Email :</span> " . htmlspecialchars($data['email']) . "
                        </div>
                        <div class='field'>
                            <span class='label'>📝 Sujet :</span> " . htmlspecialchars($data['subject'] ?? 'Non renseigné') . "
                        </div>
                        <div class='field'>
                            <span class='label'>💬 Message :</span><br><br>
                            <p style='margin: 0; padding: 10px; background: #fff; border-left: 3px solid #4CAF50;'>" . nl2br(htmlspecialchars($data['message'])) . "</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Version texte brut de l'email
     */
    private function getPlainTextEmail(array $data): string
    {
        return "=== Nouveau message de contact EcoRide ===\n\n" .
            "Nom: {$data['name']}\n" .
            "Email: {$data['email']}\n" .
            "Sujet: " . ($data['subject'] ?? 'Non renseigné') . "\n\n" .
            "Message:\n" .
            "----------------------------------------\n" .
            "{$data['message']}\n" .
            "----------------------------------------\n";
    }
}
