<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Services\MasterDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class UserController extends MasterDataController
{
    private const ROUTE = 'users';

    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
        private readonly MasterDataService $masterData,
    ) {}

    public function index(): View
    {
        return $this->indexPage('Users', self::ROUTE, [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'role_name', 'label' => 'Role'],
            ['key' => 'is_active', 'label' => 'Status', 'boolean' => true],
        ]);
    }

    public function data(DataTableRequest $request): JsonResponse
    {
        return $this->dataTableResponse($this->users->dataTable($request), self::ROUTE);
    }

    public function create(): View
    {
        return $this->formPage('User', self::ROUTE, $this->fields(), options: [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());

        return $this->saved(self::ROUTE, 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return $this->formPage('User', self::ROUTE, $this->fields(), $user, [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $attributes = $request->validated();
        if ($attributes['password'] === null) {
            unset($attributes['password']);
        }

        $this->users->update($user, $attributes);

        return $this->saved(self::ROUTE, 'User updated successfully.');
    }

    public function destroy(DeleteMasterDataRequest $request, User $user): RedirectResponse
    {
        $this->masterData->deleteUser($user, (int) $request->user()->getAuthIdentifier());

        return $this->saved(self::ROUTE, 'User deleted successfully.');
    }

    private function fields(): array
    {
        return [
            ['name' => 'role_id', 'label' => 'Role', 'type' => 'select', 'required' => true, 'options_key' => 'roles'],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'maxlength' => 255],
            ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'minlength' => 8],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
        ];
    }

    private function roleOptions(): array
    {
        return $this->roles->options()
            ->mapWithKeys(fn ($role) => [$role->id => $role->name])
            ->all();
    }
}
