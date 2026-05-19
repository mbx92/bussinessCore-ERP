<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTablePagination from '@/Components/DataTablePagination.vue';
import InputError from '@/Components/InputError.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { computed, reactive } from 'vue';
import { useCurrency } from '@/composables/useCurrency';
import { useDateFormat } from '@/composables/useDateFormat';

const props = defineProps({
  project: Object,
  notes: Object,
  budgetItems: Array,
  purchases: Object,
  outputs: Array,
  summary: Object,
  users: Array,
  suppliers: Array,
  statusOptions: Array,
  productOptions: Array,
});

const { format } = useCurrency();
const { formatDate, formatDateTime } = useDateFormat();

const supplierOptions = computed(() => props.suppliers || []);

const noteForm = useForm({ title: '', content: '', attachments: [] });
const editNoteForm = useForm({ title: '', content: '', attachments: [] });
const budgetForm = useForm({ name: '', qty: 1, estimated_unit_price: 0 });
const editBudgetForm = useForm({ name: '', qty: 1, estimated_unit_price: 0 });
const purchaseForm = useForm({ master_product_id: '', supplier_id: '', qty: 1, unit_price: 0, category: 'alat', purchase_date: new Date().toISOString().slice(0, 10), notes: '', receipt: null });
const editPurchaseForm = useForm({ master_product_id: '', supplier_id: '', qty: 1, unit_price: 0, category: 'alat', purchase_date: '', notes: '', receipt: null });
const outputForm = useForm({ name: '', description: '', units_produced: 0, notes: '' });
const editOutputForm = useForm({ name: '', description: '', units_produced: 0, notes: '' });
const deleteProjectForm = useForm({});

const selectedNote = reactive({ id: null });
const selectedBudget = reactive({ id: null });
const selectedPurchase = reactive({ id: null });
const selectedOutput = reactive({ id: null });

const onNoteAttachments = (e, form) => {
  form.attachments = [...(e.target.files || [])];
};

const onPurchaseReceipt = (e, form) => {
  form.receipt = e.target.files?.[0] ?? null;
};

const submitNote = () => {
  noteForm.post(route('rnd.projects.notes.store', props.project.id), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      noteForm.title = '';
      noteForm.content = '';
      noteForm.attachments = [];
    },
  });
};

const openEditNote = (note) => {
  selectedNote.id = note.id;
  editNoteForm.title = note.title;
  editNoteForm.content = note.content ?? '';
  editNoteForm.attachments = [];
  document.getElementById('modal-edit-note')?.showModal();
};

const submitEditNote = () => {
  editNoteForm.patch(route('rnd.projects.notes.update', [props.project.id, selectedNote.id]), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      document.getElementById('modal-edit-note')?.close();
      editNoteForm.reset();
    },
  });
};

const deleteNote = (noteId) => router.delete(route('rnd.projects.notes.destroy', [props.project.id, noteId]), { preserveScroll: true });

const submitBudget = () => {
  budgetForm.post(route('rnd.projects.budgets.store', props.project.id), {
    preserveScroll: true,
    onSuccess: () => {
      budgetForm.name = '';
      budgetForm.qty = 1;
      budgetForm.estimated_unit_price = 0;
    },
  });
};

const openEditBudget = (item) => {
  selectedBudget.id = item.id;
  editBudgetForm.name = item.name;
  editBudgetForm.qty = item.qty;
  editBudgetForm.estimated_unit_price = item.estimated_unit_price;
  document.getElementById('modal-edit-budget-item')?.showModal();
};

const submitEditBudget = () => {
  editBudgetForm.patch(route('rnd.projects.budgets.update', [props.project.id, selectedBudget.id]), {
    preserveScroll: true,
    onSuccess: () => document.getElementById('modal-edit-budget-item')?.close(),
  });
};

const deleteBudget = (budgetId) => router.delete(route('rnd.projects.budgets.destroy', [props.project.id, budgetId]), { preserveScroll: true });

const submitPurchase = () => {
  purchaseForm.post(route('rnd.projects.purchases.store', props.project.id), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      purchaseForm.master_product_id = '';
      purchaseForm.supplier_id = '';
      purchaseForm.qty = 1;
      purchaseForm.unit_price = 0;
      purchaseForm.category = 'alat';
      purchaseForm.purchase_date = new Date().toISOString().slice(0, 10);
      purchaseForm.notes = '';
      purchaseForm.receipt = null;
    },
  });
};

const openEditPurchase = (purchase) => {
  selectedPurchase.id = purchase.id;
  editPurchaseForm.master_product_id = purchase.master_product_id;
  editPurchaseForm.supplier_id = purchase.supplier_id;
  editPurchaseForm.qty = purchase.qty;
  editPurchaseForm.unit_price = purchase.unit_price;
  editPurchaseForm.category = purchase.category;
  editPurchaseForm.purchase_date = purchase.purchase_date;
  editPurchaseForm.notes = purchase.notes ?? '';
  editPurchaseForm.receipt = null;
  document.getElementById('modal-edit-purchase')?.showModal();
};

const submitEditPurchase = () => {
  editPurchaseForm.patch(route('rnd.projects.purchases.update', [props.project.id, selectedPurchase.id]), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => document.getElementById('modal-edit-purchase')?.close(),
  });
};

const deletePurchase = (purchaseId) => router.delete(route('rnd.projects.purchases.destroy', [props.project.id, purchaseId]), { preserveScroll: true });

const submitOutput = () => {
  outputForm.post(route('rnd.projects.outputs.store', props.project.id), {
    preserveScroll: true,
    onSuccess: () => outputForm.reset(),
  });
};

const openEditOutput = (output) => {
  selectedOutput.id = output.id;
  editOutputForm.name = output.name;
  editOutputForm.description = output.description ?? '';
  editOutputForm.units_produced = output.units_produced;
  editOutputForm.notes = output.notes ?? '';
  document.getElementById('modal-edit-output')?.showModal();
};

const submitEditOutput = () => {
  editOutputForm.patch(route('rnd.projects.outputs.update', [props.project.id, selectedOutput.id]), {
    preserveScroll: true,
    onSuccess: () => document.getElementById('modal-edit-output')?.close(),
  });
};

const deleteOutput = (outputId) => router.delete(route('rnd.projects.outputs.destroy', [props.project.id, outputId]), { preserveScroll: true });

const deleteProject = () => {
  if (!window.confirm(`Hapus project "${props.project.name}"?`)) return;
  deleteProjectForm.delete(route('rnd.projects.destroy', props.project.id));
};
</script>

<template>
  <Head :title="`R&D - ${project.name}`" />
  <AppLayout>
    <div class="space-y-5">
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">R&amp;D Workspace</p>
              <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ocn-panel__title">{{ project.name }}</h1>
                <StatusBadge :status="project.status" />
              </div>
              <p class="ocn-panel__desc mt-1">{{ project.category }} · PIC: {{ project.pic_name || '-' }} · Mulai: {{ formatDate(project.start_date) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <Link class="btn btn-outline btn-sm" :href="route('rnd.projects.report', project.id)">Report</Link>
              <Link class="btn btn-primary btn-sm" :href="route('rnd.projects.edit', project.id)">Edit Project</Link>
              <button class="btn btn-error btn-outline btn-sm" :disabled="deleteProjectForm.processing" @click="deleteProject">Hapus</button>
              <Link class="btn btn-ghost btn-sm gap-1.5" :href="route('rnd.dashboard')">
                <ArrowLeftIcon class="h-4 w-4" />
                Back
              </Link>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="ocn-panel"><div class="card-body"><p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Estimasi Budget</p><p class="mt-2 text-2xl font-semibold">{{ format(summary.estimated_budget_total) }}</p></div></div>
        <div class="ocn-panel"><div class="card-body"><p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Modal Aktual</p><p class="mt-2 text-2xl font-semibold">{{ format(summary.actual_spend_total) }}</p></div></div>
        <div class="ocn-panel"><div class="card-body"><p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Units Produced</p><p class="mt-2 text-2xl font-semibold">{{ summary.units_produced_total }}</p></div></div>
        <div class="ocn-panel"><div class="card-body"><p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">HPP / Unit</p><p class="mt-2 text-2xl font-semibold">{{ format(summary.hpp_per_unit) }}</p></div></div>
      </div>

      <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="ocn-panel xl:col-span-2">
          <div class="ocn-panel__head">
            <h2 class="ocn-panel__title">Research Notes</h2>
          </div>
          <div class="card-body space-y-4">
            <div class="rounded-2xl border border-base-300 p-4">
              <div class="grid grid-cols-1 gap-3">
                <div>
                  <label class="label"><span class="label-text">Judul</span></label>
                  <input v-model="noteForm.title" class="input input-bordered w-full" />
                  <InputError :message="noteForm.errors.title" class="mt-1" />
                </div>
                <div>
                  <label class="label"><span class="label-text">Konten</span></label>
                  <textarea v-model="noteForm.content" class="textarea textarea-bordered min-h-36 w-full" placeholder="Boleh HTML ringan seperti <p>, <strong>, <ul>, dll." />
                  <InputError :message="noteForm.errors.content" class="mt-1" />
                </div>
                <div>
                  <label class="label"><span class="label-text">Lampiran</span></label>
                  <input type="file" multiple class="file-input file-input-bordered w-full" @change="(e) => onNoteAttachments(e, noteForm)" />
                  <InputError :message="noteForm.errors['attachments.0'] || noteForm.errors.attachments" class="mt-1" />
                </div>
              </div>
              <div class="mt-4 flex justify-end">
                <button class="btn btn-primary btn-sm" :disabled="noteForm.processing" @click="submitNote">Simpan Catatan</button>
              </div>
            </div>

            <div class="space-y-3">
              <div v-for="note in (notes?.data || [])" :key="note.id" class="rounded-2xl border border-base-300 p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <h3 class="font-semibold">{{ note.title }}</h3>
                    <p class="text-xs text-base-content/60">{{ note.created_by || '-' }} · {{ formatDateTime(note.created_at) }}</p>
                  </div>
                  <div class="flex gap-2">
                    <button class="btn btn-ghost btn-xs" @click="openEditNote(note)">Edit</button>
                    <button class="btn btn-ghost btn-xs text-error" @click="deleteNote(note.id)">Hapus</button>
                  </div>
                </div>
                <div class="prose prose-sm mt-3 max-w-none" v-html="note.content || '<p class=&quot;text-base-content/60&quot;>Tidak ada konten.</p>'" />
                <div v-if="note.attachments?.length" class="mt-3 flex flex-wrap gap-2">
                  <a v-for="attachment in note.attachments" :key="attachment.id" :href="attachment.url" target="_blank" class="btn btn-ghost btn-xs">{{ attachment.name }}</a>
                </div>
              </div>
            </div>
          </div>
          <DataTablePagination :paginator="notes" :show-per-page="false" />
        </div>

        <div class="space-y-5">
          <div class="ocn-panel">
            <div class="ocn-panel__head">
              <h2 class="ocn-panel__title">Ringkasan Biaya</h2>
            </div>
            <div class="card-body space-y-3 text-sm">
              <div class="flex items-center justify-between"><span>Alat</span><strong>{{ format(summary.alat_total) }}</strong></div>
              <div class="flex items-center justify-between"><span>Bahan</span><strong>{{ format(summary.bahan_total) }}</strong></div>
              <div class="flex items-center justify-between"><span>Variance</span><strong :class="summary.variance < 0 ? 'text-error' : 'text-success'">{{ format(summary.variance) }}</strong></div>
              <div class="flex items-center justify-between"><span>Budget Items</span><strong>{{ summary.budget_item_count }}</strong></div>
              <div class="flex items-center justify-between"><span>Purchases</span><strong>{{ summary.purchase_count }}</strong></div>
              <div class="flex items-center justify-between"><span>Outputs</span><strong>{{ summary.output_count }}</strong></div>
            </div>
          </div>

          <div class="ocn-panel">
            <div class="ocn-panel__head">
              <h2 class="ocn-panel__title">Project Notes</h2>
            </div>
            <div class="card-body text-sm text-base-content/70">
              <p>{{ project.description || 'Tidak ada deskripsi.' }}</p>
              <hr class="my-3 border-base-300">
              <p>{{ project.notes || 'Tidak ada catatan internal.' }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <div class="ocn-panel">
          <div class="ocn-panel__head">
            <h2 class="ocn-panel__title">Budget Planning</h2>
          </div>
          <div class="card-body space-y-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
              <div class="md:col-span-3">
                <label class="label"><span class="label-text">Nama Item</span></label>
                <input v-model="budgetForm.name" class="input input-bordered w-full" />
                <InputError :message="budgetForm.errors.name" class="mt-1" />
              </div>
              <div>
                <label class="label"><span class="label-text">Qty</span></label>
                <input v-model="budgetForm.qty" type="number" min="0.01" step="0.01" class="input input-bordered w-full" />
                <InputError :message="budgetForm.errors.qty" class="mt-1" />
              </div>
              <div>
                <label class="label"><span class="label-text">Harga Est.</span></label>
                <input v-model="budgetForm.estimated_unit_price" type="number" min="0" step="0.01" class="input input-bordered w-full" />
                <InputError :message="budgetForm.errors.estimated_unit_price" class="mt-1" />
              </div>
              <div class="flex items-end">
                <button class="btn btn-primary w-full" :disabled="budgetForm.processing" @click="submitBudget">Tambah</button>
              </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-base-300">
              <table class="table table-sm">
                <thead><tr><th>Item</th><th>Qty</th><th>Harga</th><th>Total</th><th></th></tr></thead>
                <tbody>
                  <tr v-for="item in budgetItems" :key="item.id">
                    <td>{{ item.name }}</td>
                    <td>{{ item.qty }}</td>
                    <td>{{ format(item.estimated_unit_price) }}</td>
                    <td>{{ format(item.total_price) }}</td>
                    <td class="text-right">
                      <button class="btn btn-ghost btn-xs" @click="openEditBudget(item)">Edit</button>
                      <button class="btn btn-ghost btn-xs text-error" @click="deleteBudget(item.id)">Hapus</button>
                    </td>
                  </tr>
                  <tr v-if="!budgetItems.length"><td colspan="5" class="py-6 text-center text-base-content/50">Belum ada item budget.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="ocn-panel">
          <div class="ocn-panel__head">
            <h2 class="ocn-panel__title">Product Output</h2>
          </div>
          <div class="card-body space-y-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
              <div>
                <label class="label"><span class="label-text">Nama Output</span></label>
                <input v-model="outputForm.name" class="input input-bordered w-full" />
                <InputError :message="outputForm.errors.name" class="mt-1" />
              </div>
              <div>
                <label class="label"><span class="label-text">Units Produced</span></label>
                <input v-model="outputForm.units_produced" type="number" min="0" step="0.01" class="input input-bordered w-full" />
                <InputError :message="outputForm.errors.units_produced" class="mt-1" />
              </div>
              <div class="md:col-span-2">
                <label class="label"><span class="label-text">Deskripsi</span></label>
                <textarea v-model="outputForm.description" class="textarea textarea-bordered min-h-24 w-full" />
              </div>
              <div class="md:col-span-2">
                <label class="label"><span class="label-text">Catatan</span></label>
                <textarea v-model="outputForm.notes" class="textarea textarea-bordered min-h-24 w-full" />
              </div>
            </div>
            <div class="flex justify-end">
              <button class="btn btn-primary" :disabled="outputForm.processing" @click="submitOutput">Tambah Output</button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-base-300">
              <table class="table table-sm">
                <thead><tr><th>Output</th><th>Units</th><th>HPP / Unit</th><th>Allocated</th><th></th></tr></thead>
                <tbody>
                  <tr v-for="output in outputs" :key="output.id">
                    <td>
                      <div class="font-medium">{{ output.name }}</div>
                      <div class="text-xs text-base-content/60">{{ output.description || '-' }}</div>
                    </td>
                    <td>{{ output.units_produced }}</td>
                    <td>{{ format(output.hpp_per_unit) }}</td>
                    <td>{{ format(output.allocated_cost) }}</td>
                    <td class="text-right">
                      <button class="btn btn-ghost btn-xs" @click="openEditOutput(output)">Edit</button>
                      <button class="btn btn-ghost btn-xs text-error" @click="deleteOutput(output.id)">Hapus</button>
                    </td>
                  </tr>
                  <tr v-if="!outputs.length"><td colspan="5" class="py-6 text-center text-base-content/50">Belum ada output produk.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Purchases / Pembelian Alat &amp; Bahan</h2>
        </div>
        <div class="card-body space-y-4">
          <div class="grid grid-cols-1 gap-3 md:grid-cols-3 xl:grid-cols-6">
            <div class="xl:col-span-2">
              <label class="label"><span class="label-text">Item</span></label>
              <select v-model="purchaseForm.master_product_id" class="select select-bordered w-full">
                <option value="">Pilih item</option>
                <option v-for="product in productOptions" :key="product.id" :value="product.id">{{ product.sku }} - {{ product.name }}</option>
              </select>
              <InputError :message="purchaseForm.errors.master_product_id" class="mt-1" />
            </div>
            <div class="xl:col-span-2">
              <label class="label"><span class="label-text">Supplier</span></label>
              <select v-model="purchaseForm.supplier_id" class="select select-bordered w-full">
                <option value="">Pilih supplier</option>
                <option v-for="supplier in supplierOptions" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
              </select>
              <InputError :message="purchaseForm.errors.supplier_id" class="mt-1" />
            </div>
            <div>
              <label class="label"><span class="label-text">Qty</span></label>
              <input v-model="purchaseForm.qty" type="number" min="0.01" step="0.01" class="input input-bordered w-full" />
            </div>
            <div>
              <label class="label"><span class="label-text">Unit Price</span></label>
              <input v-model="purchaseForm.unit_price" type="number" min="0" step="0.01" class="input input-bordered w-full" />
            </div>
            <div>
              <label class="label"><span class="label-text">Kategori</span></label>
              <select v-model="purchaseForm.category" class="select select-bordered w-full">
                <option value="alat">alat</option>
                <option value="bahan">bahan</option>
              </select>
            </div>
            <div>
              <label class="label"><span class="label-text">Tanggal Beli</span></label>
              <input v-model="purchaseForm.purchase_date" type="date" class="input input-bordered w-full" />
            </div>
            <div class="xl:col-span-2">
              <label class="label"><span class="label-text">Receipt / Foto</span></label>
              <input type="file" class="file-input file-input-bordered w-full" @change="(e) => onPurchaseReceipt(e, purchaseForm)" />
            </div>
            <div class="xl:col-span-3">
              <label class="label"><span class="label-text">Catatan</span></label>
              <input v-model="purchaseForm.notes" class="input input-bordered w-full" />
            </div>
            <div class="flex items-end">
              <button class="btn btn-primary w-full" :disabled="purchaseForm.processing" @click="submitPurchase">Tambah</button>
            </div>
          </div>

          <div class="overflow-x-auto rounded-2xl border border-base-300">
            <table class="table table-sm">
              <thead><tr><th>Tanggal</th><th>Item</th><th>Supplier</th><th>Kategori</th><th>Qty</th><th>Harga</th><th>Total</th><th></th></tr></thead>
              <tbody>
                <tr v-for="purchase in (purchases?.data || [])" :key="purchase.id">
                  <td>{{ formatDate(purchase.purchase_date) }}</td>
                  <td>
                    <div class="font-medium">{{ purchase.product_name }}</div>
                    <div class="text-xs text-base-content/60">{{ purchase.product_sku }} · {{ purchase.uom || '-' }}</div>
                  </td>
                  <td>{{ purchase.supplier_name }}</td>
                  <td><StatusBadge :status="purchase.category" /></td>
                  <td>{{ purchase.qty }}</td>
                  <td>{{ format(purchase.unit_price) }}</td>
                  <td>{{ format(purchase.total_price) }}</td>
                  <td class="text-right">
                    <a v-if="purchase.receipt_url" :href="purchase.receipt_url" target="_blank" class="btn btn-ghost btn-xs">Receipt</a>
                    <button class="btn btn-ghost btn-xs" @click="openEditPurchase(purchase)">Edit</button>
                    <button class="btn btn-ghost btn-xs text-error" @click="deletePurchase(purchase.id)">Hapus</button>
                  </td>
                </tr>
                <tr v-if="!(purchases?.data || []).length"><td colspan="8" class="py-6 text-center text-base-content/50">Belum ada pembelian.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <DataTablePagination :paginator="purchases" :show-per-page="false" />
      </div>
    </div>

    <dialog id="modal-edit-note" class="modal">
      <div class="modal-box max-w-2xl">
        <h3 class="text-lg font-bold">Edit Research Note</h3>
        <div class="mt-4 space-y-3">
          <div>
            <label class="label"><span class="label-text">Judul</span></label>
            <input v-model="editNoteForm.title" class="input input-bordered w-full" />
          </div>
          <div>
            <label class="label"><span class="label-text">Konten</span></label>
            <textarea v-model="editNoteForm.content" class="textarea textarea-bordered min-h-36 w-full" />
          </div>
          <div>
            <label class="label"><span class="label-text">Tambah Lampiran</span></label>
            <input type="file" multiple class="file-input file-input-bordered w-full" @change="(e) => onNoteAttachments(e, editNoteForm)" />
          </div>
        </div>
        <div class="modal-action">
          <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
          <button class="btn btn-primary" :disabled="editNoteForm.processing" @click="submitEditNote">Simpan</button>
        </div>
      </div>
    </dialog>

    <dialog id="modal-edit-budget-item" class="modal">
      <div class="modal-box">
        <h3 class="text-lg font-bold">Edit Budget Item</h3>
        <div class="mt-4 space-y-3">
          <input v-model="editBudgetForm.name" class="input input-bordered w-full" placeholder="Nama item" />
          <input v-model="editBudgetForm.qty" type="number" min="0.01" step="0.01" class="input input-bordered w-full" placeholder="Qty" />
          <input v-model="editBudgetForm.estimated_unit_price" type="number" min="0" step="0.01" class="input input-bordered w-full" placeholder="Harga estimasi" />
        </div>
        <div class="modal-action">
          <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
          <button class="btn btn-primary" :disabled="editBudgetForm.processing" @click="submitEditBudget">Simpan</button>
        </div>
      </div>
    </dialog>

    <dialog id="modal-edit-purchase" class="modal">
      <div class="modal-box max-w-3xl">
        <h3 class="text-lg font-bold">Edit Purchase</h3>
        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
          <select v-model="editPurchaseForm.master_product_id" class="select select-bordered w-full">
            <option value="">Pilih item</option>
            <option v-for="product in productOptions" :key="product.id" :value="product.id">{{ product.sku }} - {{ product.name }}</option>
          </select>
          <select v-model="editPurchaseForm.supplier_id" class="select select-bordered w-full">
            <option value="">Pilih supplier</option>
            <option v-for="supplier in supplierOptions" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
          </select>
          <input v-model="editPurchaseForm.qty" type="number" min="0.01" step="0.01" class="input input-bordered w-full" />
          <input v-model="editPurchaseForm.unit_price" type="number" min="0" step="0.01" class="input input-bordered w-full" />
          <select v-model="editPurchaseForm.category" class="select select-bordered w-full">
            <option value="alat">alat</option>
            <option value="bahan">bahan</option>
          </select>
          <input v-model="editPurchaseForm.purchase_date" type="date" class="input input-bordered w-full" />
          <input v-model="editPurchaseForm.notes" class="input input-bordered w-full md:col-span-2" placeholder="Catatan" />
          <input type="file" class="file-input file-input-bordered w-full md:col-span-2" @change="(e) => onPurchaseReceipt(e, editPurchaseForm)" />
        </div>
        <div class="modal-action">
          <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
          <button class="btn btn-primary" :disabled="editPurchaseForm.processing" @click="submitEditPurchase">Simpan</button>
        </div>
      </div>
    </dialog>

    <dialog id="modal-edit-output" class="modal">
      <div class="modal-box">
        <h3 class="text-lg font-bold">Edit Output Produk</h3>
        <div class="mt-4 space-y-3">
          <input v-model="editOutputForm.name" class="input input-bordered w-full" placeholder="Nama output" />
          <textarea v-model="editOutputForm.description" class="textarea textarea-bordered min-h-24 w-full" placeholder="Deskripsi" />
          <input v-model="editOutputForm.units_produced" type="number" min="0" step="0.01" class="input input-bordered w-full" placeholder="Units produced" />
          <textarea v-model="editOutputForm.notes" class="textarea textarea-bordered min-h-24 w-full" placeholder="Catatan" />
        </div>
        <div class="modal-action">
          <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
          <button class="btn btn-primary" :disabled="editOutputForm.processing" @click="submitEditOutput">Simpan</button>
        </div>
      </div>
    </dialog>
  </AppLayout>
</template>
