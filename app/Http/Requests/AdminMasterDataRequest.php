<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class AdminMasterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    protected function uniqueFor(string $table, string $column, string $routeParameter): Unique
    {
        $rule = Rule::unique($table, $column);
        $record = $this->route($routeParameter);

        return $record instanceof Model ? $rule->ignore($record) : $rule;
    }
}
