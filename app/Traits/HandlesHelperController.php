<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait HandlesHelperController{

	public function paginateResponse(LengthAwarePaginator $paginator)
	{
		return response()->json([
			'success' => true,
			'data' => $paginator->items(),
			'pagination' => [
				'current_page' => $paginator->currentPage(),
				'first_page_url' => $paginator->url(1),
				'last_page' => $paginator->lastPage(),
				'last_page_url' => $paginator->url($paginator->lastPage()),
				'next_page_url' => $paginator->nextPageUrl(),
				'prev_page_url' => $paginator->previousPageUrl(),
				'per_page' => $paginator->perPage(),
				'total' => $paginator->total(),
			],
		], 200);
	}
}


