<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

defineProps({
  title: String,
  description: String,
});
</script>

<template>
  <Head :title="`Personal — ${title}`" />
  <AppLayout>
    <div class="space-y-5">
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Personal Workspace</p>
              <h1 class="ocn-panel__title mt-1">{{ title }}</h1>
              <p class="ocn-panel__desc mt-1">{{ description }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
              <Link class="btn btn-ghost btn-sm shrink-0 gap-1.5" :href="route('personal')">
              <ArrowLeftIcon class="h-4 w-4" />
              Back
            </Link>
            </div>
          </div>
        </div>
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
