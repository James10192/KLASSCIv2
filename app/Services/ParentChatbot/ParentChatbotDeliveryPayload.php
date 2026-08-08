<?php

namespace App\Services\ParentChatbot;

use Illuminate\Support\Facades\Crypt;

/**
 * Encrypted outbox payload of a pending activation dispatch. It holds the
 * recipient, the operation to replay and the template variables, so a retry
 * never has to rebuild them from the parent record.
 */
final class ParentChatbotDeliveryPayload
{
    /** @param array<int, string> $parameters */
    private function __construct(
        public readonly string $phone,
        public readonly string $operationKey,
        public readonly array $parameters,
    ) {}

    /** @param array<int, string> $parameters */
    public static function encrypt(string $phone, string $operationKey, array $parameters): string
    {
        return Crypt::encryptString(json_encode([
            'phone' => $phone,
            'operation' => $operationKey,
            'parameters' => array_values($parameters),
        ], JSON_THROW_ON_ERROR));
    }

    public static function decrypt(?string $ciphertext): ?self
    {
        try {
            $decoded = json_decode(Crypt::decryptString((string) $ciphertext), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($decoded) || ! is_string($decoded['phone'] ?? null) || $decoded['phone'] === '') {
            return null;
        }

        $parameters = self::parameters($decoded);
        if ($parameters === null) {
            return null;
        }

        $operationKey = $decoded['operation'] ?? null;

        return new self(
            $decoded['phone'],
            is_string($operationKey) && $operationKey !== '' ? $operationKey : ParentChatbotDispatcher::OPERATION_LINK_CODE,
            $parameters,
        );
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<int, string>|null
     */
    private static function parameters(array $decoded): ?array
    {
        // Payloads written before the invitation rail only carried the code.
        $parameters = is_array($decoded['parameters'] ?? null)
            ? array_values($decoded['parameters'])
            : (is_string($decoded['code'] ?? null) ? [$decoded['code']] : null);

        if ($parameters === null || $parameters === []) {
            return null;
        }

        foreach ($parameters as $parameter) {
            if (! is_string($parameter) || $parameter === '') {
                return null;
            }
        }

        return $parameters;
    }
}
