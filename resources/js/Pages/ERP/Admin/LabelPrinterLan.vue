<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  labelLan: Object,
  labelProfiles: Array,
});

const form = useForm({
  label_lan_enabled: props.labelLan?.label_lan_enabled ?? false,
  label_lan_host: props.labelLan?.label_lan_host ?? '',
  label_lan_port: props.labelLan?.label_lan_port ?? 9100,
  label_lan_profile_id: props.labelLan?.label_lan_profile_id ?? '',
});

const testForm = useForm({});

const hasSavedHost = computed(() => String(props.labelLan?.label_lan_host ?? '').trim().length > 0);

const hasAnyProfile = computed(() => {
  const lan = props.labelLan?.label_lan_profile_id;
  const smb = props.labelLan?.label_smb_profile_id;
  return (lan != null && lan !== '' && Number(lan) > 0) || (smb != null && smb !== '' && Number(smb) > 0);
});

const canTest = computed(() =>
  Boolean(props.labelLan?.label_lan_enabled) &&
  hasSavedHost.value &&
  hasAnyProfile.value
);

const submit = () => {
  form.post(route('erp.admin.label-printer-lan.update'), { preserveScroll: true });
};

const testPrint = () => {
  if (!canTest.value) return;
  testForm.post(route('erp.admin.label-printer-lan.test'), { preserveScroll: true });
};
</script>

<template>
  <Head title="Administration - Label LAN (TSPL)" />
  <AppLayout>
    <div class="space-y-5">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Administration Workspace</p>
        <div class="mt-2 flex items-center justify-between gap-3">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Label LAN (TSPL)</h1>
            <p class="mt-2 text-sm text-base-content/70">
              Kirim perintah <strong>TSPL</strong> mentah lewat TCP (biasanya port <strong>9100</strong>) ke printer TSC / kompatibel. Ukuran label mengikuti
              <strong>profil label</strong> (mm, DPI, margin, gap).
            </p>
          </div>
          <Link class="btn btn-ghost btn-sm" :href="route('erp.administration')">Back</Link>
        </div>
      </div>

      <div class="rounded-xl border border-base-200 bg-base-100 p-4 text-sm">
        <p class="font-medium text-base-content">Profil &amp; bahasa ZPL/EPL</p>
        <p class="mt-1 text-xs text-base-content/70">
          Kolom <em>protocol</em> di profil (ZPL/EPL) dipakai untuk SMB; untuk halaman ini hanya <strong>ukuran &amp; margin</strong> yang dipakai ke perintah SIZE/GAP TSPL.
        </p>
        <Link :href="route('erp.admin.label-profiles')" class="btn btn-outline btn-sm mt-3">Kelola profil label</Link>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Printer label (IP)</h2>
          <p class="ocn-panel__desc">
            Data dikirim dari server PHP ke alamat IP printer. Jika profil LAN dikosongkan, dipakai profil yang sama dengan pengaturan Label Windows (SMB).
          </p>
        </div>
        <div class="card-body space-y-4">
          <label class="label cursor-pointer justify-start gap-3 border border-base-200 rounded-xl p-3">
            <input v-model="form.label_lan_enabled" type="checkbox" class="checkbox checkbox-primary">
            <div>
              <span class="label-text font-semibold">Aktifkan Label LAN (TSPL)</span>
              <p class="text-xs text-base-content/60">Cetak barcode produk memakai saluran ini jika diaktifkan (prioritas di atas SMB).</p>
            </div>
          </label>

          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <label class="label p-0"><span class="label-text">Alamat IP atau hostname</span></label>
              <input
                v-model="form.label_lan_host"
                type="text"
                class="input input-bordered w-full font-mono text-sm"
                placeholder="192.168.1.80"
                autocomplete="off"
              >
              <p v-if="form.errors.label_lan_host" class="text-xs text-error">{{ form.errors.label_lan_host }}</p>
            </div>
            <div class="space-y-2">
              <label class="label p-0"><span class="label-text">Port RAW</span></label>
              <input
                v-model.number="form.label_lan_port"
                type="number"
                min="1"
                max="65535"
                class="input input-bordered w-full font-mono"
              >
              <p v-if="form.errors.label_lan_port" class="text-xs text-error">{{ form.errors.label_lan_port }}</p>
            </div>
          </div>

          <div class="space-y-2">
            <label class="label p-0"><span class="label-text">Profil label (opsional)</span></label>
            <select v-model="form.label_lan_profile_id" class="select select-bordered w-full font-mono text-sm">
              <option value="">
                — pakai profil dari Label SMB (jika ada) —
              </option>
              <option v-for="p in labelProfiles" :key="p.id" :value="p.id">
                {{ p.name }} — {{ p.width_mm }}×{{ p.height_mm }} mm, {{ p.dpi }} dpi
              </option>
            </select>
            <p v-if="form.errors.label_lan_profile_id" class="text-xs text-error">{{ form.errors.label_lan_profile_id }}</p>
            <p v-if="!labelProfiles?.length" class="text-xs text-warning">
              Belum ada profil label di master.
            </p>
          </div>

          <div class="rounded-xl border border-base-200 bg-base-200/40 p-4 text-sm text-base-content/80">
            <p class="font-medium text-base-content">Uji cetak TSPL</p>
            <p class="mt-1 text-xs">
              Menggunakan <strong>IP, port, dan profil yang sudah disimpan</strong>. Simpan pengaturan dulu; tombol aktif jika LAN diaktifkan, host terisi, dan ada profil (LAN atau SMB).
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
              <button
                type="button"
                class="btn min-w-[9rem]"
                :class="canTest ? 'btn-primary text-white' : 'btn-outline border-base-300 text-base-content/50'"
                :disabled="!canTest || testForm.processing || form.processing"
                @click="testPrint"
              >
                {{ testForm.processing ? 'Mengirim…' : 'Test cetak TSPL' }}
              </button>
            </div>
          </div>

          <div class="flex justify-end gap-2">
            <button class="btn btn-primary" :disabled="form.processing" @click="submit">
              {{ form.processing ? 'Menyimpan…' : 'Simpan pengaturan' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
