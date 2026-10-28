<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Brand\GenerateFavicons;
use App\Actions\Media\AttachUpload;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\AssetUsage;
use App\Settings\BrandSettings;
use App\Support\AdminLinks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The media library. The page itself is Inertia; everything the grid and the
 * picker dialog do (upload, browse, edit, replace, delete) is JSON so it works
 * the same from any screen without a page visit.
 */
class MediaController extends Controller
{
    private const int PER_PAGE = 30;

    /**
     * GET /admin/media
     */
    public function index(Request $request): Response
    {
        return Inertia::render('admin/media/Index', [
            'assets' => $this->search($request),
            'filters' => $this->filters($request),
        ]);
    }

    /**
     * GET /admin/media/browse — the same listing as JSON, for the picker.
     */
    public function browse(Request $request): JsonResponse
    {
        return response()->json($this->search($request));
    }

    /**
     * POST /admin/media — one file per request; the uploader sends them in turn
     * so each gets its own progress bar and error.
     */
    public function store(Request $request, AttachUpload $attach): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:15360', 'mimetypes:'.implode(',', AttachUpload::ACCEPTED_MIMES)],
            'alt' => ['nullable', 'string', 'max:255'],
        ], [
            'file.mimetypes' => 'Sunt acceptate imagini JPG, PNG, WebP, GIF, AVIF și SVG.',
            'file.max' => 'Fișierul depășește 15 MB.',
        ]);

        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);

        $asset = DB::transaction(function () use ($request, $attach, $file, $validated): Asset {
            $asset = Asset::create([
                'title' => AttachUpload::titleFrom($file),
                'alt' => $validated['alt'] ?? null,
                'uploaded_by' => $request->user()?->id,
            ]);

            return $attach($asset, $file);
        });

        return response()->json(['asset' => $this->present($asset)], 201);
    }

    /**
     * GET /admin/media/{asset}
     */
    public function show(Asset $asset): JsonResponse
    {
        $asset->loadCount('usages');

        $usages = $asset->usages()->with('usable')->get()
            ->filter(fn (AssetUsage $usage): bool => $usage->usable !== null)
            ->unique(fn (AssetUsage $usage): string => $usage->usable_type.'#'.$usage->usable_id)
            ->map(fn (AssetUsage $usage): array => AdminLinks::describe($usage->usable))
            ->values()
            ->all();

        foreach ($asset->settingsReferences() as $label) {
            $usages[] = ['type' => 'Setări', 'title' => $label, 'url' => null];
        }

        return response()->json([
            'asset' => $this->present($asset),
            'usages' => $usages,
        ]);
    }

    /**
     * PATCH /admin/media/{asset}
     */
    public function update(Request $request, Asset $asset): JsonResponse
    {
        $asset->update($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
        ]));

        return response()->json(['asset' => $this->present($asset)]);
    }

    /**
     * POST /admin/media/{asset}/inlocuieste — swap the file, keep the asset, so
     * every page using it shows the new image.
     */
    public function replace(Request $request, Asset $asset, AttachUpload $attach): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:15360', 'mimetypes:'.implode(',', AttachUpload::ACCEPTED_MIMES)],
        ]);

        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);

        $asset = $attach($asset, $file);

        // The favicon files are made from this image; remake them.
        $brand = app(BrandSettings::class);

        if ($brand->favicon_asset_id === $asset->id) {
            app(GenerateFavicons::class)($brand);
        }

        return response()->json(['asset' => $this->present($asset)]);
    }

    /**
     * DELETE /admin/media/{asset} — refused while anything still uses it.
     */
    public function destroy(Asset $asset): JsonResponse|HttpResponse
    {
        $inUse = $asset->usages()->exists() || $asset->settingsReferences() !== [];

        if ($inUse) {
            return response()->json([
                'message' => 'Imaginea este folosită în conținut. Înlocuiește-o acolo înainte de a o șterge.',
            ], 422);
        }

        $asset->delete();

        return response()->noContent();
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function search(Request $request): LengthAwarePaginator
    {
        ['q' => $search, 'type' => $type, 'unused' => $unused] = $this->filters($request);

        /** @var LengthAwarePaginator<int, Asset> $assets */
        $assets = Asset::query()
            ->with('media')
            ->withCount('usages')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('title', 'like', "%{$search}%")
                ->orWhere('alt', 'like', "%{$search}%")
                ->orWhereHas('media', fn (Builder $media) => $media->where('file_name', 'like', "%{$search}%"))))
            ->when($type === 'svg', fn (Builder $query) => $query->whereHas('media', fn (Builder $media) => $media->where('mime_type', 'image/svg+xml')))
            ->when($type === 'raster', fn (Builder $query) => $query->whereHas('media', fn (Builder $media) => $media->where('mime_type', '!=', 'image/svg+xml')))
            ->when($unused, fn (Builder $query) => $query->doesntHave('usages'))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return $assets->through(fn (Asset $asset): array => $this->present($asset));
    }

    /**
     * @return array{q: string, type: string, unused: bool}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'type' => in_array($request->query('type'), ['svg', 'raster'], true) ? (string) $request->query('type') : '',
            'unused' => $request->boolean('unused'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Asset $asset): array
    {
        return (new AssetResource($asset))->resolve();
    }
}
