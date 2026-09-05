<?php

namespace Tests\Unit\Services\LMD;

use App\Services\LMD\SuppressionUeService;
use PHPUnit\Framework\TestCase;

/**
 * Le refus de supprimer une unite partagee doit NOMMER les maquettes.
 *
 * Un refus qui ne dit pas ou aller retirer l'unite est un mur : la directrice
 * des etudes ne peut ni obeir ni contourner. Ces cas verifient la partie du
 * service qui se lit sans base — le libelle et le message.
 */
class SuppressionUeServiceMessagesTest extends TestCase
{
    public function test_le_message_de_refus_nomme_chaque_maquette(): void
    {
        $message = SuppressionUeService::formaterRefusPartage([
            'Batiment et Urbanisme (BU)',
            'Travaux Publics (TIR)',
        ]);

        $this->assertStringContainsString('Batiment et Urbanisme (BU)', $message);
        $this->assertStringContainsString('Travaux Publics (TIR)', $message);
        $this->assertStringContainsString('2 maquettes', $message);
        // La sortie doit dire ou agir, pas seulement interdire.
        $this->assertStringContainsString('Lier à des parcours', $message);
    }

    public function test_le_libelle_associe_le_nom_et_le_code(): void
    {
        $this->assertSame(
            'Travaux Publics (TIR)',
            SuppressionUeService::libelleParcours('Travaux Publics', 'TIR')
        );

        // Un parcours sans code reste identifiable par son nom.
        $this->assertSame('Travaux Publics', SuppressionUeService::libelleParcours('Travaux Publics', null));
        // Et un parcours sans nom par son code, plutot qu'une parenthese vide.
        $this->assertSame('TIR', SuppressionUeService::libelleParcours('', 'TIR'));
    }

    /**
     * Les reglages sont stockes en texte : un « 0 » ou un « false » venus de la
     * base doivent valoir non, et l'absence doit rendre le defaut — sans quoi
     * un deploiement changerait le comportement de toutes les instances.
     *
     * @dataProvider valeursDeReglage
     */
    public function test_interpretation_du_reglage(mixed $valeur, bool $defaut, bool $attendu): void
    {
        $this->assertSame($attendu, SuppressionUeService::interpreterBooleen($valeur, $defaut));
    }

    public static function valeursDeReglage(): array
    {
        return [
            'absent rend le defaut vrai' => [null, true, true],
            'absent rend le defaut faux' => [null, false, false],
            'chaine vide rend le defaut' => ['', true, true],
            'zero texte vaut non' => ['0', true, false],
            'false texte vaut non' => ['false', true, false],
            'non vaut non' => ['Non', true, false],
            'off vaut non' => ['off', true, false],
            'un texte vaut oui' => ['1', false, true],
            'true texte vaut oui' => ['true', false, true],
            'booleen conserve' => [false, true, false],
            'entier zero vaut non' => [0, true, false],
            'entier non nul vaut oui' => [1, false, true],
        ];
    }
}
