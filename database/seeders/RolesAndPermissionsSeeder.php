<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Clubs
            'clubs.approve',
            'clubs.reject',
            'clubs.manage',

            // Venues
            'venues.create',
            'venues.update',
            'venues.delete',

            // Bookings
            'bookings.view',
            'bookings.create',
            'bookings.cancel',
            'bookings.manage',

            // Settlements
            'settlements.create',
            'settlements.approve',
            'settlements.view',

            // Reviews
            'reviews.moderate',
            'reviews.delete',

            // Geography
            'geography.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Admin: all permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        // Club Manager
        $clubManager = Role::firstOrCreate(['name' => 'club_manager', 'guard_name' => 'web']);
        $clubManager->syncPermissions([
            'clubs.manage',
            'venues.create',
            'venues.update',
            'venues.delete',
            'bookings.view',
            'bookings.manage',
        ]);

        // Club Staff
        $clubStaff = Role::firstOrCreate(['name' => 'club_staff', 'guard_name' => 'web']);
        $clubStaff->syncPermissions([
            'venues.update',
            'bookings.view',
        ]);

        // Player
        $player = Role::firstOrCreate(['name' => 'player', 'guard_name' => 'web']);
        $player->syncPermissions([
            'bookings.create',
            'bookings.cancel',
        ]);

        // Also create legacy enum-based roles for compatibility
        Role::firstOrCreate(['name' => 'venue_manager', 'guard_name' => 'web']);

        $this->command->info('✓ Roles & permissions seeded: 5 roles, ' . count($permissions) . ' permissions');
    }
}
