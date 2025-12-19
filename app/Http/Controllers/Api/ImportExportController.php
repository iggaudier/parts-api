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
        // Only System Admins can import
        if (! $request->user()->isSystemAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $import = new SystemPartsImport;
            Excel::import($import, $request->file('file'));

            $errors = $import->errors();
            $errorCount = count($errors);

            return response()->json([
                'success' => true,
                'message' => $errorCount > 0
                    ? "Import completed with {$errorCount} errors"
                    : 'System parts imported successfully',
                'errors' => $errorCount > 0 ? $errors : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
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

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'team_id' => 'required|exists:teams,id',
        ]);

        // Team Admins can only import for their team
        if ($user->isTeamAdmin() && $user->team_id != $request->team_id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only import pricing for your own team',
            ], 403);
        }

        try {
            $import = new TeamPricingImport($request->team_id);
            Excel::import($import, $request->file('file'));

            $errors = $import->errors();
            $errorCount = count($errors);

            return response()->json([
                'success' => true,
                'message' => $errorCount > 0
                    ? "Import completed with {$errorCount} errors"
                    : 'Team pricing imported successfully',
                'errors' => $errorCount > 0 ? $errors : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
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
