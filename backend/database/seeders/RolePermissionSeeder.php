<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed roles, permissions, and the role-permission matrix.
     *
     * Uses updateOrCreate throughout so this seeder is fully idempotent —
     * safe to run on an existing database without creating duplicates.
     */
    public function run(): void
    {
        // ----------------------------------------------------------------
        // 1. Permissions
        // ----------------------------------------------------------------
        $permissionDefinitions = [
            // Group: entities
            ['group' => 'entities', 'slug' => 'entities.view',   'name' => 'View Entities'],
            ['group' => 'entities', 'slug' => 'entities.create', 'name' => 'Create Entities'],
            ['group' => 'entities', 'slug' => 'entities.update', 'name' => 'Update Entities'],
            ['group' => 'entities', 'slug' => 'entities.delete', 'name' => 'Delete Entities'],

            // Group: employees
            ['group' => 'employees', 'slug' => 'employees.view',   'name' => 'View Employees'],
            ['group' => 'employees', 'slug' => 'employees.create', 'name' => 'Create Employees'],
            ['group' => 'employees', 'slug' => 'employees.update', 'name' => 'Update Employees'],
            ['group' => 'employees', 'slug' => 'employees.delete', 'name' => 'Delete Employees'],
            ['group' => 'employees', 'slug' => 'employees.import', 'name' => 'Import Employees'],

            // Group: attendance
            ['group' => 'attendance', 'slug' => 'attendance.clock_in',   'name' => 'Clock In'],
            ['group' => 'attendance', 'slug' => 'attendance.view_own',   'name' => 'View Own Attendance'],
            ['group' => 'attendance', 'slug' => 'attendance.view_all',   'name' => 'View All Attendance'],
            ['group' => 'attendance', 'slug' => 'attendance.correct',    'name' => 'Correct Attendance'],

            // Group: leave
            ['group' => 'leave', 'slug' => 'leave.request',    'name' => 'Request Leave'],
            ['group' => 'leave', 'slug' => 'leave.view_own',   'name' => 'View Own Leave'],
            ['group' => 'leave', 'slug' => 'leave.view_all',   'name' => 'View All Leave'],
            ['group' => 'leave', 'slug' => 'leave.approve',    'name' => 'Approve Leave'],
            ['group' => 'leave', 'slug' => 'leave.configure',  'name' => 'Configure Leave Types'],

            // Group: payroll
            ['group' => 'payroll', 'slug' => 'payroll.view_own_slip', 'name' => 'View Own Payslip'],
            ['group' => 'payroll', 'slug' => 'payroll.view_all',      'name' => 'View All Payroll'],
            ['group' => 'payroll', 'slug' => 'payroll.process',       'name' => 'Process Payroll'],
            ['group' => 'payroll', 'slug' => 'payroll.lock',          'name' => 'Lock Payroll Run'],
            ['group' => 'payroll', 'slug' => 'payroll.configure',     'name' => 'Configure Payroll'],

            // Group: reports
            ['group' => 'reports', 'slug' => 'reports.entity',  'name' => 'Entity Reports'],
            ['group' => 'reports', 'slug' => 'reports.holding', 'name' => 'Holding Reports'],

            // Group: settings
            ['group' => 'settings', 'slug' => 'settings.roles',    'name' => 'Manage Roles'],
            ['group' => 'settings', 'slug' => 'settings.entities', 'name' => 'Manage Entity Settings'],
        ];

        // Upsert all permissions and collect a slug => id map for matrix assignment.
        $permissionMap = [];

        foreach ($permissionDefinitions as $def) {
            $record = DB::table('permissions')->updateOrInsert(
                ['slug' => $def['slug']],
                [
                    'id'         => Str::uuid()->toString(),
                    'name'       => $def['name'],
                    'slug'       => $def['slug'],
                    'group'      => $def['group'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Re-fetch id after upsert (updateOrInsert does not return it directly).
            $permissionMap[$def['slug']] = DB::table('permissions')
                ->where('slug', $def['slug'])
                ->value('id');
        }

        // ----------------------------------------------------------------
        // 2. Roles
        // ----------------------------------------------------------------
        $roleDefinitions = [
            ['slug' => 'super_admin',   'name' => 'Super Admin',    'scope' => 'SYSTEM'],
            ['slug' => 'holding_admin', 'name' => 'Holding Admin',  'scope' => 'HOLDING'],
            ['slug' => 'entity_admin',  'name' => 'Entity Admin',   'scope' => 'ENTITY'],
            ['slug' => 'manager',       'name' => 'Manager',        'scope' => 'ENTITY'],
            ['slug' => 'employee',      'name' => 'Employee',       'scope' => 'ENTITY'],
        ];

        $roleMap = [];

        foreach ($roleDefinitions as $def) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $def['slug']],
                [
                    'id'         => Str::uuid()->toString(),
                    'name'       => $def['name'],
                    'slug'       => $def['slug'],
                    'scope'      => $def['scope'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $roleMap[$def['slug']] = DB::table('roles')
                ->where('slug', $def['slug'])
                ->value('id');
        }

        // ----------------------------------------------------------------
        // 3. Role-Permission Matrix
        // ----------------------------------------------------------------
        $allPermissionSlugs = array_column($permissionDefinitions, 'slug');

        $matrix = [
            // super_admin: every permission
            'super_admin' => $allPermissionSlugs,

            // holding_admin: cross-entity read access + holding reports
            'holding_admin' => [
                'entities.view',
                'employees.view',
                'attendance.view_all',
                'leave.view_all',
                'payroll.view_all',
                'reports.entity',
                'reports.holding',
            ],

            // entity_admin: full employee + attendance + leave + payroll management within entity
            'entity_admin' => [
                'employees.view',
                'employees.create',
                'employees.update',
                'employees.delete',
                'employees.import',
                'attendance.view_all',
                'attendance.correct',
                'leave.view_all',
                'leave.approve',
                'leave.configure',
                'payroll.view_all',
                'payroll.process',
                'payroll.lock',
                'payroll.configure',
                'reports.entity',
            ],

            // manager: read + leave approval within department/entity
            'manager' => [
                'employees.view',
                'attendance.view_all',
                'leave.view_all',
                'leave.approve',
                'payroll.view_all',
                'reports.entity',
            ],

            // employee: self-service only
            'employee' => [
                'attendance.clock_in',
                'attendance.view_own',
                'leave.request',
                'leave.view_own',
                'payroll.view_own_slip',
            ],
        ];

        foreach ($matrix as $roleSlug => $permissionSlugs) {
            $roleId = $roleMap[$roleSlug];

            foreach ($permissionSlugs as $permSlug) {
                $permissionId = $permissionMap[$permSlug];

                // role_permissions has a composite PK (role_id, permission_id).
                // Use insertOrIgnore to skip duplicates without touching updated_at.
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $this->command->info('RolePermissionSeeder: ' . count($permissionDefinitions) . ' permissions and ' . count($roleDefinitions) . ' roles seeded.');
    }
}
