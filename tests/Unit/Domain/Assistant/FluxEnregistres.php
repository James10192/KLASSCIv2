<?php

namespace Tests\Unit\Domain\Assistant;

/**
 * Flux SSE enregistrés (forme des API publiques), rejoués sans réseau.
 */
final class FluxEnregistres
{
    public static function anthropicTexteEtOutil(): string
    {
        return implode('', [
            "event: message_start\n",
            'data: {"type":"message_start","message":{"id":"msg_1","role":"assistant","content":[],"usage":{"input_tokens":12,"cache_read_input_tokens":100,"output_tokens":1}}}' . "\n\n",
            "event: content_block_start\n",
            'data: {"type":"content_block_start","index":0,"content_block":{"type":"text","text":""}}' . "\n\n",
            "event: ping\n",
            'data: {"type": "ping"}' . "\n\n",
            "event: content_block_delta\n",
            'data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"Je cherche "}}' . "\n\n",
            "event: content_block_delta\n",
            'data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"les inscriptions."}}' . "\n\n",
            "event: content_block_stop\n",
            'data: {"type":"content_block_stop","index":0}' . "\n\n",
            "event: content_block_start\n",
            'data: {"type":"content_block_start","index":1,"content_block":{"type":"tool_use","id":"toolu_01","name":"search_inscriptions","input":{}}}' . "\n\n",
            "event: content_block_delta\n",
            'data: {"type":"content_block_delta","index":1,"delta":{"type":"input_json_delta","partial_json":"{\"search\": \"KON"}}' . "\n\n",
            "event: content_block_delta\n",
            'data: {"type":"content_block_delta","index":1,"delta":{"type":"input_json_delta","partial_json":"AN\", \"limit\": 5}"}}' . "\n\n",
            "event: content_block_stop\n",
            'data: {"type":"content_block_stop","index":1}' . "\n\n",
            "event: message_delta\n",
            'data: {"type":"message_delta","delta":{"stop_reason":"tool_use","stop_sequence":null},"usage":{"output_tokens":42}}' . "\n\n",
            "event: message_stop\n",
            'data: {"type":"message_stop"}' . "\n\n",
        ]);
    }

    public static function anthropicTexte(string $texte = 'Voici les résultats.'): string
    {
        return implode('', [
            "event: message_start\n",
            'data: {"type":"message_start","message":{"id":"msg_2","usage":{"input_tokens":30,"output_tokens":1}}}' . "\n\n",
            "event: content_block_start\n",
            'data: {"type":"content_block_start","index":0,"content_block":{"type":"text","text":""}}' . "\n\n",
            "event: content_block_delta\n",
            'data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":' . json_encode($texte, JSON_UNESCAPED_UNICODE) . '}}' . "\n\n",
            "event: content_block_stop\n",
            'data: {"type":"content_block_stop","index":0}' . "\n\n",
            "event: message_delta\n",
            'data: {"type":"message_delta","delta":{"stop_reason":"end_turn"},"usage":{"output_tokens":6}}' . "\n\n",
            "event: message_stop\n",
            'data: {"type":"message_stop"}' . "\n\n",
        ]);
    }

    public static function openAiTexteEtOutil(): string
    {
        return implode("\n\n", [
            'data: {"id":"c1","choices":[{"index":0,"delta":{"role":"assistant","content":""},"finish_reason":null}]}',
            'data: {"id":"c1","choices":[{"index":0,"delta":{"content":"Je cherche "},"finish_reason":null}]}',
            'data: {"id":"c1","choices":[{"index":0,"delta":{"content":"les classes."},"finish_reason":null}]}',
            'data: {"id":"c1","choices":[{"index":0,"delta":{"tool_calls":[{"index":0,"id":"call_1","type":"function","function":{"name":"search_classes","arguments":""}}]},"finish_reason":null}]}',
            'data: {"id":"c1","choices":[{"index":0,"delta":{"tool_calls":[{"index":0,"function":{"arguments":"{\"search\":\"BTS"}}]},"finish_reason":null}]}',
            'data: {"id":"c1","choices":[{"index":0,"delta":{"tool_calls":[{"index":0,"function":{"arguments":"\"}"}}]},"finish_reason":null}]}',
            'data: {"id":"c1","choices":[{"index":0,"delta":{},"finish_reason":"tool_calls"}]}',
            'data: {"id":"c1","choices":[],"usage":{"prompt_tokens":120,"completion_tokens":18,"total_tokens":138}}',
            'data: [DONE]',
        ]) . "\n\n";
    }

    public static function geminiTexteEtOutil(): string
    {
        return implode("\r\n\r\n", [
            'data: {"candidates":[{"content":{"parts":[{"text":"Voici "}],"role":"model"},"index":0}],"usageMetadata":{"promptTokenCount":50,"candidatesTokenCount":2}}',
            'data: {"candidates":[{"content":{"parts":[{"functionCall":{"name":"search_classes","args":{"search":"BTS"}}}],"role":"model"},"finishReason":"STOP","index":0}],"usageMetadata":{"promptTokenCount":50,"candidatesTokenCount":9}}',
        ]) . "\r\n\r\n";
    }
}
