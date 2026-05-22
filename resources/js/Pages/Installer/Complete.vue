<script setup>
import { CheckIcon } from '@heroicons/vue/24/outline';
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
  loginUrl: { type: String, required: true },
});

const progress = ref(0);
const phaseIndex = ref(0);

const phases = [
  'Menyelesaikan konfigurasi environment...',
  'Menyimpan data instalasi akhir...',
  'Menyiapkan halaman awal untuk administrator...',
  'Instalasi selesai. Aplikasi siap digunakan.',
];

const currentPhase = computed(() => phases[phaseIndex.value] ?? phases[phases.length - 1]);
const isComplete = computed(() => progress.value >= 100);

let progressTimer = null;

onMounted(() => {
  progressTimer = setInterval(() => {
    if (progress.value < 100) {
      progress.value += progress.value < 75 ? 5 : 3;
    }

    if (progress.value >= 25 && phaseIndex.value === 0) phaseIndex.value = 1;
    if (progress.value >= 60 && phaseIndex.value === 1) phaseIndex.value = 2;
    if (progress.value >= 90 && phaseIndex.value <= 2) phaseIndex.value = 3;

    if (progress.value >= 100) {
      clearInterval(progressTimer);
      progressTimer = null;
    }
  }, 120);
});

onBeforeUnmount(() => {
  if (progressTimer) clearInterval(progressTimer);
});
</script>

<template>
  <Head title="Finishing Installation" />

  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.2),_transparent_35%),linear-gradient(160deg,#020617_0%,#0f172a_100%)] px-4 py-8 text-white">
    <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-3xl flex-col items-center justify-center text-center">
      <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-200/80">BusinessCore ERP Installer</p>
      <div
        class="mt-6 flex h-28 w-28 items-center justify-center rounded-full border bg-white/5 shadow-[0_0_80px_rgba(34,211,238,0.15)] transition-all duration-500"
        :class="isComplete ? 'border-emerald-300/30 text-emerald-200' : 'border-cyan-300/20 text-cyan-200'"
      >
        <CheckIcon v-if="isComplete" class="h-14 w-14 stroke-[1.9]" />
        <div v-else class="h-12 w-12 rounded-full border-4 border-cyan-200/20 border-t-cyan-200 animate-spin" />
      </div>
      <h1 class="mt-6 text-4xl font-bold tracking-tight">{{ isComplete ? 'Instalasi selesai' : 'Instalasi hampir selesai' }}</h1>
      <p class="mt-3 max-w-xl text-sm leading-7 text-slate-300">
        {{ isComplete
          ? 'Aplikasi sudah siap dipakai. Anda bisa menikmati layar akhir ini dulu, lalu lanjut masuk ke aplikasi saat siap.'
          : 'Sistem sedang menuntaskan langkah akhir sebelum aplikasi siap digunakan.' }}
      </p>

      <div class="mt-8 w-full max-w-2xl rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
        <div class="flex items-center justify-between gap-4 text-sm font-medium">
          <span>{{ currentPhase }}</span>
          <span class="text-base font-bold text-cyan-200">{{ progress }}%</span>
        </div>
        <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/10">
          <div
            class="h-full rounded-full bg-gradient-to-r from-cyan-300 via-sky-400 to-blue-500 transition-all duration-200"
            :style="{ width: `${progress}%` }"
          />
        </div>
      </div>

      <div v-if="isComplete" class="mt-8">
        <a :href="loginUrl" class="btn btn-lg border-0 bg-cyan-300 text-slate-950 hover:bg-cyan-200">
          Mulai Aplikasi
        </a>
      </div>
    </div>
  </div>
</template>
