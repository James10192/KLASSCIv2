<?php

namespace App\Enums;

/**
 * Ce que l'on sait de la convocation d'une reservation.
 *
 * Une reservation creee avant ce suivi n'a pas d'etat (NULL) : on ignore si son
 * courriel est parti. Ce n'est volontairement pas un cas de cette enum — le
 * confondre avec EnAttente ferait partir un lot entier au premier passage de la
 * tache planifiee.
 */
enum StatutConvocationRdv: string
{
    case EnAttente = 'en_attente';
    case Envoyee = 'envoyee';
    case Echec = 'echec';
    case SansEmail = 'sans_email';

    /** Le creneau est passe avant l'envoi : ni a relancer, ni a traiter. */
    case SansObjet = 'sans_objet';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente d\'envoi',
            self::Envoyee => 'Convocation envoyée',
            self::Echec => 'Envoi échoué',
            self::SansEmail => 'Sans e-mail',
            self::SansObjet => 'Sans objet (créneau passé)',
        };
    }

    /** Suffixe de la classe CSS du badge : le vert, l'orange et le rouge portent un etat. */
    public function ton(): string
    {
        return match ($this) {
            self::Envoyee => 'succes',
            self::EnAttente => 'attente',
            self::Echec => 'echec',
            self::SansEmail, self::SansObjet => 'neutre',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
