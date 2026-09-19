<?php

namespace Tests\Unit\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\CoverageTeacherContactResolver;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Services\BulletinInlineConfigurationService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Doublure du lecteur canonique : elle note la periode qu'on lui demande et
 * rend le tableau qu'on lui a pose, sans toucher aux reglages ni a la base.
 */
class ConfigurationQuiNoteLaPeriode extends BulletinInlineConfigurationService
{
    public ?string $periodeDemandee = null;

    /** @param array<int|string, string> $template */
    public function __construct(private array $template = [])
    {
        // Pas d'appel au parent : ce double ne lit rien.
    }

    public function loadProfesseursTemplate(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $this->periodeDemandee = $periode;

        return $this->template;
    }
}

/**
 * Le repli sur les noms de professeurs saisis par l'ecole.
 *
 * Cas fondateur, ESBTP Abidjan, classe 46 (2BTS GBAT B) : aucune planification
 * academique pour ce couple filiere x niveau, donc les onze matieres
 * affichaient « aucun enseignant » — alors que le reglage
 * `bulletin_professeurs_template.46.4.semestre2` portait DIX-SEPT noms, dont
 * « M TOURE » pour Pathologie (matiere 31).
 *
 * Une premiere version lisait `esbtp_bulletins.professeurs` en direct. Ce
 * champ n'est renseigne qu'a la GENERATION d'un bulletin, et l'ecole consulte
 * ce bandeau AVANT de generer : il etait vide. D'ou le passage par le lecteur
 * canonique, qui regarde le reglage d'abord.
 */
class CoverageTeacherContactResolverTest extends TestCase
{
    private function matieres(int ...$ids): Collection
    {
        return new Collection(array_map(function (int $id): ESBTPMatiere {
            $m = new ESBTPMatiere();
            $m->forceFill(['id' => $id]);

            return $m;
        }, $ids));
    }

    private function classe(int $id = 46): ESBTPClasse
    {
        $c = new ESBTPClasse();
        $c->forceFill(['id' => $id]);

        return $c;
    }

    private function completer(array $carte, array $template, ?int $semestre, array $ids, ?ConfigurationQuiNoteLaPeriode $double = null): array
    {
        $double ??= new ConfigurationQuiNoteLaPeriode($template);
        $resolveur = new CoverageTeacherContactResolver($double);
        $m = new ReflectionMethod(CoverageTeacherContactResolver::class, 'completerParLaConfiguration');
        $m->setAccessible(true);

        return $m->invoke($resolveur, $carte, $this->classe(), 4, $semestre, $this->matieres(...$ids));
    }

    public function test_le_nom_saisi_par_l_ecole_comble_une_matiere_sans_planning(): void
    {
        $carte = $this->completer([31 => null], [31 => 'M TOURE'], 2, [31]);

        $this->assertSame('M TOURE', $carte[31]['name']);
        $this->assertSame('bulletin', $carte[31]['source']);
        $this->assertNull($carte[31]['phone'], "La configuration ne stocke qu'un nom.");
        $this->assertNull($carte[31]['id']);
    }

    public function test_un_contact_venu_du_planning_n_est_jamais_ecrase(): void
    {
        $duPlanning = ['id' => 5, 'name' => 'Mme DIALLO', 'phone' => '+22507000000', 'source' => 'planning'];

        $carte = $this->completer([31 => $duPlanning], [31 => 'M TOURE'], 2, [31]);

        $this->assertSame($duPlanning, $carte[31], 'Le planning porte un telephone : il prime.');
    }

    /**
     * Une cle ecrite en chaine dans le JSON arrive en ENTIER : `json_decode`
     * rend `{"31":…}` sous la cle `31`, et PHP normalise de toute facon toute
     * cle numerique a la construction du tableau. Ce test le FIGE, au lieu de
     * pretendre couvrir une branche qui n'existe pas : la version precedente
     * portait un repli `$template[(string) $matiereId]` inatteignable, et ce
     * test passait sans jamais l'emprunter.
     */
    public function test_une_cle_numerique_ecrite_en_chaine_arrive_en_entier(): void
    {
        $reglage = json_decode('{"31":"M TOURE"}', true);

        $this->assertSame([31], array_keys($reglage), 'json_decode rend une cle entiere.');

        $carte = $this->completer([31 => null], $reglage, 2, [31]);

        $this->assertSame('M TOURE', $carte[31]['name']);
    }

    public function test_un_nom_vide_ou_blanc_ne_comble_rien(): void
    {
        $this->assertNull($this->completer([31 => null], [31 => '   '], 2, [31])[31]);
        $this->assertNull($this->completer([31 => null], [31 => ''], 2, [31])[31]);
        $this->assertNull($this->completer([31 => null], [], 2, [31])[31]);
    }

    public function test_la_periode_demandee_suit_le_semestre_du_bandeau(): void
    {
        $double = new ConfigurationQuiNoteLaPeriode([]);
        $this->completer([31 => null], [], 2, [31], $double);
        $this->assertSame('semestre2', $double->periodeDemandee);

        $double = new ConfigurationQuiNoteLaPeriode([]);
        $this->completer([31 => null], [], 1, [31], $double);
        $this->assertSame('semestre1', $double->periodeDemandee);
    }

    public function test_en_periode_annuelle_le_lecteur_canonique_tranche(): void
    {
        // `loadProfesseursTemplate('annuel')` essaie semestre1 puis semestre2 et
        // rend le premier non vide : c'est lui qui decide, pas ce service.
        $double = new ConfigurationQuiNoteLaPeriode([31 => 'M TOURE']);
        $carte = $this->completer([31 => null], [], null, [31], $double);

        $this->assertSame('annuel', $double->periodeDemandee);
        $this->assertSame('M TOURE', $carte[31]['name']);
    }

    public function test_aucune_lecture_quand_tout_est_deja_nomme(): void
    {
        $double = new ConfigurationQuiNoteLaPeriode([31 => 'M TOURE']);
        $duPlanning = ['id' => 5, 'name' => 'Mme DIALLO', 'phone' => null, 'source' => 'planning'];

        $this->completer([31 => $duPlanning], [], 2, [31], $double);

        $this->assertNull($double->periodeDemandee, 'Rien a combler : pas de lecture inutile.');
    }
}
