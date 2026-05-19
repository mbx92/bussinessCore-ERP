<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTablePagination from '@/Components/DataTablePagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import { useCurrency } from '@/composables/useCurrency';
import { useDateFormat } from '@/composables/useDateFormat';

const props = defineProps({
  projects: Object,
  filters: Object,
  statusOptions: Array,
  summary: Object,
});

const { format } = useCurrency();
const { formatDate } = useDateFormat();

const filters = reactive({
  q: props.filters?.q ?? '',
  status: props.filters?.status ?? '',
  per_page: props.filters?.per_page ?? props.projects?.per_page ?? 25,
});

let timer;
watch(filters, (val) => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    router.get(route('rnd.dashboard'), val, { preserveState: true, replace: true });
  }, 250);
}, { deep: true });
</script>

<template>
  <Head title="R&D Dashboard" />
  <AppLayout>
    <div class="space-y-5">
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">R&amp;D Workspace</p>
              <h1 class="ocn-panel__title mt-1">Project Dashboard</h1>
              <p class="ocn-panel__desc mt-1">Pantau status riset, total modal, dan progres output produk dari semua project R&amp;D.</p>
            </div>
            <Link class="btn btn-primary btn-sm" :href="route('rnd.projects.create')">+ Project R&amp;D</Link>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="ocn-panel">
          <div class="card-body">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Total Project</p>
            <p class="mt-2 text-3xl font-semibold">{{ summary.project_count }}</p>
          </div>
        </div>
        <div class="ocn-panel">
          <div class="card-body">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Project Aktif</p>
            <p class="mt-2 text-3xl font-semibold">{{ summary.active_count }}</p>
          </div>
        </div>
        <div class="ocn-panel">
          <div class="card-body">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Total Estimasi</p>
            <p class="mt-2 text-2xl font-semibold">{{ format(summary.total_estimated_budget) }}</p>
          </div>
        </div>
        <div class="ocn-panel">
          <div class="card-body">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Total Modal Aktual</p>
            <p class="mt-2 text-2xl font-semibold">{{ format(summary.total_actual_spend) }}</p>
          </div>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Filter Project</h2>
        </div>
        <div class="card-body">
          <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[220px] flex-1">
              <label class="label"><span class="label-text text-xs uppercase tracking-wide">Search</span></label>
              <input v-model="filters.q" class="input input-sm input-bordered w-full" placeholder="Nama project / kategori / status" />
            </div>
            <div class="min-w-[180px]">
              <label class="label"><span class="label-text text-xs uppercase tracking-wide">Status</span></label>
              <select v-model="filters.status" class="select select-sm select-bordered w-full">
                <option value="">Semua</option>
                <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Daftar Project</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>Project</th>
                <th>PIC</th>
                <th>Start</th>
                <th>Status</th>
                <th>Estimasi</th>
                <th>Aktual</th>
                <th>Variance</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="project in (projects?.data || [])" :key="project.id" class="cursor-pointer hover:bg-primary/5" @click="router.visit(route('rnd.projects.show', project.id))">
                <td>
                  <div class="font-semibold">{{ project.name }}</div>
                  <div class="text-xs text-base-content/60">{{ project.category }}</div>
                </td>
                <td>{{ project.pic_name || '-' }}</td>
                <td>{{ formatDate(project.start_date) }}</td>
                <td><StatusBadge :status="project.status" /></td>
                <td>{{ format(project.estimated_budget_total) }}</td>
                <td>{{ format(project.actual_spend_total) }}</td>
                <td :class="project.variance < 0 ? 'text-error font-semibold' : 'text-success font-semibold'">{{ format(project.variance) }}</td>
              </tr>
              <tr v-if="!(projects?.data || []).length">
                <td colspan="7" class="py-10 text-center text-base-content/50">Belum ada project R&amp;D.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <DataTablePagination :paginator="projects" @update:per-page="(n) => { filters.per_page = n; }" />
      </div>
    </div>
  </AppLayout>
</template>
