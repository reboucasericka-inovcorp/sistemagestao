<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class SetPasswordNotification extends ResetPassword
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $url = $frontendUrl.'/reset-password?token='.$this->token.'&email='.urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Defina sua senha')
            ->line('Sua conta foi criada. Clique abaixo para definir sua senha.')
            ->action('Definir senha', $url)
            ->line('Se você não solicitou isso, nenhuma ação é necessária.');
    }
}
