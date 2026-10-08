<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('esbtp_frais_configurations', 'audience')) {
            Schema::table('esbtp_frais_configurations', function (Blueprint $table) {
                $table->string('audience', 40)->nullable()->after('amount_non_affecte');
            });
        }

        // Jusqu'ici l'audience vivait sur la catégorie : cocher « nouveaux »
        // dans UNE combinaison modifiait donc toutes les filières/niveaux qui
        // utilisaient cette catégorie. On fige la valeur effective actuelle sur
        // chaque configuration existante avant de rendre les choix indépendants.
        DB::table('esbtp_frais_configurations')
            ->whereNull('audience')
            ->orderBy('id')
            ->chunkById(250, function ($configurations): void {
                $categories = DB::table('esbtp_frais_categories')
                    ->whereIn('id', $configurations->pluck('frais_category_id')->unique()->values())
                    ->pluck('audience', 'id');

                foreach ($configurations as $configuration) {
                    $audience = $categories[$configuration->frais_category_id] ?? null;
                    if ($audience === null || trim((string) $audience) === '') {
                        $audience = 'tous';
                    } elseif (! in_array($audience, ['tous', 'nouveaux_etablissement', 'anciens_etablissement'], true)) {
                        throw new RuntimeException(sprintf(
                            'Audience de frais invalide « %s » sur la catégorie #%d (configuration #%d). Corrigez la donnée avant de relancer la migration.',
                            (string) $audience,
                            (int) $configuration->frais_category_id,
                            (int) $configuration->id,
                        ));
                    }

                    DB::table('esbtp_frais_configurations')
                        ->where('id', $configuration->id)
                        ->update(['audience' => $audience]);
                }
            });
    }

    public function down(): void
    {
        // Ne pas recopier les audiences de portées différentes vers la catégorie :
        // ce serait précisément recréer le bug corrigé. Le rollback retire
        // uniquement la colonne et laisse le catalogue inchangé.
        if (Schema::hasColumn('esbtp_frais_configurations', 'audience')) {
            Schema::table('esbtp_frais_configurations', function (Blueprint $table) {
                $table->dropColumn('audience');
            });
        }
    }
};
