<?php

namespace Tests\Unit\Inscription;

use App\Services\Inscription\PortailCandidaturePublication;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * « Je viens quand ? » — la question que la scolarite entend le plus.
 *
 * Deposer une candidature en ligne ne finit rien : les pieces et le paiement
 * se remettent a l'etablissement. Le portail repond lui-meme, a partir d'une
 * date que l'ecole renseigne dans ses reglages, et il doit dire trois choses
 * differentes selon ou l'on se trouve par rapport a elle.
 *
 * Le jour meme compte comme ouvert : « a partir du 12 septembre » veut dire
 * toute la journee du 12, pas a partir de minuit une. C'est ce que l'ecole
 * annonce a ses candidats, et c'est ce qu'ils comprennent.
 */
class InscriptionsPhysiquesTest extends TestCase
{
    private function avecReglage(?string $valeur): array
    {
        // SettingsHelper lit son cache avant la base : y poser la valeur
        // permet d'exercer la decision sans base de donnees, qui est ici le
        // seul detail dont elle ne depend pas.
        Cache::put('setting_'.PortailCandidaturePublication::REGLAGE_PHYSIQUES, $valeur ?? '', 60);

        return app(PortailCandidaturePublication::class)->inscriptionsPhysiques();
    }

    public function test_sans_date_le_portail_n_annonce_rien(): void
    {
        $this->assertSame(
            ['debut' => null, 'ouvertes' => false],
            $this->avecReglage('')
        );
    }

    public function test_une_date_a_venir_est_annoncee_sans_ouvrir(): void
    {
        $dans_un_mois = now()->addMonth()->toDateString();

        $this->assertSame(
            ['debut' => $dans_un_mois, 'ouvertes' => false],
            $this->avecReglage($dans_un_mois)
        );
    }

    public function test_une_date_passee_ouvre_le_guichet(): void
    {
        $le_mois_dernier = now()->subMonth()->toDateString();

        $this->assertSame(
            ['debut' => $le_mois_dernier, 'ouvertes' => true],
            $this->avecReglage($le_mois_dernier)
        );
    }

    /**
     * Le cas limite qui compte : un candidat qui depose le matin du premier
     * jour doit lire « rendez-vous a l'etablissement », pas « ce sera pour
     * plus tard ».
     */
    public function test_le_jour_meme_le_guichet_est_ouvert(): void
    {
        $aujourdhui = now()->toDateString();

        $this->assertTrue($this->avecReglage($aujourdhui)['ouvertes']);
    }

    /**
     * Une date mal saisie ne doit pas casser le depot : le candidat a rempli
     * son formulaire, il a droit a sa confirmation. On retombe sur le message
     * neutre, qui reste vrai en toutes circonstances.
     */
    public function test_une_date_illisible_retombe_sur_le_message_neutre(): void
    {
        $this->assertSame(
            ['debut' => null, 'ouvertes' => false],
            $this->avecReglage('la semaine prochaine')
        );
    }

    /** Les espaces autour de la saisie ne changent rien. */
    public function test_la_saisie_est_nettoyee(): void
    {
        $demain = now()->addDay()->toDateString();

        $this->assertSame($demain, $this->avecReglage('  '.$demain.'  ')['debut']);
    }
}
