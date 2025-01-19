<?php

namespace App\Http\Controllers\API\TMAR;

use App\Http\Controllers\Controller;
use App\Traits\HandlesHelperController;
use Illuminate\Http\Request;
use App\Models\TmarReport as TmarSummary;

class TmarReportController extends Controller
{
    use HandlesHelperController;

    public function index(Request $request)
    {
        // Define the default columns
        $defaultColumns = [
            'id', 'pc', 'area', 'count_per_area', 'store_number',
            'sas_name', 'other_name', 'star_0', 'star_1', 'star_2',
            'star_3', 'star_4', 'all_star', 'team_leader', 'sldc',
            'sletp', 'total_team_member', 'average_tenure', 'retention_90_days',
            'restaurant_basics', 'foh'
        ];

        // Retrieve columns from JSON input
        $columns = $request->input('columns', $defaultColumns);

        // Validate columns against defaults
        $validColumns = array_intersect($columns, $defaultColumns);

        if (empty($validColumns)) {
            return response()->json([
                'message' => 'Invalid columns selected.',
            ], 400);
        }

        $search = trim($request->input('search', ''));
        $perPage = (int) $request->input('per_page', 10);

        // Query TmarSummary with selected columns
        $query = TmarSummary::select($validColumns);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('area', 'ILIKE', "%{$search}%")
                  ->orWhere('store_number', 'ILIKE', "%{$search}%")
                  ->orWhere('sas_name', 'ILIKE', "%{$search}%")
                  ->orWhere('other_name', 'ILIKE', "%{$search}%");
            });
        }

        // Apply pagination
        $paginatedData = $query->paginate($perPage);

        return $this->paginateResponse($paginatedData);
    }
}
