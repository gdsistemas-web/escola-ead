<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ScormImportService;
use Illuminate\Http\Request;
use RuntimeException;

class ScormImportController extends Controller
{
    public function store(Request $request, ScormImportService $importer)
    {
        abort_unless($request->user()->hasAnyRole(['administrador', 'professor']), 403);

        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'course_name' => ['nullable', 'string', 'max:255'],
            'package' => ['required', 'file', 'mimes:zip', 'max:204800'],
        ]);

        try {
            return response()->json($importer->import(
                $data['package'],
                $request->user(),
                (int) $data['category_id'],
                $data['course_name'] ?? null,
            ), 201);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
