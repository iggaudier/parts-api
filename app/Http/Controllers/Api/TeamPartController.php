<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemPart;
use App\Models\TeamPart;
use Illuminate\Http\Request;

class TeamPartController extends Controller
{
    /**
     * Calculate team price explicitly
     */
    protected function calculateTeamPrice(SystemPart $systemPart, $multiplier, $staticPrice): float
    {
        if ($staticPrice !== null) {
            return (float) $staticPrice;
        }

        if ($multiplier !== null) {
            return round($systemPart->list_price * $multiplier, 2);
        }

        return (float) $systemPart->list_price;
    }

    // =========================
    // INDEX
    // =========================
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->isSystemAdmin() && ! $user->team_id) {
            return response()->json([
                'success' => false,
                'message' => 'User is not assigned to a team',
            ], 403);
        }

        $teamId = $user->isSystemAdmin() && $request->has('team_id')
            ? $request->team_id
            : $user->team_id;

        $query = TeamPart::with('systemPart')
            ->where('team_id', $teamId);

        if ($request->has('part_type')) {
            $query->whereHas('systemPart', fn ($q) => $q->where('part_type', $request->part_type)
            );
        }

        if ($request->has('manufacturer')) {
            $query->whereHas('systemPart', fn ($q) => $q->where('manufacturer', $request->manufacturer)
            );
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('systemPart', fn ($q) => $q->where('model_number', 'like', "%{$search}%")
                ->orWhere('manufacturer', 'like', "%{$search}%")
                ->orWhere('part_type', 'like', "%{$search}%")
            );
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->per_page ?? 50),
        ]);
    }

    // =========================
    // STORE (FIXED)
    // =========================
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->isTeamMember()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'system_part_id' => 'required|exists:system_parts,id',
            'multiplier' => 'nullable|numeric|min:0',
            'static_price' => 'nullable|numeric|min:0',
        ]);

        if ($user->isTeamAdmin() && $user->team_id != $validated['team_id']) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (TeamPart::where('team_id', $validated['team_id'])
            ->where('system_part_id', $validated['system_part_id'])
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This part is already associated with the team',
            ], 422);
        }

        if (isset($validated['multiplier'], $validated['static_price'])) {
            unset($validated['static_price']);
        }

        $systemPart = SystemPart::findOrFail($validated['system_part_id']);

        $teamPrice = $this->calculateTeamPrice(
            $systemPart,
            $validated['multiplier'] ?? null,
            $validated['static_price'] ?? null
        );

        $teamPart = TeamPart::create([
            ...$validated,
            'team_price' => $teamPrice,
        ]);

        $teamPart->load('systemPart');

        return response()->json([
            'success' => true,
            'message' => 'Part associated with team successfully',
            'data' => $teamPart,
        ], 201);
    }

    // =========================
    // UPDATE (FIXED)
    // =========================
    public function update(Request $request, TeamPart $teamPart)
    {
        $user = $request->user();

        if ($user->isTeamMember()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($user->isTeamAdmin() && $user->team_id !== $teamPart->team_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'multiplier' => 'nullable|numeric|min:0',
            'static_price' => 'nullable|numeric|min:0',
        ]);

        if (isset($validated['multiplier'], $validated['static_price'])) {
            unset($validated['static_price']);
        }

        $validated['team_price'] = $this->calculateTeamPrice(
            $teamPart->systemPart,
            $validated['multiplier'] ?? $teamPart->multiplier,
            $validated['static_price'] ?? $teamPart->static_price
        );

        $teamPart->update($validated);
        $teamPart->load('systemPart');

        return response()->json([
            'success' => true,
            'message' => 'Team part pricing updated successfully',
            'data' => $teamPart,
        ]);
    }

    // =========================
    // BULK ASSOCIATE (FIXED)
    // =========================
    public function bulkAssociate(Request $request)
    {
        $user = $request->user();

        if ($user->isTeamMember()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'part_type' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'multiplier' => 'nullable|numeric|min:0',
            'static_price' => 'nullable|numeric|min:0',
        ]);

        if ($user->isTeamAdmin() && $user->team_id != $validated['team_id']) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (! isset($validated['part_type']) && ! isset($validated['manufacturer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Either part_type or manufacturer must be provided',
            ], 422);
        }

        $query = SystemPart::where('is_active', true);

        if (isset($validated['part_type'])) {
            $query->where('part_type', $validated['part_type']);
        }

        if (isset($validated['manufacturer'])) {
            $query->where('manufacturer', $validated['manufacturer']);
        }

        $created = 0;
        $skipped = 0;

        foreach ($query->get() as $part) {
            if (TeamPart::where('team_id', $validated['team_id'])
                ->where('system_part_id', $part->id)
                ->exists()) {
                $skipped++;

                continue;
            }

            $teamPrice = $this->calculateTeamPrice(
                $part,
                $validated['multiplier'] ?? null,
                $validated['static_price'] ?? null
            );

            TeamPart::create([
                'team_id' => $validated['team_id'],
                'system_part_id' => $part->id,
                'multiplier' => $validated['multiplier'] ?? null,
                'static_price' => $validated['static_price'] ?? null,
                'team_price' => $teamPrice,
            ]);

            $created++;
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk association completed. Created: {$created}, Skipped: {$skipped}",
            'data' => compact('created', 'skipped'),
        ]);
    }
}
