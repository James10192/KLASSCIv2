<?php

namespace App\Jobs\Annonces;

use App\Models\ESBTPAnnonce;
use App\Models\ESBTPEtudiant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Prépare des envois individuels dédupliqués, sans appel au fournisseur depuis HTTP.
 * Les trois cibles sont exactement celles de l'annonce publiée.
 */
class DiffuserAnnonceParCourriel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $annonceId)
    {
    }

    public function handle(): void
    {
        $annonce = ESBTPAnnonce::find($this->annonceId);
        if (!$annonce || !$annonce->is_published || !$annonce->email_notification_enabled
            || $annonce->date_publication->isFuture()) {
            return;
        }

        $etudiants = ESBTPEtudiant::query()
            ->select(['id', 'user_id', 'email', 'email_personnel'])
            ->with('user:id,email');

        if ($annonce->type === 'classe') {
            $classes = $annonce->classes()->pluck('esbtp_classes.id');
            $etudiants->whereHas('inscriptions', fn ($q) => $q->anneeEnCours()->whereIn('classe_id', $classes));
        } elseif ($annonce->type === 'etudiant') {
            $etudiants->whereIn('esbtp_etudiants.id', fn ($q) => $q->select('etudiant_id')
                ->from('esbtp_annonce_etudiant')->where('annonce_id', $annonce->id));
        } elseif ($annonce->type !== 'general') {
            return;
        }

        $maintenant = now();
        $etudiants->chunkById(200, function ($lot) use ($annonce, $maintenant): void {
            $lignes = [];
            foreach ($lot as $etudiant) {
                $email = null;
                // Inclut les étudiants sans compte utilisateur, si leur adresse est connue.
                foreach ([$etudiant->email_personnel, $etudiant->email, $etudiant->user?->email] as $candidate) {
                    $candidate = strtolower(trim((string) $candidate));
                    if (strlen($candidate) <= 254 && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                        $email = $candidate;
                        break;
                    }
                }
                if ($email === null) {
                    continue;
                }
                $lignes[] = [
                    'annonce_id' => $annonce->id,
                    'email' => $email,
                    'status' => 'pending',
                    'created_at' => $maintenant,
                    'updated_at' => $maintenant,
                ];
            }

            if ($lignes !== []) {
                // Reprise du distributeur ou adresse partagée : aucune entrée doublée.
                DB::table('esbtp_annonce_email_deliveries')->insertOrIgnore($lignes);
            }
        });

        DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->chunkById(200, function ($lot): void {
                foreach ($lot as $livraison) {
                    EnvoyerAnnonceCourriel::dispatch((int) $livraison->id);
                }
            });
    }
}
