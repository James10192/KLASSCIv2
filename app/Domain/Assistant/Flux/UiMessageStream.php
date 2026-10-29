<?php

namespace App\Domain\Assistant\Flux;

/**
 * Ecrit le protocole « UI message stream » v1 du Vercel AI SDK.
 *
 * Chaque partie est un evenement SSE `data: {json}` ; le flux se termine par
 * `data: [DONE]`. Le client (public/js/assistant/*.js) lit exactement ce format,
 * et n'importe quel client AI SDK (useChat) pourrait le lire aussi.
 *
 * La sortie passe par un « puits » injecte : en production il ecrit et vide les
 * tampons, en test il accumule les parties pour qu'on les compare.
 */
final class UiMessageStream
{
    public const HEADERS = [
        'Content-Type' => 'text/event-stream; charset=UTF-8',
        'Cache-Control' => 'no-cache, no-transform',
        'Connection' => 'keep-alive',
        'X-Accel-Buffering' => 'no',
        'x-vercel-ai-ui-message-stream' => 'v1',
    ];

    /** @var callable(string):void */
    private $sink;

    /** @var callable():bool */
    private $isAborted;

    private bool $closed = false;

    private int $textSequence = 0;

    public function __construct(callable $sink, ?callable $isAborted = null)
    {
        $this->sink = $sink;
        $this->isAborted = $isAborted ?? static fn (): bool => false;
    }

    /**
     * Puits de production : ecrit sur la sortie et la pousse au navigateur a
     * chaque partie. connection_aborted() n'est fiable qu'apres un flush, d'ou
     * le flush systematique.
     */
    public static function versLaSortie(): self
    {
        return new self(
            static function (string $frame): void {
                echo $frame;
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            },
            static fn (): bool => connection_aborted() === 1
        );
    }

    /** Puits muet : la route JSON non diffusée passe par la même boucle, sans rien écrire. */
    public static function silencieux(): self
    {
        return new self(static function (string $frame): void {
        });
    }

    public function write(array $part): void
    {
        if ($this->closed) {
            return;
        }

        ($this->sink)('data: ' . json_encode($part, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . "\n\n");
    }

    public function aborted(): bool
    {
        return ($this->isAborted)();
    }

    public function nextTextId(): string
    {
        return 'txt_' . (++$this->textSequence);
    }

    public function start(string $messageId, array $metadata = []): void
    {
        $part = ['type' => 'start', 'messageId' => $messageId];
        if ($metadata !== []) {
            $part['messageMetadata'] = $metadata;
        }
        $this->write($part);
    }

    public function startStep(): void
    {
        $this->write(['type' => 'start-step']);
    }

    public function finishStep(): void
    {
        $this->write(['type' => 'finish-step']);
    }

    public function textStart(string $id): void
    {
        $this->write(['type' => 'text-start', 'id' => $id]);
    }

    public function textDelta(string $id, string $delta): void
    {
        if ($delta === '') {
            return;
        }
        $this->write(['type' => 'text-delta', 'id' => $id, 'delta' => $delta]);
    }

    public function textEnd(string $id): void
    {
        $this->write(['type' => 'text-end', 'id' => $id]);
    }

    /** Emet d'un bloc un texte que le modele n'a pas diffuse lui-meme. */
    public function text(string $text): void
    {
        if ($text === '') {
            return;
        }
        $id = $this->nextTextId();
        $this->textStart($id);
        $this->textDelta($id, $text);
        $this->textEnd($id);
    }

    /**
     * Partie de donnees personnalisee (`data-<nom>`). Deux parties de meme id
     * se remplacent cote client : c'est ce qui fait passer une puce d'outil de
     * « en cours » a « termine ».
     */
    public function data(string $name, array $data, ?string $id = null): void
    {
        $part = ['type' => 'data-' . $name];
        if ($id !== null) {
            $part['id'] = $id;
        }
        $part['data'] = $data;
        $this->write($part);
    }

    public function metadata(array $metadata): void
    {
        $this->write(['type' => 'message-metadata', 'messageMetadata' => $metadata]);
    }

    /** Le texte d'erreur est toujours un message pour l'utilisateur, jamais le texte d'une exception. */
    public function error(string $errorText): void
    {
        $this->write(['type' => 'error', 'errorText' => $errorText]);
    }

    public function abort(): void
    {
        $this->write(['type' => 'abort']);
    }

    public function finish(): void
    {
        $this->write(['type' => 'finish']);
    }

    public function done(): void
    {
        if ($this->closed) {
            return;
        }
        ($this->sink)("data: [DONE]\n\n");
        $this->closed = true;
    }
}
