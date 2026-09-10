<?php

use App\Enums\StatutReservationRdv;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_rdv_creneaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_universitaire_id')
                ->constrained('esbtp_annee_universitaires')
                ->restrictOnDelete();
            $table->date('date');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->unsignedSmallInteger('capacite');
            $table->boolean('ouvert')->default(true);
            $table->timestamps();

            $table->unique(
                ['annee_universitaire_id', 'date', 'heure_debut'],
                'unique_rdv_creneau_debut'
            );
            $table->index(['date', 'ouvert'], 'idx_rdv_creneaux_date_ouvert');
        });

        Schema::create('esbtp_rdv_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creneau_id')
                ->constrained('esbtp_rdv_creneaux')
                ->restrictOnDelete();
            $table->foreignId('candidature_id')->nullable()
                ->constrained('esbtp_candidatures')
                ->restrictOnDelete();
            $table->foreignId('reinscription_demande_id')->nullable()
                ->constrained('esbtp_reinscription_demandes')
                ->restrictOnDelete();
            $table->string('statut', 20);
            $table->string('nom', 100);
            $table->string('prenoms', 150);
            $table->string('telephone', 30);
            $table->date('date_naissance');
            $table->string('email', 150)->nullable();
            $table->timestamp('libere_at')->nullable();
            $table->timestamps();

            $table->index(['creneau_id', 'statut'], 'idx_rdv_reservations_creneau_statut');
        });

        $this->colonnesGenerees();
        $this->gardes();
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_rdv_reservations');
        Schema::dropIfExists('esbtp_rdv_creneaux');
    }

    private function colonnesGenerees(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $occupants = collect(StatutReservationRdv::valeursOccupantes())
            ->map(fn (string $v) => "'".$v."'")
            ->implode(', ');

        DB::statement("
            ALTER TABLE esbtp_rdv_reservations
            ADD COLUMN candidature_id_active BIGINT UNSIGNED
                GENERATED ALWAYS AS (
                    CASE WHEN statut IN ({$occupants}) THEN candidature_id ELSE NULL END
                ) STORED,
            ADD COLUMN reinscription_demande_id_active BIGINT UNSIGNED
                GENERATED ALWAYS AS (
                    CASE WHEN statut IN ({$occupants}) THEN reinscription_demande_id ELSE NULL END
                ) STORED,
            ADD UNIQUE KEY unique_rdv_candidature_active (candidature_id_active),
            ADD UNIQUE KEY unique_rdv_reinscription_active (reinscription_demande_id_active)
        ");
    }

    private function gardes(): void
    {
        if (! $this->moteurAppliqueLesCheck()) {
            return;
        }

        $statuts = collect(StatutReservationRdv::values())
            ->map(fn (string $v) => "'".$v."'")
            ->implode(', ');

        DB::statement("
            ALTER TABLE esbtp_rdv_reservations
            ADD CONSTRAINT esbtp_rdv_reservations_statut_connu
            CHECK (statut IN ({$statuts}))
        ");

        DB::statement("
            ALTER TABLE esbtp_rdv_reservations
            ADD CONSTRAINT esbtp_rdv_reservations_une_source
            CHECK (
                (candidature_id IS NOT NULL AND reinscription_demande_id IS NULL)
                OR (candidature_id IS NULL AND reinscription_demande_id IS NOT NULL)
            )
        ");
    }

    private function moteurAppliqueLesCheck(): bool
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        $version = (string) DB::selectOne('SELECT VERSION() AS v')->v;

        if (stripos($version, 'mariadb') !== false) {
            return version_compare($this->numeroDeVersion($version), '10.2.0', '>=');
        }

        return version_compare($this->numeroDeVersion($version), '8.0.16', '>=');
    }

    private function numeroDeVersion(string $version): string
    {
        preg_match('/(\d+\.\d+\.\d+)/', $version, $trouve);

        return $trouve[1] ?? '0.0.0';
    }
};
