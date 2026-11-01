<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\Category;
use App\Models\Item;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_a_category(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->post(route('categories.store'), ['name' => 'Packaging', 'description' => 'Supplies'])
            ->assertRedirect(route('categories.index'));

        $category = Category::query()->where('name', 'Packaging')->firstOrFail();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'description' => 'Supplies']);

        $this->put(route('categories.update', $category), [
            'name' => 'Packaging',
            'description' => 'Updated description',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'description' => 'Updated description']);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_non_admin_cannot_access_master_data_routes(): void
    {
        $user = $this->userWithRole('warehouse');

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertForbidden();

        $this->getJson(route('categories.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]))->assertForbidden();

        $this->post(route('categories.store'), ['name' => 'Hidden'])
            ->assertForbidden();

        $this->assertDatabaseMissing('categories', ['name' => 'Hidden']);
    }

    public function test_admin_can_open_all_master_data_lists_and_forms(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin);

        foreach (['categories', 'units', 'warehouses', 'partners', 'items', 'users'] as $resource) {
            $this->get(route($resource.'.index'))->assertOk();
            $this->get(route($resource.'.create'))->assertOk();
            $this->getJson(route($resource.'.data', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => ''],
                'order' => [['column' => 0, 'dir' => 'asc']],
            ]))->assertOk()->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);
        }
    }

    public function test_master_data_forms_render_client_side_constraints(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        $categoryForm = $this->get(route('categories.create'));
        $categoryForm->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('required', false)
            ->assertSee('maxlength="100"', false)
            ->assertSee('assets/js/admin.js', false);

        $itemForm = $this->get(route('items.create'));
        $itemForm->assertOk()
            ->assertSee('name="sale_price"', false)
            ->assertSee('min="0"', false)
            ->assertSee('step="0.01"', false);

        $userForm = $this->get(route('users.create'));
        $userForm->assertOk()
            ->assertSee('type="email"', false)
            ->assertSee('name="email"', false)
            ->assertSee('maxlength="255"', false)
            ->assertSee('name="password"', false)
            ->assertSee('minlength="8"', false)
            ->assertSee('required', false);
    }

    public function test_master_data_table_search_and_pagination_are_server_side(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        foreach (range(1, 12) as $number) {
            Category::query()->create(['name' => 'Category '.$number]);
        }
        Category::query()->create(['name' => 'Search Match']);

        $this->getJson(route('categories.data', [
            'draw' => 2,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'Search Match'],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ]))->assertOk()
            ->assertJsonPath('draw', 2)
            ->assertJsonPath('recordsTotal', 13)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Search Match');

        $this->getJson(route('categories.data', [
            'draw' => 3,
            'start' => 10,
            'length' => 5,
            'search' => ['value' => ''],
        ]))->assertOk()
            ->assertJsonPath('recordsTotal', 13)
            ->assertJsonPath('recordsFiltered', 13)
            ->assertJsonCount(3, 'data');
    }

    public function test_items_table_uses_raw_query_server_side_helper(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $category = Category::query()->create(['name' => 'Meat']);
        $unit = Unit::query()->create(['code' => 'KG', 'name' => 'Kilogram']);
        Item::query()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'MEAT-001',
            'name' => 'Beef',
            'item_type' => 'cut',
            'sale_price' => '10.00',
            'is_active' => true,
        ]);
        Item::query()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'MEAT-002',
            'name' => 'Lamb',
            'item_type' => 'cut',
            'sale_price' => '12.00',
            'is_active' => true,
        ]);

        $this->getJson(route('items.data', [
            'draw' => 4,
            'start' => 0,
            'length' => 1,
            'search' => ['value' => 'MEAT-002'],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ]))->assertOk()
            ->assertJsonPath('draw', 4)
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.sku', 'MEAT-002')
            ->assertJsonPath('data.0.category_name', 'Meat')
            ->assertJsonPath('data.0.unit_code', 'KG')
            ->assertJsonPath('data.0.edit_url', route('items.edit', Item::query()->where('sku', 'MEAT-002')->value('id')))
            ->assertJsonPath('data.0.delete_url', route('items.destroy', Item::query()->where('sku', 'MEAT-002')->value('id')));

        $this->get(route('items.index'))
            ->assertOk()
            ->assertSee('No items data is available yet.', false)
            ->assertSee('No items match your search.', false)
            ->assertSee('No items to display.', false);
    }

    public function test_used_category_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $category = Category::query()->create(['name' => 'Meat']);
        $unit = Unit::query()->create(['code' => 'KG', 'name' => 'Kilogram']);
        Item::query()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'MEAT-001',
            'name' => 'Beef',
            'item_type' => 'cut',
            'sale_price' => '10.00',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('categories.index'))
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasErrors('business');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_admin_can_create_users_without_exposing_or_overwriting_passwords(): void
    {
        $admin = $this->userWithRole('admin');
        $salesRole = Role::query()->where('name', 'sales')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'role_id' => $salesRole->id,
                'name' => 'Sales User',
                'email' => 'sales@example.test',
                'password' => 'original-password',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $created = User::query()->where('email', 'sales@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('original-password', $created->password));

        $this->put(route('users.update', $created), [
            'role_id' => $salesRole->id,
            'name' => 'Updated Sales User',
            'email' => 'sales@example.test',
            'password' => '',
            'is_active' => '1',
        ])->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('original-password', $created->fresh()->password));

        $this->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('original-password');

        $this->getJson(route('users.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]))->assertOk()
            ->assertJsonPath('data.1.name', 'Updated Sales User')
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonPath('data.1.role_name', 'sales');
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
