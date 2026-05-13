<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  products: Array,
});

const form = useForm({
  product_id: '',
  physical_stock: 0,
  note: '',
});

const submit = () => {
  form.post(route('erp.inventory.stock-opname.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};
</script>

<template>
  <Head title="Inventory - Stok Opname" />
  <AppLayout>
    <div class="space-y-5">
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Inventory Workspace</p>
              <h1 class="ocn-panel__title mt-1">Stok Opname</h1>
              <p class="ocn-panel__desc mt-1">Lakukan penyesuaian stok fisik berkala untuk menjaga akurasi inventory.</p>
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

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Penyesuaian stok opname</h2>
        </div>
        <div class="card-body grid gap-3 md:grid-cols-2">
          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Produk</span></label>
            <select v-model="form.product_id" class="select select-bordered w-full">
              <option value="">Pilih produk</option>
              <option v-for="product in products" :key="product.id" :value="product.id">
                {{ product.sku }} - {{ product.name }} (stok saat ini: {{ product.stock }} {{ product.uom }})
              </option>
            </select>
          </div>
          <div>
            <label class="label"><span class="label-text">Stok Fisik</span></label>
            <input v-model.number="form.physical_stock" type="number" min="0" class="input input-bordered w-full" />
          </div>
          <div>
            <label class="label"><span class="label-text">Catatan</span></label>
            <input v-model="form.note" type="text" class="input input-bordered w-full" />
          </div>
          <div class="md:col-span-2 flex justify-end">
            <button class="btn btn-primary" :disabled="form.processing" @click="submit">Simpan Opname</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
