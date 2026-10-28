<?php

namespace App\Console\Commands;

use App\Support\Seo\SeoAudit;
use Illuminate\Console\Command;

/**
 * Checks every URL in the sitemap against the site's title and description
 * rules (Support\Seo\SeoAudit); fails when any breaks one. After a content
 * change, or before launch.
 */
class AuditSeo extends Command
{
    protected $signature = 'seo:audit {--all : List every URL, not only those with problems}';

    protected $description = 'Check every sitemap URL\'s title and description against the SEO rules';

    public function handle(SeoAudit $audit): int
    {
        $rows = $audit->run();
        $failing = array_values(array_filter($rows, fn (array $row): bool => $row['problems'] !== []));
        $shown = $this->option('all') ? $rows : $failing;

        if ($shown !== []) {
            $this->table(['Path', 'Title', 'Problems'], array_map(fn (array $row): array => [
                $row['path'],
                mb_strimwidth($row['title'], 0, 64, '…'),
                implode(', ', $row['problems']) ?: 'ok',
            ], $shown));
        }

        if ($failing !== []) {
            $this->error(count($failing).' of '.count($rows).' URLs break a rule.');

            return self::FAILURE;
        }

        $this->info('All '.count($rows).' URLs have a unique title and description within the limits.');

        return self::SUCCESS;
    }
}
