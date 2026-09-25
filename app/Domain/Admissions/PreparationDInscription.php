<?php

namespace App\Domain\Admissions;

use App\Domain\Notifications\PhoneFormatter;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPSystemSetting;
use App\Services\StudentDuplicateDetector;
use App\Services\TenantScolariteSettings;
use App\Services\EnrollmentAmountVisibility;

/**
 * Tout ce que la fenetre « Accepter et inscrire » doit montrer avant le clic,
 * lu en une fois.
 *
 * Elle ne decide rien et n'ecrit rien : l'inscription part ensuite par le flux
 * canonique (ESBTPInscriptionController::store), avec ses gardes — classe
 * complete, doublons, date de naissance divergente, quota. Ici, on prepare ce
 * que l'agent doit voir pour que ces gardes ne le surprennent pas.
 */
class PreparationDInscription
{

    public function __construct(
        private readonly StudentDuplicateDetector $doublons,
        private readonly TenantScolariteSettings $scolarite,
    ) {
    }

    /** @return array<string, mixed> */
    public function pour(ESBTPCandidature $c): array
    {
        $c->loadMissing(['filiere:id,name', 'niveau:id,name', 'anneeUniversitaire:id,name', 'reservations' => fn ($r) => $r->occupantes()->with('creneau')]);

        return [
            'candidature' => [
                'id' => (int) $c->id,
                'statut' => (string) $c->statut,
                'reference' => (string) ($c->referencePubliqueAffichee() ?? ''),
                'voeu' => $c->voeu(),
                'annee_universitaire_id' => $c->annee_universitaire_id,
                'annee' => (string) $c->anneeUniversitaire?->name,
                'affectation_status' => $c->affectation_status ?: ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
            ],
            'identite' => [
                'nom' => (string) $c->nom,
                'prenoms' => (string) $c->prenoms,
                'sexe' => (string) $c->sexe,
                'date_naissance' => $c->date_naissance?->toDateString(),
                'lieu_naissance' => (string) $c->lieu_naissance,
                'telephone' => PhoneFormatter::toReadable($c->telephone) ?: (string) $c->telephone,
                'email_personnel' => (string) $c->email,
                'ville' => (string) $c->ville,
                'commune' => (string) $c->commune,
            ],
            'tuteur' => [
                'declare' => trim((string) $c->tuteur_nom),
                'nom' => trim((string) $c->tuteur_nom),
                'prenoms' => '',
                'telephone' => PhoneFormatter::toReadable($c->tuteur_telephone) ?: (string) $c->tuteur_telephone,
                'relation' => ESBTPCandidature::relationTuteurNormalisee($c->tuteur_lien),
                'profession' => (string) $c->tuteur_profession,
            ],
            'doublons' => $this->doublons($c),
            'classes' => $this->classes($c->filiere_id, $c->niveau_id),
            'matricule_automatique' => ESBTPSystemSetting::isMatriculeAutomatic(),
            'statut_etablissement_requis' => $this->scolarite->confirmerStatutEtablissement(),
            'montants_masques' => app(EnrollmentAmountVisibility::class)->hideAmounts(auth()->user()),
            'rendez_vous' => $this->rendezVous($c),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function doublons(ESBTPCandidature $c): array
    {
        return $this->doublons->find((string) $c->nom, (string) $c->prenoms, $c->date_naissance?->toDateString(), $c->sexe ?: null, 4)
            ->map(fn (array $d) => $d + [
                'bloquant' => ($d['score'] ?? 0) >= StudentDuplicateDetector::SCORE_BLOQUANT,
                // Pas de lien vers une fiche que l'agent ne peut pas ouvrir.
                'fiche' => auth()->user()?->can('students.view') ? route('esbtp.etudiants.show', $d['id']) : null,
            ])->values()->all();
    }

    /**
     * Les classes actives, le voeu en tete, avec leurs places comptees comme
     * le flux canonique les compte (ESBTPClasse::placesPrisesParClasse, la
     * regle de nombre_etudiants) — mais en une requete, pas une par classe.
     * `complete` suit le refus de ESBTPInscriptionController::store() :
     * places disponibles nulles, capacite non reglee comprise.
     *
     * Sert aussi a la reinscription, sans voeu.
     *
     * @return list<array<string, mixed>>
     */
    public function classes(?int $filiereVoulue = null, ?int $niveauVoulu = null): array
    {
        $inscrits = ESBTPClasse::placesPrisesParClasse();

        return ESBTPClasse::query()->where('is_active', true)->with(['filiere:id,name', 'niveau:id,name'])
            ->get(['id', 'name', 'filiere_id', 'niveau_etude_id', 'places_totales'])
            ->map(function (ESBTPClasse $classe) use ($filiereVoulue, $niveauVoulu, $inscrits) {
                $total = (int) ($classe->places_totales ?? 0);
                $pris = (int) ($inscrits[$classe->id] ?? 0);

                return [
                    'id' => (int) $classe->id,
                    'nom' => (string) $classe->name,
                    'detail' => trim(($classe->filiere?->name ?? '').' · '.($classe->niveau?->name ?? ''), ' ·'),
                    'places_totales' => $total,
                    'places_prises' => $pris,
                    'places_libres' => max(0, $total - $pris),
                    'complete' => max(0, $total - $pris) <= 0,
                    'voeu' => $filiereVoulue && (int) $classe->filiere_id === (int) $filiereVoulue
                        && (! $niveauVoulu || (int) $classe->niveau_etude_id === (int) $niveauVoulu),
                ];
            })
            ->sortBy([fn ($a, $b) => $b['voeu'] <=> $a['voeu'], fn ($a, $b) => strcmp($a['nom'], $b['nom'])])
            ->values()->all();
    }

    /** @return array{jour: string, heure: string, aujourdhui: bool}|null */
    private function rendezVous(ESBTPCandidature $c): ?array
    {
        $r = $c->reservations->first();

        return $r?->creneau === null ? null : [
            'jour' => ucfirst($r->creneau->date->translatedFormat('l j F')),
            'heure' => $r->creneau->heureDebutHi(),
            'aujourdhui' => $r->creneau->date->isToday() || $r->creneau->date->isPast(),
        ];
    }
}
