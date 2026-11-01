<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Role::class);
    }

    public function options(): Collection
    {
        return Role::query()->orderBy('name')->get(['id', 'name']);
    }
}
