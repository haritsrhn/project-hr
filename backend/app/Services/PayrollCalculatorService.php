<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employment;
use Carbon\Carbon;

class PayrollCalculatorService
{
    /**
     * PTKP values per status (annual, in IDR)
     */
    private const PTKP = [
        'TK0' => 54_000_000,
        'TK1' => 58_500_000,
        'TK2' => 63_000_000,
        'TK3' => 67_500_000,
        'K0'  => 58_500_000,
        'K1'  => 63_000_000,
        'K2'  => 67_500_000,
        'K3'  => 72_000_000,
    ];

    /**
     * PPh 21 progressive tax brackets (UU HPP 2021 / Pasal 17)
     * Each entry: [upper_limit, rate]
     * The last bracket has no upper limit (PHP_INT_MAX is used).
     */
    private const PPH21_BRACKETS = [
        [60_000_000,         0.05],
        [250_000_000,        0.15],
        [500_000_000,        0.25],
        [5_000_000_000,      0.30],
        [PHP_INT_MAX,        0.35],
    ];

    /**
     * Calculate all payroll fields for one employment in a given month/year.
     *
     * @return array<string, mixed>  All fields needed to create/update a PayrollItem.
     */
    public function calculate(Employment $employment, int $month, int $year): array
    {
        // ── A. Gross salary ──────────────────────────────────────────────────
        $allowances = $this->extractComponents($employment->salary_structure ?? [], 'ALLOWANCE');
        $deductions  = $this->extractComponents($employment->salary_structure ?? [], 'DEDUCTION');

        $allowancesTotal = array_sum(array_column($allowances, 'amount'));
        $gross           = (int) $employment->salary_basic + $allowancesTotal;

        // ── B. Attendance ────────────────────────────────────────────────────
        $workingDays = $this->countBusinessDays($month, $year);

        $attendances  = Attendance::where('employment_id', $employment->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $presentDays = $attendances->whereIn('status', ['PRESENT', 'LATE'])->count();
        $leaveDays   = $attendances->where('status', 'LEAVE')->count();
        $absentDays  = max(0, $workingDays - $presentDays - $leaveDays);

        // ── C. BPJS Kesehatan ────────────────────────────────────────────────
        $bpjsKesEmployee = 0;
        $bpjsKesEmployer = 0;

        if ($employment->bpjs_kesehatan === true) {
            $kesBase         = min($gross, 12_000_000);
            $bpjsKesEmployee = (int) round(0.01 * $kesBase);
            $bpjsKesEmployer = (int) round(0.04 * $kesBase);
        }

        // ── D. BPJS Ketenagakerjaan ──────────────────────────────────────────
        $bpjsJhtEmployee = 0;
        $bpjsJhtEmployer = 0;
        $bpjsJkk         = 0;
        $bpjsJkm         = 0;
        $bpjsJpEmployee  = 0;
        $bpjsJpEmployer  = 0;

        if ($employment->bpjs_tk === true) {
            $bpjsJhtEmployee = (int) round(0.02   * $gross);
            $bpjsJhtEmployer = (int) round(0.037  * $gross);
            $bpjsJkk         = (int) round(0.0024 * $gross);
            $bpjsJkm         = (int) round(0.003  * $gross);

            $jpBase         = min($gross, 9_077_600);
            $bpjsJpEmployee = (int) round(0.01 * $jpBase);
            $bpjsJpEmployer = (int) round(0.02 * $jpBase);
        }

        // ── E. PPh 21 ────────────────────────────────────────────────────────
        $annualGross    = $gross * 12;
        $biayaJabatan   = min((int) round(0.05 * $annualGross), 6_000_000);
        $ptkp           = self::PTKP[$employment->ptkp_status ?? 'TK0'] ?? 54_000_000;
        $pkp            = max(0, $annualGross - $biayaJabatan - $ptkp);

        [$annualTax, $pph21Breakdown] = $this->calculateProgressiveTax($pkp);

        $pph21AnnualBase = $pkp;
        $pph21Amount     = (int) round($annualTax / 12);

        // ── F. Net salary ────────────────────────────────────────────────────
        $employeeDeductions     = $bpjsKesEmployee + $bpjsJhtEmployee + $bpjsJpEmployee + $pph21Amount;
        $customDeductionsTotal  = array_sum(array_column($deductions, 'amount'));
        $netSalary              = max(0, $gross - $employeeDeductions - $customDeductionsTotal);

        return [
            'gross_salary'       => $gross,
            'allowances'         => $allowances,
            'deductions'         => $deductions,

            // BPJS Kesehatan
            'bpjs_kes_employee'  => $bpjsKesEmployee,
            'bpjs_kes_employer'  => $bpjsKesEmployer,

            // BPJS TK
            'bpjs_jht_employee'  => $bpjsJhtEmployee,
            'bpjs_jht_employer'  => $bpjsJhtEmployer,
            'bpjs_jkk'           => $bpjsJkk,
            'bpjs_jkm'           => $bpjsJkm,
            'bpjs_jp_employee'   => $bpjsJpEmployee,
            'bpjs_jp_employer'   => $bpjsJpEmployer,

            // PPh 21
            'pph21_annual_base'  => $pph21AnnualBase,
            'pph21_amount'       => $pph21Amount,
            'pph21_breakdown'    => $pph21Breakdown,

            // Net
            'net_salary'         => $netSalary,

            // Attendance
            'working_days'       => $workingDays,
            'present_days'       => $presentDays,
            'absent_days'        => $absentDays,
            'leave_days'         => $leaveDays,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Filter salary_structure components by type and return only name + amount.
     *
     * @param  array<int, array{name: string, amount: int|float, type: string}>  $structure
     * @param  string  $type  'ALLOWANCE' or 'DEDUCTION'
     * @return array<int, array{name: string, amount: int}>
     */
    private function extractComponents(array $structure, string $type): array
    {
        $result = [];

        foreach ($structure as $item) {
            if (isset($item['type']) && strtoupper($item['type']) === $type) {
                $result[] = [
                    'name'   => $item['name'] ?? '',
                    'amount' => (int) ($item['amount'] ?? 0),
                ];
            }
        }

        return $result;
    }

    /**
     * Count business days (Mon–Fri) in the given month/year.
     */
    private function countBusinessDays(int $month, int $year): int
    {
        $start   = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end     = $start->copy()->endOfMonth();
        $count   = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Apply UU HPP 2021 Pasal 17 progressive rates to PKP (annual).
     *
     * @return array{0: int, 1: array<int, array{bracket: string, taxable: int, rate: float, tax: int}>}
     */
    private function calculateProgressiveTax(int $pkp): array
    {
        $annualTax = 0;
        $breakdown = [];
        $previous  = 0;

        foreach (self::PPH21_BRACKETS as [$ceiling, $rate]) {
            if ($pkp <= $previous) {
                break;
            }

            $taxable   = min($pkp, $ceiling) - $previous;
            $tax       = (int) round($taxable * $rate);
            $annualTax += $tax;

            if ($ceiling === PHP_INT_MAX) {
                $bracketLabel = 'Di atas Rp 5.000.000.000';
            } elseif ($previous === 0) {
                $bracketLabel = 'Sampai Rp ' . number_format($ceiling, 0, ',', '.');
            } else {
                $bracketLabel = 'Rp ' . number_format($previous + 1, 0, ',', '.') . ' – Rp ' . number_format($ceiling, 0, ',', '.');
            }

            $breakdown[] = [
                'bracket' => $bracketLabel,
                'taxable' => $taxable,
                'rate'    => $rate,
                'tax'     => $tax,
            ];

            $previous = $ceiling;
        }

        return [$annualTax, $breakdown];
    }
}
