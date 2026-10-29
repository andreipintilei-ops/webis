<?php

namespace App\Actions\Seo;

use App\Enums\PageType;
use App\Enums\RedirectCode;
use App\Models\Page;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\SlugHistory;
use App\Support\PublicUrls;
use App\Support\TrailingSlash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\RedirectController;
use Illuminate\Support\Arr;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The last chance for a request that would 404 (bootstrap/app.php hands it
 * every NotFoundHttpException): the old WordPress site's addresses rank, so
 * each should reach its new equivalent in one hop, or a 410 if it has none.
 * Null means "a genuine miss": the normal errors/404 page renders.
 *
 * Resolution order:
 *
 *   real route (200)
 *     → SlugHistory 301 inside PageController / ProjectController (live data,
 *       so it wins: those controllers return before they abort)
 *       → this class: ?p= ids, feed and pagination suffixes, the redirects
 *         table (exact, then longest prefix), dated article addresses,
 *         uppercase variants
 *         → not_found_logs + the 404 page
 *
 * The table comes before the dated-address rule, so a row wins over it: the
 * 410 prefixes for the spam once injected under /2025/11/ and /2026/03/28/
 * keep those paths gone even where a slug matches an article.
 *
 * Only public GET/HEAD pages take part. A redirect would drop a POST's body,
 * JSON callers want the plain 404, and a 404 on the admin or account side (a
 * deleted record's id) is no legacy URL and no visitor's dead end.
 */
class HandleNotFound
{
    /** First path segments that never redirect and never log. */
    public const array IGNORED_PREFIXES = [
        'admin', 'settings', 'api', 'storage', 'build', 'up', 'login', 'logout',
        'register', 'user', 'email', 'passkeys', 'two-factor-challenge',
        'confirm-password', 'forgot-password', 'reset-password', '_ignition',
    ];

    /** WordPress's own short links: /?p=123, /?page_id=45, /?attachment_id=67. */
    public const array LEGACY_ID_KEYS = ['p', 'page_id', 'attachment_id'];

    /** Longer paths are probes, not addresses anyone links to. */
    private const int MAX_PATH_LENGTH = 512;

    public function __construct(
        private readonly ResolveRedirect $resolveRedirect,
        private readonly LogNotFound $logNotFound,
    ) {}

    public function __invoke(Request $request): ?Response
    {
        if (! $this->applies($request)) {
            return null;
        }

        $path = Redirect::normalisePath($request->getPathInfo());

        if ($path === '/' && ($response = $this->legacyIdRedirect($request)) !== null) {
            return $response;
        }

        // /feed/, /comments/feed/, /{post}/feed/: the new site publishes no RSS.
        if (str_ends_with($path, '/feed')) {
            return $this->gone();
        }

        // /{archive}/page/N/: an archive's later pages land on the archive. A
        // bare /page/N/ is left to the table (its `/page` prefix row).
        if (preg_match('#^(/.+)/page/\d+$#', $path, $match) === 1
            && ($response = $this->archive($match[1], $request)) !== null) {
            return $response;
        }

        if (($redirect = ($this->resolveRedirect)($path, $request->getHost())) !== null) {
            return $this->follow($redirect, $request);
        }

        // /2022/02/14/{slug}/: the old site's dated permalinks, in the
        // variants the table has no row for (the April 2026 re-dating made
        // more of them).
        if (($article = $this->datedArticle($path)) !== null) {
            return $this->redirectTo(PublicUrls::for($article), RedirectCode::Permanent, $request);
        }

        // /Ce-Este-SEO/: routes and slugs are lowercase, and the table can't
        // hold a row for a page that is live.
        $typed = rawurldecode($request->getPathInfo());
        $lower = mb_strtolower($typed);

        if ($typed !== $lower && ($address = $this->liveAddress($lower)) !== null) {
            // A live page in its canonical form, so not via a second 301.
            return $this->redirectTo($address, RedirectCode::Permanent, $request);
        }

        ($this->logNotFound)($request, $path);

        return null;
    }

    /**
     * /?p=123 (and page_id, attachment_id) → the post's new address, through
     * the id → path map that `legacy:inventory` writes. An id the map doesn't
     * know is a 410: every post the old site kept is in it, so the rest are
     * deleted posts or spam (/?p=19525). No map is no answer. Public: "/" is
     * a live route, so the RedirectLegacyIds middleware asks this before the
     * home page renders.
     */
    public function legacyIdRedirect(Request $request): ?Response
    {
        $id = collect(self::LEGACY_ID_KEYS)
            ->map(fn (string $key) => $request->query($key))
            ->first(fn (mixed $value) => is_string($value) && ctype_digit($value));

        if ($id === null) {
            return null;
        }

        $ids = $this->legacyIds();

        if ($ids === []) {
            return null;
        }

        $target = $ids[$id] ?? null;

        return is_string($target) && $target !== ''
            ? $this->redirectTo($target, RedirectCode::Permanent, $request, except: self::LEGACY_ID_KEYS)
            : $this->gone();
    }

    /**
     * The id → path map: `{"123": "/some-path"}`.
     */
    public static function legacyIdsPath(): string
    {
        return (string) config('site.legacy_ids');
    }

    private function applies(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return false;
        }

        $path = $request->getPathInfo();
        $first = mb_strtolower(explode('/', ltrim(rawurldecode($path), '/'))[0]);

        return strlen($path) <= self::MAX_PATH_LENGTH && ! in_array($first, self::IGNORED_PREFIXES, true);
    }

    /**
     * Page N of an archive: the archive itself if it's live, else wherever the
     * table sends the archive.
     */
    private function archive(string $archive, Request $request): ?Response
    {
        if (($address = $this->liveAddress($archive)) !== null) {
            return $this->redirectTo($address, RedirectCode::Permanent, $request);
        }

        $redirect = ($this->resolveRedirect)($archive, $request->getHost());

        return $redirect === null ? null : $this->follow($redirect, $request);
    }

    private function follow(Redirect $redirect, Request $request): Response
    {
        $this->resolveRedirect->recordHit($redirect->id);

        return $redirect->status_code === RedirectCode::Gone || $redirect->target === null
            ? $this->gone()
            : $this->redirectTo($redirect->target, $redirect->status_code, $request);
    }

    /**
     * The published article a dated address (/{YYYY}/{MM}/{DD}/{slug}) names,
     * by its slug or one it used to have.
     */
    private function datedArticle(string $path): ?Page
    {
        if (preg_match('#^/\d{4}/\d{2}/\d{2}/([a-z0-9]+(?:-[a-z0-9]+)*)$#', $path, $match) !== 1) {
            return null;
        }

        $article = Page::query()->published()->ofType(PageType::Article)->where('slug', $match[1])->first()
            ?? SlugHistory::resolve(Page::class, $match[1]);

        return $article instanceof Page && $article->type === PageType::Article && $article->isPublished()
            ? $article
            : null;
    }

    /**
     * Where a lowercase path answers, in canonical form, or null if it would
     * not answer 200: a route without parameters, a CMS page or project
     * published at that slug — or, for one of the app's own permanent
     * redirects (/proiect, /clienti), straight where it leads, so not via a
     * second 301.
     */
    private function liveAddress(string $path): ?string
    {
        try {
            $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
        } catch (HttpExceptionInterface) {
            return null;
        }

        if (str_starts_with(ltrim($route->getActionName(), '\\'), RedirectController::class)) {
            return TrailingSlash::href($path);
        }

        $live = match ($route->getName()) {
            'pages.show' => Page::query()->published()->where('slug', $route->parameter('slug'))->exists(),
            'projects.show' => Project::query()->published()->where('slug', $route->parameter('slug'))->exists(),
            default => $route->parameterNames() === [],
        };

        return $live ? TrailingSlash::href($path) : null;
    }

    /**
     * A redirect to the target exactly as stored (no slash added or trimmed),
     * carrying the request's query string, UTM tags included, onto a target
     * that has none of its own.
     *
     * @param  list<string>  $except  query keys that were the address itself
     */
    private function redirectTo(string $target, RedirectCode $code, Request $request, array $except = []): RedirectResponse
    {
        $query = Arr::query(Arr::except($request->query->all(), $except));

        if ($query !== '' && parse_url($target, PHP_URL_QUERY) === null) {
            $target .= '?'.$query;
        }

        if (str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            $target = $request->getSchemeAndHttpHost().$target;
        }

        return new RedirectResponse($target, $code->value);
    }

    private function gone(): Response
    {
        return response()->view('errors.410', ['errors' => new ViewErrorBag], RedirectCode::Gone->value);
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyIds(): array
    {
        $file = self::legacyIdsPath();

        if (! is_file($file)) {
            return [];
        }

        $map = json_decode((string) file_get_contents($file), true);

        return is_array($map) ? $map : [];
    }
}
