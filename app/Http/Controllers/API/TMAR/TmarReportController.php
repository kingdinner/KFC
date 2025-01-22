<?php

namespace App\Http\Controllers\API\TMAR;

use App\Http\Controllers\Controller;
use App\Traits\HandlesHelperController;
use Illuminate\Http\Request;
use App\Models\TmarReport as TmarSummary;
use App\Models\TMARAchievement;
use App\Models\Store;
use App\Models\StoreEmployee;
use App\Models\Employee;

use Illuminate\Pagination\LengthAwarePaginator;
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

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|numeric',
            'service_provider' => 'required|string|max:255',
            'tenure_months' => 'required|numeric',
            'ninety_day_retention' => 'required|boolean',
            'all_star' => 'nullable|string|in:gold,silver,bronze',
            'team_leader' => 'nullable|boolean',
            'sletp' => 'nullable|boolean',
            'resigned' => 'nullable|boolean',
            'rtm' => 'nullable|boolean',
            'remarks' => 'nullable|string',
            'basic_certification' => 'nullable|date',
            'food_safety' => 'nullable|date',
            'champs_certification' => 'nullable|date',
            'kitchen_station.level' => 'nullable|string|in:gold,silver,bronze',
            'kitchen_station.date' => 'nullable|date',
            'counter_station.level' => 'nullable|string|in:gold,silver,bronze',
            'counter_station.date' => 'nullable|date',
            'dining_station.level' => 'nullable|string|in:gold,silver,bronze',
            'dining_station.date' => 'nullable|date',
        ]);
        $tmarAchievement = TMARAchievement::create([
            'employee_id' => $validatedData['employee_id'],
            'service_provider' => $validatedData['service_provider'],
            'tenure_months' => $validatedData['tenure_months'],
            'ninety_day_retention' => $validatedData['ninety_day_retention'],
            'all_star' => $validatedData['all_star'],
            'team_leader' => $validatedData['team_leader'] ?? false,
            'sletp' => $validatedData['sletp'] ?? false,
            'resigned' => $validatedData['resigned'] ?? false,
            'rtm' => $validatedData['rtm'] ?? false,
            'remarks' => $validatedData['remarks'],
            'basic_certification' => $validatedData['basic_certification'],
            'food_safety' => $validatedData['food_safety'],
            'champs_certification' => $validatedData['champs_certification'],
        ]);

        $tmarAchievement->stationLevels()->createMany([
            [
                'station_type' => 'kitchen_station',
                'level' => $validatedData['kitchen_station']['level'] ?? null,
                'date' => $validatedData['kitchen_station']['date'] ?? null,
            ],
            [
                'station_type' => 'counter_station',
                'level' => $validatedData['counter_station']['level'] ?? null,
                'date' => $validatedData['counter_station']['date'] ?? null,
            ],
            [
                'station_type' => 'dining_station',
                'level' => $validatedData['dining_station']['level'] ?? null,
                'date' => $validatedData['dining_station']['date'] ?? null,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'TMAR report created successfully.',
            'data' => $tmarAchievement->load('stationLevels'),
        ], 201);
    }

    public function viewAchievement(Request $request)
    {
        $search = $request->query('search', '');
        $perPage = (int) $request->query('per_page', 10);

        try {
            $achievementsQuery = TMARAchievement::with(['stationLevels', 'employee:id,firstname,lastname']);

            if (!empty($search)) {
                $achievementsQuery->where(function ($query) use ($search) {
                    $query->where('service_provider', 'like', "%$search%")
                        ->orWhere('remarks', 'like', "%$search%")
                        ->orWhere('all_star', $search);
                });

                // Check if search matches a store
                $store = Store::where('store_code', $search)->first();
                if ($store) {
                    // Fetch employees from the store
                    $employees = StoreEmployee::where('store_id', $store->id)
                        ->with('employee:id,firstname,lastname')
                        ->get();

                    $employeeIds = $employees->pluck('employee_id');

                    // Add employee IDs to the query
                    $achievementsQuery->orWhereIn('employee_id', $employeeIds);
                }
            }

            // Paginate results
            $achievements = $achievementsQuery->paginate($perPage);

            // Transform data for response
            $achievementData = $achievements->getCollection()->map(function ($achievement) {
                $employee = $achievement->employee;

                return [
                    'id' => $achievement->id,
                    'Name' => $employee ? $employee->firstname . ' ' . $employee->lastname : 'Unknown Employee',
                    'service_provider' => $achievement->service_provider,
                    'tenure_months' => $achievement->tenure_months,
                    'ninety_day_retention' => $achievement->ninety_day_retention,
                    'all_star' => $achievement->all_star,
                    'team_leader' => $achievement->team_leader,
                    'sletp' => $achievement->sletp,
                    'resigned' => $achievement->resigned,
                    'rtm' => $achievement->rtm,
                    'remarks' => $achievement->remarks,
                    'basic_certification' => $achievement->basic_certification,
                    'food_safety' => $achievement->food_safety,
                    'champs_certification' => $achievement->champs_certification,
                    'restaurant_basic' => $achievement->restaurant_basic,
                    'fod' => $achievement->fod,
                    'mod' => $achievement->mod,
                    'boh' => $achievement->boh,
                    'basic' => $achievement->basic,
                    'certification' => $achievement->certification,
                    'sldc' => $achievement->sldc,
                    'station_levels' => $achievement->stationLevels->map(function ($level) {
                        return [
                            'station_type' => $level->station_type,
                            'level' => $level->level,
                            'date' => $level->date,
                        ];
                    }),
                ];
            });

            $formattedPaginator = new LengthAwarePaginator(
                $achievementData,
                $achievements->total(),
                $achievements->perPage(),
                $achievements->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return response()->json([
                'success' => true,
                'data' => $formattedPaginator->items(),
                'pagination' => [
                    'current_page' => $formattedPaginator->currentPage(),
                    'from' => $formattedPaginator->firstItem(),
                    'to' => $formattedPaginator->lastItem(),
                    'per_page' => $formattedPaginator->perPage(),
                    'total' => $formattedPaginator->total(),
                    'last_page' => $formattedPaginator->lastPage(),
                    'next_page_url' => $formattedPaginator->nextPageUrl(),
                    'prev_page_url' => $formattedPaginator->previousPageUrl(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch achievements.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateAchievement(Request $request, $id)
    {
        $validatedData = $request->validate([
            'service_provider' => 'nullable|string|max:255',
            'tenure_months' => 'nullable|numeric',
            'ninety_day_retention' => 'nullable|boolean',
            'all_star' => 'nullable|string|in:gold,silver,bronze',
            'team_leader' => 'nullable|boolean',
            'sletp' => 'nullable|boolean',
            'resigned' => 'nullable|boolean',
            'rtm' => 'nullable|boolean',
            'remarks' => 'nullable|string',

            // Certification dates
            'basic_certification' => 'nullable|date',
            'food_safety' => 'nullable|date',
            'champs_certification' => 'nullable|date',
            'restaurant_basic' => 'nullable|date',
            'fod' => 'nullable|date',
            'mod' => 'nullable|date',
            'boh' => 'nullable|date',
            'basic' => 'nullable|date',
            'certification' => 'nullable|date',
            'sldc' => 'nullable|date',

            // Station levels and dates
            'kitchen_station.level' => 'nullable|string|in:gold,silver,bronze',
            'kitchen_station.date' => 'nullable|date',
            'counter_station.level' => 'nullable|string|in:gold,silver,bronze',
            'counter_station.date' => 'nullable|date',
            'dining_station.level' => 'nullable|string|in:gold,silver,bronze',
            'dining_station.date' => 'nullable|date',
        ]);

        try {
            $achievement = TMARAchievement::findOrFail($id);

            // Update main achievement data
            $achievement->update([
                'service_provider' => $validatedData['service_provider'] ?? $achievement->service_provider,
                'tenure_months' => $validatedData['tenure_months'] ?? $achievement->tenure_months,
                'ninety_day_retention' => $validatedData['ninety_day_retention'] ?? $achievement->ninety_day_retention,
                'all_star' => $validatedData['all_star'] ?? $achievement->all_star,
                'team_leader' => $validatedData['team_leader'] ?? $achievement->team_leader,
                'sletp' => $validatedData['sletp'] ?? $achievement->sletp,
                'resigned' => $validatedData['resigned'] ?? $achievement->resigned,
                'rtm' => $validatedData['rtm'] ?? $achievement->rtm,
                'remarks' => $validatedData['remarks'] ?? $achievement->remarks,
                'basic_certification' => $validatedData['basic_certification'] ?? $achievement->basic_certification,
                'food_safety' => $validatedData['food_safety'] ?? $achievement->food_safety,
                'champs_certification' => $validatedData['champs_certification'] ?? $achievement->champs_certification,
                'restaurant_basic' => $validatedData['restaurant_basic'] ?? $achievement->restaurant_basic,
                'fod' => $validatedData['fod'] ?? $achievement->fod,
                'mod' => $validatedData['mod'] ?? $achievement->mod,
                'boh' => $validatedData['boh'] ?? $achievement->boh,
                'basic' => $validatedData['basic'] ?? $achievement->basic,
                'certification' => $validatedData['certification'] ?? $achievement->certification,
                'sldc' => $validatedData['sldc'] ?? $achievement->sldc,
            ]);

            // Update or create station levels
            $stationUpdates = [
                'kitchen_station' => $validatedData['kitchen_station'] ?? null,
                'counter_station' => $validatedData['counter_station'] ?? null,
                'dining_station' => $validatedData['dining_station'] ?? null,
            ];

            foreach ($stationUpdates as $stationType => $data) {
                if ($data) {
                    $achievement->stationLevels()->updateOrCreate(
                        ['station_type' => $stationType],
                        ['level' => $data['level'] ?? null, 'date' => $data['date'] ?? null]
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'TMAR Achievement updated successfully.',
                'data' => $achievement->load('stationLevels'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update TMAR Achievement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
