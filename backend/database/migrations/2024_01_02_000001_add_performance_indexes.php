<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Indexes Migration
 *
 * Addresses the following missing/suboptimal indexes identified during schema review:
 *
 * 1. attendances — composite (employment_id, date) already exists as UNIQUE, but no
 *    dedicated index on (employment_id, status) for attendance recap queries.
 *
 * 2. leave_requests — has separate indexes on employment_id and status, but the
 *    dominant query pattern is WHERE employment_id = ? AND status = ? which needs a
 *    composite index to avoid a secondary filter pass on a potentially large set.
 *
 * 3. employments — WHERE user_id = ? AND status = 'ACTIVE' is the RBAC hot path and
 *    runs on every authenticated request. The existing separate indexes on user_id and
 *    status force the planner to choose one or bitmap-merge; a composite index
 *    eliminates this ambiguity and covers the is_primary flag lookup too.
 *
 * 4. payroll_items — WHERE payroll_run_id = ? drives bulk payroll processing. The
 *    unique constraint on (payroll_run_id, employment_id) provides this implicitly,
 *    but PostgreSQL composite unique indexes are usable for range/equality scans only
 *    when the leading column matches — this is fine here, so no additional index is
 *    needed. However a covering index including net_salary avoids heap fetches for
 *    payroll summary aggregations.
 *
 * 5. audit_logs — created_at index exists, but date-range reports frequently also
 *    filter by action; a composite (created_at, action) index supports both the range
 *    scan and the secondary filter without a heap fetch for the action value.
 *
 * 6. user_roles — the existing index on user_id is correct for single-column lookups.
 *    However the RBAC check also joins role_permissions, and role_permissions has no
 *    index on permission_id for reverse lookups. Adding one supports permission-first
 *    queries (e.g. "which roles have permission X?").
 *
 * 7. leave_balances — no index on (employment_id, year) despite being the primary
 *    lookup key for balance checks during leave approval.
 *
 * All indexes are created CONCURRENTLY so this migration does not acquire an
 * AccessExclusiveLock on production tables. Note: CONCURRENTLY cannot run inside a
 * transaction, so this migration explicitly disables transactions.
 */
return new class extends Migration
{
    /**
     * Disable wrapping in a transaction so CREATE INDEX CONCURRENTLY works.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        // ----------------------------------------------------------------
        // 1. attendances: composite covering (employment_id, status)
        //    Supports: SELECT ... WHERE employment_id = ? AND status = ?
        //    (monthly attendance recap, absent/leave counts per employee)
        // ----------------------------------------------------------------
        if (! $this->indexExists('attendances', 'idx_attendances_employment_status')) {
            DB::statement('
                CREATE INDEX CONCURRENTLY idx_attendances_employment_status
                ON attendances (employment_id, status)
            ');
        }

        // ----------------------------------------------------------------
        // 2. leave_requests: composite (employment_id, status)
        //    Supports: WHERE employment_id = ? AND status IN (...)
        //    Much cheaper than bitmap-AND on two separate single-column indexes.
        // ----------------------------------------------------------------
        if (! $this->indexExists('leave_requests', 'idx_leave_requests_employment_status')) {
            DB::statement('
                CREATE INDEX CONCURRENTLY idx_leave_requests_employment_status
                ON leave_requests (employment_id, status)
            ');
        }

        // ----------------------------------------------------------------
        // 3. employments: composite (user_id, status) — RBAC hot path
        //    Supports: WHERE user_id = ? AND status = \'ACTIVE\'
        //    Runs on every authenticated API request via middleware.
        //    Include is_primary so payroll/portal queries are index-only.
        // ----------------------------------------------------------------
        if (! $this->indexExists('employments', 'idx_employments_user_status')) {
            DB::statement('
                CREATE INDEX CONCURRENTLY idx_employments_user_status
                ON employments (user_id, status)
                INCLUDE (is_primary, entity_id)
            ');
        }

        // ----------------------------------------------------------------
        // 4. payroll_items: covering index for payroll summary aggregations
        //    The UNIQUE on (payroll_run_id, employment_id) already covers
        //    the WHERE payroll_run_id = ? lookup. Add a covering index that
        //    includes net_salary and gross_salary so SUM() aggregations for
        //    the payroll run summary report are index-only scans.
        // ----------------------------------------------------------------
        if (! $this->indexExists('payroll_items', 'idx_payroll_items_run_covering')) {
            DB::statement('
                CREATE INDEX CONCURRENTLY idx_payroll_items_run_covering
                ON payroll_items (payroll_run_id)
                INCLUDE (employment_id, gross_salary, net_salary, present_days, absent_days)
            ');
        }

        // ----------------------------------------------------------------
        // 5. audit_logs: composite (created_at, action)
        //    Supports: WHERE created_at BETWEEN ? AND ? [AND action = ?]
        //    created_at is the leading column so date-range scans remain fast;
        //    action is included to avoid heap fetches for action-filtered reports.
        // ----------------------------------------------------------------
        if (! $this->indexExists('audit_logs', 'idx_audit_logs_created_action')) {
            DB::statement('
                CREATE INDEX CONCURRENTLY idx_audit_logs_created_action
                ON audit_logs (created_at DESC, action)
            ');
        }

        // ----------------------------------------------------------------
        // 6. role_permissions: index on permission_id for reverse lookups
        //    The composite PK (role_id, permission_id) only supports
        //    role-first queries. An index on permission_id supports
        //    "which roles have this permission?" queries used in admin UI.
        // ----------------------------------------------------------------
        if (! $this->indexExists('role_permissions', 'idx_role_permissions_permission_id')) {
            DB::statement('
                CREATE INDEX CONCURRENTLY idx_role_permissions_permission_id
                ON role_permissions (permission_id)
            ');
        }

        // ----------------------------------------------------------------
        // 7. leave_balances: composite (employment_id, year)
        //    Supports: WHERE employment_id = ? AND year = ?
        //    Primary lookup during leave request validation and approval.
        //    The existing UNIQUE on (employment_id, leave_type_id, year)
        //    starts with employment_id so partial matches already benefit,
        //    but an explicit (employment_id, year) index is faster when
        //    fetching all leave types for one employee in one year.
        // ----------------------------------------------------------------
        if (! $this->indexExists('leave_balances', 'idx_leave_balances_employment_year')) {
            DB::statement('
                CREATE INDEX CONCURRENTLY idx_leave_balances_employment_year
                ON leave_balances (employment_id, year DESC)
            ');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_attendances_employment_status');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_leave_requests_employment_status');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_employments_user_status');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_payroll_items_run_covering');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_audit_logs_created_action');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_role_permissions_permission_id');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_leave_balances_employment_year');
    }

    /**
     * Check whether a named index already exists (idempotency guard).
     * Useful when re-running after a partial failure.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select("
            SELECT 1
            FROM pg_indexes
            WHERE tablename = ?
              AND indexname = ?
        ", [$table, $indexName]);

        return ! empty($result);
    }
};
