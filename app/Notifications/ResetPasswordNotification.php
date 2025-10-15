<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    public $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    // Par quels canaux envoyer la notification (ici e-mail)
    public function via($notifiable)
    {
        return ['mail'];
    }

    // Construire le mail envoyé
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Réinitialisation du mot de passe')
            ->line('Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.')
            ->action('Réinitialiser le mot de passe', $this->url)
            ->line('Si vous n\'avez pas demandé cette réinitialisation, aucune action n\'est requise.');
    }
}
