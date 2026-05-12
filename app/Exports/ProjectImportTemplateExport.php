<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProjectImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'import_key',
            'name',
            'client_name',
            'client_contact',
            'project_type',
            'total_value',
            'status',
            'invoice_number',
            'started_at',
            'finished_at',
            'description',
            'term_percentages',
            'term_notes',
            'item_sku',
            'item_warehouse_code',
            'item_planned_qty',
            'item_reserved_qty',
            'item_issued_qty',
            'item_status',
            'item_notes',
            'team_email',
            'team_role',
            'team_percentage',
            'team_bonus',
        ];
    }

    public function array(): array
    {
        return [
            [
                'LEGACY-PRJ-CCTV-001',
                'Instalasi CCTV Gudang Timur',
                'PT Contoh Klien',
                '08123456789 / billing@klien.test',
                'cctv_installation',
                '150000000',
                'berjalan',
                'INV-MIG-001',
                '2025-01-15',
                '2025-06-30',
                'Baris 1: item + tim lead',
                '40,35,25',
                'DP 40%|Progress 35%|Final 25%',
                'CCTV-UTP-CAT6',
                'CCTV',
                '20',
                '20',
                '0',
                'reserved',
                'Tarik kabel backbone',
                'admin@ocn.test',
                'lead',
                '60',
                '500000',
            ],
            [
                'LEGACY-PRJ-CCTV-001',
                'Instalasi CCTV Gudang Timur',
                'PT Contoh Klien',
                '08123456789 / billing@klien.test',
                'cctv_installation',
                '150000000',
                'berjalan',
                'INV-MIG-001',
                '2025-01-15',
                '2025-06-30',
                'Baris 2: item + anggota tim',
                '40,35,25',
                'DP 40%|Progress 35%|Final 25%',
                'CCTV-UTP-CAT6',
                'CCTV',
                '35',
                '35',
                '0',
                'reserved',
                'Penarikan jalur lantai 2',
                'budi@ocn.test',
                'teknisi',
                '40',
                '0',
            ],
        ];
    }
}
