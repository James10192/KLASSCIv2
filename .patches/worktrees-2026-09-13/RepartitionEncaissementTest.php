<?php

namespace Tests\Unit\Paiements;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\Frais\RepartitionEncaissement;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Ou va l'argent d'un versement.
 *
 * L'ecran d'encaissement borne la saisie, mais un garde-fou qui ne vit que dans
 * le navigateur ne protege rien : rien n'empeche un appel direct sur la route.
 * Ces tests fixent ce que le SERVEUR accepte, refuse, et calcule tout seul.
 *
 * Sans base : `restes()` est la seule porte vers les donnees, et elle est
 * remplacee ici par ce que l'inscription reclamerait. On boote quand meme
 * l'application — un refus passe par ValidationException, qui construit un
 * validateur via les facades — mais aucun test n'ouvre de connexion.
 */
class RepartitionEncaissementTest extends TestCase
{
    /**
     * Une inscription qui reclame 300 000 F d'inscription puis 500 000 F de
     * scolarite, dans cet ordre — celui de l'ecole.
     */
    private function servicePourDeuxFrais(): RepartitionEncaissement
    {
        return $this->serviceAvec([
            $this->frais(1, 'Frais d\'inscription', du: 300000, paye: 0),
            $this->frais(2, 'Scolarite', du: 500000, paye: 0),
        ]);
    }

    private function serviceAvec(array $frais): RepartitionEncaissement
    {
        return new class($frais) extends RepartitionEncaissement
        {
            public function __construct(private array $frais) {}

            public function restes(ESBTPInscription $inscription): Collection
            {
                return collect($this->frais);
            }
        };
    }

    private function frais(int $id, string $nom, float $du, float $paye): array
    {
        return [
            'frais_category_id' => $id,
            'nom' => $nom,
            'du' => $du,
            'paye' => $paye,
            'reste' => max(0, $du - $paye),
            'depose_en_nature' => false,
            'montant_non_defini' => $du <= 0,
        ];
    }

    /** @return array<int, float> */
    private function parCategorie(array $lignes): array
    {
        $sortie = [];

        foreach ($lignes as $ligne) {
            $sortie[$ligne['frais_category_id']] = $ligne['montant'];
        }

        return $sortie;
    }

    public function test_la_repartition_automatique_sert_les_frais_dans_l_ordre_de_l_ecole(): void
    {
        $service = new RepartitionEncaissement;

        $allocations = $service->repartirAutomatiquement([1 => 300000.0, 2 => 500000.0], 400000);

        // Le premier frais est solde, le second recoit ce qui reste.
        $this->assertSame([1 => 300000.0, 2 => 100000.0], $allocations);
    }

    public function test_la_repartition_automatique_ne_donne_jamais_a_un_frais_plus_qu_il_ne_reclame(): void
    {
        $service = new RepartitionEncaissement;

        $allocations = $service->repartirAutomatiquement([1 => 300000.0, 2 => 500000.0], 250000);

        $this->assertSame([1 => 250000.0], $allocations);
    }

    public function test_le_frais_designe_est_servi_avant_les_autres(): void
    {
        $service = new RepartitionEncaissement;

        $allocations = $service->repartirAutomatiquement([1 => 300000.0, 2 => 500000.0], 500000, 2);

        // La scolarite passe devant l'inscription parce que le caissier l'a
        // designee : c'est l'intention explicite du versement.
        $this->assertSame([2 => 500000.0], $allocations);
    }

    public function test_le_surplus_reste_en_avance_sur_le_frais_designe_quand_tout_est_solde(): void
    {
        $service = new RepartitionEncaissement;

        $allocations = $service->repartirAutomatiquement([1 => 100000.0, 2 => 50000.0], 200000, 2);

        // 150 000 couvrent les deux dettes, les 50 000 restants sont une avance
        // et se posent sur le frais designe.
        $this->assertSame([2 => 100000.0, 1 => 100000.0], $allocations);
        $this->assertSame(200000.0, array_sum($allocations));
    }

    public function test_une_repartition_qui_depasse_le_reste_d_un_frais_est_refusee(): void
    {
        $service = $this->servicePourDeuxFrais();

        try {
            $service->valider(new ESBTPInscription, 400000, [
                ['frais_category_id' => 1, 'montant' => 400000],
            ]);

            $this->fail('Un frais a recu plus que son du sans que le serveur ne bronche.');
        } catch (ValidationException $e) {
            $message = $e->validator->errors()->first('allocations');

            // Le refus doit nommer le frais et chiffrer le debordement, sinon le
            // caissier ne sait pas quoi corriger.
            $this->assertStringContainsString('Frais d\'inscription', $message);
            $this->assertStringContainsString('100 000', $message);
        }
    }

    public function test_une_repartition_qui_depasse_le_montant_encaisse_est_refusee(): void
    {
        $service = $this->servicePourDeuxFrais();

        $this->expectException(ValidationException::class);

        $service->valider(new ESBTPInscription, 300000, [
            ['frais_category_id' => 1, 'montant' => 300000],
            ['frais_category_id' => 2, 'montant' => 100000],
        ]);
    }

    public function test_un_reliquat_non_affecte_est_refuse_tant_qu_il_reste_des_dettes(): void
    {
        $service = $this->servicePourDeuxFrais();

        try {
            $service->valider(new ESBTPInscription, 400000, [
                ['frais_category_id' => 1, 'montant' => 300000],
            ]);

            $this->fail('100 000 F sont partis nulle part sans que le serveur ne bronche.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString(
                '100 000',
                $e->validator->errors()->first('allocations')
            );
        }
    }

    public function test_le_surplus_devient_une_avance_lorsque_tous_les_frais_sont_soldes(): void
    {
        $service = $this->serviceAvec([
            $this->frais(1, 'Frais d\'inscription', du: 100000, paye: 0),
            $this->frais(2, 'Scolarite', du: 50000, paye: 0),
        ]);

        $lignes = $service->valider(new ESBTPInscription, 200000, [
            ['frais_category_id' => 1, 'montant' => 100000],
            ['frais_category_id' => 2, 'montant' => 50000],
        ], 2);

        $parCategorie = $this->parCategorie($lignes);

        // Rien n'est perdu : les 50 000 F de trop sont poses sur le frais
        // designe, pas ecretes.
        $this->assertSame(200000.0, array_sum($parCategorie));
        $this->assertSame(100000.0, $parCategorie[1]);
        $this->assertSame(100000.0, $parCategorie[2]);
    }

    public function test_le_serveur_repartit_lui_meme_quand_le_navigateur_n_annonce_rien(): void
    {
        $service = $this->servicePourDeuxFrais();

        $lignes = $service->valider(new ESBTPInscription, 400000, null);

        // Un appel muet ne doit pas retomber sur l'ancien comportement, celui
        // qui collait tout sur un seul frais et rendait le trop-percu invisible.
        $this->assertSame([1 => 300000.0, 2 => 100000.0], $this->parCategorie($lignes));
    }

    public function test_deux_lignes_pour_le_meme_frais_sont_refusees(): void
    {
        $service = $this->servicePourDeuxFrais();

        $this->expectException(ValidationException::class);

        $service->valider(new ESBTPInscription, 300000, [
            ['frais_category_id' => 1, 'montant' => 200000],
            ['frais_category_id' => 1, 'montant' => 100000],
        ]);
    }

    public function test_les_lignes_a_zero_sont_ignorees(): void
    {
        $service = $this->servicePourDeuxFrais();

        $lignes = $service->valider(new ESBTPInscription, 300000, [
            ['frais_category_id' => 1, 'montant' => 300000],
            ['frais_category_id' => 2, 'montant' => 0],
        ]);

        $this->assertSame([1 => 300000.0], $this->parCategorie($lignes));
    }

    public function test_le_frais_qui_recoit_la_plus_grosse_part_porte_le_paiement(): void
    {
        $service = new RepartitionEncaissement;

        $dominante = $service->categorieDominante([
            ['frais_category_id' => 1, 'montant' => 100000.0],
            ['frais_category_id' => 2, 'montant' => 250000.0],
        ]);

        $this->assertSame(2, $dominante);
    }

    public function test_sans_repartition_le_paiement_garde_la_categorie_annoncee(): void
    {
        $service = new RepartitionEncaissement;

        $this->assertSame(7, $service->categorieDominante([], 7));
    }

    public function test_un_versement_mono_frais_n_ecrit_aucune_allocation(): void
    {
        $service = new RepartitionEncaissement;

        $paiement = new ESBTPPaiement(['frais_category_id' => 3, 'montant' => 75000]);

        // La repartition ne dit rien de plus que le paiement : l'ecrire
        // afficherait « 1 frais » partout au lieu du nom du frais.
        $ecrites = $service->enregistrer($paiement, [
            ['frais_category_id' => 3, 'montant' => 75000.0],
        ]);

        $this->assertSame(0, $ecrites);
    }

    public function test_deux_repartitions_differentes_du_meme_montant_ont_des_empreintes_distinctes(): void
    {
        $service = new RepartitionEncaissement;

        $premiere = $service->empreinte([
            ['frais_category_id' => 1, 'montant' => 200000.0],
            ['frais_category_id' => 2, 'montant' => 100000.0],
        ]);

        $seconde = $service->empreinte([
            ['frais_category_id' => 1, 'montant' => 100000.0],
            ['frais_category_id' => 2, 'montant' => 200000.0],
        ]);

        // Sans cette distinction, la protection anti-doublon absorberait le
        // second versement et l'argent disparaitrait.
        $this->assertNotSame($premiere, $seconde);
    }

    public function test_l_empreinte_ignore_l_ordre_des_lignes(): void
    {
        $service = new RepartitionEncaissement;

        $this->assertSame(
            $service->empreinte([
                ['frais_category_id' => 1, 'montant' => 200000.0],
                ['frais_category_id' => 2, 'montant' => 100000.0],
            ]),
            $service->empreinte([
                ['frais_category_id' => 2, 'montant' => 100000.0],
                ['frais_category_id' => 1, 'montant' => 200000.0],
            ])
        );
    }
}
