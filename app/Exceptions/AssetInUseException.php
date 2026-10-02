<?php

namespace App\Exceptions;

use App\Models\Asset;
use RuntimeException;

/**
 * Thrown when deleting an asset that content still references. The admin
 * checks usages first and explains; this is the backstop for every other path.
 */
class AssetInUseException extends RuntimeException
{
    public static function for(Asset $asset, int $usages): self
    {
        return new self("Asset #{$asset->id} is still used in {$usages} place(s) and cannot be deleted.");
    }
}
