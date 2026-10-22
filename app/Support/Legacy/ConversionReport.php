<?php

namespace App\Support\Legacy;

/**
 * What a content conversion left behind: everything it dropped, by kind
 * (`widget:spacer`, `element:script`, `link:staging` …), and every image or
 * link it could not resolve, with the old URL. The import report adds these
 * up across posts, so content the converters lose is counted, never silent.
 */
final class ConversionReport
{
    /** @var array<string, int> */
    private array $dropped = [];

    /** @var array<string, list<string>> */
    private array $unresolved = [];

    /** @var list<string> */
    private array $notes = [];

    public function drop(string $kind, int $count = 1): void
    {
        if ($count > 0) {
            $this->dropped[$kind] = ($this->dropped[$kind] ?? 0) + $count;
        }
    }

    /**
     * An image or link with no target on the new site: `image` or `link`,
     * and the old URL.
     */
    public function unresolved(string $kind, string $url): void
    {
        $this->unresolved[$kind][] = $url;
    }

    public function note(string $note): void
    {
        $this->notes[] = $note;
    }

    /** @return array<string, int> */
    public function dropped(): array
    {
        ksort($this->dropped);

        return $this->dropped;
    }

    /** @return array<string, list<string>> */
    public function unresolvedItems(): array
    {
        ksort($this->unresolved);

        return $this->unresolved;
    }

    /** @return list<string> */
    public function notes(): array
    {
        return $this->notes;
    }

    public function droppedCount(?string $kind = null): int
    {
        return $kind === null ? array_sum($this->dropped) : ($this->dropped[$kind] ?? 0);
    }

    public function unresolvedCount(?string $kind = null): int
    {
        return $kind === null
            ? array_sum(array_map(count(...), $this->unresolved))
            : count($this->unresolved[$kind] ?? []);
    }

    /**
     * @return array{dropped: array<string, int>, unresolved: array<string, list<string>>, notes: list<string>}
     */
    public function toArray(): array
    {
        return ['dropped' => $this->dropped(), 'unresolved' => $this->unresolvedItems(), 'notes' => $this->notes];
    }
}
