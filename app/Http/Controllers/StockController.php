<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Repositories\StockRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    public function __construct(private readonly StockRepository $stock) {}

    public function index(): View
    {
        return view('inventory.stock.index', [
            'user' => request()->user(),
        ]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        return response()->json($this->stock->dataTableBalances($request));
    }
}
