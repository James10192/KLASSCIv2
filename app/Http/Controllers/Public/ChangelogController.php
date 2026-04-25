<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Environment\Environment;

class ChangelogController extends Controller
{
    /**
     * Render the public Changelog page.
     *
     * Source-of-truth = CHANGELOG.md at repo root, parsed via league/commonmark
     * (already a transitive Laravel dep). Cached for 24h to avoid disk reads
     * on every visit. Cache invalidated automatically on `php artisan cache:clear`
     * (run during the standard tenant deploy).
     */
    public function show()
    {
        $payload = Cache::remember('public.changelog.v1', now()->addHours(24), function () {
            return $this->buildPayload();
        });

        return response()
            ->view('public.changelog', $payload)
            ->withHeaders([
                'Cache-Control' => 'public, max-age=3600',
                'Expires' => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT',
            ]);
    }

    /**
     * Build the rendered HTML + sidebar entries from CHANGELOG.md.
     *
     * @return array{html: string, months: array<int, array{anchor: string, label: string}>}
     */
    private function buildPayload(): array
    {
        $path = base_path('CHANGELOG.md');

        if (! is_file($path)) {
            Log::warning('CHANGELOG.md missing at expected path', ['path' => $path]);
            return ['html' => '<p>Le changelog n\'est pas disponible pour le moment.</p>', 'months' => []];
        }

        $markdown = (string) file_get_contents($path);

        // Drop the top-level H1 + intro blockquote so the page-hero owns the title visually.
        // Anything above the first horizontal rule (---) belongs to "header" prose,
        // anything below is the actual changelog body.
        $parts = preg_split('/^---\s*$/m', $markdown, 2);
        $body = $parts[1] ?? $markdown;

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'symbol' => '',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AutolinkExtension());

        $converter = new CommonMarkConverter([], $environment);
        $html = (string) $converter->convert($body);

        $html = $this->addAnchorsToMonthHeadings($html);

        $months = $this->extractMonthAnchors($html);

        return [
            'html' => $html,
            'months' => $months,
        ];
    }

    /**
     * Inject `id` attributes on H2 month headings so the sidebar links anchor to them.
     */
    private function addAnchorsToMonthHeadings(string $html): string
    {
        return preg_replace_callback(
            '/<h2>(?<title>[^<]+)<\/h2>/u',
            function (array $m): string {
                $slug = $this->slug($m['title']);
                return sprintf('<h2 id="%s">%s</h2>', $slug, e($m['title']));
            },
            $html
        ) ?? $html;
    }

    /**
     * Extract { anchor, label } pairs from H2 ids in the rendered HTML.
     *
     * @return array<int, array{anchor: string, label: string}>
     */
    private function extractMonthAnchors(string $html): array
    {
        if (! preg_match_all('/<h2 id="(?<anchor>[^"]+)">(?<label>[^<]+)<\/h2>/u', $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return array_map(
            fn (array $m): array => ['anchor' => $m['anchor'], 'label' => $m['label']],
            $matches
        );
    }

    /**
     * Slugify a heading label for use as an HTML id.
     */
    private function slug(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}\s-]+/u', '', $value) ?? '';
        $value = preg_replace('/\s+/u', '-', trim($value)) ?? '';
        return $value !== '' ? $value : 'section';
    }
}
