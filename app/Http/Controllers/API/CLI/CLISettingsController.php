<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lire les reglages d'un etablissement, a distance.
 *
 * Un reglage decide de choses qu'on ne peut pas deviner depuis le code : la
 * couleur d'un fond de document, un seuil, un bareme. Quand un rendu est faux
 * chez une ecole et juste chez une autre, la difference est la — et sans moyen
 * de la lire, on corrige a l'aveugle.
 *
 * LECTURE SEULE, et deliberement. Un endpoint SQL generique aurait repondu a la
 * meme question, mais il serait parti sur TOUS les tenants, y compris ceux qui
 * portent plus de deux mille etudiants : une requete libre par HTTP y expose
 * l'etat civil, les paiements, les identifiants. Le prix ne vaut pas la
 * commodite.
 *
 * Les valeurs qui ressemblent a un secret sont masquees : un reglage n'est pas
 * cense en contenir, mais une cle d'API mal rangee ne doit pas sortir d'ici pour
 * autant.
 */
class CLISettingsController extends BaseApiController
{
    /**
     * Motifs de cles dont la valeur ne sort jamais en clair.
     */
    private const SENSIBLES = ['token', 'secret', 'password', 'mot_de_passe', 'api_key', 'apikey', 'cle_api'];

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $valide = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'group' => ['nullable', 'string', 'max:100'],
        ]);

        $reglages = Setting::query()
            ->when($valide['group'] ?? null, fn ($q, $g) => $q->where('group', $g))
            ->when(
                $valide['search'] ?? null,
                fn ($q, $s) => $q->where(fn ($w) => $w->where('key', 'like', "%{$s}%")
                    ->orWhere('group', 'like', "%{$s}%"))
            )
            ->orderBy('group')
            ->orderBy('key')
            ->get(['key', 'value', 'group', 'type']);

        $lignes = $reglages->map(fn (Setting $r) => [
            'key' => $r->key,
            'group' => $r->group,
            'type' => $r->type,
            'value' => $this->estSensible($r->key) ? '(masque)' : $r->value,
        ])->all();

        return $this->successResponse(
            ['total' => count($lignes), 'reglages' => $lignes],
            sprintf('%d reglage(s).', count($lignes))
        );
    }

    private function estSensible(string $cle): bool
    {
        $cle = mb_strtolower($cle);

        foreach (self::SENSIBLES as $motif) {
            if (str_contains($cle, $motif)) {
                return true;
            }
        }

        return false;
    }
}
