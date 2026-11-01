<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Repositories\ItemRepository;
use App\Repositories\ReportRepository;
use App\Repositories\WarehouseRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportRepository $reports,
        private readonly ItemRepository $items,
        private readonly WarehouseRepository $warehouses,
    ) {}

    public function profitAndLoss(): View
    {
        return view('reports.profit-loss', ['user' => request()->user()]);
    }

    public function profitAndLossData(DataTableRequest $request): JsonResponse
    {
        return response()->json($this->reports->profitAndLoss($request));
    }

    public function stockCard(): View
    {
        return view('reports.stock-card', [
            'items' => $this->items->activeOptions(),
            'warehouses' => $this->warehouses->activeOptions(),
            'user' => request()->user(),
        ]);
    }

    public function stockCardData(DataTableRequest $request): JsonResponse
    {
        return response()->json($this->reports->stockCard($request));
    }
}
