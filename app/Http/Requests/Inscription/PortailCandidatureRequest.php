<?php

namespace App\Http\Requests\Inscription;

use App\Domain\Notifications\PhoneNormalizer;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPEcheancierRule;
use App\Support\Nationalites;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Candidature d'un nouvel etudiant.
 *
 * Contrairement a la reinscription, l'echec de validation peut ici etre
 * EXPLICITE : il n'y a personne a identifier, donc dire « le telephone est
 * invalide » ne revele l'existence de personne. Un bachelier qui se trompe de
 * format doit pouvoir le corriger, pas deviner.
 */
class PortailCandidatureRequest extends FormRequest
{
    /** L'authentification est portee par la signature, verifiee en amont. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            // Les bornes suivent celles du formulaire de l'ecole
            // (StoreInscriptionRequest), et non l'inverse.
            //
            // Le portail acceptait plus large — 150 ici, 100 la-bas. Le
            // candidat passait, la valeur atterrissait dans le formulaire
            // pre-rempli, et l'agent lisait « ne doit pas depasser 100
            // caracteres » sur un champ qu'il n'avait pas tape, sans savoir
            // d'ou il venait. Refuser au depot met l'erreur devant celui qui
            // peut la corriger : le candidat, sur son propre nom.
            'prenoms' => ['required', 'string', 'max:100'],
            'date_naissance' => ['required', 'date_format:Y-m-d', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:100'],
            'sexe' => ['nullable', Rule::in(['M', 'F'])],
            // Ferme sur la meme liste que le formulaire d'inscription de
            // l'ecole. En texte libre, « ivoirienne » ou « Côte d'Ivoire »
            // seraient acceptes ici puis ne correspondraient a aucune option
            // la-bas : le champ, pourtant obligatoire, retomberait a vide au
            // pre-remplissage sans que personne ne s'en apercoive.
            'nationalite' => ['nullable', Rule::in(Nationalites::valeurs())],

            // Le telephone est la cle d'unicite : c'est par lui que l'ecole
            // rappelle, et c'est lui qui evite les doublons dans la corbeille.
            //
            // Donc il doit avoir UNE seule ecriture possible, et c'est pour
            // cela qu'on refuse ce que l'analyseur du projet refuse plutot que
            // de se rabattre sur « les chiffres seuls ». Ce repli ne
            // rapprochait pas les deux ecritures d'un meme numero : « 27 20 30
            // 10 20 » et « +225 27 20 30 10 20 » donnaient deux cles, faute
            // d'indicatif pays connu — et deux cles font deux lignes, donc
            // aucun des refus « deja inscrit » ou « autre personne » ne se
            // declenchait. La cle etait canonique en apparence seulement.
            //
            // Le perimetre — mobile ivoirien — est celui du champ : « c'est par
            // ce numero que l'etablissement vous rappellera », six ecoles
            // ivoiriennes. Un fixe ou un numero etranger n'est pas perdu pour
            // autant : `tuteur_telephone` reste libre, et c'est souvent lui
            // qu'une famille de la diaspora renseigne.
            'telephone' => [
                'required',
                'string',
                'max:30',
                static function (string $attribut, $valeur, callable $echec): void {
                    if (PhoneNormalizer::toE164(is_string($valeur) ? $valeur : null) === null) {
                        $echec('Indiquez un numéro de téléphone mobile ivoirien, par exemple 07 07 12 12 34.');
                    }
                },
            ],
            'email' => ['nullable', 'email:rfc', 'max:100'],
            'ville' => ['nullable', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],

            // Voeu : soit un choix dans la liste publiee, soit du texte libre.
            // Un bachelier ne connait pas toujours le nom exact d'une filiere.
            //
            // `is_active`, et pas seulement `exists` : /choix ne publie que les
            // filieres et niveaux actifs, or ce canal est public — un
            // identifiant se tape a la main. Sans ce filtre, une filiere que
            // l'ecole a justement fermee revient par la porte de derriere, et
            // la scolarite recoit un voeu pour une formation qu'elle
            // n'ouvre plus. Les deux listes doivent dire la meme chose.
            'filiere_id' => ['nullable', 'integer', Rule::exists('esbtp_filieres', 'id')->where('is_active', true)],
            'niveau_id' => ['nullable', 'integer', Rule::exists('esbtp_niveau_etudes', 'id')->where('is_active', true)],
            'voeu_libre' => ['nullable', 'string', 'max:255'],

            'serie_bac' => ['nullable', 'string', 'max:60'],
            'etablissement_origine' => ['nullable', 'string', 'max:150'],
            'annee_bac' => ['nullable', 'integer', 'min:1980', 'max:'.(date('Y') + 1)],

            // Declare par le candidat, verifie par l'ecole : en Cote d'Ivoire
            // un bachelier est affecte par l'Etat ou ne l'est pas, et cela
            // change ce qu'il paie. Nullable, parce que beaucoup ne le savent
            // pas encore et qu'une reponse au hasard vaut moins qu'une case vide.
            'affectation_status' => ['nullable', Rule::in(array_keys(ESBTPCandidature::affectationsDeclarables()))],

            // Le tuteur : c'est souvent lui que l'ecole joindra.
            // 100, comme `parents[*][nom]` cote ecole : le bouton « Reprendre
            // ce tuteur » y recopie cette valeur telle quelle.
            'tuteur_nom' => ['nullable', 'string', 'max:100'],
            'tuteur_telephone' => ['nullable', 'string', 'max:30'],
            'tuteur_lien' => ['nullable', 'string', 'max:60'],
            'tuteur_profession' => ['nullable', 'string', 'max:120'],

            'message' => ['nullable', 'string', 'max:2000'],

            // Obligation de la loi ivoirienne 2013-450 sur les donnees a
            // caractere personnel.
            'consentement' => ['required', 'accepted'],

            // Adresse du VISITEUR, transmise dans le corps signe par le site
            // vitrine. Celle que verrait Laravel est celle du site, identique
            // pour toute l'ecole : elle ne borne rien et n'identifie rien.
            'ip_client' => ['required', 'ip'],
        ];
    }

    /**
     * Le statut d'affectation arrive parfois sans ses accents.
     *
     * Le site vitrine sert les valeurs exactes, mais ce canal est public : un
     * formulaire recopie, un client tiers, un copier-coller passe par un
     * traitement qui aplatit les accents, et « affecte » arriverait a la place
     * d'« affecté ». ESBTPEcheancierRule::normalizeStatus existe justement
     * parce que ces variantes circulent deja dans le produit ; on la reutilise
     * plutot que d'en ecrire une seconde.
     *
     * Une valeur qu'elle ne reconnait pas rend STATUS_ALL — un joker de regle
     * de frais, pas un etat d'etudiant. On garde alors la saisie d'origine :
     * la regle la refusera, et le message d'erreur parlera de ce que le
     * candidat a reellement envoye.
     */
    protected function prepareForValidation(): void
    {
        $this->canoniserLeTelephone();

        $brut = $this->input('affectation_status');

        if (! is_string($brut) || trim($brut) === '') {
            return;
        }

        $normalise = ESBTPEcheancierRule::normalizeStatus($brut);

        if ($normalise !== ESBTPEcheancierRule::STATUS_ALL) {
            $this->merge(['affectation_status' => $normalise]);
        }
    }

    /**
     * Le telephone est la CLE d'unicite : il doit avoir une seule ecriture.
     *
     * Sans cela, tout l'edifice des refus repose sur du vide. Aya depose
     * « 0707121234 », l'ecole accepte et l'inscrit. La famille redepose en
     * tapant « +225 07 07 12 12 34 » — le gabarit meme que le formulaire de
     * l'ecole affiche en exemple. La recherche par telephone ne trouve rien :
     * nouvelle ligne, aucun refus ne se declenche, et la corbeille propose de
     * reinscrire quelqu'un qui l'est deja. Le meme contournement annule le
     * garde d'identite, donc tout le raisonnement sur le cadet qui reprend le
     * numero du foyer.
     *
     * PhoneNormalizer est l'analyseur canonique du projet, deja utilise pour
     * les relances et les exports. Il rend du E.164 pour un mobile ivoirien
     * ecrit de n'importe quelle facon, et refuse le reste.
     *
     * Pas de repli ici : ce qu'il refuse, la regle de validation le refuse
     * aussi, et la saisie d'origine reste alors intacte pour que le message
     * d'erreur parle du numero que le candidat a reellement tape.
     */
    private function canoniserLeTelephone(): void
    {
        $brut = $this->input('telephone');

        // Un canal public peut recevoir un tableau la ou on attend une chaine :
        // `telephone[]=x` suffit. L'analyseur exige `?string`, et un TypeError
        // ici rendrait un 500 la ou la validation doit rendre un 422.
        $canonique = PhoneNormalizer::toE164(is_string($brut) ? $brut : null);

        if ($canonique !== null) {
            $this->merge(['telephone' => $canonique]);
        }
    }

    /**
     * Au moins un voeu, d'une facon ou d'une autre.
     *
     * Une candidature sans voeu oblige la scolarite a rappeler pour demander
     * « vous voulez faire quoi ? ». Autant le demander tout de suite.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $sansListe = ! $this->filled('filiere_id') && ! $this->filled('niveau_id');
            $sansTexte = trim((string) $this->input('voeu_libre')) === '';

            if ($sansListe && $sansTexte) {
                // Sur « voeu », et non sur `voeu_libre` : la contrainte porte
                // sur les TROIS champs ensemble, et le site vitrine ancre ce
                // message au niveau de la section pour cette raison. Rattache
                // au troisieme champ, il s'affichait sous un libelle qui
                // commence par « Ou » et envoyait decrire a la main une
                // formation qu'il suffisait de choisir dans la liste au-dessus.
                $validator->errors()->add('voeu', 'Indiquez la filière, le niveau, ou décrivez la formation qui vous intéresse.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'enregistre' => false,
            'message' => 'Certaines informations sont incomplètes ou invalides.',
            'champs' => $validator->errors()->toArray(),
        ], 422));
    }
}
