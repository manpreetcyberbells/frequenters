<?php

namespace App\Traits;

use App\Models\Admin\Category;
use Illuminate\Http\JsonResponse;

/**
 * Grand category creation logic — was duplicated in MenuController and ServiceController
 */
trait ResolvesGrandCategory
{
    /**
     * Returns resolved category_id OR a JsonResponse error.
     *
     * @return int|JsonResponse
     */
    protected function resolveGrandCategoryId(array $validated): int|JsonResponse
    {
        if (!empty($validated['grandcategory_id'] ?? null)) {
            return (int) $validated['grandcategory_id'];
        }

        if (empty($validated['grandcategory'])) {
            return 0; // No grandcategory needed — caller should handle
        }

        if (empty($validated['parent_id']) || empty($validated['child_id'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Both parent_id and child_id are required to create a grandchild category.',
            ], 422);
        }

        $child = Category::find($validated['child_id']);
        if (!$child || $child->parent_id != $validated['parent_id']) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid child-parent category relationship.',
            ], 422);
        }

        $existing = Category::where('name', $validated['grandcategory'])
            ->where('parent_id', $validated['child_id'])
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return Category::create([
            'name'      => $validated['grandcategory'],
            'parent_id' => $validated['child_id'],
        ])->id;
    }
}
