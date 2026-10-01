<?php

namespace App\Domain\Assistant\Outils;

use App\Domain\Reglages\ImageDeReglage;
use App\Domain\Reglages\ModificationDeReglages;
use App\Models\Setting;
use App\Services\Chatbot\Tools\ChatbotTool;

/**
 * Lit des réglages de l'établissement par clé ou par mot-clé, avec leur valeur
 * actuelle et si Nanan peut les changer (et sinon, pourquoi). Un secret n'en
 * sort jamais en clair (ModificationDeReglages::estSensible).
 */
class LireReglages extends ChatbotTool
{
    private const MAX = 25;

    public function name(): string
    {
        return 'lire_reglages';
    }

    public function description(): string
    {
        return "Lit des réglages de l'établissement (nom, adresse, directeur, couleurs PDF, bulletin, seuils LMD, inscriptions en ligne, téléphone…) : "
            .'clé exacte, valeur actuelle, type, et si proposer_modification_reglages peut la changer. Cherche par `cles` exactes ou par `recherche` (mot-clé dans la clé ou la description).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cles' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Clés exactes.'],
                'recherche' => ['type' => 'string', 'description' => 'Mot-clé (ex. « logo », « pdf_header », « directeur »).'],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $cles = array_values(array_filter(array_map(fn ($c) => trim((string) $c), (array) ($args['cles'] ?? []))));
        $recherche = trim((string) ($args['recherche'] ?? ''));
        if ($cles === [] && $recherche === '') {
            return ['error' => 'Donne des clés ou un mot-clé à chercher.'];
        }

        $reglages = Setting::query()
            ->when($cles !== [], fn ($q) => $q->whereIn('key', $cles))
            ->when($recherche !== '', fn ($q) => $q->where(fn ($w) => $w->where('key', 'like', "%{$recherche}%")->orWhere('description', 'like', "%{$recherche}%")))
            ->orderBy('key')->limit(self::MAX + 1)->get();

        $modification = app(ModificationDeReglages::class);
        $images = app(ImageDeReglage::class);
        $lignes = $reglages->take(self::MAX)->map(function (Setting $r) use ($modification, $images) {
            $estImage = $images->refusCle($r->key) === null;
            $refus = $estImage ? null : $modification->refusPourNanan($r->key, $r);

            return [
                'cle' => $r->key,
                'libelle' => (string) ($r->description ?: $r->key),
                'type' => $r->type,
                'valeur' => ModificationDeReglages::estSensible($r->key) ? '(masquée)' : mb_strimwidth((string) $r->value, 0, 200, '…'),
                'modifiable_par_nanan' => $estImage ? 'oui, par proposer_image_reglage' : ($refus === null ? 'oui' : 'non'),
                'raison' => $refus,
            ];
        })->values()->all();

        return [
            'results' => $lignes,
            'count' => count($lignes),
            'total' => $reglages->count(),
            'display_type' => 'table',
            'diagnostic' => [
                'introuvables' => array_values(array_diff($cles, $reglages->pluck('key')->all())),
                'plus_de_resultats' => $reglages->count() > self::MAX,
            ],
        ];
    }
}
