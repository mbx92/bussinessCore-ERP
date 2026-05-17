<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kode akun kas/bank (dropdown pembayaran)
    |--------------------------------------------------------------------------
    |
    | Daftar kode CoA yang boleh dipilih saat bayar supplier, invoice project, dll.
    | Pisahkan dengan koma. Contoh production: 1101,1102
    |
    | Jika kosong, sistem memakai akun dari Pengaturan COA (pos_sale_cash_account,
    | project_invoice_cash_account) lalu pola nama yang mengandung Kas/Bank.
    |
    */
    'cash_bank_account_codes' => array_values(array_filter(array_map(
        static fn (string $code): string => trim($code),
        explode(',', (string) env('ACCOUNTING_CASH_BANK_CODES', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Fallback kode akun (hanya jika belum di-set di Pengaturan COA)
    |--------------------------------------------------------------------------
    */
    'coa_fallback_codes' => [
        'pos_sale_cash_account' => env('ACCOUNTING_FALLBACK_CASH_CODE', '1001'),
        'project_invoice_cash_account' => env('ACCOUNTING_FALLBACK_CASH_CODE', '1001'),
        'project_invoice_revenue_account' => env('ACCOUNTING_FALLBACK_PROJECT_REVENUE_CODE', '4003'),
        'pos_sale_revenue_account' => env('ACCOUNTING_FALLBACK_POS_REVENUE_CODE', '4002'),
    ],

];
