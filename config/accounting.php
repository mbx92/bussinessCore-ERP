<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fallback kode akun (hanya jika belum di-set di Pengaturan COA)
    |--------------------------------------------------------------------------
    |
    | Dropdown kas/bank memakai flag is_cash_bank pada akun CoA (Chart of Accounts).
    |
    */
    'coa_fallback_codes' => [
        'pos_sale_cash_account' => env('ACCOUNTING_FALLBACK_CASH_CODE', '1001'),
        'project_invoice_cash_account' => env('ACCOUNTING_FALLBACK_CASH_CODE', '1001'),
        'project_invoice_revenue_account' => env('ACCOUNTING_FALLBACK_PROJECT_REVENUE_CODE', '4003'),
        'pos_sale_revenue_account' => env('ACCOUNTING_FALLBACK_POS_REVENUE_CODE', '4002'),
    ],

];
