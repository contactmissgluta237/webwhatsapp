<?php

// database/seeders/RolePermissionSeeder.php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = PermissionEnum::values();
        $this->createPermissions($permissions);
        $this->createRolesWithPermissions();
        $this->assignAllPermissionsToAdmin();

        $this->command->info('Roles and permissions created successfully!');
    }

    /**
     * Create all permissions from enum.
     *
     * @param  array  $permissions  List of permission names
     */
    private function createPermissions(array $permissions): void
    {
        $this->command->info('Creating permissions...');

        $count = 0;
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $count++;
        }

        $this->command->info("{$count} permissions created.");
    }

    /**
     * Create roles and assign permissions to them using enums.
     */
    private function createRolesWithPermissions(): void
    {
        $this->command->info('Creating roles and assigning permissions...');

        foreach (UserRole::cases() as $roleEnum) {
            $roleName = $roleEnum->value;
            $permissions = $roleEnum->permissions();

            $role = Role::firstOrCreate(['name' => $roleName]);

            if (! empty($permissions)) {
                $role->syncPermissions($permissions);
                $this->command->info("Role '{$roleName}' created with ".count($permissions).' permissions.');
            } else {
                $this->command->info("Role '{$roleName}' created without specific permissions.");
            }
        }
    }

    /**
     * Ensure admin has all permissions
     */
    private function assignAllPermissionsToAdmin(): void
    {
        $this->command->info('Assigning all permissions to Admin...');

        $adminRole = Role::where('name', UserRole::ADMIN()->value)->first();

        if ($adminRole) {
            $allPermissions = Permission::all();
            $adminRole->syncPermissions($allPermissions);

            $this->command->info('Admin now has all '.$allPermissions->count().' permissions.');
        } else {
            $this->command->error('Admin role not found!');
        }
    }
}
