<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Drag-to-reorder: the list posts every id in its new order and each row's
 * `sort_order` becomes its position. Lists offer dragging only when they show
 * every row, so positions never collide with an unseen page.
 */
trait SavesOrder
{
    /**
     * @param  class-string<Model>  $model
     */
    protected function saveOrder(string $model, Request $request): void
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ])['ids'];

        DB::transaction(function () use ($model, $ids): void {
            foreach (array_values($ids) as $position => $id) {
                $model::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });
    }
}
