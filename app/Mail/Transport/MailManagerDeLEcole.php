<?php

namespace App\Mail\Transport;

use Illuminate\Mail\MailManager;

/**
 * Le gestionnaire de mailers de Laravel, à une méthode près : le mailer par
 * défaut se demande à `MailerDeLEcole` au moment de l'envoi, pas à la
 * configuration figée au démarrage. Tout ce qui envoie sans nommer de mailer
 * (`Mail::to()`, mailables en file, canal `mail` des notifications) passe par
 * `getDefaultDriver()`, donc suit le réglage de l'école.
 */
final class MailManagerDeLEcole extends MailManager
{
    public function getDefaultDriver()
    {
        return $this->app->make(MailerDeLEcole::class)->parDefaut(parent::getDefaultDriver());
    }
}
