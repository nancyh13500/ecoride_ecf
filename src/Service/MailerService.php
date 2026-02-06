<?php

namespace Ecoride\Ecf\Service;

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
        $this->smtpHost = getenv('SMTP_HOST') ?: 'smtp.free.fr';
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
            $mail->addAddress(getenv('CONTACT_EMAIL') ?: 'contact@ecoride.lokia.fr');
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
                    .container { max-width: 600px; margin: 0 auto; padding: 10px; }
                    .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
                    .field { margin-bottom: 15px; padding: 10px; background: white; border-radius: 3px; }
                    .label { font-weight: bold; color: #4CAF50; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2 style='margin: 0;'>🚗 Nouveau message de contact Ecoride</h2>
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

/**
 * Envoie un email de confirmation de réservation de covoiturage
 * 
 * @param array $data Données de la réservation
 * @return bool True si envoyé, False sinon
 */
public function sendReservationConfirmation(array $data): bool
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
        $mail->addAddress($data['passenger_email'], $data['passenger_name']);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = 'Confirmation de réservation - EcoRide';
        $mail->Body = $this->getReservationEmailTemplate($data);
        $mail->AltBody = $this->getReservationPlainTextEmail($data);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi email réservation: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Template HTML de l'email de confirmation de réservation
 */
private function getReservationEmailTemplate(array $data): string
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
                .important { background-color: #FFF9C4; padding: 15px; border-left: 4px solid #FBC02D; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin: 0;'>🚗 Confirmation de réservation EcoRide</h2>
                </div>
                <div class='content'>
                    <p>Bonjour <strong>" . htmlspecialchars($data['passenger_name']) . "</strong>,</p>
                    <p>Votre réservation de covoiturage a bien été confirmée !</p>
                    
                    <div class='important'>
                        <strong>📍 Informations du trajet</strong>
                    </div>
                    
                    <div class='field'>
                        <span class='label'>🗓️ Date :</span> " . htmlspecialchars($data['date_depart']) . "
                    </div>
                    <div class='field'>
                        <span class='label'>🕐 Heure de départ :</span> " . htmlspecialchars($data['heure_depart']) . "
                    </div>
                    <div class='field'>
                        <span class='label'>📍 Départ :</span> " . htmlspecialchars($data['lieu_depart']) . "
                    </div>
                    <div class='field'>
                        <span class='label'>📍 Arrivée :</span> " . htmlspecialchars($data['lieu_arrivee']) . "
                    </div>
                    <div class='field'>
                        <span class='label'>👤 Chauffeur :</span> " . htmlspecialchars($data['driver_name']) . "
                    </div>
                    <div class='field'>
                        <span class='label'>🚗 Véhicule :</span> " . htmlspecialchars($data['vehicle_info']) . "
                    </div>
                    <div class='field'>
                        <span class='label'>💰 Prix :</span> " . htmlspecialchars($data['prix']) . " crédits
                    </div>
                    
                    <div style='margin-top: 20px; padding: 15px; background: #E8F5E9; border-radius: 5px;'>
                        <p style='margin: 0;'><strong>Numéro de réservation :</strong> #" . htmlspecialchars($data['reservation_id']) . "</p>
                    </div>
                    
                    <p style='margin-top: 20px;'>Merci de voyager avec EcoRide ! 🌱</p>
                    <p style='font-size: 12px; color: #666;'>Pour toute question, contactez-nous via le formulaire de contact sur notre site.</p>
                </div>
            </div>
        </body>
        </html>
    ";
}

/**
 * Version texte brut de l'email de confirmation de réservation
 */
private function getReservationPlainTextEmail(array $data): string
{
    return "=== Confirmation de réservation EcoRide ===\n\n" .
           "Bonjour " . $data['passenger_name'] . ",\n\n" .
           "Votre réservation de covoiturage a bien été confirmée !\n\n" .
           "INFORMATIONS DU TRAJET\n" .
           "----------------------------------------\n" .
           "Date : " . $data['date_depart'] . "\n" .
           "Heure de départ : " . $data['heure_depart'] . "\n" .
           "Départ : " . $data['lieu_depart'] . "\n" .
           "Arrivée : " . $data['lieu_arrivee'] . "\n" .
           "Chauffeur : " . $data['driver_name'] . "\n" .
           "Véhicule : " . $data['vehicle_info'] . "\n" .
           "Prix : " . $data['prix'] . " crédits\n" .
           "----------------------------------------\n\n" .
           "Numéro de réservation : #" . $data['reservation_id'] . "\n\n" .
           "Merci de voyager avec EcoRide !\n";
}