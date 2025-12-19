<?php

namespace App\Http\Controllers\Api;

use App\Exports\SystemPartsExport;
use App\Exports\TeamPricingExport;
use App\Http\Controllers\Controller;
use App\Imports\SystemPartsImport;
use App\Imports\TeamPricingImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    // Import System Parts
    public function importSystemParts(Request $request)
    {
        if (! $request->user()->isSystemAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv',
        ]);

        try {
            $importer = new SystemPartsImport;
            $errors = $importer->import($request->file('file')->getRealPath());

            return response()->json([
                'success' => true,
                'message' => count($errors)
                    ? 'Import completed with errors'
                    : 'System parts imported successfully',
                'errors' => $errors ?: null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Export System Parts
    public function exportSystemParts(Request $request)
    {
        // Only System Admins can export
        if (! $request->user()->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $filters = [
                'is_active' => $request->boolean('is_active', null),
                'part_type' => $request->part_type,
                'manufacturer' => $request->manufacturer,
            ];

            $fileName = 'system-parts-'.now()->format('Y-m-d-His').'.xlsx';

            return Excel::download(new SystemPartsExport($filters), $fileName);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: '.$e->getMessage(),
            ], 500);
        }
    }

    // Import Team Pricing
    public function importTeamPricing(Request $request)
    {
        $user = $request->user();

        // Team Members cannot import
        if ($user->isTeamMember()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:csv',
            'team_id' => 'required|exists:teams,id',
        ]);

        // Team Admins can only import for their own team
        if ($user->isTeamAdmin() && $user->team_id != $validated['team_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You can only import pricing for your own team',
            ], 403);
        }

        try {
            $importer = new TeamPricingImport((int) $validated['team_id']);
            $errors = $importer->import(
                $validated['file']->getRealPath()
            );

            return response()->json([
                'success' => true,
                'message' => count($errors)
                    ? 'Import completed with errors'
                    : 'Team pricing imported successfully',
                'errors' => count($errors) ? $errors : null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Export Team Pricing
    public function exportTeamPricing(Request $request)
    {
        $user = $request->user();

        // Team Members cannot export
        if ($user->isTeamMember()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        // Team Admins can only export their team
        if ($user->isTeamAdmin() && $user->team_id != $request->team_id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only export pricing for your own team',
            ], 403);
        }

        try {
            $fileName = 'team-pricing-'.$request->team_id.'-'.now()->format('Y-m-d-His').'.xlsx';

            return Excel::download(new TeamPricingExport($request->team_id), $fileName);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
