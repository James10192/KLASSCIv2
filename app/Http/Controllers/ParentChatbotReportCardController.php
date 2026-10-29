<?php

namespace App\Http\Controllers;

use App\Models\ESBTPBulletin;
use App\Models\ParentChatbotLink;
use App\Services\ParentChatbot\ParentChatbotReportCardAccess;
use App\Services\ParentChatbot\ParentChatbotReportCardPdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Streams the official report card PDF behind a temporary signed link.
 *
 * The route signature is validated by the `signed` middleware (403 on tampering
 * or expiry). Everything else is revalidated here, and every refusal answers the
 * exact same 410 so the page never reveals whether a report card exists.
 */
class ParentChatbotReportCardController extends Controller
{
    public function __invoke(
        Request $request,
        ParentChatbotReportCardAccess $access,
        ParentChatbotReportCardPdfRenderer $renderer,
    ): Response {
        $bulletin = $this->bulletinFrom($request);
        $link = $this->linkFrom($request);

        if (! $access->grants($link, $bulletin)) {
            return $this->gone();
        }

        try {
            $pdf = $renderer->render($bulletin);
        } catch (Throwable $exception) {
            Log::error('Parent chatbot report card PDF could not be rendered', [
                'bulletin_id' => $bulletin->getKey(),
                'exception' => $exception->getMessage(),
            ]);

            return $this->gone();
        }

        return $this->privateResponse($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="bulletin.pdf"',
        ]);
    }

    private function bulletinFrom(Request $request): ?ESBTPBulletin
    {
        $id = $request->route('bulletin');

        return is_string($id) && ctype_digit($id) ? ESBTPBulletin::find((int) $id) : null;
    }

    private function linkFrom(Request $request): ?ParentChatbotLink
    {
        $id = $request->query('link');

        return is_string($id) && ctype_digit($id) ? ParentChatbotLink::find((int) $id) : null;
    }

    private function gone(): Response
    {
        return $this->privateResponse(
            'Ce lien de bulletin n\'est plus disponible. Envoyez BULLETIN sur WhatsApp pour recevoir un nouveau lien.',
            410,
        );
    }

    /** @param array<string, string> $headers */
    private function privateResponse(string $content, int $status, array $headers = []): Response
    {
        return response()
            ->make($content, $status, $headers)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
