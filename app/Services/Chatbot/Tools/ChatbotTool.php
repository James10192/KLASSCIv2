<?php

namespace App\Services\Chatbot\Tools;

/**
 * Contrat de base pour les outils du chatbot IA.
 */
abstract class ChatbotTool
{
    abstract public function name(): string;
    abstract public function description(): string;

    /** @return array{type:string,properties:array,required?:array} */
    abstract public function parameters(): array;

    /** @param array $args @param \App\Models\User $user */
    abstract public function execute(array $args, $user): array;

    public function requiredPermissions(): array
    {
        return $this->toolConfig()['all_permissions'] ?? [];
    }

    public function isAvailableFor($user): bool
    {
        $config = $this->toolConfig();
        if (($config['enabled'] ?? false) !== true || ! $user) return false;

        $allPermissions = $config['all_permissions'] ?? [];
        $anyPermissions = $config['any_permissions'] ?? [];
        $allowedRoles = $config['allowed_roles'] ?? [];
        if ($allPermissions === [] && $anyPermissions === []) return false;

        if ($allowedRoles !== []) {
            if (! method_exists($user, 'hasRole') || ! $user->hasRole($allowedRoles)) return false;
        }

        return collect($allPermissions)->every(fn (string $permission) => $user->can($permission))
            && ($anyPermissions === [] || collect($anyPermissions)->contains(fn (string $permission) => $user->can($permission)));
    }

    public function executeAuthorized(array $args, $user): array
    {
        if (! $this->isAvailableFor($user)) return $this->unavailableResponse();
        return $this->execute($args, $user);
    }

    public function libelle(): string
    {
        return (string) ($this->toolConfig()['libelle'] ?? 'Consultation des données…');
    }

    public function suggestion(): ?string
    {
        $suggestion = $this->toolConfig()['suggestion'] ?? null;
        return is_string($suggestion) && $suggestion !== '' ? $suggestion : null;
    }

    protected function unavailableResponse(): array
    {
        return ['error' => 'Outil indisponible.'];
    }

    /** @return array<string,mixed> */
    protected function toolConfig(): array
    {
        $principal = config('chatbot.tools.' . $this->name());
        if (is_array($principal) && $principal !== []) return $principal;

        // Extension volontairement explicite : même principe opt-in que chatbot.php.
        // Un outil absent des DEUX registres reste fermé par défaut.
        $extension = config('assistant_tools_nanan.tools.' . $this->name(), []);
        return is_array($extension) ? $extension : [];
    }

    public function applyFuzzyNameSearch($query, string $search, string $nomCol = 'nom', string $prenomsCol = 'prenoms'): void
    {
        $terms = preg_split('/\s+/', trim($search));
        $query->where(function ($q) use ($search, $terms, $nomCol, $prenomsCol) {
            if ($nomCol === 'nom') $q->orWhere('matricule', trim($search));
            $q->orWhere($nomCol, 'like', "%{$search}%")
              ->orWhere($prenomsCol, 'like', "%{$search}%")
              ->orWhereRaw("CONCAT({$nomCol}, ' ', {$prenomsCol}) LIKE ?", ["%{$search}%"]);
            if (count($terms) > 1) {
                $q->orWhere(function ($sub) use ($terms, $nomCol, $prenomsCol) {
                    foreach ($terms as $term) {
                        $sub->where(function ($inner) use ($term, $nomCol, $prenomsCol) {
                            $inner->where($nomCol, 'like', "%{$term}%")->orWhere($prenomsCol, 'like', "%{$term}%");
                        });
                    }
                });
            }
            foreach ($terms as $term) {
                $q->orWhereRaw("SOUNDEX({$nomCol}) = SOUNDEX(?)", [$term])
                  ->orWhereRaw("SOUNDEX({$prenomsCol}) = SOUNDEX(?)", [$term]);
            }
        });
    }

    protected function studentFullName($etudiant, string $fallback = 'N/A'): string
    {
        return $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')) : $fallback;
    }

    protected function studentInitials($etudiant, string $fallback = '?'): string
    {
        if (!$etudiant) return $fallback;
        return mb_strtoupper(mb_substr($etudiant->nom ?? '', 0, 1) . mb_substr($etudiant->prenoms ?? '', 0, 1));
    }

    protected function applyClasseSearch($query, string $search, string $relation = 'classe'): void
    {
        $query->whereHas($relation, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
        });
    }

    protected function clampLimit(array $args, int $default = 10, int $max = 25): int
    {
        return min(max((int) ($args['limit'] ?? $default), 1), $max);
    }

    protected function formatFCFA(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }

    protected function mapPeriode(string $periode): ?string
    {
        return match (mb_strtoupper(trim($periode))) {
            'S1' => 'semestre1', 'S2' => 'semestre2', 'SEMESTRE1' => 'semestre1', 'SEMESTRE2' => 'semestre2', default => null,
        };
    }

    public function toToolDefinition(): array
    {
        $schema = $this->parameters();
        $schema['additionalProperties'] = false;
        return ['name' => $this->name(), 'description' => $this->description(), 'input_schema' => $schema];
    }
}
