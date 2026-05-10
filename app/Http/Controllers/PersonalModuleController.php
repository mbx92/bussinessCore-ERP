<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PersonalModuleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Personal/Index', [
            'menus' => [
                [
                    'title' => 'Ringkasan',
                    'description' => 'Gambaran pemasukan, pengeluaran, dan saldo pribadi/keluarga.',
                    'route' => 'personal.overview',
                    'icon' => 'chart-bar',
                ],
                [
                    'title' => 'Pemasukan & pengeluaran',
                    'description' => 'Catat transaksi harian: gaji, tagihan, belanja, tabungan.',
                    'route' => 'personal.transactions',
                    'icon' => 'arrows-right-left',
                ],
                [
                    'title' => 'Anggaran keluarga',
                    'description' => 'Alokasi bulanan per kategori (makan, pendidikan, dll.).',
                    'route' => 'personal.budgets',
                    'icon' => 'clipboard-list',
                ],
            ],
        ]);
    }

    public function overview(): Response
    {
        return Inertia::render('Personal/Workspace', [
            'title' => 'Ringkasan keuangan',
            'description' => 'Dashboard ringkas untuk keuangan pribadi dan keluarga.',
        ]);
    }

    public function transactions(): Response
    {
        return Inertia::render('Personal/Workspace', [
            'title' => 'Pemasukan & pengeluaran',
            'description' => 'Daftar dan form transaksi akan ditambahkan di sini.',
        ]);
    }

    public function budgets(): Response
    {
        return Inertia::render('Personal/Workspace', [
            'title' => 'Anggaran keluarga',
            'description' => 'Perencanaan dan pemantauan anggaran per periode.',
        ]);
    }
}
