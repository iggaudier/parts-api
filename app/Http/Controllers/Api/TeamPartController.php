<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemPart;
use App\Models\Team;
use App\Models\TeamPart;
use Illuminate\Http\Request;

class TeamPartController extends Controller
{
    // Team Members view their team's parts
    public function index(Request $request)
    {
        $user = $request->user();

        // Team Members and Team Admins can only view their team's parts
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

        // Filters
        if ($request->has('part_type')) {
            $query->whereHas('systemPart', function ($q) use ($request) {
                $q->where('part_type', $request->part_type);
            });
        }

        if ($request->has('manufacturer')) {
            $query->whereHas('systemPart', function ($q) use ($request) {
                $q->where('manufacturer', $request->manufacturer);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('systemPart', function ($q) use ($search) {
                $q->where('model_number', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%")
                    ->orWhere('part_type', 'like', "%{$search}%");
            });
        }

        $teamParts = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $teamParts,
        ]);
    }

    // Team Admins can associate parts to their team
    public function store(Request $request)
    {
        $user = $request->user();

        // Only Team Admins can associate parts (for their team) and System Admins (any team)
        if ($user->isTeamMember()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'system_part_id' => 'required|exists:system_parts,id',
            'multiplier' => 'nullable|numeric|min:0',
            'static_price' => 'nullable|numeric|min:0',
        ]);

        // Team Admins can only add to their own team
        if ($user->isTeamAdmin() && $user->team_id != $validated['team_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You can only add parts to your own team',
            ], 403);
        }

        // Check if already associated
        $exists = TeamPart::where('team_id', $validated['team_id'])
            ->where('system_part_id', $validated['system_part_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This part is already associated with the team',
            ], 422);
        }

        // Prefer multiplier over static_price
        if (isset($validated['multiplier']) && isset($validated['static_price'])) {
            unset($validated['static_price']);
        }

        $teamPart = TeamPart::create($validated);
        $teamPart->load('systemPart');

        return response()->json([
            'success' => true,
            'message' => 'Part associated with team successfully',
            'data' => $teamPart,
        ], 201);
    }

    public function show(Request $request, TeamPart $teamPart)
    {
        $user = $request->user();

        // Check permission
        if (! $user->isSystemAdmin() && $user->team_id !== $teamPart->team_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $teamPart->load('systemPart');

        return response()->json([
            'success' => true,
            'data' => $teamPart,
        ]);
    }

    // Team Admins can update pricing for their team parts
    public function update(Request $request, TeamPart $teamPart)
    {
        $user = $request->user();

        // Team Members cannot update
        if ($user->isTeamMember()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Team Admins can only update their own team
        if ($user->isTeamAdmin() && $user->team_id !== $teamPart->team_id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update parts for your own team',
            ], 403);
        }

        $validated = $request->validate([
            'multiplier' => 'nullable|numeric|min:0',
            'static_price' => 'nullable|numeric|min:0',
        ]);

        // Prefer multiplier over static_price
        if (isset($validated['multiplier']) && isset($validated['static_price'])) {
            unset($validated['static_price']);
        }

        $teamPart->update($validated);
        $teamPart->load('systemPart');

        return response()->json([
            'success' => true,
            'message' => 'Team part pricing updated successfully',
            'data' => $teamPart,
        ]);
    }

    public function destroy(Request $request, TeamPart $teamPart)
    {
        $user = $request->user();

        // Team Members cannot delete
        if ($user->isTeamMember()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Team Admins can only delete from their own team
        if ($user->isTeamAdmin() && $user->team_id !== $teamPart->team_id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only remove parts from your own team',
            ], 403);
        }

        $teamPart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Part removed from team successfully',
        ]);
    }

    // Bulk associate parts by part_type or manufacturer
    public function bulkAssociate(Request $request)
    {
        $user = $request->user();

        if ($user->isTeamMember()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'part_type' => 'nullable|string',
            'manufacturer' => 'nullable|string',
            'multiplier' => 'nullable|numeric|min:0',
            'static_price' => 'nullable|numeric|min:0',
        ]);

        // Team Admins can only add to their own team
        if ($user->isTeamAdmin() && $user->team_id != $validated['team_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You can only add parts to your own team',
            ], 403);
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

        $systemParts = $query->get();
        $created = 0;
        $skipped = 0;

        foreach ($systemParts as $part) {
            $exists = TeamPart::where('team_id', $validated['team_id'])
                ->where('system_part_id', $part->id)
                ->exists();

            if (! $exists) {
                TeamPart::create([
                    'team_id' => $validated['team_id'],
                    'system_part_id' => $part->id,
                    'multiplier' => $validated['multiplier'] ?? null,
                    'static_price' => $validated['static_price'] ?? null,
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk association completed. Created: {$created}, Skipped: {$skipped}",
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
            ],
        ]);
    }
}
