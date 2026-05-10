<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  state: Object,
  moduleLabels: Object,
});

const moduleKeys = computed(() => Object.keys(props.state?.modules ?? {}));

const form = useForm({
  maintenance_global_enabled: props.state?.maintenance_global_enabled ?? false,
  maintenance_global_message: props.state?.maintenance_global_message ?? '',
  modules: JSON.parse(JSON.stringify(props.state?.modules ?? {})),
});

const buildModulesPayload = () => {
  const out = {};
  for (const key of moduleKeys.value) {
    const row = form.modules[key];
    out[key] = {
      enabled: Boolean(row?.enabled),
      message: row?.message && String(row.message).trim() !== '' ? String(row.message).trim() : null,
    };
  }

  return out;
};

const submit = () => {
  form
    .transform((data) => ({
      maintenance_global_enabled: Boolean(data.maintenance_global_enabled),
      maintenance_global_message: data.maintenance_global_message ?? '',
      modules: buildModulesPayload(),
    }))
    .post(route('erp.admin.maintenance-mode.update'), { preserveScroll: true });
};

const labelFor = (key) => props.moduleLabels?.[key] ?? key;
</script>

<template>
  <Head title="Administration - Maintenance mode" />
  <AppLayout>
    <div class="space-y-5">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Administration Workspace</p>
        <div class="mt-2 flex items-center justify-between gap-3">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Maintenance mode</h1>
            <p class="mt-2 text-sm text-base-content/70">
              <strong>Global</strong> memblokir semua rute bisnis (ERP, projects, kas, laporan, dll.) kecuali role <strong>admin</strong>.
              <strong>Per modul</strong> hanya memblokir area yang dipetakan ke modul tersebut. Halaman ini selalu bisa diakses admin.
            </p>
            <p class="mt-2 text-xs text-base-content/60">
              Untuk pemeliharaan server penuh (termasuk login), gunakan juga <code class="rounded bg-base-200 px-1">php artisan down</code> di server.
            </p>
            <p class="mt-2 text-xs text-amber-800/90 dark:text-amber-200/90">
              Uji per modul dengan akun <strong>bukan admin</strong> (mis. manajer): role admin tidak akan melihat halaman maintenance.
            </p>
          </div>
          <Link class="btn btn-ghost btn-sm" :href="route('erp.administration')">Back</Link>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Maintenance global</h2>
          <p class="ocn-panel__desc">Aktifkan jika seluruh area ERP perlu dibekukan untuk pengguna non-admin.</p>
        </div>
        <div class="card-body space-y-4">
          <label class="label cursor-pointer justify-start gap-3 border border-base-200 rounded-xl p-3">
            <input v-model="form.maintenance_global_enabled" type="checkbox" class="checkbox checkbox-primary">
            <span class="label-text font-semibold">Aktifkan maintenance global</span>
          </label>
          <div class="space-y-2">
            <label class="label p-0"><span class="label-text">Pesan untuk pengguna (opsional)</span></label>
            <textarea
              v-model="form.maintenance_global_message"
              class="textarea textarea-bordered w-full text-sm"
              rows="3"
              placeholder="Contoh: Upgrade database pukul 22:00–24:00."
            />
            <p v-if="form.errors.maintenance_global_message" class="text-xs text-error">{{ form.errors.maintenance_global_message }}</p>
          </div>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Per modul</h2>
          <p class="ocn-panel__desc">Nonaktifkan sebagian fitur tanpa menutup seluruh ERP (kecuali global di atas aktif).</p>
        </div>
        <div class="card-body space-y-4">
          <div
            v-for="key in moduleKeys"
            :key="key"
            class="rounded-xl border border-base-200 bg-base-100 p-4 space-y-3"
          >
            <label class="label cursor-pointer justify-start gap-3 p-0">
              <input v-model="form.modules[key].enabled" type="checkbox" class="checkbox checkbox-sm checkbox-warning">
              <span class="label-text font-semibold capitalize">{{ key }}</span>
            </label>
            <p class="text-xs text-base-content/60 pl-9">{{ labelFor(key) }}</p>
            <div class="pl-9">
              <input
                v-model="form.modules[key].message"
                type="text"
                class="input input-bordered input-sm w-full max-w-xl"
                :placeholder="'Pesan khusus modul ' + key + ' (opsional)'"
              >
              <p v-if="form.errors[`modules.${key}.message`]" class="text-xs text-error mt-1">{{ form.errors[`modules.${key}.message`] }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button class="btn btn-primary" :disabled="form.processing" @click="submit">
          {{ form.processing ? 'Menyimpan…' : 'Simpan pengaturan' }}
        </button>
      </div>
    </div>
  </AppLayout>
</template>
