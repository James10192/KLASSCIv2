<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocsController extends Controller
{
    /**
     * Render the /docs landing : sections grid (rôles, modules, getting-started).
     */
    public function index()
    {
        return response()
            ->view('public.docs.index', [
                'sections' => $this->groupedArticles(),
            ])
            ->withHeaders([
                'Cache-Control' => 'public, max-age=3600',
                'Expires' => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT',
            ]);
    }

    /**
     * Render an individual article at /docs/{slug}.
     */
    public function show(string $slug)
    {
        $articles = config('docs.articles', []);

        if (! isset($articles[$slug]) || ! ($articles[$slug]['available'] ?? false)) {
            throw new NotFoundHttpException("Article not found: {$slug}");
        }

        $article = $articles[$slug];
        $payload = Cache::remember(
            "public.docs.article.{$slug}.v1",
            now()->addHours(24),
            fn () => $this->buildArticlePayload($slug, $article)
        );

        [$prev, $next] = $this->neighbours($slug);

        return response()
            ->view('public.docs.show', [
                'article' => $article,
                'slug' => $slug,
                'html' => $payload['html'],
                'toc' => $payload['toc'],
                'prev' => $prev,
                'next' => $next,
                'sidebar' => $this->groupedArticles(),
            ])
            ->withHeaders([
                'Cache-Control' => 'public, max-age=3600',
                'Expires' => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT',
            ]);
    }

    /**
     * Articles grouped by section, sorted by `order`. Both available and
     * placeholder articles are returned (placeholders render greyed-out).
     *
     * @return array<string, array{title: string, description: string, articles: array<int, array<string, mixed>>}>
     */
    private function groupedArticles(): array
    {
        $sections = config('docs.sections', []);
        $articles = config('docs.articles', []);

        $grouped = [];
        foreach ($sections as $key => $meta) {
            $grouped[$key] = [
                'title' => $meta['title'],
                'description' => $meta['description'] ?? '',
                'articles' => [],
            ];
        }

        foreach ($articles as $slug => $article) {
            $section = $article['section'] ?? null;
            if ($section === null || ! isset($grouped[$section])) {
                continue;
            }
            $grouped[$section]['articles'][] = ['slug' => $slug] + $article;
        }

        foreach ($grouped as $key => $_) {
            usort(
                $grouped[$key]['articles'],
                fn ($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99)
            );
        }

        return $grouped;
    }

    /**
     * Find the previous and next AVAILABLE articles in `reading_order`.
     *
     * @return array{0: ?array<string, mixed>, 1: ?array<string, mixed>}
     */
    private function neighbours(string $slug): array
    {
        $order = config('docs.reading_order', []);
        $articles = config('docs.articles', []);

        $availableOrder = array_values(array_filter(
            $order,
            fn ($s) => isset($articles[$s]) && ($articles[$s]['available'] ?? false)
        ));

        $idx = array_search($slug, $availableOrder, true);
        if ($idx === false) {
            return [null, null];
        }

        $prevSlug = $idx > 0 ? $availableOrder[$idx - 1] : null;
        $nextSlug = $idx < count($availableOrder) - 1 ? $availableOrder[$idx + 1] : null;

        return [
            $prevSlug ? ['slug' => $prevSlug] + $articles[$prevSlug] : null,
            $nextSlug ? ['slug' => $nextSlug] + $articles[$nextSlug] : null,
        ];
    }

    /**
     * Convert a single article markdown file into HTML + extract h2/h3 TOC.
     *
     * @return array{html: string, toc: array<int, array{anchor: string, label: string, level: int}>}
     */
    private function buildArticlePayload(string $slug, array $article): array
    {
        $relative = $article['file'] ?? null;
        if (! is_string($relative) || $relative === '') {
            return ['html' => '', 'toc' => []];
        }

        $path = resource_path('docs/' . ltrim($relative, '/'));
        if (! is_file($path)) {
            Log::warning('Docs markdown missing', ['slug' => $slug, 'path' => $path]);
            return ['html' => '<p>Cet article n\'est pas disponible pour le moment.</p>', 'toc' => []];
        }

        $markdown = (string) file_get_contents($path);

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AutolinkExtension());

        $converter = new CommonMarkConverter([], $environment);
        $html = (string) $converter->convert($markdown);

        // Inject ids on h2/h3 + collect TOC entries.
        $toc = [];
        $html = preg_replace_callback(
            '/<h(?<lvl>[23])>(?<title>[^<]+)<\/h\1>/u',
            function (array $m) use (&$toc): string {
                $slug = $this->slug($m['title']);
                $toc[] = ['anchor' => $slug, 'label' => $m['title'], 'level' => (int) $m['lvl']];
                return sprintf('<h%d id="%s">%s</h%d>', (int) $m['lvl'], $slug, e($m['title']), (int) $m['lvl']);
            },
            $html
        ) ?? $html;

        // Promote :::callout markers into proper callout divs.
        // Markdown source can use :::callout / :::callout warning / :::callout note
        // to wrap a paragraph in a callout-styled box.
        $html = $this->convertCalloutSyntax($html);

        return ['html' => $html, 'toc' => $toc];
    }

    /**
     * Transform :::callout {variant?} ... ::: blocks into <div class="callout"></div>.
     */
    private function convertCalloutSyntax(string $html): string
    {
        return preg_replace_callback(
            '/<p>:::callout(?:\s+(?<variant>warning|note))?<\/p>(?<body>.+?)<p>:::<\/p>/su',
            function (array $m): string {
                $cls = 'callout';
                if (! empty($m['variant'])) {
                    $cls .= ' callout--' . $m['variant'];
                }
                return sprintf('<div class="%s">%s</div>', $cls, $m['body']);
            },
            $html
        ) ?? $html;
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
