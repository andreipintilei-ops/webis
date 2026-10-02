<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Binds Pest-style tests to the application TestCase so they boot the
| framework. Without this, `Blade::render()` and friends fail with
| "A facade root has not been set".
|
| The starter kit's own tests are classic PHPUnit classes that extend
| Tests\TestCase directly, so they were unaffected — which is why this file
| being empty went unnoticed until the first Pest-style test was written.
|
*/

pest()->extend(TestCase::class)->in('Feature', 'Unit');
