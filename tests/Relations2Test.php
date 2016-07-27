<?php

class Relations2Test extends TestCase
{
    public function tearDown()
    {
        User::truncate();
        Roler::truncate();
        Permission::truncate();
    }

    protected function out($mix){
        fwrite(STDOUT, print_r($mix, true));
    }

    public function testBelongsToMany()
    {
        $role = Roler::create([
            'id' => 'super_admin',
            'display_name' => 'Super Admin',
            'description' => 'Super admin, can do everything!'
        ]);

        $perm = new Permission([
            'id' => 'create_exchanges',
            'display_name' => 'Create Exchanges',
            'description' => 'Create news exchanges pairs'
        ]);

        // Add 2 clients
        $role->permissions()->save($perm);

        $role->permissions()->create([
            'id' => 'edit_exchanges',
            'display_name' => 'Edit Exchanges',
            'description' => 'Edit existing exchanges pairs'
        ]);

         //Refetch
        $role = Roler::with('permissions')->find($role->getKey());
        $permission = Permission::with('rolers')->first();

        $this->assertTrue(array_key_exists('roler_ids', $permission->getAttributes()));
        $this->assertTrue(array_key_exists('permission_ids', $role->getAttributes()));

        $permissions = $role->getRelation('permissions');
        $roles = $permission->getRelation('rolers');

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $roles);
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $permissions);

        $this->assertInstanceOf('Permission', $permissions[0]);
        $this->assertInstanceOf('Roler', $roles[0]);
        $this->assertCount(2, $role->permissions);
        $this->assertCount(1, $permission->rolers);

        $permissions = Permission::whereIn('roler_ids', $role->getKey())->get();
        $roles = Roler::whereIn('permission_ids', $permission->getKey())->get();

        $this->assertCount(2, $permissions);
        $this->assertCount(1, $roles);

        $role2 = Roler::create([
            'id' => 'root',
            'display_name' => 'Root',
            'description' => 'Root user, can do everything and more!'
        ]);

        $permission = $role->permissions()->create([
            'id' => 'create_currencies',
            'display_name' => 'Create Currencies',
            'description' => 'Create new currencies',
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $permission->rolers);
        $this->assertInstanceOf('Roler', $permission->rolers->first());
        $this->assertCount(1, $permission->rolers);

        $role2->permissions()->attach($permission);

        $roles = Roler::whereIn('permission_ids', $permission->getKey())->get();
        $this->assertCount(2, $roles);

        //detach
        $permission = Permission::find('create_currencies');

        $permission->rolers()->detach($role);
        $this->assertCount(1, $permission->rolers);
    }
}
