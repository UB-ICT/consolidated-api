<?php

namespace Modules\RequisitionSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RequisitionSystem\Models\Setting;

class SettingController extends Controller
{
    public function gstRate(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'key' => Setting::GST_RATE_PERCENT_KEY,
                'rate_percent' => Setting::gstRatePercent(),
            ],
        ]);
    }

    public function updateGstRate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rate_percent' => 'required|numeric|min:0|max:100',
        ]);

        $setting = Setting::query()->updateOrCreate(
            ['key' => Setting::GST_RATE_PERCENT_KEY],
            [
                'value' => (string) $data['rate_percent'],
                'description' => 'General Sales Tax rate applied to GST-applicable requisition line items (percent).',
            ]
        );

        Setting::forgetCached(Setting::GST_RATE_PERCENT_KEY);

        return response()->json([
            'success' => true,
            'message' => 'GST rate updated.',
            'data' => [
                'key' => $setting->key,
                'rate_percent' => (float) $setting->value,
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Setting::query()->orderBy('key')->get(),
        ]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $data = $request->validate([
            'value' => 'required|string|max:1000',
            'description' => 'nullable|string|max:255',
        ]);

        $allowed = [Setting::GST_RATE_PERCENT_KEY];

        if (!in_array($key, $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unknown setting key.',
            ], 404);
        }

        if ($key === Setting::GST_RATE_PERCENT_KEY) {
            $request->merge(['rate_percent' => $data['value']]);
            $request->validate([
                'rate_percent' => 'required|numeric|min:0|max:100',
            ]);
        }

        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $data['value'],
                'description' => $data['description'] ?? null,
            ]
        );

        Setting::forgetCached($key);

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }
}
