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

    /**
     * Corrige la valeur d'un reglage.
     *
     * Montre par defaut. N'ecrit que sur `apply`, et jamais sur une cle qui
     * evoque un secret : celles-la se changent depuis l'ecran de configuration,
     * ou l'ecole voit ce qu'elle fait.
     *
     * L'ancienne valeur est rendue dans la reponse ET journalisee : une couleur
     * remise a la main doit pouvoir etre remise en arriere sans deviner.
     */
    public function update(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'key' => ['required', 'string', 'max:190'],
            'value' => ['present', 'nullable', 'string', 'max:5000'],
            'apply' => ['nullable', 'boolean'],
        ]);

        if ($valide['key'] === 'mailpulse_api_key') {
            if (! ($valide['apply'] ?? false)) {
                return $this->errorResponse('mailpulse_api_key : passer apply=true pour ecrire. La valeur ne ressort jamais.', [], 422);
            }
            $cle = trim((string) ($valide['value'] ?? ''));
            if (! preg_match('/^mp_(live|test)_[A-Za-z0-9_-]+$/', $cle)) {
                return $this->errorResponse('Cle MailPulse invalide (mp_live_... ou mp_test_...).', [], 422);
            }
            \App\Helpers\SettingsHelper::setOrCreate('mailpulse_api_key', $cle, 'mailpulse', 'string');
            \Log::warning('[reglages] mailpulse_api_key ecrite a distance', ['length' => strlen($cle)]);

            return $this->successResponse(
                ['key' => 'mailpulse_api_key', 'value' => '(masque)', 'applique' => true],
                'Cle MailPulse enregistree.'
            );
        }

        if ($this->estSensible($valide['key'])) {
            return $this->errorResponse(
                "Cette cle evoque un secret : elle se change depuis l'ecran de configuration.",
                [],
                422
            );
        }

        $reglage = Setting::query()->where('key', $valide['key'])->first();

        if (! $reglage) {
            $creables = [
                \App\Services\TenantScolariteSettings::CLERK_LMD_ACCESS,
                \App\Services\TenantScolariteSettings::CLERK_PEDAGOGIE,
                \App\Services\TenantScolariteSettings::MANAGE_TEACHERS,
                \App\Services\TenantScolariteSettings::PRINT_REQUIRES_APPROVAL,
            ];
            if (! in_array($valide['key'], $creables, true) || ! ($valide['apply'] ?? false)) {
                return $this->errorResponse(sprintf("Reglage « %s » introuvable.", $valide['key']), [], 404);
            }

            \App\Helpers\SettingsHelper::setOrCreate($valide['key'], $valide['value'] ?? '0', 'scolarite', 'boolean');
            $reglage = Setting::query()->where('key', $valide['key'])->first();
        }

        $avant = $reglage->value;
        $apres = $valide['value'];
        $applique = (bool) ($valide['apply'] ?? false);

        if ((string) $avant === (string) $apres) {
            return $this->successResponse(
                ['key' => $reglage->key, 'avant' => $avant, 'apres' => $apres, 'applique' => false],
                "La valeur est deja celle-la. Rien a faire."
            );
        }

        if ($applique) {
            $reglage->value = $apres;
            $reglage->save();

            \Log::warning('[reglages] valeur modifiee a distance', [
                'key' => $reglage->key,
                'avant' => $avant,
                'apres' => $apres,
            ]);
        }

        return $this->successResponse(
            ['key' => $reglage->key, 'avant' => $avant, 'apres' => $apres, 'applique' => $applique],
            $applique
                ? sprintf("« %s » : %s -> %s", $reglage->key, $avant, $apres)
                : sprintf("« %s » passerait de %s a %s. Rien n'a ete ecrit.", $reglage->key, $avant, $apres)
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
