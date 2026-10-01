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
    /**
     * Remplace le gestionnaire de Laravel en gardant ce qu'on lui aurait déjà
     * confié : mailers créés sur mesure (`Mail::extend`), mailers résolus.
     * Aujourd'hui `extend` s'applique avant tout `Mail::extend` (le fournisseur
     * de Laravel est différé, et le seul `Mail::extend` vit dans un `boot`),
     * donc il n'y a rien à reporter ; les copier ferme la porte au jour où un
     * paquet en enregistrerait un plus tôt.
     */
    public static function depuis(MailManager $original, $app): self
    {
        $gestionnaire = new self($app);
        $gestionnaire->customCreators = $original->customCreators;
        $gestionnaire->mailers = $original->mailers;

        return $gestionnaire;
    }

    public function getDefaultDriver()
    {
        return $this->app->make(MailerDeLEcole::class)->parDefaut(parent::getDefaultDriver());
    }
}
