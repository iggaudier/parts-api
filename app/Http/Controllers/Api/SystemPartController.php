<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemPart;
use Illuminate\Http\Request;

class SystemPartController extends Controller
{
    public function index(Request $request)
    {
        // Only System Admins and Team Admins can view system parts
        if ($request->user()->isTeamMember()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $query = SystemPart::query();

        // Filters
        if (in_array($request->is_active, ['0', '1', 0, 1], true)) {
            $query->where('is_active', (bool) $request->is_active);
        }

        if ($request->has('part_type')) {
            $query->where('part_type', $request->part_type);
        }

        if ($request->has('manufacturer')) {
            $query->where('manufacturer', $request->manufacturer);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $parts = $query->orderBy('manufacturer')
            ->orderBy('model_number')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $parts,
        ]);
    }

    public function store(Request $request)
    {
        // Only System Admins can create system parts
        if (! $request->user()->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'part_type' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'model_number' => 'required|string|max:255',
            'list_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate manufacturer + model_number
        $exists = SystemPart::where('manufacturer', $validated['manufacturer'])
            ->where('model_number', $validated['model_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A part with this manufacturer and model number already exists',
            ], 422);
        }

        $part = SystemPart::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'System part created successfully',
            'data' => $part,
        ], 201);
    }

    public function show(Request $request, SystemPart $systemPart)
    {
        // Only System Admins and Team Admins can view system parts
        if ($request->user()->isTeamMember()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $systemPart,
        ]);
    }

    public function update(Request $request, SystemPart $systemPart)
    {
        // Only System Admins can update system parts
        if (! $request->user()->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'part_type' => 'sometimes|string|max:255',
            'manufacturer' => 'sometimes|string|max:255',
            'model_number' => 'sometimes|string|max:255',
            'list_price' => 'sometimes|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate if manufacturer or model_number changed
        if (isset($validated['manufacturer']) || isset($validated['model_number'])) {
            $manufacturer = $validated['manufacturer'] ?? $systemPart->manufacturer;
            $modelNumber = $validated['model_number'] ?? $systemPart->model_number;

            $exists = SystemPart::where('manufacturer', $manufacturer)
                ->where('model_number', $modelNumber)
                ->where('id', '!=', $systemPart->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A part with this manufacturer and model number already exists',
                ], 422);
            }
        }

        $systemPart->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'System part updated successfully',
            'data' => $systemPart,
        ]);
    }

    public function destroy(Request $request, SystemPart $systemPart)
    {
        // Only System Admins can delete system parts
        if (! $request->user()->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $systemPart->delete();

        return response()->json([
            'success' => true,
            'message' => 'System part deleted successfully',
        ]);
    }

    // Get unique part types
    public function partTypes(Request $request)
    {
        if ($request->user()->isTeamMember()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $partTypes = SystemPart::select('part_type')
            ->distinct()
            ->orderBy('part_type')
            ->pluck('part_type');

        return response()->json([
            'success' => true,
            'data' => $partTypes,
        ]);
    }

    // Get unique manufacturers
    public function manufacturers(Request $request)
    {
        if ($request->user()->isTeamMember()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $manufacturers = SystemPart::select('manufacturer')
            ->distinct()
            ->orderBy('manufacturer')
            ->pluck('manufacturer');

        return response()->json([
            'success' => true,
            'data' => $manufacturers,
        ]);
    }
}
