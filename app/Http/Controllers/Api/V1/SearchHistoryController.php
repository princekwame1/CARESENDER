<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->searches()
            ->latest('created_at')->limit(20)
            ->get(['id', 'query', 'type', 'city', 'area', 'results_count', 'created_at'])]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->searches()->delete();

        return response()->json(['message' => 'Search history cleared.']);
    }
}
