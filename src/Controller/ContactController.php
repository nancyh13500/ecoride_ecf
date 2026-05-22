<?php

namespace Ecoride\Ecf\Controller;

use Ecoride\Ecf\Core\Session;
use Ecoride\Ecf\Service\MailerService;

/**
 * Contrôleur du formulaire de contact — logique métier séparée de la vue.
 */
class ContactController
{
    public function __construct(
        private readonly Session $session = new Session(),
        private readonly MailerService $mailer = new MailerService()
    ) {
    }

    /**
     * Données par défaut pour le rendu du formulaire.
     *
     * @return array{name: string, email: string, subject: string, message: string, messageSuccess: string, messageError: string}
     */
    public function defaultViewData(): array
    {
        return [
            'name' => '',
            'email' => '',
            'subject' => '',
            'message' => '',
            'messageSuccess' => '',
            'messageError' => '',
        ];
    }

    /**
     * Traite une soumission POST et retourne les données pour la vue.
     *
     * @param array<string, mixed> $post
     * @return array{name: string, email: string, subject: string, message: string, messageSuccess: string, messageError: string}
     */
    public function handlePost(array $post): array
    {
        $this->session->verifyCSRFToken();

        $name = trim((string) ($post['name'] ?? ''));
        $email = trim((string) ($post['email'] ?? ''));
        $subject = trim((string) ($post['subject'] ?? ''));
        $message = trim((string) ($post['message'] ?? ''));

        $errors = $this->validate($name, $email, $subject, $message);

        if (!empty($errors)) {
            return [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'messageSuccess' => '',
                'messageError' => implode('<br>', $errors),
            ];
        }

        $success = $this->mailer->sendContactEmail([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
        ]);

        if ($success) {
            return [
                'name' => '',
                'email' => '',
                'subject' => '',
                'message' => '',
                'messageSuccess' => 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.',
                'messageError' => '',
            ];
        }

        return [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'messageSuccess' => '',
            'messageError' => 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer.',
        ];
    }

    /**
     * @return list<string>
     */
    private function validate(string $name, string $email, string $subject, string $message): array
    {
        $errors = [];

        if ($name === '') {
            $errors[] = 'Le nom est obligatoire';
        }
        if ($email === '') {
            $errors[] = 'L\'email est obligatoire';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email n\'est pas valide';
        }
        if ($subject === '') {
            $errors[] = 'Le sujet est obligatoire';
        }
        if ($message === '') {
            $errors[] = 'Le message est obligatoire';
        }

        return $errors;
    }
}
