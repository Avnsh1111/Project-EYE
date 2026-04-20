<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Services\FamilyService;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function __construct(private FamilyService $familyService) {}

    public function index(Request $request)
    {
        $families = $request->user()->families()->with('members')->get();
        return response()->json($families);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $family = $this->familyService->create($request->user(), $request->name);
        return response()->json($family, 201);
    }

    public function join(Request $request, int $id)
    {
        $request->validate(['invite_code' => 'required|string']);
        try {
            $family = $this->familyService->join($request->user(), $id, $request->invite_code);
            return response()->json($family);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function leave(Request $request, int $id)
    {
        try {
            $this->familyService->leave($request->user(), $id);
            return response()->json(['message' => 'Left family']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
