<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
  title: { type: String, default: 'Maintenance' },
  message: { type: String, default: null },
});
</script>

<template>
  <Head :title="title" />
  <GuestLayout>
    <div class="space-y-4 text-center">
      <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">503</p>
      <h1 class="text-xl font-bold text-gray-900">{{ title }}</h1>
      <p v-if="message" class="text-sm leading-relaxed text-gray-600">{{ message }}</p>
      <p v-else class="text-sm text-gray-600">Modul sedang tidak tersedia. Silakan coba lagi nanti.</p>
      <div class="flex flex-col gap-2 pt-2 sm:flex-row sm:justify-center">
        <Link :href="route('dashboard')" class="btn btn-sm btn-outline border-gray-300">
          Beranda
        </Link>
        <Link :href="route('logout')" method="post" as="button" class="btn btn-sm btn-ghost">
          Keluar
        </Link>
      </div>
      <p class="text-xs text-gray-500">Administrator dapat menonaktifkan maintenance di menu Pengaturan ERP.</p>
    </div>
  </GuestLayout>
</template>
