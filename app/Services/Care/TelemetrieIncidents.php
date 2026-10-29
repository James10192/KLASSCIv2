<?php
namespace App\Services\Care;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDOException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Envoie uniquement des metadonnees sur liste blanche, apres la reponse.
 * Jamais de texte SQL, message d'exception, corps de requete, URL, PII ou token.
 */
final class TelemetrieIncidents
{
    public function preparer(Throwable $exception, Request $request): ?array
    {
        $statut = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        if ($statut < 500 || $statut > 599) {
            return null;
        }

        $reference = (string) $request->attributes->get('request_id');
        if ($reference === '') {
            $reference = (string) Str::ulid();
            $request->attributes->set('request_id', $reference);
        }

        $route = $request->route()?->getName();
        $route = is_string($route) && preg_match('/^[A-Za-z0-9_.:-]{1,160}$/D', $route)
            ? $route : null;

        $base = $exception instanceof QueryException || $exception instanceof PDOException;
        $info = $base ? (array) ($exception->errorInfo ?? []) : [];
        $sqlstate = $info[0] ?? null;
        $sqlstate = is_string($sqlstate) && preg_match('/^[A-Z0-9]{5}$/D', $sqlstate)
            ? $sqlstate : null;
        $errno = $info[1] ?? null;
        $errno = is_numeric($errno) && (int) $errno >= 0 && (int) $errno <= 65535
            ? (int) $errno : null;

        return [
            'request_id' => $reference,
            'route_name' => $route,
            'exception_class' => get_class($exception),
            'category' => $base ? 'database' : 'application',
            'method' => $request->getMethod(),
            'http_status' => $statut,
            'sqlstate' => $sqlstate,
            'db_errno' => $errno,
        ];
    }

    public function programmer(Throwable $exception): void
    {
        if (app()->runningInConsole() || ! config('support.telemetry_enabled', false)) {
            return;
        }

        $url = rtrim((string) config('services.master.api_url'), '/');
        $token = (string) config('services.master.support_token');
        if ($url === '' || $token === '') {
            return;
        }
        $donnees = $this->preparer($exception, request());
        if ($donnees === null) {
            return;
        }

        app()->terminating(static function () use ($url, $token, $donnees): void {
            try {
                $reponse = Http::withToken($token)
                    ->connectTimeout(1)->timeout(2)->acceptJson()
                    ->post($url.'/v1/support/incidents', $donnees);
                if (! $reponse->successful()) {
                    Log::warning('Transmission incident Master refusee', [
                        'statut' => $reponse->status(),
                        'request_id' => $donnees['request_id'],
                    ]);
                }
            } catch (Throwable $erreur) {
                Log::warning('Transmission incident Master indisponible', [
                    'request_id' => $donnees['request_id'],
                    'type' => get_class($erreur),
                ]);
            }
        });
    }
}
