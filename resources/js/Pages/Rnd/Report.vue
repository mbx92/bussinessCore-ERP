<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTablePagination from '@/Components/DataTablePagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { useCurrency } from '@/composables/useCurrency';
import { useDateFormat } from '@/composables/useDateFormat';

defineProps({
  project: Object,
  summary: Object,
  budgetItems: Array,
  outputs: Array,
  purchases: Object,
});

const { format } = useCurrency();
const { formatDate } = useDateFormat();
</script>

<template>
  <Head :title="`R&D Report - ${project.name}`" />
  <AppLayout>
    <div class="space-y-5">
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">R&amp;D Report</p>
              <div class="mt-1 flex items-center gap-2">
                <h1 class="ocn-panel__title">{{ project.name }}</h1>
                <StatusBadge :status="project.status" />
              </div>
              <p class="ocn-panel__desc mt-1">{{ project.category }} · PIC: {{ project.pic_name || '-' }} · Mulai: {{ formatDate(project.start_date) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <a class="btn btn-primary btn-sm" :href="route('rnd.projects.report.pdf', project.id)">Export PDF</a>
              <Link class="btn btn-ghost btn-sm gap-1.5" :href="route('rnd.projects.show', project.id)">
                <ArrowLeftIcon class="h-4 w-4" />
                Back
              </Link>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="ocn-panel"><div class="card-body"><p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Estimated Budget</p><p class="mt-2 text-2xl font-semibold">{{ format(summary.estimated_budget_total) }}</p></div></div>
        <div class="ocn-panel"><div class="card-body"><p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Actual Spend</p><p class="mt-2 text-2xl font-semibold">{{ format(summary.actual_spend_total) }}</p></div></div>
        <div class="ocn-panel"><div class="card-body"><p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Alat vs Bahan</p><p class="mt-2 text-sm">{{ format(summary.alat_total) }} / {{ format(summary.bahan_total) }}</p></div></div>
        <div class="ocn-panel"><div class="card-body"><p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">HPP / Unit</p><p class="mt-2 text-2xl font-semibold">{{ format(summary.hpp_per_unit) }}</p></div></div>
      </div>

      <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <div class="ocn-panel">
          <div class="ocn-panel__head">
            <h2 class="ocn-panel__title">Budget Planning</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="table table-sm">
              <thead><tr><th>Item</th><th>Qty</th><th>Harga Est.</th><th>Total</th></tr></thead>
              <tbody>
                <tr v-for="item in budgetItems" :key="item.id">
                  <td>{{ item.name }}</td>
                  <td>{{ item.qty }}</td>
                  <td>{{ format(item.estimated_unit_price) }}</td>
                  <td>{{ format(item.total_price) }}</td>
                </tr>
                <tr v-if="!budgetItems.length"><td colspan="4" class="py-6 text-center text-base-content/50">Belum ada item budget.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="ocn-panel">
          <div class="ocn-panel__head">
            <h2 class="ocn-panel__title">Product Output &amp; HPP</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="table table-sm">
              <thead><tr><th>Output</th><th>Units</th><th>HPP / Unit</th><th>Allocated</th></tr></thead>
              <tbody>
                <tr v-for="output in outputs" :key="output.id">
                  <td>{{ output.name }}</td>
                  <td>{{ output.units_produced }}</td>
                  <td>{{ format(output.hpp_per_unit) }}</td>
                  <td>{{ format(output.allocated_cost) }}</td>
                </tr>
                <tr v-if="!outputs.length"><td colspan="4" class="py-6 text-center text-base-content/50">Belum ada output produk.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Purchase Breakdown</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="table table-zebra">
            <thead><tr><th>Tanggal</th><th>Item</th><th>Supplier</th><th>Kategori</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Receipt</th></tr></thead>
            <tbody>
              <tr v-for="purchase in (purchases?.data || [])" :key="purchase.id">
                <td>{{ formatDate(purchase.purchase_date) }}</td>
                <td>
                  <div class="font-medium">{{ purchase.product_name }}</div>
                  <div class="text-xs text-base-content/60">{{ purchase.product_sku }}</div>
                </td>
                <td>{{ purchase.supplier_name }}</td>
                <td><StatusBadge :status="purchase.category" /></td>
                <td>{{ purchase.qty }} {{ purchase.uom || '' }}</td>
                <td>{{ format(purchase.unit_price) }}</td>
                <td>{{ format(purchase.total_price) }}</td>
                <td>
                  <a v-if="purchase.receipt_url" :href="purchase.receipt_url" target="_blank" class="btn btn-ghost btn-xs">Lihat</a>
                  <span v-else class="text-base-content/40">-</span>
                </td>
              </tr>
              <tr v-if="!(purchases?.data || []).length"><td colspan="8" class="py-8 text-center text-base-content/50">Belum ada purchase.</td></tr>
            </tbody>
          </table>
        </div>
        <DataTablePagination :paginator="purchases" />
      </div>
    </div>
  </AppLayout>
</template>
