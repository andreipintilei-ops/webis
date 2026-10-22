<?php

namespace App\Actions\Seo;

use App\Enums\RedirectCode;
use App\Models\Page;
use App\Models\Project;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
 *         table (exact, then longest prefix), uppercase variants
 *         → not_found_logs + the 404 page
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

        // /Ce-Este-SEO/: routes and slugs are lowercase, and the table can't
        // hold a row for a page that is live.
        $typed = rawurldecode($request->getPathInfo());
        $lower = mb_strtolower($typed);

        if ($typed !== $lower && $this->isLive($lower)) {
            return $this->redirectTo($lower, RedirectCode::Permanent, $request);
        }

        ($this->logNotFound)($request, $path);

        return null;
    }

    /**
     * /?p=123 (and page_id, attachment_id) → the post's new address, through
     * the id → path map that `legacy:inventory` writes. No map, or an id it
     * doesn't know, is no answer. Public: "/" is a live route, so the
     * RedirectLegacyIds middleware asks this before the home page renders.
     */
    public function legacyIdRedirect(Request $request): ?RedirectResponse
    {
        $id = collect(self::LEGACY_ID_KEYS)
            ->map(fn (string $key) => $request->query($key))
            ->first(fn (mixed $value) => is_string($value) && ctype_digit($value));

        if ($id === null) {
            return null;
        }

        $target = $this->legacyIds()[$id] ?? null;

        return is_string($target) && $target !== ''
            ? $this->redirectTo($target, RedirectCode::Permanent, $request, except: self::LEGACY_ID_KEYS)
            : null;
    }

    /**
     * The id → path map: `{"123": "/some-path"}`.
     */
    public static function legacyIdsPath(): string
    {
        return storage_path('app/private/legacy/legacy-ids.json');
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
        if ($this->isLive($archive)) {
            return $this->redirectTo($archive, RedirectCode::Permanent, $request);
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
     * Whether a lowercase path would answer 200: a route without parameters,
     * or a CMS page or project that is published at that slug.
     */
    private function isLive(string $path): bool
    {
        try {
            $route = app('router')->getRoutes()->match(Request::create($path, 'GET'));
        } catch (HttpExceptionInterface) {
            return false;
        }

        return match ($route->getName()) {
            'pages.show' => Page::query()->published()->where('slug', $route->parameter('slug'))->exists(),
            'projects.show' => Project::query()->published()->where('slug', $route->parameter('slug'))->exists(),
            default => $route->parameterNames() === [],
        };
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
