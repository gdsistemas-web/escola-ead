<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\ScormRuntimeService;
use Illuminate\Http\Request;

class ScormRuntimeController extends Controller
{
    public function launch(Request $request, Lesson $lesson, ScormRuntimeService $runtime)
    {
        return $runtime->launchData($lesson, $request->user());
    }

    public function commit(Request $request, Lesson $lesson, ScormRuntimeService $runtime)
    {
        $data = $request->validate([
            'values' => ['required', 'array'],
            'finished' => ['nullable', 'boolean'],
        ]);

        return $runtime->commit($lesson, $request->user(), $data['values'], (bool) ($data['finished'] ?? false));
    }
}
