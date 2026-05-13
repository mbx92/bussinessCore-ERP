<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  detail: Object,
});

const poListUrl = () =>
  `${route('erp.purchasing.purchase-orders')}?supplier=${encodeURIComponent(props.detail.code)}`;

const goBack = () => {
  router.visit(route('erp.purchasing.suppliers'));
};
</script>

<template>
  <Head :title="`Supplier — ${detail.name}`" />
  <AppLayout>
    <div class="space-y-5">
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Purchasing · Supplier</p>
              <h1 class="ocn-panel__title mt-1">{{ detail.name }}</h1>
              <p class="mt-1 font-mono text-sm text-base-content/70">{{ detail.code }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
              <button type="button" class="btn btn-ghost btn-sm shrink-0 gap-1.5" @click="goBack"><ArrowLeftIcon class="h-4 w-4" />
            Back</button>
            </div>
          </div>
        </div>
      </div>

      <div class="grid gap-5 lg:grid-cols-3">
        <div class="ocn-panel lg:col-span-2">
          <div class="ocn-panel__head">
            <h2 class="ocn-panel__title">Informasi supplier</h2>
          </div>
          <div class="card-body">
            <dl class="grid gap-3 sm:grid-cols-2">
              <div>
                <dt class="text-xs font-semibold uppercase text-base-content/50">Telepon</dt>
                <dd>{{ detail.phone }}</dd>
              </div>
              <div>
                <dt class="text-xs font-semibold uppercase text-base-content/50">Email</dt>
                <dd>{{ detail.email }}</dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase text-base-content/50">Alamat</dt>
                <dd>{{ detail.address }}</dd>
              </div>
              <div>
                <dt class="text-xs font-semibold uppercase text-base-content/50">NPWP</dt>
                <dd class="font-mono text-sm">{{ detail.tax_id }}</dd>
              </div>
              <div>
                <dt class="text-xs font-semibold uppercase text-base-content/50">Termin bayar</dt>
                <dd>{{ detail.payment_terms }}</dd>
              </div>
              <div>
                <dt class="text-xs font-semibold uppercase text-base-content/50">Lead time</dt>
                <dd>{{ detail.lead_time_days }} hari</dd>
              </div>
              <div>
                <dt class="text-xs font-semibold uppercase text-base-content/50">Status</dt>
                <dd><StatusBadge :status="detail.status" /></dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase text-base-content/50">Catatan</dt>
                <dd class="text-sm text-base-content/80">{{ detail.notes }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <div class="card border border-primary/20 bg-primary/5 shadow">
          <div class="card-body">
            <h2 class="card-title text-lg">Lanjutkan proses</h2>
            <p class="text-sm text-base-content/70">Hubungkan supplier dengan alur pembelian.</p>
            <div class="card-actions mt-4 flex-col items-stretch gap-2">
              <Link class="btn btn-primary btn-sm" :href="poListUrl()">Purchase Order untuk supplier ini</Link>
              <Link class="btn btn-outline btn-sm" :href="route('erp.purchasing.reorder-planning')">
                Perencanaan reorder
              </Link>
              <Link class="btn btn-ghost btn-sm" :href="route('erp.purchasing.goods-receipts')">Penerimaan barang</Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
