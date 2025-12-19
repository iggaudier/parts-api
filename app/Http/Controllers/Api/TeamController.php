<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        // Only System Admins can view all teams
        if (! $request->user()->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $query = Team::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $teams = $query->withCount(['users', 'teamParts'])
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $teams,
        ]);
    }

    public function store(Request $request)
    {
        // Only System Admins can create teams
        if (! $request->user()->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $team = Team::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Team created successfully',
            'data' => $team,
        ], 201);
    }

    public function show(Request $request, Team $team)
    {
        // System Admins can view any team
        // Team Admins can view their own team
        if (! $request->user()->isSystemAdmin() && $request->user()->team_id !== $team->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $team->load(['users', 'teamParts.systemPart']);

        return response()->json([
            'success' => true,
            'data' => $team,
        ]);
    }

    public function update(Request $request, Team $team)
    {
        // Only System Admins can update teams
        if (! $request->user()->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:teams,name,'.$team->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $team->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Team updated successfully',
            'data' => $team,
        ]);
    }

    public function destroy(Request $request, Team $team)
    {
        // Only System Admins can delete teams
        if (! $request->user()->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team deleted successfully',
        ]);
    }
}
