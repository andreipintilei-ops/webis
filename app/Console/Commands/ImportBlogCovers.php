<?php

namespace App\Console\Commands;

use App\Actions\Blog\StoreCover;
use App\Enums\PageType;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

/**
 * The articles' covers, from a folder of image files each named after its
 * article's slug (`{slug}.png`, any case; .png, .jpg, .jpeg or .webp; a
 * trailing `-1` or `-v2`, a second take, ignored when the name without it
 * is the slug). An `alt.csv` beside them (`slug,alt`, a header row
 * allowed) gives their alt texts.
 *
 * An article that already has a cover keeps it unless --force; --dry-run
 * only says what would happen. Ends with what was imported, what was
 * skipped, the files no article matched and the articles with a cover but
 * no alt text. Each file goes through StoreCover, as an upload does.
 */
class ImportBlogCovers extends Command
{
    protected $signature = 'blog:import-covers
        {folder : The folder of cover images}
        {--dry-run : List what would be imported, write nothing}
        {--force : Replace a cover an article already has}';

    protected $description = 'Import blog article covers from a folder of images named after their slugs';

    private const array EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    public function handle(StoreCover $storeCover): int
    {
        $folder = rtrim((string) $this->argument('folder'), '/\\');

        if (! File::isDirectory($folder)) {
            $this->error("No such folder: {$folder}");

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $articles = Page::query()->ofType(PageType::Article)->with('media')->get()->keyBy('slug');
        $alts = $this->alts($folder);

        $matches = [];
        $unmatched = [];

        foreach (File::files($folder) as $file) {
            if (! in_array(strtolower($file->getExtension()), self::EXTENSIONS, true)) {
                continue;
            }

            $slug = self::slugFor($file->getFilename(), $articles->keys());

            if ($slug === null) {
                $unmatched[] = $file->getFilename();

                continue;
            }

            // Two files for one article: the exact name wins, then the first.
            $exact = strtolower($file->getFilenameWithoutExtension()) === $slug;

            if (! isset($matches[$slug]) || ($exact && ! $matches[$slug]['exact'])) {
                $matches[$slug] = ['file' => $file->getPathname(), 'name' => $file->getFilename(), 'exact' => $exact];
            }
        }

        ksort($matches);
        $imported = [];
        $skipped = [];
        $failed = [];

        foreach ($matches as $slug => $match) {
            $article = $articles[$slug];
            $has = $article->hasMedia(Page::COVER);

            if ($has && ! $this->option('force')) {
                $skipped[] = $slug;
                $this->line("  skip    {$match['name']} → /{$slug}/ (has a cover; --force replaces it)");

                continue;
            }

            $this->line(($dry ? '  would   ' : '  import  ').$match['name'].' → /'.$slug.'/'.($has ? ' (replacing its cover)' : ''));

            if ($dry) {
                $imported[] = $slug;

                continue;
            }

            try {
                $storeCover($article, $match['file'], $alts[$slug] ?? null);
                $imported[] = $slug;
            } catch (Throwable $e) {
                $failed[] = $slug;
                $this->error("  failed  {$match['name']}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info(($dry ? 'Would import ' : 'Imported ').count($imported).', skipped '.count($skipped).(count($failed) > 0 ? ', failed '.count($failed) : '').'.');
        $this->list('Files matching no article', $unmatched);

        if ($dry) {
            $this->list('Articles still without a cover', $articles->keys()
                ->reject(fn (string $slug): bool => $articles[$slug]->hasMedia(Page::COVER) || in_array($slug, $imported, true))
                ->values()->all());
        } else {
            $this->list('Articles with a cover but no alt text', Page::query()->ofType(PageType::Article)
                ->whereHas('media', fn ($query) => $query->where('collection_name', Page::COVER))
                ->where(fn ($query) => $query->whereNull('cover_alt')->orWhere('cover_alt', ''))
                ->orderBy('slug')->pluck('slug')->all());
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * The article a file is for: its name (without extension, any case) as
     * a slug, else without a trailing `-1` / `-v2`.
     *
     * @param  Collection<int, string>  $slugs
     */
    public static function slugFor(string $filename, Collection $slugs): ?string
    {
        $name = Str::lower(pathinfo($filename, PATHINFO_FILENAME));

        foreach ([$name, (string) preg_replace('/-v?\d{1,2}$/', '', $name)] as $candidate) {
            if ($slugs->contains($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string, string> slug → alt text
     */
    private function alts(string $folder): array
    {
        $path = $folder.DIRECTORY_SEPARATOR.'alt.csv';

        if (! File::exists($path)) {
            return [];
        }

        $alts = [];
        $handle = fopen($path, 'r');

        while ($handle !== false && ($row = fgetcsv($handle, escape: '')) !== false) {
            $slug = Str::lower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? ''))));
            $alt = trim((string) ($row[1] ?? ''));

            if ($slug === '' || $slug === 'slug' || $alt === '') {
                continue;
            }

            $alts[pathinfo($slug, PATHINFO_FILENAME)] = Str::limit($alt, 125, '');
        }

        if ($handle !== false) {
            fclose($handle);
        }

        return $alts;
    }

    /** @param list<string> $items */
    private function list(string $title, array $items): void
    {
        $this->line($title.': '.(count($items) === 0 ? 'none' : count($items)));

        foreach ($items as $item) {
            $this->line('  - '.$item);
        }
    }
}
