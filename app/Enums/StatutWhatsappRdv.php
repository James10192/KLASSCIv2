<?php

namespace App\Enums;

/**
 * Le relais WhatsApp d'une convocation de rendez-vous, canal distinct du
 * courriel (`StatutConvocationRdv`).
 *
 * Le rang ordonne la progression normale : un evenement MailPulse n'est
 * applique que s'il fait avancer la ligne. Un accuse « remise » rejoue, ou un
 * « accord demande » arrive apres la remise, ne la fait pas reculer. Les etats
 * finaux sans remise (refus, sans reponse, echec) ne s'appliquent plus une fois
 * la convocation remise.
 */
enum StatutWhatsappRdv: string
{
    /** Remise a MailPulse ; la demande d'accord attend son tour dans la file d'envoi. */
    case Demandee = 'demandee';
    case AccordDemande = 'accord_demande';
    case Accordee = 'accordee';
    case Envoyee = 'envoyee';
    case Remise = 'remise';
    case Lue = 'lue';
    case Refusee = 'refusee';
    case SansReponse = 'sans_reponse';
    case Echec = 'echec';

    public function label(): string
    {
        return match ($this) {
            self::Demandee => 'WhatsApp : demande en file',
            self::AccordDemande => 'WhatsApp : accord demandé',
            self::Accordee => 'WhatsApp : accord donné',
            self::Envoyee => 'WhatsApp : envoyée',
            self::Remise => 'WhatsApp : remise',
            self::Lue => 'WhatsApp : lue',
            self::Refusee => 'WhatsApp : refusée',
            self::SansReponse => 'WhatsApp : sans réponse (48 h)',
            self::Echec => 'WhatsApp : échec',
        };
    }

    /** Suffixe du badge : le vert, l'orange et le rouge portent un etat. */
    public function ton(): string
    {
        return match ($this) {
            self::Remise, self::Lue => 'succes',
            self::Demandee, self::AccordDemande, self::Accordee, self::Envoyee => 'attente',
            self::Refusee, self::Echec => 'echec',
            self::SansReponse => 'neutre',
        };
    }

    public function rang(): int
    {
        return match ($this) {
            self::Demandee => 0,
            self::AccordDemande => 1,
            self::Accordee => 2,
            self::Envoyee => 3,
            self::Remise => 4,
            self::Lue => 5,
            self::Refusee, self::SansReponse, self::Echec => 6,
        };
    }

    /** La convocation est arrivee sur le telephone de la famille. */
    public function aAtteintLaFamille(): bool
    {
        return in_array($this, [self::Remise, self::Lue], true);
    }

    /** L'attente est terminee sans remise : la famille revient a la liste d'appel avec ce motif. */
    public function estUnRetourALAppel(): bool
    {
        return in_array($this, [self::Refusee, self::SansReponse, self::Echec], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<string> */
    public static function valeursRemises(): array
    {
        return [self::Remise->value, self::Lue->value];
    }
}
