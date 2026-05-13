<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DataTablePagination from '@/Components/DataTablePagination.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
  uoms: Object,
  conversions: Object,
  uomsForSelect: Array,
});

const uomForm = useForm({
  code: '',
  name: '',
  status: 'active',
});

const conversionForm = useForm({
  from_uom_id: '',
  to_uom_id: '',
  multiplier: 1,
});

const editUomForm = useForm({
  code: '',
  name: '',
  status: 'active',
});

const editingUomId = ref(null);

const editConversionForm = useForm({
  from_uom_id: '',
  to_uom_id: '',
  multiplier: 1,
});

const editingConversionId = ref(null);

const submitUom = () => {
  uomForm.post(route('erp.inventory.uoms.store'), {
    preserveScroll: true,
    onSuccess: () => uomForm.reset(),
  });
};

const submitConversion = () => {
  conversionForm.post(route('erp.inventory.uom-conversions.store'), {
    preserveScroll: true,
    onSuccess: () => conversionForm.reset(),
  });
};

const openEditUom = (uom) => {
  editingUomId.value = uom.id;
  editUomForm.code = uom.code ?? '';
  editUomForm.name = uom.name ?? '';
  editUomForm.status = uom.status === 'inactive' ? 'inactive' : 'active';
  editUomForm.clearErrors();
  document.getElementById('modal-edit-uom')?.showModal();
};

const closeEditUomModal = () => {
  document.getElementById('modal-edit-uom')?.close();
};

const onEditUomDialogClose = () => {
  editingUomId.value = null;
  editUomForm.reset();
  editUomForm.clearErrors();
};

const submitEditUom = () => {
  if (!editingUomId.value) return;
  editUomForm.patch(route('erp.inventory.uoms.update', editingUomId.value), {
    preserveScroll: true,
    onSuccess: () => closeEditUomModal(),
  });
};

const confirmDeleteUom = () => {
  if (!editingUomId.value) return;
  if (!confirm('Hapus satuan ini? Konversi yang memakai satuan ini akan ikut terhapus.')) return;
  router.delete(route('erp.inventory.uoms.destroy', editingUomId.value), {
    preserveScroll: true,
    onSuccess: () => closeEditUomModal(),
  });
};

const openEditConversion = (conversion) => {
  editingConversionId.value = conversion.id;
  editConversionForm.from_uom_id = String(conversion.from_uom_id ?? conversion.from_uom?.id ?? '');
  editConversionForm.to_uom_id = String(conversion.to_uom_id ?? conversion.to_uom?.id ?? '');
  editConversionForm.multiplier = Number(conversion.multiplier) || 1;
  editConversionForm.clearErrors();
  document.getElementById('modal-edit-conversion')?.showModal();
};

const closeEditConversionModal = () => {
  document.getElementById('modal-edit-conversion')?.close();
};

const onEditConversionDialogClose = () => {
  editingConversionId.value = null;
  editConversionForm.reset();
  editConversionForm.clearErrors();
};

const submitEditConversion = () => {
  if (!editingConversionId.value) return;
  editConversionForm.patch(route('erp.inventory.uom-conversions.update', editingConversionId.value), {
    preserveScroll: true,
    onSuccess: () => closeEditConversionModal(),
  });
};

const confirmDeleteConversionFromModal = () => {
  if (!editingConversionId.value) return;
  if (!confirm('Hapus konversi ini?')) return;
  router.delete(route('erp.inventory.uom-conversions.destroy', editingConversionId.value), {
    preserveScroll: true,
    onSuccess: () => closeEditConversionModal(),
  });
};

const applyPerPage = (n) => {
  const params = Object.fromEntries(new URLSearchParams(window.location.search).entries());
  params.per_page = String(n);
  delete params.uoms_page;
  delete params.conversions_page;
  router.get(route('erp.inventory.uoms'), params, { preserveState: true, replace: true });
};

/** Tampilan di tabel: DB menyimpan hingga 4 desimal; nol di belakang koma tidak ditampilkan. */
const formatMultiplier = (value) => {
  const n = Number(value);
  if (!Number.isFinite(n)) return String(value ?? '');
  if (Number.isInteger(n)) return String(n);
  return String(parseFloat(n.toFixed(4)));
};
</script>

<template>
  <Head title="Inventory - UoM & Konversi" />
  <AppLayout>
    <div class="space-y-5">
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Inventory Workspace</p>
              <h1 class="ocn-panel__title mt-1">UoM & Konversi</h1>
              <p class="ocn-panel__desc mt-1">Kelola satuan produk dan hubungan konversi antar satuan untuk transaksi inventory.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
              <Link class="btn btn-ghost btn-sm shrink-0 gap-1.5" :href="route('erp.inventory')">
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
            <h2 class="ocn-panel__title">Tambah UoM</h2>
          </div>
          <div class="card-body space-y-3">
            <div class="flex flex-wrap items-end gap-2">
              <input
                v-model="uomForm.code"
                class="input input-bordered input-sm min-w-[5.5rem] flex-1"
                placeholder="Code"
              />
              <input
                v-model="uomForm.name"
                class="input input-bordered input-sm min-w-[8rem] flex-[2]"
                placeholder="Nama satuan"
              />
              <div class="flex items-center gap-2 shrink-0 pb-0.5">
                <span class="text-xs text-base-content/60 whitespace-nowrap">Aktif</span>
                <input
                  :checked="uomForm.status === 'active'"
                  type="checkbox"
                  class="toggle toggle-success toggle-sm"
                  @change="uomForm.status = $event.target.checked ? 'active' : 'inactive'"
                />
              </div>
            </div>
            <div class="flex justify-start border-t border-base-200 pt-3">
              <button type="button" class="btn btn-primary btn-sm" :disabled="uomForm.processing" @click="submitUom">
                Simpan
              </button>
            </div>
          </div>
        </div>

        <div class="ocn-panel">
          <div class="ocn-panel__head">
            <h2 class="ocn-panel__title">Tambah konversi UoM</h2>
          </div>
          <div class="card-body space-y-3">
            <div class="flex flex-wrap items-end gap-2">
              <select v-model="conversionForm.from_uom_id" class="select select-bordered select-sm min-w-[7rem] flex-1">
                <option value="">From</option>
                <option v-for="uom in uomsForSelect" :key="uom.id" :value="uom.id">{{ uom.code }}</option>
              </select>
              <select v-model="conversionForm.to_uom_id" class="select select-bordered select-sm min-w-[7rem] flex-1">
                <option value="">To</option>
                <option v-for="uom in uomsForSelect" :key="`to-${uom.id}`" :value="uom.id">{{ uom.code }}</option>
              </select>
              <input
                v-model.number="conversionForm.multiplier"
                type="number"
                min="0.0001"
                step="0.0001"
                class="input input-bordered input-sm w-28 shrink-0"
                placeholder="×"
              />
            </div>
            <div class="flex justify-start border-t border-base-200 pt-3">
              <button type="button" class="btn btn-primary btn-sm" :disabled="conversionForm.processing" @click="submitConversion">
                Simpan
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Daftar satuan (UoM)</h2>
          <p class="ocn-panel__desc mt-1 text-xs text-base-content/60">Klik baris untuk mengedit atau menghapus.</p>
        </div>
        <div class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>Code</th>
                <th>Nama</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="uom in (uoms?.data || [])"
                :key="uom.id"
                class="cursor-pointer hover:bg-base-200/60"
                role="button"
                tabindex="0"
                @click="openEditUom(uom)"
                @keydown.enter.prevent="openEditUom(uom)"
              >
                <td class="font-mono text-xs">{{ uom.code }}</td>
                <td class="font-semibold">{{ uom.name }}</td>
                <td><StatusBadge :status="uom.status" /></td>
              </tr>
              <tr v-if="!(uoms?.data || []).length">
                <td colspan="3" class="py-8 text-center text-base-content/50">Belum ada UoM.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <DataTablePagination :paginator="uoms" @update:per-page="applyPerPage" />
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Daftar konversi</h2>
          <p class="ocn-panel__desc mt-1 text-xs text-base-content/60">Klik baris untuk mengedit atau menghapus.</p>
        </div>
        <div class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>From</th>
                <th>To</th>
                <th>Multiplier</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="conversion in (conversions?.data || [])"
                :key="conversion.id"
                class="cursor-pointer hover:bg-base-200/60"
                role="button"
                tabindex="0"
                @click="openEditConversion(conversion)"
                @keydown.enter.prevent="openEditConversion(conversion)"
              >
                <td>{{ conversion.from_uom?.code }} - {{ conversion.from_uom?.name }}</td>
                <td>{{ conversion.to_uom?.code }} - {{ conversion.to_uom?.name }}</td>
                <td class="font-mono text-sm">{{ formatMultiplier(conversion.multiplier) }}</td>
              </tr>
              <tr v-if="!(conversions?.data || []).length">
                <td colspan="3" class="text-center text-base-content/50">Belum ada konversi.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <DataTablePagination :paginator="conversions" @update:per-page="applyPerPage" />
      </div>

      <dialog id="modal-edit-uom" class="modal" @close="onEditUomDialogClose">
        <div class="modal-box max-w-md">
          <h3 class="font-bold text-lg">Edit satuan (UoM)</h3>
          <div class="mt-4 space-y-3">
            <div>
              <label class="label"><span class="label-text">Code</span></label>
              <input v-model="editUomForm.code" class="input input-bordered w-full" />
              <p v-if="editUomForm.errors.code" class="text-error text-xs mt-1">{{ editUomForm.errors.code }}</p>
            </div>
            <div>
              <label class="label"><span class="label-text">Nama</span></label>
              <input v-model="editUomForm.name" class="input input-bordered w-full" />
              <p v-if="editUomForm.errors.name" class="text-error text-xs mt-1">{{ editUomForm.errors.name }}</p>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-sm text-base-content/70">Status aktif</span>
              <input
                :checked="editUomForm.status === 'active'"
                type="checkbox"
                class="toggle toggle-success"
                @change="editUomForm.status = $event.target.checked ? 'active' : 'inactive'"
              />
            </div>
            <p v-if="editUomForm.errors.status" class="text-error text-xs">{{ editUomForm.errors.status }}</p>
          </div>
          <div class="modal-action mt-2 flex w-full flex-wrap items-center justify-between gap-2">
            <button type="button" class="btn btn-error btn-outline btn-sm" @click="confirmDeleteUom">
              Hapus
            </button>
            <div class="flex flex-wrap gap-2">
              <button type="button" class="btn btn-ghost btn-sm" @click="closeEditUomModal">Batal</button>
              <button type="button" class="btn btn-primary btn-sm" :disabled="editUomForm.processing" @click="submitEditUom">
                {{ editUomForm.processing ? 'Menyimpan…' : 'Simpan' }}
              </button>
            </div>
          </div>
        </div>
        <form method="dialog" class="modal-backdrop">
          <button type="submit" aria-label="Tutup">close</button>
        </form>
      </dialog>

      <dialog id="modal-edit-conversion" class="modal" @close="onEditConversionDialogClose">
        <div class="modal-box max-w-md">
          <h3 class="font-bold text-lg">Edit konversi UoM</h3>
          <p class="mt-2 text-xs text-base-content/60">
            1 satuan <span class="font-mono">dari</span> = multiplier × satuan <span class="font-mono">ke</span>.
            Desimal (hingga 4 digit) dipakai jika rasio bukan bilangan bulat, misalnya 0,25 atau 12,5.
          </p>
          <div class="mt-4 space-y-3">
            <div>
              <label class="label"><span class="label-text">Dari satuan</span></label>
              <select v-model="editConversionForm.from_uom_id" class="select select-bordered w-full">
                <option value="" disabled>Pilih</option>
                <option v-for="uom in uomsForSelect" :key="`ef-${uom.id}`" :value="String(uom.id)">{{ uom.code }} — {{ uom.name }}</option>
              </select>
              <p v-if="editConversionForm.errors.from_uom_id" class="text-error text-xs mt-1">{{ editConversionForm.errors.from_uom_id }}</p>
            </div>
            <div>
              <label class="label"><span class="label-text">Ke satuan</span></label>
              <select v-model="editConversionForm.to_uom_id" class="select select-bordered w-full">
                <option value="" disabled>Pilih</option>
                <option v-for="uom in uomsForSelect" :key="`et-${uom.id}`" :value="String(uom.id)">{{ uom.code }} — {{ uom.name }}</option>
              </select>
              <p v-if="editConversionForm.errors.to_uom_id" class="text-error text-xs mt-1">{{ editConversionForm.errors.to_uom_id }}</p>
            </div>
            <div>
              <label class="label"><span class="label-text">Multiplier</span></label>
              <input
                v-model.number="editConversionForm.multiplier"
                type="number"
                min="0.0001"
                step="0.0001"
                class="input input-bordered w-full"
              />
              <p v-if="editConversionForm.errors.multiplier" class="text-error text-xs mt-1">{{ editConversionForm.errors.multiplier }}</p>
            </div>
          </div>
          <div class="modal-action mt-2 flex w-full flex-wrap items-center justify-between gap-2">
            <button type="button" class="btn btn-error btn-outline btn-sm" @click="confirmDeleteConversionFromModal">
              Hapus
            </button>
            <div class="flex flex-wrap gap-2">
              <button type="button" class="btn btn-ghost btn-sm" @click="closeEditConversionModal">Batal</button>
              <button type="button" class="btn btn-primary btn-sm" :disabled="editConversionForm.processing" @click="submitEditConversion">
                {{ editConversionForm.processing ? 'Menyimpan…' : 'Simpan' }}
              </button>
            </div>
          </div>
        </div>
        <form method="dialog" class="modal-backdrop">
          <button type="submit" aria-label="Tutup">close</button>
        </form>
      </dialog>
    </div>
  </AppLayout>
</template>
