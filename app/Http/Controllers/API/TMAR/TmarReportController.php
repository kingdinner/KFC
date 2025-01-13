<?php

namespace App\Http\Controllers\API\TMAR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TmarReport as TmarSummary;

class TmarReportController extends Controller
{
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

        // Fetch the data with the selected columns
        $data = TmarSummary::select($validColumns)->get();

        return response()->json([
            'data' => $data,
            'selected_columns' => $validColumns,
        ]);
    }
}
