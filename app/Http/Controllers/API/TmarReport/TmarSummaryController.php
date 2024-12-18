<?php

namespace App\Http\Controllers\API\TmarReport;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TmarSummary;

class TmarSummaryController extends Controller
{
    public function index(Request $request)
    {
        // columns to filter
        $defaultColumns = [
            'id', 'pc', 'area', 'count_per_area', 'store_number',
            'sas_name', 'other_name', 'star_0', 'star_1', 'star_2',
            'star_3', 'star_4', 'all_star', 'team_leader', 'sldc',
            'sletp', 'total_team_member', 'average_tenure', 'retention_90_days',
            'restaurant_basics', 'foh'
        ];

        $columns = $request->input('columns', $defaultColumns);

        $validColumns = array_intersect($columns, $defaultColumns);

        if (empty($validColumns)) {
            return response()->json([
                'message' => 'Invalid columns selected.'
            ], 400);
        }

        $data = TmarSummary::select($validColumns)->get();

        return response()->json([
            'data' => $data,
            'selected_columns' => $validColumns
        ]);
    }
}
