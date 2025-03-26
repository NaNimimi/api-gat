<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        \DB::table('roles')->insert([
            [
                'name' => 'Admin',
                'guard_name' => 'api',
            ],
        ]);



        \DB::table('roles')->insert([
            [
                'name' => 'Manager',
                'guard_name' => 'api',
            ],
        ]);


        User::factory()->create([
            'name' => 'Test User',
            'username' => 'admin',
            'password' => bcrypt(12345),
        ])->assignRole('Admin');

        $permission = Permission::create([
            'name' => 'create',
            'translate' => 'создать',
        ]);

        $permission2 = Permission::create([
            'name' => 'update',
            'translate' => 'обновить',
        ]);

        $role2 = Role::find(2);

        $role2->syncPermissions([$permission->name, $permission2->name]);
        $permission->assignRole($role2);
        $permission2->assignRole($role2);
    }
}
