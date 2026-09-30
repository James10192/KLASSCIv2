<?php

namespace App\Services\Admissions;

use App\Models\ESBTPCandidature;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Crée l'accès étudiant lorsqu'un parcours tenant le demande.
 *
 * Le mot de passe envoyé est temporaire : `must_change_password` impose son
 * remplacement à la première connexion via le middleware déjà présent dans
 * KLASSCI. La création est idempotente et verrouille la fiche étudiant afin
 * que deux validations de paiement concurrentes ne créent pas deux comptes.
 */
class CompteEtudiantWorkflow
{
    public function __construct(
        private ParcoursInscriptionReglages $reglages,
        private WorkflowWhatsAppNotifier $whatsApp,
    ) {
    }

    public function activerApresPaiement(ESBTPPaiement $paiement): ?User
    {
        if (! $this->reglages->activerCompteApresPaiement() || ! $this->paiementEstValide($paiement)) {
            return null;
        }

        $paiement->loadMissing('inscription.etudiant');
        $inscription = $paiement->inscription;
        $etudiant = $inscription?->etudiant;

        // Le mécanisme ne s'applique qu'aux dossiers issus du portail public.
        // Un paiement ordinaire ne doit jamais créer un compte par surprise.
        $candidature = $inscription
            ? ESBTPCandidature::query()->where('inscription_id', $inscription->id)->first()
            : null;

        if (! $inscription || ! $etudiant || ! $candidature) {
            return null;
        }

        $motDePasse = null;
        $user = DB::transaction(function () use ($etudiant, $paiement, &$motDePasse): User {
            /** @var ESBTPEtudiant $verrouille */
            $verrouille = ESBTPEtudiant::query()->whereKey($etudiant->id)->lockForUpdate()->firstOrFail();

            if ($verrouille->user_id) {
                return User::query()->findOrFail($verrouille->user_id);
            }

            $username = $this->usernameUnique($verrouille->prenoms, $verrouille->nom);
            $motDePasse = Str::random(12).'!9';
            $adresse = app(\App\Services\Emails\AdresseDeCompte::class)
                ->pour($verrouille->email_personnel ?: $verrouille->email);

            $user = User::create([
                'name' => trim($verrouille->prenoms.' '.$verrouille->nom),
                'first_name' => $verrouille->prenoms,
                'last_name' => $verrouille->nom,
                'email' => $adresse,
                'username' => $username,
                'password' => Hash::make($motDePasse),
                'is_active' => true,
                'must_change_password' => true,
                'created_by' => $paiement->validateur_id ?: $paiement->created_by,
            ]);

            $role = Role::query()->where('name', 'etudiant')->first();
            if ($role) {
                $user->assignRole($role);
            }

            $verrouille->update([
                'user_id' => $user->id,
                'email' => $adresse ?: $verrouille->email,
                'updated_by' => $paiement->validateur_id ?: $paiement->updated_by,
            ]);

            return $user;
        });

        // Si le compte existait déjà, aucun secret en clair n'a été généré :
        // surtout ne renvoyer ni ne réinitialiser ses accès.
        if ($motDePasse === null) {
            return $user;
        }

        $nom = trim($etudiant->prenoms.' '.$etudiant->nom);
        $loginUrl = route('login');
        $email = $candidature->email ?: $etudiant->email_personnel ?: $etudiant->email;
        $telephone = $candidature->telephone ?: $etudiant->telephone;

        if ($this->reglages->envoyerParEmail() && $email) {
            $this->envoyerEmail($email, $nom, $user->username, $motDePasse, $loginUrl);
        }

        if ($this->reglages->envoyerParWhatsApp() && $telephone) {
            $this->whatsApp->envoyer($telephone, $nom, $user->username, $motDePasse, $loginUrl);
        }

        return $user;
    }

    private function paiementEstValide(ESBTPPaiement $paiement): bool
    {
        return in_array((string) ($paiement->statut ?: $paiement->status), ['validé', 'valide'], true);
    }

    private function usernameUnique(?string $prenoms, ?string $nom): string
    {
        $premierPrenom = preg_split('/\s+/', trim((string) $prenoms))[0] ?? 'etudiant';
        $base = Str::lower(Str::ascii(trim($premierPrenom.'.'.(string) $nom)));
        $base = preg_replace('/[^a-z0-9.]+/', '', $base) ?: 'etudiant';
        $username = $base;
        $suffixe = 1;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $base.'.'.$suffixe;
            $suffixe++;
        }

        return $username;
    }

    private function envoyerEmail(string $email, string $nom, string $username, string $motDePasse, string $loginUrl): void
    {
        try {
            Mail::raw(
                "Bonjour {$nom},\n\nVotre accès étudiant KLASSCI est prêt.\n\nIdentifiant : {$username}\nMot de passe temporaire : {$motDePasse}\nConnexion : {$loginUrl}\n\nVous devrez choisir un nouveau mot de passe lors de votre première connexion.\n\nConservez ces informations de manière confidentielle.",
                function ($message) use ($email) {
                    $message->to($email)->subject('Votre accès étudiant KLASSCI');
                }
            );
        } catch (\Throwable $e) {
            // Le paiement reste valide même si un fournisseur de notification
            // est momentanément indisponible. L'échec est traçable et l'accès
            // peut être renvoyé par l'administration.
            Log::error('Échec de l’envoi email des accès étudiant.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
