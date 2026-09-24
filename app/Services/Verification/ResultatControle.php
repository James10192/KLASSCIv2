<?php

namespace App\Services\Verification;

final class ResultatControle
{
    private function __construct(
        public readonly bool $verifie,
        public readonly ?string $type = null,
        public readonly ?string $motif = null,
    ) {}

    public static function verifiee(string $type): self
    {
        return new self(true, $type);
    }

    public static function refus(string $motif): self
    {
        return new self(false, null, $motif);
    }

    /** @return array{verifie: bool, type?: string, motif?: string} */
    public function corps(): array
    {
        return $this->verifie
            ? ['verifie' => true, 'type' => (string) $this->type]
            : ['verifie' => false, 'motif' => (string) $this->motif];
    }

    public function statutHttp(): int
    {
        return match (true) {
            $this->verifie => 200,
            $this->motif === ControleVerification::INDISPONIBLE => 503,
            default => 422,
        };
    }
}
