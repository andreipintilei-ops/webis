<?php

namespace App\Support\Seo;

use App\Settings\SeoSettings;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

/**
 * Every URL in the sitemap, rendered, held to the site's title and
 * description rules: both present, a title at most 60 characters ending in
 * the " | Webis" suffix, a description at most 155, none of either twice,
 * and no "organizație"/"organizații" in them.
 *
 * Run against the real content with `php artisan seo:audit`, and by the
 * tests against content of every kind.
 */
final class SeoAudit
{
    public function __construct(private readonly Sitemap $sitemap) {}

    /**
     * @return list<array{path: string, title: string, description: string, problems: list<string>}>
     */
    public function run(): array
    {
        $kernel = app(Kernel::class);
        $suffix = app(SeoSettings::class)->title_suffix;
        $rows = [];

        foreach ($this->sitemap->entries() as $entry) {
            $path = (string) parse_url($entry['loc'], PHP_URL_PATH);
            $response = $kernel->handle(Request::create($path));
            $html = (string) $response->getContent();

            preg_match('~<title>(.*?)</title>~s', $html, $title);
            preg_match('~<meta name="description" content="([^"]*)"~', $html, $description);

            $rows[] = [
                'path' => $path,
                'status' => $response->getStatusCode(),
                'title' => html_entity_decode(trim($title[1] ?? ''), ENT_QUOTES | ENT_HTML5),
                'description' => html_entity_decode($description[1] ?? '', ENT_QUOTES | ENT_HTML5),
            ];
        }

        $titles = array_count_values(array_map(fn (array $row): string => mb_strtolower($row['title']), $rows));
        $descriptions = array_count_values(array_map(fn (array $row): string => mb_strtolower($row['description']), $rows));

        return array_map(function (array $row) use ($titles, $descriptions, $suffix): array {
            $problems = [];

            if ($row['status'] !== 200) {
                $problems[] = "HTTP {$row['status']}";
            }

            if ($row['title'] === '') {
                $problems[] = 'no title';
            } else {
                if (mb_strlen($row['title']) > SeoText::TITLE_MAX) {
                    $problems[] = 'title '.mb_strlen($row['title']).' characters';
                }

                if (! str_ends_with($row['title'], $suffix)) {
                    $problems[] = "title without \"{$suffix}\"";
                }

                if ($titles[mb_strtolower($row['title'])] > 1) {
                    $problems[] = 'title not unique';
                }
            }

            if ($row['description'] === '') {
                $problems[] = 'no description';
            } else {
                if (mb_strlen($row['description']) > SeoText::DESCRIPTION_MAX) {
                    $problems[] = 'description '.mb_strlen($row['description']).' characters';
                }

                if ($descriptions[mb_strtolower($row['description'])] > 1) {
                    $problems[] = 'description not unique';
                }
            }

            if (preg_match('/organiza(ț|ţ|t)i/iu', $row['title'].' '.$row['description']) === 1) {
                $problems[] = '"organizați…"';
            }

            return ['path' => $row['path'], 'title' => $row['title'], 'description' => $row['description'], 'problems' => $problems];
        }, $rows);
    }
}
