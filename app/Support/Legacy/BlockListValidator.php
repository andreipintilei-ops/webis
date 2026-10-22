<?php

namespace App\Support\Legacy;

use App\Blocks\BlockRegistry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Validation\ValidationException;

/**
 * Holds converted blocks to the page builder's own rules (BlockRegistry's,
 * exactly as `validate()` applies them). A block that fails is left out and
 * reported, never returned invalid.
 *
 * The preview has no media library yet — its image ids are placeholders —
 * so it can ask for asset ids to be taken as existing; every other rule,
 * `exists` on other tables included, runs as usual.
 *
 * @phpstan-import-type StoredBlock from BlockRegistry
 */
final class BlockListValidator
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly bool $assumeAssetsExist = false,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<StoredBlock>
     */
    public function validate(array $blocks, ConversionReport $report): array
    {
        if (count($blocks) > BlockRegistry::MAX_BLOCKS) {
            $report->drop('invalid:too-many-blocks', count($blocks) - BlockRegistry::MAX_BLOCKS);
            $blocks = array_slice($blocks, 0, BlockRegistry::MAX_BLOCKS);
        }

        $valid = [];

        foreach ($blocks as $block) {
            $errors = $this->errors([$block]);

            if ($errors === []) {
                $valid[] = $block;

                continue;
            }

            $report->drop('invalid:'.($block['type'] ?? 'unknown'));
            $report->note('Left out an invalid '.($block['type'] ?? 'unknown').' block: '.implode(' ', Arr::flatten($errors)));
        }

        $errors = $this->errors($valid);

        if ($errors !== []) {
            $report->drop('invalid:block-list', count($valid));
            $report->note('Left out every block: '.implode(' ', Arr::flatten($errors)));

            return [];
        }

        return $this->registry->prepare($valid);
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array<string, list<string>>
     */
    private function errors(array $blocks): array
    {
        $validator = Validator::make(['blocks' => $blocks], $this->registry->rules($blocks));

        if ($this->assumeAssetsExist) {
            $validator->setPresenceVerifier($this->assetsAssumed($validator->getPresenceVerifier()));
        }

        try {
            $validator->validate();
        } catch (ValidationException $e) {
            return $e->errors();
        }

        return [];
    }

    private function assetsAssumed(PresenceVerifierInterface $verifier): PresenceVerifierInterface
    {
        return new class($verifier) implements PresenceVerifierInterface
        {
            public function __construct(private readonly PresenceVerifierInterface $verifier) {}

            /**
             * @param  array<mixed>  $extra
             */
            public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = [])
            {
                return $collection === 'assets' ? 1 : $this->verifier->getCount($collection, $column, $value, $excludeId, $idColumn, $extra);
            }

            /**
             * @param  array<mixed>  $values
             * @param  array<mixed>  $extra
             */
            public function getMultiCount($collection, $column, array $values, array $extra = [])
            {
                return $collection === 'assets' ? count(array_unique($values)) : $this->verifier->getMultiCount($collection, $column, $values, $extra);
            }
        };
    }
}
