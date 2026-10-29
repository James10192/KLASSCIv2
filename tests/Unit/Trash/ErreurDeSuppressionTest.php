<?php

namespace Tests\Unit\Trash;

use App\Domain\Trash\ErreurDeSuppression;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\TestCase;

/**
 * Une suppression refusée doit dire ce qui la retient, en français, sans livrer
 * le nom de la base ni celui des contraintes.
 */
class ErreurDeSuppressionTest extends TestCase
{
    private function violationCleEtrangere(string $table): QueryException
    {
        return new QueryException(
            'delete from `esbtp_inscriptions` where `id` = ?',
            [298],
            new \PDOException(
                'SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update '
                ."a parent row: a foreign key constraint fails (`c2569688c_islg-rostan`.`{$table}`, "
                ."CONSTRAINT `{$table}_inscription_id_foreign` FOREIGN KEY (`inscription_id`) "
                .'REFERENCES `esbtp_inscriptions` (`id`))'
            )
        );
    }

    /** @test */
    public function elle_nomme_la_table_qui_retient_en_francais(): void
    {
        $this->assertSame('des factures', ErreurDeSuppression::retenuePar($this->violationCleEtrangere('esbtp_factures')));
        $this->assertSame('des notes', ErreurDeSuppression::retenuePar($this->violationCleEtrangere('esbtp_notes')));
    }

    /** @test */
    public function une_table_inconnue_reste_vague_plutot_que_technique(): void
    {
        $this->assertSame(
            "d'autres données",
            ErreurDeSuppression::retenuePar($this->violationCleEtrangere('table_jamais_vue'))
        );
    }

    /** @test */
    public function le_message_ne_contient_aucun_fragment_sql(): void
    {
        $message = ErreurDeSuppression::messageLisible(
            $this->violationCleEtrangere('esbtp_factures'),
            'Cette inscription'
        );

        $this->assertStringStartsWith('Cette inscription ne peut pas être supprimée', $message);
        $this->assertStringContainsString('des factures', $message);

        foreach (['SQLSTATE', 'CONSTRAINT', 'foreign key', 'esbtp_factures', 'c2569688c'] as $fuite) {
            $this->assertStringNotContainsStringIgnoringCase($fuite, $message);
        }
    }

    /** @test */
    public function une_erreur_qui_n_est_pas_une_cle_etrangere_donne_un_message_technique_neutre(): void
    {
        $message = ErreurDeSuppression::messageLisible(
            new \RuntimeException('Connexion perdue vers 10.0.0.4:3306'),
            'Ce versement'
        );

        $this->assertStringContainsString('une erreur technique est survenue', $message);
        $this->assertStringNotContainsString('10.0.0.4', $message);
        $this->assertNull(ErreurDeSuppression::retenuePar(new \RuntimeException('boum')));
    }
}
