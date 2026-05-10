<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  metrics: Object,
});

const db = () => props.metrics?.database ?? {};
const net = () => props.metrics?.network ?? {};
const app = () => props.metrics?.app ?? {};

const refresh = () => {
  router.visit(route('erp.admin.server-monitoring'), { preserveScroll: true });
};
</script>

<template>
  <Head title="Administration - Monitoring server" />
  <AppLayout>
    <div class="space-y-5">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Administration Workspace</p>
        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Monitoring server</h1>
            <p class="mt-2 text-sm text-base-content/70">
              Metrik diambil di sisi <strong>server PHP</strong> saat halaman dimuat: waktu kueri ke basis data, perkiraan ukuran DB, koneksi TCP ke host DB (jika bukan SQLite), dan satu permintaan HTTP keluar untuk mengukur jaringan keluar.
            </p>
            <p class="mt-1 text-xs text-base-content/60">
              Diukur: {{ metrics?.collected_at ?? '—' }}
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline btn-sm" @click="refresh">Muat ulang metrik</button>
            <Link class="btn btn-ghost btn-sm" :href="route('erp.administration')">Back</Link>
          </div>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-2xl border border-base-200 bg-base-100 p-5 shadow-sm">
          <p class="text-xs font-semibold uppercase tracking-wide text-base-content/50">Aplikasi</p>
          <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between gap-2"><dt class="text-base-content/60">PHP</dt><dd class="font-mono">{{ app().php_version ?? '—' }}</dd></div>
            <div class="flex justify-between gap-2"><dt class="text-base-content/60">Laravel</dt><dd class="font-mono">{{ app().laravel_version ?? '—' }}</dd></div>
          </dl>
        </article>

        <article class="rounded-2xl border border-primary/20 bg-primary/5 p-5 shadow-sm md:col-span-2 xl:col-span-2">
          <p class="text-xs font-semibold uppercase tracking-wide text-primary/80">Basis data (koneksi default)</p>
          <div v-if="db().query_error" role="alert" class="alert alert-error mt-3 text-xs">{{ db().query_error }}</div>
          <div v-if="db().size_error" role="alert" class="alert alert-warning mt-3 text-xs">{{ db().size_error }}</div>
          <dl class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
            <div class="rounded-xl border border-base-200 bg-base-100/80 p-3">
              <dt class="text-xs text-base-content/60">Driver</dt>
              <dd class="mt-1 font-mono font-semibold">{{ db().driver ?? '—' }}</dd>
            </div>
            <div class="rounded-xl border border-base-200 bg-base-100/80 p-3">
              <dt class="text-xs text-base-content/60">Koneksi</dt>
              <dd class="mt-1 font-mono text-xs break-all">{{ db().connection ?? '—' }}</dd>
            </div>
            <div class="rounded-xl border border-base-200 bg-base-100/80 p-3 sm:col-span-2">
              <dt class="text-xs text-base-content/60">Host &amp; basis data</dt>
              <dd class="mt-1 font-mono text-xs break-all">
                <span v-if="db().host">{{ db().host }}<span v-if="db().port">:{{ db().port }}</span></span>
                <span v-else>—</span>
                <span v-if="db().database" class="text-base-content/70"> / {{ db().database }}</span>
              </dd>
            </div>
            <div class="rounded-xl border border-base-200 bg-base-100/80 p-3">
              <dt class="text-xs text-base-content/60">Latensi kueri (SELECT 1)</dt>
              <dd class="mt-1 text-2xl font-bold tabular-nums text-primary">
                {{ db().query_latency_ms != null ? `${db().query_latency_ms} ms` : '—' }}
              </dd>
              <p class="mt-1 text-[11px] text-base-content/55">Termasuk jaringan app→DB + eksekusi ringan.</p>
            </div>
            <div class="rounded-xl border border-base-200 bg-base-100/80 p-3">
              <dt class="text-xs text-base-content/60">TCP ke host DB</dt>
              <dd class="mt-1 text-2xl font-bold tabular-nums">
                <span v-if="db().tcp_connect_ms != null">{{ db().tcp_connect_ms }} ms</span>
                <span v-else class="text-base-content/50 text-base font-normal">N/A (SQLite / tanpa host)</span>
              </dd>
              <p v-if="db().tcp_error" class="mt-1 text-xs text-error">{{ db().tcp_error }}</p>
            </div>
            <div class="rounded-xl border border-base-200 bg-base-100/80 p-3 sm:col-span-2">
              <dt class="text-xs text-base-content/60">Perkiraan ukuran basis data</dt>
              <dd class="mt-1 text-2xl font-bold tabular-nums">
                {{ db().size_human ?? '—' }}
                <span v-if="db().size_bytes != null" class="ml-2 text-sm font-normal text-base-content/60">({{ db().size_bytes.toLocaleString() }} byte)</span>
              </dd>
              <p class="mt-1 text-[11px] text-base-content/55">PostgreSQL: pg_database_size · MySQL: information_schema · SQLite: ukuran file.</p>
            </div>
          </dl>
        </article>

        <article class="rounded-2xl border border-sky-200/60 bg-sky-50/80 p-5 shadow-sm dark:border-sky-900/40 dark:bg-sky-950/30 md:col-span-2 xl:col-span-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-sky-800/80 dark:text-sky-200/80">Jaringan keluar (dari server)</p>
          <div v-if="net().outbound_error" role="alert" class="alert alert-warning mt-3 text-xs">{{ net().outbound_error }}</div>
          <dl class="mt-3 flex flex-wrap items-end gap-6 text-sm">
            <div>
              <dt class="text-xs text-base-content/60">HTTP GET (waktu total)</dt>
              <dd class="mt-1 text-2xl font-bold tabular-nums text-sky-700 dark:text-sky-300">
                {{ net().outbound_http_ms != null ? `${net().outbound_http_ms} ms` : '—' }}
              </dd>
            </div>
            <div class="min-w-0 flex-1">
              <dt class="text-xs text-base-content/60">Target</dt>
              <dd class="mt-1 font-mono text-xs break-all text-base-content/80">{{ net().outbound_target ?? '—' }}</dd>
            </div>
          </dl>
          <p class="mt-3 text-xs text-base-content/60">
            Firewall atau proxy dapat memperlambat atau memblokir; ini bukan latensi browser pengguna.
          </p>
        </article>
      </div>
    </div>
  </AppLayout>
</template>
