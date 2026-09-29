<?php

namespace App\Services\Testing;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cree un etudiant strictement reserve aux tests E2E de l'instance presentation.
 * Il est inscrit sur l'annee precedente et volontairement absent de l'annee
 * courante afin que le vrai portail de reinscription puisse le retrouver.
 */
class ReinscriptionStudentFixture
{
    /** @return array<string,mixed> */
    public function create(
        string $email,
        string $telephone,
        bool $apply = false,
        ?string $matricule = null
    ): array {
        $courante = ESBTPAnneeUniversitaire::query()->where('is_current', true)->first();
        if (! $courante) {
            throw new RuntimeException('Aucune annee universitaire courante configuree.');
        }

        $precedente = ESBTPAnneeUniversitaire::query()
            ->where('end_date', '<', $courante->start_date)
            ->orderByDesc('end_date')
            ->first();
        if (! $precedente) {
            throw new RuntimeException('Aucune annee universitaire precedente exploitable.');
        }

        $classe = ESBTPClasse::query()
            ->where('name', '1BTS IG A')
            ->whereNotNull('filiere_id')
            ->whereNotNull('niveau_etude_id')
            ->first()
            ?? ESBTPClasse::query()
                ->whereNotNull('filiere_id')
                ->whereNotNull('niveau_etude_id')
                ->orderBy('id')
                ->first();

        if (! $classe) {
            throw new RuntimeException('Aucune classe exploitable pour le test de reinscription.');
        }

        $suffix = strtoupper(Str::random(6));
        $matricule = trim((string) $matricule) ?: 'E2E' . now()->format('ymd') . $suffix;
        $dateNaissance = '2004-04-12';
        $email = mb_strtolower(trim($email));
        $telephone = preg_replace('/\D+/', '', $telephone) ?: '';

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Adresse e-mail de test invalide.');
        }
        if (! preg_match('/^0\d{9}$/', $telephone)) {
            throw new RuntimeException('Numero de telephone de test invalide (10 chiffres, commencant par 0).');
        }

        $plan = [
            'matricule' => $matricule,
            'nom' => 'TEST',
            'prenoms' => 'REINSCRIPTION ' . $suffix,
            'date_naissance' => $dateNaissance,
            'email' => $email,
            'telephone' => $telephone,
            'classe' => ['id' => $classe->id, 'nom' => $classe->name],
            'annee_precedente' => ['id' => $precedente->id, 'nom' => $precedente->name],
            'annee_courante' => ['id' => $courante->id, 'nom' => $courante->name],
        ];

        if (! $apply) {
            return ['applique' => false, 'plan' => $plan];
        }

        if (ESBTPEtudiant::query()->where('matricule', $matricule)->exists()) {
            throw new RuntimeException("Le matricule {$matricule} existe deja.");
        }

        $auteur = User::query()->min('id');
        if (! $auteur) {
            throw new RuntimeException('Aucun utilisateur auteur disponible sur cette instance.');
        }

        $result = DB::transaction(function () use ($plan, $classe, $precedente, $auteur): array {
            // Le compte applicatif reste volontairement technique pour eviter une
            // collision d'e-mail unique avec un vrai utilisateur. Les contacts
            // testes par le portail vivent sur la fiche etudiant ci-dessous.
            $username = strtolower($plan['matricule']);
            $user = User::create([
                'name' => $plan['prenoms'] . ' ' . $plan['nom'],
                'first_name' => $plan['prenoms'],
                'last_name' => $plan['nom'],
                'username' => $username,
                'email' => $username . '@demo.klassci.local',
                'phone' => null,
                'password' => Hash::make(Str::random(40)),
                'is_active' => true,
            ]);

            $etudiant = new ESBTPEtudiant();
            $etudiant->fill([
                'user_id' => $user->id,
                'matricule' => $plan['matricule'],
                'nom' => $plan['nom'],
                'prenoms' => $plan['prenoms'],
                'sexe' => 'M',
                'date_naissance' => $plan['date_naissance'],
                'lieu_naissance' => 'Abidjan',
                'nationalite' => 'Ivoirienne',
                'ville' => 'Abidjan',
                'commune' => 'Cocody',
                'telephone' => $plan['telephone'],
                'email' => $plan['email'],
                'statut' => 'actif',
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $precedente->id,
                'created_by' => $auteur,
                'updated_by' => $auteur,
            ]);
            $etudiant->save();

            $inscription = new ESBTPInscription();
            $inscription->fill([
                'etudiant_id' => $etudiant->id,
                'annee_universitaire_id' => $precedente->id,
                'classe_id' => $classe->id,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'affectation_status' => ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
                'date_inscription' => $precedente->start_date,
                'type_inscription' => 'première_inscription',
                'statut_etablissement' => ESBTPInscription::STATUT_ETABLISSEMENT_NOUVEAU,
                'status' => 'active',
                'workflow_step' => 'etudiant_cree',
                'date_validation' => $precedente->start_date,
                'numero_recu' => 'E2E-' . $plan['matricule'],
                'montant_scolarite' => 0,
                'frais_inscription' => 0,
                'created_by' => $auteur,
                'updated_by' => $auteur,
            ]);
            $inscription->save();

            return [
                'etudiant_id' => $etudiant->id,
                'inscription_id' => $inscription->id,
            ];
        });

        return ['applique' => true, 'plan' => $plan, 'ecrit' => $result];
    }
}
