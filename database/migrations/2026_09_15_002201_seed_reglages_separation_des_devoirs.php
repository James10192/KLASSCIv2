<?php

use App\Services\Security\SeparationOfDutiesService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Les trois reglages de separation des devoirs, crees en base.
 *
 * `SeparationOfDutiesService` les LIT deja (`SettingsHelper::get()`), et le
 * service se replie proprement sur la valeur d'usine quand ils n'existent pas.
 * Mais la boucle d'enregistrement de l'ecran des reglages, elle, ignore en
 * SILENCE toute cle absente de la table : sans ces lignes, les trois nouvelles
 * cases s'afficheraient, se cocheraient, et ne s'enregistreraient jamais.
 *
 * Meme panne que pour les reglages de composition de la moyenne : les defauts
 * declares dans le code n'atteignent aucune instance en service.
 *
 * Additive et rejouable : une valeur deja posee par l'ecole n'est jamais
 * ecrasee. Le defaut reprend `config/sod.php`, c'est-a-dire le comportement
 * actuel — les trois regles restent actives tant que l'ecole ne demande rien.
 */
return new class extends Migration
{
    /**
     * Les trois cles, GELEES ici plutot que relues depuis `config/sod.php`.
     *
     * Une migration decrit un etat fige du passe : elle ne doit pas suivre
     * l'evolution des constantes. Lire la configuration au moment du retour en
     * arriere ferait supprimer a `down()` une quatrieme regle ajoutee plus tard
     * et semee par une AUTRE migration — un effacement qu'`up()` n'a jamais
     * ecrit. Le controle qui garantit qu'aucune regle ne reste sans migration
     * vit dans `SeparationOfDutiesExpositionTest`, pas ici.
     */
    private const CLES = [
        'lmd.sod.jury_publish_requires_distinct_pv_issuer',
        'lmd.sod.pv_rectification_requires_distinct_issuer',
        'lmd.sod.pv_reissue_after_publication_requires_distinct_publisher',
    ];

    public function up(): void
    {
        // Le premier utilisateur existant, et null sur une base vide : ecrire
        // « 1 » en dur casse la contrainte de cle etrangere des que la table
        // users est vide, et avec elle toute la suite de tests.
        $auteur = DB::table('users')->min('id');
        $maintenant = now();

        $definitions = collect(SeparationOfDutiesService::reglesExposables())->keyBy('cle');

        foreach (self::CLES as $cle) {
            if (DB::table('settings')->where('key', $cle)->exists()) {
                continue;
            }

            $regle = $definitions->get($cle);

            if ($regle === null) {
                // La cle gelee n'est plus declaree : semer une ligne avec la cle
                // technique en description, que plus rien ne lit, serait un
                // repli muet de plus. On passe, et on le dit.
                Log::warning('Reglage de separation des devoirs non seme : la regle a disparu de la configuration', [
                    'cle' => $cle,
                ]);

                continue;
            }

            $valeur = $regle['defaut'] ? '1' : '0';

            DB::table('settings')->insert([
                'key' => $cle,
                'value' => $valeur,
                'type' => 'boolean',
                'description' => $regle['label'].' — '.$regle['hint'],
                'default_value' => $valeur,
                'is_required' => false,
                'is_active' => true,
                'requires_restart' => false,
                'group' => 'lmd',
                'created_by' => $auteur,
                'updated_by' => $auteur,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', self::CLES)
            ->delete();
    }
};
