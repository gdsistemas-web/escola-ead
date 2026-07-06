<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return Setting::orderBy('group')->orderBy('key')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
            'value' => ['nullable', 'array'],
            'group' => ['nullable', 'string'],
        ]);

        return Setting::updateOrCreate(['key' => $data['key']], $data);
    }

    public function show(Setting $setting)
    {
        return $setting;
    }

    public function update(Request $request, Setting $setting)
    {
        $setting->update($request->validate(['value' => ['nullable', 'array'], 'group' => ['nullable', 'string']]));

        return $setting;
    }

    public function destroy(Setting $setting)
    {
        $setting->delete();

        return response()->noContent();
    }
}
