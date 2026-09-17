<?php

namespace Tests\Feature\Attendance;

use App\Models\ESBTPAttendance;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Les heures d'une absence sont des CHAÎNES, et le rester est un invariant.
 *
 * ## L'incident que ce test verrouille
 *
 * Le piège #14 (`.claude/rules/klassci-debugging-discipline.md`) dit qu'une
 * heure lue en contexte texte rend la DATE et non l'heure, et que le geste est
 * `->format('H:i')`. C'est vrai — **sur `ESBTPSeanceCours`**, qui porte un
 * accesseur `Carbon::parse()`.
 *
 * Le contrôle générique de cette rule ne regarde pas le modèle : il signale
 * toute heure mise en texte. Il a donc signalé deux lectures d'`ESBTPAttendance`
 * — le message d'absence et l'écran des absences de l'étudiant — qui étaient
 * justes. Les « corriger » en y posant `->format('H:i')` a produit un appel de
 * méthode sur une chaîne, donc une `Error` :
 *
 *   - dans la vue, elle tombait dans une boucle non gardée : **500 pour tout
 *     étudiant ayant une absence horodatée** ;
 *   - dans le service de notification, elle traversait le `catch (\Exception)`
 *     alentour sans être rattrapée, `Error` ne descendant pas d'`Exception`.
 *
 * ## Ce que ce test exécute, et ce qu'il ne couvre pas
 *
 * Il monte `esbtp_attendances` sur **SQLite en mémoire** (cet environnement n'a
 * pas de MySQL), hydrate un vrai modèle, et fait passer les DEUX expressions
 * réellement écrites dans le code par le vrai compilateur Blade.
 *
 * Il ne rend PAS la page complète : `mes-absences` étend le layout applicatif et
 * demande une session, des réglages d'instance et une inscription. Ce test
 * couvre l'expression qui plantait, pas le gabarit autour.
 */
class HeuresDAbsenceSontDesChainesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite_memoire');
        config()->set('database.connections.sqlite_memoire', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        Schema::create('esbtp_attendances', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->string('statut', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        ESBTPAttendance::query()->insert([
            'id' => 1,
            'date' => '2026-09-17',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'statut' => 'absent',
        ]);
    }

    /**
     * Compile un fragment Blade et l'exécute avec les variables fournies.
     *
     * `Blade::render()` ferait la même chose en passant par la fabrique de vues,
     * donc par les composeurs `View::composer('*', …)` de l'application — qui
     * lisent les réglages d'instance en base. Ici on veut éprouver le rendu de
     * l'heure, pas le gabarit applicatif.
     */
    private function rendreBlade(string $fragment, array $donnees): string
    {
        $php = Blade::compileString($fragment);

        $rendu = (static function () use ($php, $donnees) {
            extract($donnees, EXTR_SKIP);
            ob_start();
            eval('?>'.$php);

            return ob_get_clean();
        })();

        return trim($rendu);
    }

    /** @test */
    public function une_heure_d_absence_se_lit_comme_une_chaine_pas_comme_un_carbon(): void
    {
        $absence = ESBTPAttendance::findOrFail(1);

        // C'est CET invariant qui rend le code des deux écrans correct. Poser un
        // cast ou un accesseur sur ces colonnes casserait ce test AVANT de
        // casser la production — c'est tout l'intérêt.
        $this->assertIsString($absence->heure_debut);
        $this->assertSame('08:00:00', $absence->heure_debut);
        $this->assertIsString($absence->heure_fin);
    }

    /** @test */
    public function l_ecran_des_absences_affiche_l_heure_et_non_la_date(): void
    {
        $absence = ESBTPAttendance::findOrFail(1);

        // L'expression exacte de resources/views/esbtp/attendances/mes-absences.blade.php,
        // passée par le vrai compilateur Blade — mais SANS la fabrique de vues :
        // `Blade::render()` déclenche les composeurs globaux de l'application,
        // qui interrogent les réglages d'instance et n'ont rien à faire ici.
        $rendu = $this->rendreBlade(
            '{{ \Illuminate\Support\Str::limit((string) $abs->heure_debut, 5, \'\') }}'
            .'@if($abs->heure_fin) — {{ \Illuminate\Support\Str::limit((string) $abs->heure_fin, 5, \'\') }}@endif',
            ['abs' => $absence]
        );

        $this->assertSame('08:00 — 10:00', $rendu);
        $this->assertStringNotContainsString('2026-', $rendu);
    }

    /** @test */
    public function le_message_d_absence_affiche_l_heure_et_non_la_date(): void
    {
        $absence = ESBTPAttendance::findOrFail(1);

        // L'interpolation exacte de NotificationService::notifyNewAbsence().
        $message = "Une absence a été enregistrée à {$absence->heure_debut}";

        $this->assertSame('Une absence a été enregistrée à 08:00:00', $message);
        $this->assertStringNotContainsString('2026-', $message);
    }

    /** @test */
    public function y_poser_format_leve_une_error_que_catch_exception_ne_rattrape_pas(): void
    {
        $absence = ESBTPAttendance::findOrFail(1);

        // Ce test ne protège pas un comportement : il FIGE la raison pour
        // laquelle le geste du piège #14 est interdit ici. Sans lui, la rule
        // affirme et le code obéit ; avec lui, la contrainte s'exécute.
        $rattrapeParException = false;

        try {
            try {
                $absence->heure_debut->format('H:i');
                $this->fail('Attendu : une Error sur un appel de méthode sur une chaîne.');
            } catch (\Exception $e) {
                $rattrapeParException = true;
            }
        } catch (\Error $e) {
            $this->assertStringContainsString('format()', $e->getMessage());
        }

        $this->assertFalse(
            $rattrapeParException,
            'Une Error ne descend pas d\'Exception : le catch alentour ne la voit pas.'
        );
    }
}
