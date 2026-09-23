<?php

namespace App\Services\Emails;

use App\Enums\EtatEmail;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\RendezVous\ConvocationsRemises;
use App\Services\RendezVous\FamillesAPrevenirRdv;
use App\Services\Verification\MasqueContact;

/**
 * Le diagnostic `GET /api/cli/emails/diagnostic`. Contrat partage avec
 * klassci-cli : la forme ne change pas sans lui.
 *
 * Aucune adresse complete ne sort d'ici : des domaines et des comptes, et,
 * sur demande, au plus vingt exemples masques.
 */
class DiagnosticEmailsInstance
{
    private const EXEMPLES_MAX = 20;

    public function __construct(
        private readonly InventaireAdresses $inventaire,
        private readonly DiagnosticEmail $classement,
        private readonly ConvocationsRemises $convocations,
        private readonly FamillesAPrevenirRdv $familles,
    ) {}

    /** @return array<string, mixed> */
    public function rapport(bool $details = false): array
    {
        $rapport = [
            'tenant' => (string) config('app.tenant_code', ''),
            'genere_le' => now()->toIso8601String(),
            'adresses' => $this->adresses(),
            'convocations' => $this->convocations->comptes(),
            'familles_a_prevenir' => $this->familles->compter(),
            'demandes_non_verifiees' => ESBTPCandidature::sansFiltreVerification()->whereIn('verification_contact', StatutVerificationContact::valeursMasquees())->count()
                + ESBTPReinscriptionDemande::sansFiltreVerification()->whereIn('verification_contact', StatutVerificationContact::valeursMasquees())->count(),
        ];

        if ($details) {
            $rapport['exemples'] = $this->exemples();
        }

        return $rapport;
    }

    /** @return array<string, mixed> */
    private function adresses(): array
    {
        $comptes = ['valides' => 0, 'factices' => 0, 'fautes_de_frappe' => 0];
        $suspects = [];

        foreach ($this->inventaire->domaines() as $ligne) {
            $analyse = $this->classement->classerDomaine($ligne['domaine']);
            $type = $analyse->etat->typeSuspect();

            match ($analyse->etat) {
                EtatEmail::Valide, EtatEmail::FauteProbable => $comptes['valides'] += $ligne['nombre'],
                EtatEmail::Factice => $comptes['factices'] += $ligne['nombre'],
                EtatEmail::FauteDeFrappe => $comptes['fautes_de_frappe'] += $ligne['nombre'],
                default => null,
            };

            if ($type !== null) {
                $suspects[$ligne['domaine']] ??= ['domaine' => $ligne['domaine'], 'nombre' => 0, 'type' => $type, 'suggestion' => $analyse->domaineSuggere()];
                $suspects[$ligne['domaine']]['nombre'] += $ligne['nombre'];
            }
        }

        $totaux = $this->inventaire->comptes();
        usort($suspects, fn ($a, $b) => $b['nombre'] <=> $a['nombre']);

        return [
            'total' => $totaux['total'],
            'valides' => $comptes['valides'],
            'factices' => $comptes['factices'],
            'fautes_de_frappe' => $comptes['fautes_de_frappe'],
            'sans_email' => $totaux['sans_email'],
            'par_domaine_suspect' => array_values($suspects),
        ];
    }

    /** @return list<array{email_masque: string, table: string, motif: string}> */
    private function exemples(): array
    {
        $exemples = [];
        foreach ($this->inventaire->domaines() as $ligne) {
            $type = $this->classement->classerDomaine($ligne['domaine'])->etat->typeSuspect();
            if ($type === null) {
                continue;
            }
            $reste = self::EXEMPLES_MAX - count($exemples);
            if ($reste <= 0) {
                break;
            }
            foreach ($this->inventaire->lignes($ligne['table'], $ligne['colonne'], [$ligne['domaine']], min(3, $reste)) as $r) {
                $exemples[] = ['email_masque' => MasqueContact::email((string) $r->email), 'table' => $ligne['table'], 'motif' => $type];
            }
        }

        return array_slice($exemples, 0, self::EXEMPLES_MAX);
    }
}
