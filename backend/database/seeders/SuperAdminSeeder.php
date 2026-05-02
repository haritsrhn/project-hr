<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the Tridaya Sejahtera Group holding entity, a super admin user,
     * their employment record, and the super_admin role assignment.
     *
     * Fully idempotent: uses updateOrInsert / insertOrIgnore throughout.
     */
    public function run(): void
    {
        // ----------------------------------------------------------------
        // 1. Holding Entity — Tridaya Sejahtera Group
        // ----------------------------------------------------------------
        DB::table('entities')->updateOrInsert(
            ['name' => 'Tridaya Sejahtera Group', 'type' => 'HOLDING'],
            [
                'id'         => $this->resolveOrCreate('entities', ['name' => 'Tridaya Sejahtera Group', 'type' => 'HOLDING'], 'id'),
                'name'       => 'Tridaya Sejahtera Group',
                'type'       => 'HOLDING',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $entityId = DB::table('entities')
            ->where('name', 'Tridaya Sejahtera Group')
            ->where('type', 'HOLDING')
            ->value('id');

        // ----------------------------------------------------------------
        // 2. Super Admin User
        // ----------------------------------------------------------------
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@tridaya.id'],
            [
                'id'                => $this->resolveOrCreate('users', ['email' => 'admin@tridaya.id'], 'id'),
                'name'              => 'Super Admin',
                'email'             => 'admin@tridaya.id',
                'national_id'       => '0000000000000001',
                'password'          => Hash::make('TridayaAdmin2024!'),
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]
        );

        $userId = DB::table('users')
            ->where('email', 'admin@tridaya.id')
            ->value('id');

        // ----------------------------------------------------------------
        // 3. Employment — Super Admin at the Holding entity
        // ----------------------------------------------------------------
        DB::table('employments')->updateOrInsert(
            ['user_id' => $userId, 'entity_id' => $entityId],
            [
                'id'              => $this->resolveOrCreate('employments', ['user_id' => $userId, 'entity_id' => $entityId], 'id'),
                'user_id'         => $userId,
                'entity_id'       => $entityId,
                'employee_number' => 'EMP-HOLDING-001',
                'position'        => 'System Administrator',
                'department'      => 'Technology',
                'employment_type' => 'PERMANENT',
                'salary_basic'    => 0,           // not used for system admin
                'ptkp_status'     => 'TK0',
                'bpjs_kesehatan'  => false,
                'bpjs_tk'         => false,
                'join_date'       => now()->toDateString(),
                'is_primary'      => true,
                'status'          => 'ACTIVE',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]
        );

        // ----------------------------------------------------------------
        // 4. Assign super_admin role to the user (system-scoped, no entity)
        // ----------------------------------------------------------------
        $superAdminRoleId = DB::table('roles')
            ->where('slug', 'super_admin')
            ->value('id');

        if (! $superAdminRoleId) {
            $this->command->warn('SuperAdminSeeder: super_admin role not found. Run RolePermissionSeeder first.');
            return;
        }

        DB::table('user_roles')->insertOrIgnore([
            'id'         => Str::uuid()->toString(),
            'user_id'    => $userId,
            'role_id'    => $superAdminRoleId,
            'entity_id'  => null,               // SYSTEM scope role has no entity restriction
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('SuperAdminSeeder: holding entity, super admin user, and role assignment created.');
        $this->command->line('  Email    : admin@tridaya.id');
        $this->command->line('  Password : TridayaAdmin2024!');
    }

    /**
     * Return the existing primary key value for the given conditions if a record
     * already exists, otherwise generate a new UUID string.
     *
     * This prevents updateOrInsert from generating a new UUID on every re-run
     * while avoiding a race condition between the existence check and the upsert.
     */
    private function resolveOrCreate(string $table, array $conditions, string $column): string
    {
        $existing = DB::table($table)->where($conditions)->value($column);

        return $existing ?? Str::uuid()->toString();
    }
}
