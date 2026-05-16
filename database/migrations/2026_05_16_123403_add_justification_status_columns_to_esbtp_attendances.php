<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Foundation justification d'absence — W5
 *
 * Ajoute 4 colonnes au workflow de justification :
 *   - justification_status : Enum PENDING/APPROVED/REJECTED (source: JustificationStatus)
 *   - admin_comment        : commentaire de l'admin lors du rejet (séparé du commentaire étudiant)
 *   - processed_at         : timestamp du traitement admin (approve/reject)
 *   - processed_by_id      : User qui a traité la justification
 *
 * Backfill idempotent : reconstruit le statut à partir des données legacy
 * (statut/justified_at/commentaire) en extrayant le commentaire admin via regex
 * du pattern "Commentaire de l'administration: ...".
 *
 * Idempotent via Schema::hasColumn pour permettre rerun sans erreur.
 */
return new class extends Migration {
    public function up(): void
    {
        // --- 1. Ajout des colonnes (idempotent) -----------------------------
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_attendances', 'justification_status')) {
                $table->string('justification_status', 20)->nullable()->after('commentaire');
            }
            if (!Schema::hasColumn('esbtp_attendances', 'admin_comment')) {
                $table->text('admin_comment')->nullable()->after('justification_status');
            }
            if (!Schema::hasColumn('esbtp_attendances', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('admin_comment');
            }
            if (!Schema::hasColumn('esbtp_attendances', 'processed_by_id')) {
                $table->foreignId('processed_by_id')->nullable()
                    ->after('processed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        // Index sur justification_status (idempotent : add only if absent)
        $hasIndex = collect(DB::select("SHOW INDEX FROM esbtp_attendances WHERE Key_name = 'esbtp_attendances_justification_status_index'"))->isNotEmpty();
        if (!$hasIndex) {
            Schema::table('esbtp_attendances', function (Blueprint $table) {
                $table->index('justification_status');
            });
        }

        // --- 2. Backfill (idempotent : skip rows déjà set) ------------------
        DB::transaction(function () {
            $affected = 0;
            $total = DB::table('esbtp_attendances')
                ->whereNull('justification_status')
                ->where(function ($q) {
                    $q->whereNotNull('justified_at')
                      ->orWhere('statut', 'excuse');
                })
                ->count();

            Log::info("[justifications backfill] {$total} rows to process");

            DB::table('esbtp_attendances')
                ->whereNull('justification_status')
                ->where(function ($q) {
                    $q->whereNotNull('justified_at')
                      ->orWhere('statut', 'excuse');
                })
                ->orderBy('id')
                ->chunkById(500, function ($rows) use (&$affected) {
                    foreach ($rows as $row) {
                        $status = null;
                        $adminComment = null;
                        $cleanedCommentaire = $row->commentaire;

                        // Pattern legacy : "Commentaire de l'administration: ..."
                        if ($row->commentaire && preg_match(
                            "/Commentaire de l'administration:\s*(.+?)$/sm",
                            $row->commentaire,
                            $matches
                        )) {
                            $adminComment = trim($matches[1]);
                            // Retirer le bloc admin pour conserver uniquement le commentaire étudiant
                            $cleanedCommentaire = trim(preg_replace(
                                "/\n\s*\n?Commentaire de l'administration:.*$/sm",
                                '',
                                $row->commentaire
                            ));
                            // Si statut = excuse + admin_comment présent : exception très rare, garder APPROVED (statut prime)
                            // Sinon (cas normal du rejet) : REJECTED
                            $status = $row->statut === 'excuse'
                                ? \App\Enums\JustificationStatus::APPROVED->value
                                : \App\Enums\JustificationStatus::REJECTED->value;
                        } elseif ($row->statut === 'excuse') {
                            $status = \App\Enums\JustificationStatus::APPROVED->value;
                        } elseif ($row->statut === 'absent' && $row->justified_at) {
                            $status = \App\Enums\JustificationStatus::PENDING->value;
                        }

                        if ($status !== null) {
                            DB::table('esbtp_attendances')
                                ->where('id', $row->id)
                                ->update([
                                    'justification_status' => $status,
                                    'admin_comment' => $adminComment,
                                    // processed_at: pour APPROVED/REJECTED, on connaît la date par justified_at (proxy)
                                    'processed_at' => in_array($status, [
                                        \App\Enums\JustificationStatus::APPROVED->value,
                                        \App\Enums\JustificationStatus::REJECTED->value,
                                    ], true) ? $row->justified_at : null,
                                    'commentaire' => $cleanedCommentaire !== '' ? $cleanedCommentaire : null,
                                ]);
                            $affected++;
                        }
                    }
                });

            Log::info("[justifications backfill] {$affected} rows updated");
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_attendances', 'processed_by_id')) {
                $table->dropForeign(['processed_by_id']);
                $table->dropColumn('processed_by_id');
            }
            if (Schema::hasColumn('esbtp_attendances', 'processed_at')) {
                $table->dropColumn('processed_at');
            }
            if (Schema::hasColumn('esbtp_attendances', 'admin_comment')) {
                $table->dropColumn('admin_comment');
            }
            if (Schema::hasColumn('esbtp_attendances', 'justification_status')) {
                $table->dropIndex(['justification_status']);
                $table->dropColumn('justification_status');
            }
        });
    }
};
