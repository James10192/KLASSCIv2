<?php

namespace App\Rules;

use App\Enums\EtatEmail;
use App\Services\Emails\DiagnosticEmail;
use Illuminate\Contracts\Validation\InvokableRule;

/**
 * Une adresse a laquelle un courriel peut vraiment arriver.
 *
 * S'ajoute a `email` et ne le remplace pas : la forme reste verifiee par la
 * regle standard, celle-ci refuse ce que la forme laisse passer — un domaine
 * que KLASSCI a fabrique (`esbtp.edu.ci`), une faute de frappe connue
 * (`gmail.con`), un domaine qui ne recoit aucun courrier. Le rebond, lui,
 * n'arrive que des jours plus tard, quand la convocation est deja perdue.
 *
 * Vide : rien a dire, `nullable` / `required` decident.
 *
 * `InvokableRule` : le projet tourne en Laravel 9.52.
 */
class EmailJoignable implements InvokableRule
{
    /** @var list<string> */
    private array $dejaEnregistrees;

    /**
     * @param  iterable<?string>  $dejaEnregistrees  adresses deja en base, renvoyees telles quelles
     *                                             par un formulaire d'edition : on ne bloque pas la
     *                                             modification d'un telephone a cause d'une adresse
     *                                             ancienne que personne n'a touchee (le nettoyage
     *                                             s'en charge).
     */
    public function __construct(iterable $dejaEnregistrees = [])
    {
        $this->dejaEnregistrees = [];
        foreach ($dejaEnregistrees as $adresse) {
            if (is_string($adresse) && trim($adresse) !== '') {
                $this->dejaEnregistrees[] = mb_strtolower(trim($adresse));
            }
        }
    }

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function __invoke($attribute, $value, $fail): void
    {
        if (! is_string($value) || trim($value) === '' || in_array(mb_strtolower(trim($value)), $this->dejaEnregistrees, true)) {
            return;
        }

        $analyse = app(DiagnosticEmail::class)->diagnostiquer($value);

        match ($analyse->etat) {
            EtatEmail::FauteDeFrappe => $fail('Vouliez-vous dire '.$analyse->suggestion.' ?'),
            EtatEmail::Factice => $fail('Cette adresse ne reçoit aucun courrier ('.$analyse->domaine.' n\'existe pas). Indiquez une adresse personnelle, ou laissez le champ vide.'),
            EtatEmail::SansMx => $fail('Le domaine '.$analyse->domaine.' ne reçoit aucun courrier. Vérifiez l\'adresse.'),
            EtatEmail::Invalide => $fail('Cette adresse e-mail n\'est pas valide.'),
            default => null,
        };
    }
}
