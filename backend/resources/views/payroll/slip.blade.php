<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $user->name ?? '' }} - {{ $periodLabel }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 12px; }
        .header h2 { font-size: 16px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { font-size: 13px; margin-top: 4px; font-weight: bold; color: #555; }
        .employee-info { margin-bottom: 16px; }
        .employee-info table { width: 100%; }
        .employee-info td { padding: 3px 6px; vertical-align: top; }
        .employee-info td:first-child { width: 140px; color: #666; }
        .section { margin-bottom: 16px; }
        .section-title { font-weight: bold; font-size: 11px; text-transform: uppercase;
                         letter-spacing: 0.5px; color: #555; background: #f5f5f5;
                         padding: 4px 8px; border-left: 3px solid #333; margin-bottom: 4px; }
        .section table { width: 100%; border-collapse: collapse; }
        .section td { padding: 4px 8px; border-bottom: 1px solid #eee; }
        .section td:last-child { text-align: right; }
        .section tfoot td { font-weight: bold; border-top: 1px solid #999; border-bottom: none;
                            background: #f9f9f9; }
        .net-salary { background: #333; color: #fff; padding: 10px 16px;
                      display: flex; justify-content: space-between; align-items: center;
                      margin-top: 8px; }
        .net-salary .label { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .net-salary .amount { font-size: 16px; font-weight: bold; }
        .attendance-row td { color: #555; }
        .footer { margin-top: 30px; text-align: right; font-size: 11px; color: #999; }
    </style>
</head>
<body>

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="header">
        <h2>{{ $entity->name }}</h2>
        <p>SLIP GAJI &mdash; {{ strtoupper($periodLabel) }}</p>
    </div>

    {{-- ── Employee Info ────────────────────────────────────────────────────── --}}
    <div class="employee-info">
        <table>
            <tr>
                <td>Nama</td>
                <td>: <strong>{{ $user->name ?? '-' }}</strong></td>
                <td>No. Karyawan</td>
                <td>: {{ $employment->employee_number ?? '-' }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>: {{ $employment->position ?? '-' }}</td>
                <td>Departemen</td>
                <td>: {{ $employment->department ?? '-' }}</td>
            </tr>
            <tr>
                <td>Status PTKP</td>
                <td>: {{ $employment->ptkp_status ?? '-' }}</td>
                <td>Jenis Karyawan</td>
                <td>: {{ $employment->employment_type ?? '-' }}</td>
            </tr>
        </table>
    </div>

    {{-- ── Earnings ─────────────────────────────────────────────────────────── --}}
    <div class="section">
        <div class="section-title">Pendapatan</div>
        <table>
            <tbody>
                <tr>
                    <td>Gaji Pokok</td>
                    <td>Rp {{ number_format($employment->salary_basic, 0, ',', '.') }}</td>
                </tr>
                @if (!empty($item->allowances))
                    @foreach ($item->allowances as $allowance)
                    <tr>
                        <td>{{ $allowance['name'] }}</td>
                        <td>Rp {{ number_format($allowance['amount'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td>Total Pendapatan Kotor</td>
                    <td>Rp {{ number_format($item->gross_salary, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ── Deductions ───────────────────────────────────────────────────────── --}}
    <div class="section">
        <div class="section-title">Potongan</div>
        <table>
            <tbody>
                @if ($item->bpjs_kes_employee > 0)
                <tr>
                    <td>BPJS Kesehatan (1%)</td>
                    <td>Rp {{ number_format($item->bpjs_kes_employee, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if ($item->bpjs_jht_employee > 0)
                <tr>
                    <td>BPJS JHT Karyawan (2%)</td>
                    <td>Rp {{ number_format($item->bpjs_jht_employee, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if ($item->bpjs_jp_employee > 0)
                <tr>
                    <td>BPJS JP Karyawan (1%)</td>
                    <td>Rp {{ number_format($item->bpjs_jp_employee, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if ($item->pph21_amount > 0)
                <tr>
                    <td>PPh 21</td>
                    <td>Rp {{ number_format($item->pph21_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if (!empty($item->deductions))
                    @foreach ($item->deductions as $deduction)
                    <tr>
                        <td>{{ $deduction['name'] }}</td>
                        <td>Rp {{ number_format($deduction['amount'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                @php
                    $totalDeductions = $item->bpjs_kes_employee
                        + $item->bpjs_jht_employee
                        + $item->bpjs_jp_employee
                        + $item->pph21_amount
                        + array_sum(array_column($item->deductions ?? [], 'amount'));
                @endphp
                <tr>
                    <td>Total Potongan</td>
                    <td>Rp {{ number_format($totalDeductions, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- ── Attendance Summary ───────────────────────────────────────────────── --}}
    <div class="section">
        <div class="section-title">Rekap Kehadiran</div>
        <table>
            <tbody class="attendance-row">
                <tr>
                    <td>Hari Kerja</td>
                    <td>{{ $item->working_days }} hari</td>
                </tr>
                <tr>
                    <td>Hadir</td>
                    <td>{{ $item->present_days }} hari</td>
                </tr>
                <tr>
                    <td>Cuti</td>
                    <td>{{ $item->leave_days }} hari</td>
                </tr>
                <tr>
                    <td>Tidak Hadir</td>
                    <td>{{ $item->absent_days }} hari</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ── Employer BPJS Contributions (informational) ─────────────────────── --}}
    @if ($item->bpjs_kes_employer > 0 || $item->bpjs_jht_employer > 0 || $item->bpjs_jp_employer > 0)
    <div class="section">
        <div class="section-title">Iuran BPJS Ditanggung Perusahaan</div>
        <table>
            <tbody>
                @if ($item->bpjs_kes_employer > 0)
                <tr>
                    <td>BPJS Kesehatan Perusahaan (4%)</td>
                    <td>Rp {{ number_format($item->bpjs_kes_employer, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if ($item->bpjs_jht_employer > 0)
                <tr>
                    <td>BPJS JHT Perusahaan (3,7%)</td>
                    <td>Rp {{ number_format($item->bpjs_jht_employer, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if ($item->bpjs_jkk > 0)
                <tr>
                    <td>BPJS JKK (0,24%)</td>
                    <td>Rp {{ number_format($item->bpjs_jkk, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if ($item->bpjs_jkm > 0)
                <tr>
                    <td>BPJS JKM (0,3%)</td>
                    <td>Rp {{ number_format($item->bpjs_jkm, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if ($item->bpjs_jp_employer > 0)
                <tr>
                    <td>BPJS JP Perusahaan (2%)</td>
                    <td>Rp {{ number_format($item->bpjs_jp_employer, 0, ',', '.') }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Net Salary ───────────────────────────────────────────────────────── --}}
    <div class="net-salary">
        <span class="label">Gaji Bersih (Take Home Pay)</span>
        <span class="amount">Rp {{ number_format($item->net_salary, 0, ',', '.') }}</span>
    </div>

    <div class="footer">
        Dokumen ini digenerate secara otomatis oleh sistem HRIS Tridaya Sejahtera Group.
    </div>

</body>
</html>
