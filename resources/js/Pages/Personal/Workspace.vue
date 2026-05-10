<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
  title: String,
  description: String,
});
</script>

<template>
  <Head :title="`Personal — ${title}`" />
  <AppLayout>
    <div class="space-y-5">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Personal Workspace</p>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
          <h1 class="text-3xl font-bold tracking-tight">{{ title }}</h1>
          <Link class="btn btn-ghost btn-sm" :href="route('personal')">Back</Link>
        </div>
        <p class="mt-2 text-sm text-base-content/70">{{ description }}</p>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Pengembangan berikutnya</h2>
          <p class="ocn-panel__desc">Struktur halaman sudah siap; data dan form bisa ditambahkan sesuai kebutuhan Anda.</p>
        </div>
        <div class="card-body text-sm text-base-content/70">
          Contoh langkah lanjutan: tabel transaksi, kategori, multi-dompet, laporan bulanan, dan export.
        </div>
      </div>
    </div>
  </AppLayout>
</template>
