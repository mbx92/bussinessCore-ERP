<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  labelSmb: Object,
  labelProfiles: Array,
  serverIsWindows: Boolean,
});

const form = useForm({
  label_smb_enabled: props.labelSmb?.label_smb_enabled ?? false,
  label_smb_unc: props.labelSmb?.label_smb_unc ?? '',
  label_smb_profile_id: props.labelSmb?.label_smb_profile_id ?? '',
});

const testForm = useForm({
  label_smb_unc: props.labelSmb?.label_smb_unc ?? '',
  label_smb_profile_id: props.labelSmb?.label_smb_profile_id ?? '',
});

const hasSavedUnc = computed(() => String(props.labelSmb?.label_smb_unc ?? '').trim().length > 0);

const hasSavedProfile = computed(() => {
  const id = props.labelSmb?.label_smb_profile_id;
  return id != null && id !== '' && Number(id) > 0;
});

const canTest = computed(() => hasSavedUnc.value && hasSavedProfile.value && props.serverIsWindows);

const selectedProfileLabel = computed(() => {
  const id = props.labelSmb?.label_smb_profile_id;
  if (id == null || id === '') return null;
  const numId = Number(id);
  const p = (props.labelProfiles ?? []).find((x) => Number(x.id) === numId);
  return p ? `${p.name} (${String(p.width_mm)}×${String(p.height_mm)} mm, ${p.dpi} dpi, ${String(p.protocol).toUpperCase()})` : null;
});

const submit = () => {
  form.post(route('erp.admin.label-printer-smb.update'), { preserveScroll: true });
};

const testPrint = () => {
  if (!canTest.value) return;
  testForm.label_smb_unc = props.labelSmb.label_smb_unc;
  testForm.label_smb_profile_id = props.labelSmb.label_smb_profile_id;
  testForm.post(route('erp.admin.label-printer-smb.test'), { preserveScroll: true });
};
</script>

<template>
  <Head title="Administration - Label Windows (SMB)" />
  <AppLayout>
    <div class="space-y-5">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Administration Workspace</p>
        <div class="mt-2 flex items-center justify-between gap-3">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Label Windows (SMB)</h1>
            <p class="mt-2 text-sm text-base-content/70">
              Kirim <strong>ZPL atau EPL mentah</strong> ke printer share (UNC / SMB). Ukuran dan posisi isi struk mengikuti
              <strong>profil label</strong> (mm + DPI + margin). Path contoh:
              <code class="rounded bg-base-200 px-1">smb://NAMA-PC/ZebraZPL</code>
              → dinormalisasi ke
              <code class="rounded bg-base-200 px-1">\\NAMA-PC\ZebraZPL</code>.
            </p>
          </div>
          <Link class="btn btn-ghost btn-sm" :href="route('erp.administration')">Back</Link>
        </div>
      </div>

      <div v-if="!serverIsWindows" role="alert" class="alert alert-warning text-sm">
        <span>Server PHP Anda bukan Windows: pembukaan path UNC lewat <code class="mx-0.5 rounded bg-base-200 px-1">fopen</code> biasanya tidak didukung. Untuk cetak dari Linux gunakan menu <strong>Label LAN (TSPL)</strong> (IP + port 9100), atau jalankan PHP di Windows untuk SMB.</span>
      </div>

      <div class="rounded-xl border border-base-200 bg-base-100 p-4 text-sm">
        <p class="font-medium text-base-content">Ukuran kertas “random” (3×5, 50×30, …)</p>
        <p class="mt-1 text-xs text-base-content/70">
          Buat <strong>satu profil per ukuran stok</strong> (boleh banyak baris di master). Saat cetak nanti, aplikasi memilih profil yang sesuai (mis. dari SKU / jenis kemasan). Uji cetak di halaman ini memakai <strong>satu profil default</strong> yang Anda pilih di bawah.
        </p>
        <Link :href="route('erp.admin.label-profiles')" class="btn btn-outline btn-sm mt-3">Kelola profil label</Link>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Printer share (UNC)</h2>
          <p class="ocn-panel__desc">
            Antrian printer harus menerima data RAW. Akun yang menjalankan PHP harus punya akses ke share tersebut.
          </p>
        </div>
        <div class="card-body space-y-4">
          <label class="label cursor-pointer justify-start gap-3 border border-base-200 rounded-xl p-3">
            <input v-model="form.label_smb_enabled" type="checkbox" class="checkbox checkbox-primary">
            <div>
              <span class="label-text font-semibold">Aktifkan penggunaan label SMB</span>
              <p class="text-xs text-base-content/60">Flag untuk modul lain (nanti).</p>
            </div>
          </label>

          <div class="space-y-2">
            <label class="label p-0"><span class="label-text">Path UNC printer</span></label>
            <input
              v-model="form.label_smb_unc"
              type="text"
              class="input input-bordered w-full font-mono text-sm"
              placeholder="smb://NAMA-PC/NamaPrinterShare"
              autocomplete="off"
            >
            <p class="text-xs text-base-content/60">Boleh memakai prefiks <code class="rounded bg-base-200 px-1">smb://</code> — akan dinormalisasi ke UNC Windows.</p>
            <p v-if="form.errors.label_smb_unc" class="text-xs text-error">{{ form.errors.label_smb_unc }}</p>
          </div>

          <div class="space-y-2">
            <label class="label p-0"><span class="label-text">Profil label (ukuran &amp; bahasa)</span></label>
            <select v-model="form.label_smb_profile_id" class="select select-bordered w-full font-mono text-sm">
              <option value="" disabled>
                — pilih profil —
              </option>
              <option v-for="p in labelProfiles" :key="p.id" :value="p.id">
                {{ p.name }} — {{ p.width_mm }}×{{ p.height_mm }} mm, {{ p.dpi }} dpi, {{ String(p.protocol).toUpperCase() }}
              </option>
            </select>
            <p v-if="form.errors.label_smb_profile_id" class="text-xs text-error">{{ form.errors.label_smb_profile_id }}</p>
            <p v-if="!labelProfiles?.length" class="text-xs text-warning">
              Belum ada profil. Tambahkan di menu Profil label.
            </p>
          </div>

          <div class="rounded-xl border border-base-200 bg-base-200/40 p-4 text-sm text-base-content/80">
            <p class="font-medium text-base-content">Uji cetak</p>
            <p class="mt-1 text-xs">
              Mengirim contoh ke <strong>UNC</strong> dengan <strong>profil yang sudah disimpan</strong> (ZPL memakai ^PW/^LL dari mm×DPI; EPL memakai q/Q).
              Simpan pengaturan dulu agar tombol aktif.
            </p>
            <p v-if="selectedProfileLabel" class="mt-2 text-xs text-base-content/70">
              Profil tersimpan: {{ selectedProfileLabel }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
              <button
                type="button"
                class="btn min-w-[9rem]"
                :class="canTest ? 'btn-primary text-white' : 'btn-outline border-base-300 text-base-content/50'"
                :disabled="!canTest || testForm.processing || form.processing"
                @click="testPrint"
              >
                {{ testForm.processing ? 'Mengirim…' : 'Test cetak' }}
              </button>
            </div>
            <p v-if="testForm.errors.label_smb_unc" class="mt-2 text-xs text-error">{{ testForm.errors.label_smb_unc }}</p>
            <p v-if="testForm.errors.label_smb_profile_id" class="mt-2 text-xs text-error">{{ testForm.errors.label_smb_profile_id }}</p>
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
