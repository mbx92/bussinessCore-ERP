<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
</script>

<template>
  <Head title="Accounting - Pembayaran" />
  <AppLayout>
    <div class="space-y-5">
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Accounting Workspace</p>
              <h1 class="ocn-panel__title mt-1">Pembayaran</h1>
              <p class="ocn-panel__desc mt-1">Halaman kerja pembayaran untuk invoice project dan pembayaran anggota.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
              <Link class="btn btn-ghost btn-sm shrink-0 gap-1.5" :href="route('erp.accounting')">
              <ArrowLeftIcon class="h-4 w-4" />
              Back
            </Link>
            </div>
          </div>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="ocn-panel">
          <div class="ocn-panel__head">
            <h2 class="ocn-panel__title">Pembayaran invoice project</h2>
            <p class="ocn-panel__desc">Kelola penerimaan pembayaran dari client dan status pelunasan invoice.</p>
          </div>
          <div class="card-body">
            <p class="text-sm text-base-content/70">
              Gunakan halaman ini untuk input pembayaran, edit nominal, dan cetak kwitansi invoice project.
            </p>
            <div class="mt-4">
              <Link :href="route('erp.sales.project-invoices')" class="btn btn-primary btn-sm">Buka Invoice Project</Link>
            </div>
          </div>
        </div>

        <div class="ocn-panel">
          <div class="ocn-panel__head">
            <h2 class="ocn-panel__title">Pembayaran anggota tim</h2>
            <p class="ocn-panel__desc">Pantau distribusi pembayaran anggota berdasarkan project dan periode.</p>
          </div>
          <div class="card-body">
            <p class="text-sm text-base-content/70">
              Akses laporan pembayaran anggota untuk validasi pembagian tim dan proses approval internal.
            </p>
            <div class="mt-4">
              <Link :href="route('reports.member-payments')" class="btn btn-primary btn-sm">Buka Pembayaran Anggota</Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

