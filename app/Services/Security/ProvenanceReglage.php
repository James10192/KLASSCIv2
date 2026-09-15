<?php

namespace App\Services\Security;

use App\Helpers\SettingsHelper;
use App\Services\LMD\Tpe\TpePlanification;

final class ProvenanceReglage
{
    /** @return array{cle: string, libelle: string, valeur: string, niveau: string, defaut: string, heritee: bool, phrase: string} */
    public function de(string $cle): array
    {
        return match ($cle) {
            'tpe.mode' => $this->tpe(),
            'permissions.superadmin_gate_before' => $this->gateBefore(),
            default => [
                'cle' => $cle,
                'libelle' => $cle,
                'valeur' => '',
                'niveau' => 'inconnu',
                'defaut' => '',
                'heritee' => false,
                'phrase' => 'Réglage non catalogué.',
            ],
        };
    }

    /** @return array<int, array{cle: string, libelle: string, valeur: string, niveau: string, defaut: string, heritee: bool, phrase: string}> */
    public function catalogue(): array
    {
        return [$this->de('tpe.mode'), $this->de('permissions.superadmin_gate_before')];
    }

    /** @return array{cle: string, libelle: string, valeur: string, niveau: string, defaut: string, heritee: bool, phrase: string} */
    private function tpe(): array
    {
        $valeur = TpePlanification::mode();
        $defaut = TpePlanification::MODE_NON_PLANIFIABLE;
        $enBase = (string) SettingsHelper::get('tpe.mode', '') !== '';

        return [
            'cle' => 'tpe.mode',
            'libelle' => 'TPE à l\'emploi du temps',
            'valeur' => $valeur === $defaut ? 'Non planifiable' : 'Séance encadrée sur site',
            'niveau' => 'établissement',
            'defaut' => 'Non planifiable',
            'heritee' => ! $enBase,
            'phrase' => $enBase
                ? 'Valeur de cet établissement. Une séance TPE n\'est jamais une heure enseignante payable.'
                : 'Aucune valeur enregistrée : le défaut du produit s\'applique (non planifiable).',
        ];
    }

    /** @return array{cle: string, libelle: string, valeur: string, niveau: string, defaut: string, heritee: bool, phrase: string} */
    private function gateBefore(): array
    {
        $actif = (bool) config('permissions.superadmin_gate_before', true);

        return [
            'cle' => 'permissions.superadmin_gate_before',
            'libelle' => 'Court-circuit Super Admin',
            'valeur' => $actif ? 'Actif (tout est permis)' : 'Coupé',
            'niveau' => 'instance (.env)',
            'defaut' => 'Actif',
            'heritee' => $actif,
            'phrase' => $actif
                ? 'Le Super Admin contourne toutes les permissions. Pour UCAO, posez PERMISSIONS_SUPERADMIN_GATE_BEFORE=false.'
                : 'Le Super Admin n\'a plus le joker. Chaque droit s\'accorde un par un.',
        ];
    }
}
