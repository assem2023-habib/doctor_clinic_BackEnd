<?php

namespace Database\Seeders;

use App\Domains\RBAC\Models\Permission;
use App\Domains\RBAC\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissionDefs = [
            ['name' => 'View Appointments', 'slug' => 'appointments.view', 'group' => 'Appointments'],
            ['name' => 'Create Appointments', 'slug' => 'appointments.create', 'group' => 'Appointments'],
            ['name' => 'Edit Appointments', 'slug' => 'appointments.edit', 'group' => 'Appointments'],
            ['name' => 'Delete Appointments', 'slug' => 'appointments.delete', 'group' => 'Appointments'],
            ['name' => 'View Patients', 'slug' => 'patients.view', 'group' => 'Patients'],
            ['name' => 'Create Patients', 'slug' => 'patients.create', 'group' => 'Patients'],
            ['name' => 'Edit Patients', 'slug' => 'patients.edit', 'group' => 'Patients'],
            ['name' => 'Delete Patients', 'slug' => 'patients.delete', 'group' => 'Patients'],
            ['name' => 'View Doctors', 'slug' => 'doctors.view', 'group' => 'Doctors'],
            ['name' => 'Edit Doctors', 'slug' => 'doctors.edit', 'group' => 'Doctors'],
            ['name' => 'Delete Doctors', 'slug' => 'doctors.delete', 'group' => 'Doctors'],
            ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'RBAC'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'group' => 'RBAC'],
            ['name' => 'Edit Roles', 'slug' => 'roles.edit', 'group' => 'RBAC'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'group' => 'RBAC'],
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'group' => 'RBAC'],
            ['name' => 'Create Permissions', 'slug' => 'permissions.create', 'group' => 'RBAC'],
            ['name' => 'Edit Permissions', 'slug' => 'permissions.edit', 'group' => 'RBAC'],
            ['name' => 'Delete Permissions', 'slug' => 'permissions.delete', 'group' => 'RBAC'],
            ['name' => 'Manage User Roles', 'slug' => 'users.roles.manage', 'group' => 'RBAC'],
            ['name' => 'View Countries', 'slug' => 'countries.view', 'group' => 'Locations'],
            ['name' => 'Create Countries', 'slug' => 'countries.create', 'group' => 'Locations'],
            ['name' => 'Edit Countries', 'slug' => 'countries.edit', 'group' => 'Locations'],
            ['name' => 'Delete Countries', 'slug' => 'countries.delete', 'group' => 'Locations'],
            ['name' => 'View Cities', 'slug' => 'cities.view', 'group' => 'Locations'],
            ['name' => 'Create Cities', 'slug' => 'cities.create', 'group' => 'Locations'],
            ['name' => 'Edit Cities', 'slug' => 'cities.edit', 'group' => 'Locations'],
            ['name' => 'Delete Cities', 'slug' => 'cities.delete', 'group' => 'Locations'],
            ['name' => 'View Medicines', 'slug' => 'medicines.view', 'group' => 'Medicines'],
            ['name' => 'Create Medicines', 'slug' => 'medicines.create', 'group' => 'Medicines'],
            ['name' => 'Edit Medicines', 'slug' => 'medicines.edit', 'group' => 'Medicines'],
            ['name' => 'Delete Medicines', 'slug' => 'medicines.delete', 'group' => 'Medicines'],
        ];

        $allSlugs = collect();
        foreach ($permissionDefs as $def) {
            $perm = Permission::firstOrCreate(['slug' => $def['slug']], $def);
            $allSlugs->put($def['slug'], $perm);
        }

        $roles = [
            'super-admin' => [
                'name' => 'Super Admin',
                'guard_name' => 'api',
                'is_system' => true,
                'permission_slugs' => collect($permissionDefs)->pluck('slug')->all(),
            ],
            'admin' => [
                'name' => 'Admin',
                'guard_name' => 'api',
                'is_system' => true,
                'permission_slugs' => collect($permissionDefs)->pluck('slug')->all(),
            ],
            'doctor' => [
                'name' => 'Doctor',
                'guard_name' => 'api',
                'is_system' => true,
                'permission_slugs' => ['appointments.view', 'appointments.edit', 'patients.view', 'doctors.view', 'medicines.view', 'medicines.create', 'medicines.edit', 'medicines.delete'],
            ],
            'patient' => [
                'name' => 'Patient',
                'guard_name' => 'api',
                'is_system' => true,
                'permission_slugs' => ['appointments.view', 'appointments.create', 'patients.view', 'medicines.view', 'medicines.create'],
            ],
            'receptionist' => [
                'name' => 'Receptionist',
                'guard_name' => 'api',
                'is_system' => false,
                'permission_slugs' => ['appointments.view', 'appointments.create', 'appointments.edit', 'patients.view', 'patients.create', 'patients.edit', 'doctors.view', 'medicines.view', 'medicines.create', 'medicines.edit', 'medicines.delete'],
            ],
        ];

        foreach ($roles as $slug => $config) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $config['name'],
                    'guard_name' => $config['guard_name'],
                    'is_system' => $config['is_system'],
                ]
            );

            $permissionIds = $allSlugs->only($config['permission_slugs'])->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
