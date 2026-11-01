<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

abstract class MasterDataController extends Controller
{
    protected function indexPage(
        string $title,
        string $routeName,
        array $columns,
    ): View {
        return view('master-data.index', [
            'title' => $title,
            'routeName' => $routeName,
            'columns' => $columns,
            'user' => request()->user(),
        ]);
    }

    protected function dataTableResponse(array $result, string $routeName): JsonResponse
    {
        $result['data'] = $result['data']->map(function (Model $record) use ($routeName): array {
            return [
                ...$record->getAttributes(),
                'edit_url' => route($routeName.'.edit', $record),
                'delete_url' => route($routeName.'.destroy', $record),
            ];
        });

        return response()->json($result);
    }

    protected function formPage(
        string $title,
        string $routeName,
        array $fields,
        ?Model $record = null,
        array $options = [],
    ): View {
        return view('master-data.form', [
            'title' => $title,
            'routeName' => $routeName,
            'fields' => $fields,
            'record' => $record,
            'options' => $options,
            'user' => request()->user(),
        ]);
    }

    protected function saved(string $routeName, string $message): RedirectResponse
    {
        return redirect()->route($routeName.'.index')->with('success', $message);
    }
}
