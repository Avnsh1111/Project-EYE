<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\QuotaService;
use Illuminate\Http\Request;

class QuotaController extends Controller
{
    public function __construct(private QuotaService $quotaService) {}

    public function show(Request $request)
    {
        $usage = $this->quotaService->getUsage($request->user());
        return response()->json($usage);
    }
}
