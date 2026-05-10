<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  printer: Object,
  thermalTemplateDefaults: Object,
});

const d = props.thermalTemplateDefaults ?? {};

const form = useForm({
  thermal_printer_enabled: props.printer?.thermal_printer_enabled ?? false,
  thermal_printer_host: props.printer?.thermal_printer_host ?? '',
  thermal_printer_port: props.printer?.thermal_printer_port ?? 9100,
  thermal_paper_width: props.printer?.thermal_paper_width === '58' ? '58' : '80',
  thermal_pos_margin_left_mm: Number(props.printer?.thermal_pos_margin_left_mm ?? 2) || 0,
  thermal_pos_header_align: props.printer?.thermal_pos_header_align ?? 'center',
  thermal_pos_item_align: props.printer?.thermal_pos_item_align ?? 'left',
  thermal_pos_footer_align: props.printer?.thermal_pos_footer_align ?? 'right',
  thermal_pos_section_gap: Number(props.printer?.thermal_pos_section_gap ?? 0) || 0,
  thermal_pos_header_emphasis: props.printer?.thermal_pos_header_emphasis !== false,
  thermal_pos_header_template: props.printer?.thermal_pos_header_template || d.header || '',
  thermal_pos_item_line_template: props.printer?.thermal_pos_item_line_template || d.item_line || '',
  thermal_pos_footer_template: props.printer?.thermal_pos_footer_template || d.footer || '',
});

const testForm = useForm({
  thermal_printer_host: props.printer?.thermal_printer_host ?? '',
  thermal_printer_port: props.printer?.thermal_printer_port ?? 9100,
  thermal_paper_width: props.printer?.thermal_paper_width === '58' ? '58' : '80',
});

const posTestForm = useForm({
  thermal_printer_host: props.printer?.thermal_printer_host ?? '',
  thermal_printer_port: props.printer?.thermal_printer_port ?? 9100,
  thermal_paper_width: props.printer?.thermal_paper_width === '58' ? '58' : '80',
});

/** IP sudah pernah disimpan ke DB (boleh test print) */
const hasSavedPrinter = computed(() => String(props.printer?.thermal_printer_host ?? '').trim().length > 0);

/** Kolom teks Font A mengikuti lebar kertas (ESC/POS umum) */
const thermalCols = computed(() => (form.thermal_paper_width === '58' ? 32 : 48));

const submit = () => {
  form.post(route('erp.admin.thermal-printer.update'), { preserveScroll: true });
};

const testPrint = () => {
  if (!hasSavedPrinter.value) return;
  testForm.thermal_printer_host = props.printer.thermal_printer_host;
  testForm.thermal_printer_port = props.printer.thermal_printer_port ?? 9100;
  testForm.thermal_paper_width = props.printer.thermal_paper_width === '58' ? '58' : '80';
  testForm.post(route('erp.admin.thermal-printer.test'), { preserveScroll: true });
};

const testPosReceipt = () => {
  if (!hasSavedPrinter.value) return;
  posTestForm.thermal_printer_host = props.printer.thermal_printer_host;
  posTestForm.thermal_printer_port = props.printer.thermal_printer_port ?? 9100;
  posTestForm.thermal_paper_width = props.printer.thermal_paper_width === '58' ? '58' : '80';
  posTestForm.post(route('erp.admin.thermal-printer.test-pos-receipt'), { preserveScroll: true });
};

const applyDefaultTemplates = () => {
  form.thermal_pos_header_template = d.header || '';
  form.thermal_pos_item_line_template = d.item_line || '';
  form.thermal_pos_footer_template = d.footer || '';
};
</script>

<template>
  <Head title="Administration - Printer Thermal LAN" />
  <AppLayout>
    <div class="space-y-5">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Administration Workspace</p>
        <div class="mt-2 flex items-center justify-between gap-3">
          <div>
            <h1 class="text-3xl font-bold tracking-tight">Printer thermal (LAN)</h1>
            <p class="mt-2 text-sm text-base-content/70">
              Set IP printer Epson TM di subnet yang sama dengan server aplikasi. Port RAW biasanya <strong>9100</strong>.
            </p>
          </div>
          <Link class="btn btn-ghost btn-sm" :href="route('erp.administration')">Back</Link>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Koneksi jaringan</h2>
          <p class="ocn-panel__desc">Data dikirim sebagai ESC/POS mentah lewat TCP dari server PHP (bukan dari browser pengguna).</p>
        </div>
        <div class="card-body space-y-4">
          <label class="label cursor-pointer justify-start gap-3 border border-base-200 rounded-xl p-3">
            <input v-model="form.thermal_printer_enabled" type="checkbox" class="checkbox checkbox-primary">
            <div>
              <span class="label-text font-semibold">Aktifkan printer thermal LAN</span>
              <p class="text-xs text-base-content/60">Jika mati, modul lain tidak akan mengirim ke printer (nanti).</p>
            </div>
          </label>

          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <label class="label p-0"><span class="label-text">Alamat IP printer</span></label>
              <input
                v-model="form.thermal_printer_host"
                type="text"
                class="input input-bordered w-full font-mono"
                placeholder="192.168.1.50"
                autocomplete="off"
              >
              <p v-if="form.errors.thermal_printer_host" class="text-xs text-error">{{ form.errors.thermal_printer_host }}</p>
            </div>
            <div class="space-y-2">
              <label class="label p-0"><span class="label-text">Port RAW (JetDirect)</span></label>
              <input
                v-model.number="form.thermal_printer_port"
                type="number"
                min="1"
                max="65535"
                class="input input-bordered w-full font-mono"
              >
              <p v-if="form.errors.thermal_printer_port" class="text-xs text-error">{{ form.errors.thermal_printer_port }}</p>
            </div>
          </div>

          <div class="space-y-2">
            <label class="label p-0"><span class="label-text font-medium">Lebar kertas thermal</span></label>
            <p class="text-xs text-base-content/60">
              Mengatur lebar kolom teks struk (Font A): <strong>58 mm ≈ 32 kolom</strong>, <strong>80 mm ≈ 48 kolom</strong>. Margin kiri (mm) di template dihitung dari lebar nominal ini agar sejajar dengan area cetak kertas.
            </p>
            <div class="flex flex-wrap gap-4 pt-1">
              <label class="label cursor-pointer justify-start gap-2 border border-base-200 rounded-xl px-4 py-3 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                <input v-model="form.thermal_paper_width" type="radio" name="thermal_paper_width" class="radio radio-primary" value="58">
                <span class="label-text">58 mm</span>
              </label>
              <label class="label cursor-pointer justify-start gap-2 border border-base-200 rounded-xl px-4 py-3 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                <input v-model="form.thermal_paper_width" type="radio" name="thermal_paper_width" class="radio radio-primary" value="80">
                <span class="label-text">80 mm</span>
              </label>
            </div>
            <p v-if="form.errors.thermal_paper_width" class="text-xs text-error">{{ form.errors.thermal_paper_width }}</p>
          </div>

          <div class="rounded-xl border border-base-200 bg-base-200/40 p-4 text-sm text-base-content/80">
            <p class="font-medium text-base-content">Uji cetak koneksi</p>
            <p class="mt-1 text-xs">
              Menggunakan <strong>IP, port, dan lebar kertas yang sudah disimpan</strong>. Simpan pengaturan dulu agar tombol aktif.
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
              <button
                type="button"
                class="btn min-w-[9rem]"
                :class="hasSavedPrinter ? 'btn-primary text-white' : 'btn-outline border-base-300 text-base-content/50'"
                :disabled="!hasSavedPrinter || testForm.processing || form.processing"
                @click="testPrint"
              >
                {{ testForm.processing ? 'Mengirim…' : 'Test koneksi' }}
              </button>
            </div>
            <p v-if="testForm.errors.thermal_printer_host" class="mt-2 text-xs text-error">{{ testForm.errors.thermal_printer_host }}</p>
            <p v-if="testForm.errors.thermal_printer_port" class="mt-2 text-xs text-error">{{ testForm.errors.thermal_printer_port }}</p>
            <p v-if="testForm.errors.thermal_paper_width" class="mt-2 text-xs text-error">{{ testForm.errors.thermal_paper_width }}</p>
          </div>

          <div class="flex justify-end gap-2">
            <button class="btn btn-primary" :disabled="form.processing" @click="submit">
              {{ form.processing ? 'Menyimpan…' : 'Simpan pengaturan' }}
            </button>
          </div>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Template struk POS</h2>
          <p class="ocn-panel__desc">
            Tiga blok teks dengan <strong>placeholder</strong>. Baris item diulang per produk di keranjang. Kosongkan blok untuk memakai bawaan sistem. Perataan dan margin diterapkan saat cetak ESC/POS.
          </p>
        </div>
        <div class="card-body space-y-4">
          <div class="rounded-xl border border-base-200 bg-base-100/80 p-4 space-y-4">
            <div>
              <p class="text-sm font-semibold text-base-content">Layout struk</p>
              <p class="mt-1 text-xs text-base-content/60">
                Lebar kertas saat ini: <strong>{{ form.thermal_paper_width }} mm</strong> → sekitar <strong>{{ thermalCols }} kolom</strong> teks per baris.
              </p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              <div class="space-y-2">
                <label class="label p-0"><span class="label-text font-medium">Margin kiri (mm)</span></label>
                <input
                  v-model.number="form.thermal_pos_margin_left_mm"
                  type="number"
                  min="0"
                  max="25"
                  step="0.5"
                  class="input input-bordered w-full font-mono"
                >
                <p class="text-xs text-base-content/50">Dikonversi ke spasi di kiri baris sesuai lebar kertas.</p>
                <p v-if="form.errors.thermal_pos_margin_left_mm" class="text-xs text-error">{{ form.errors.thermal_pos_margin_left_mm }}</p>
              </div>
              <div class="space-y-2">
                <label class="label p-0"><span class="label-text font-medium">Jarak antar blok</span></label>
                <select v-model.number="form.thermal_pos_section_gap" class="select select-bordered w-full">
                  <option :value="0">Tanpa baris kosong</option>
                  <option :value="1">1 baris</option>
                  <option :value="2">2 baris</option>
                  <option :value="3">3 baris</option>
                </select>
                <p class="text-xs text-base-content/50">Antara header, garis, item, dan footer.</p>
                <p v-if="form.errors.thermal_pos_section_gap" class="text-xs text-error">{{ form.errors.thermal_pos_section_gap }}</p>
              </div>
              <div class="space-y-2 md:col-span-2 lg:col-span-1">
                <label class="label cursor-pointer justify-start gap-3 rounded-xl border border-base-200 p-3">
                  <input v-model="form.thermal_pos_header_emphasis" type="checkbox" class="checkbox checkbox-primary checkbox-sm">
                  <span class="label-text text-sm">Baris pertama header <strong>tinggi ganda</strong> (penekanan)</span>
                </label>
                <p v-if="form.errors.thermal_pos_header_emphasis" class="text-xs text-error">{{ form.errors.thermal_pos_header_emphasis }}</p>
              </div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
              <div class="space-y-2">
                <label class="label p-0"><span class="label-text font-medium">Rata header</span></label>
                <select v-model="form.thermal_pos_header_align" class="select select-bordered w-full">
                  <option value="left">Kiri</option>
                  <option value="center">Tengah</option>
                  <option value="right">Kanan</option>
                </select>
                <p v-if="form.errors.thermal_pos_header_align" class="text-xs text-error">{{ form.errors.thermal_pos_header_align }}</p>
              </div>
              <div class="space-y-2">
                <label class="label p-0"><span class="label-text font-medium">Rata baris item</span></label>
                <select v-model="form.thermal_pos_item_align" class="select select-bordered w-full">
                  <option value="left">Kiri</option>
                  <option value="center">Tengah</option>
                  <option value="right">Kanan</option>
                </select>
                <p v-if="form.errors.thermal_pos_item_align" class="text-xs text-error">{{ form.errors.thermal_pos_item_align }}</p>
              </div>
              <div class="space-y-2">
                <label class="label p-0"><span class="label-text font-medium">Rata footer</span></label>
                <select v-model="form.thermal_pos_footer_align" class="select select-bordered w-full">
                  <option value="left">Kiri</option>
                  <option value="center">Tengah</option>
                  <option value="right">Kanan</option>
                </select>
                <p v-if="form.errors.thermal_pos_footer_align" class="text-xs text-error">{{ form.errors.thermal_pos_footer_align }}</p>
              </div>
            </div>
          </div>

          <div v-pre class="rounded-xl border border-base-200 bg-base-100 p-3 text-xs text-base-content/80 space-y-1">
            <p class="font-semibold text-base-content">Placeholder header &amp; footer</p>
            <p>
              <code class="rounded bg-base-200 px-1">{{app_name}}</code>
              <code class="rounded bg-base-200 px-1">{{transaction_number}}</code>
              <code class="rounded bg-base-200 px-1">{{date}}</code>
              <code class="rounded bg-base-200 px-1">{{time}}</code>
              <code class="rounded bg-base-200 px-1">{{payment_method}}</code>
            </p>
            <p>
              <code class="rounded bg-base-200 px-1">{{gross_total}}</code>
              <code class="rounded bg-base-200 px-1">{{discount_total}}</code>
              <code class="rounded bg-base-200 px-1">{{grand_total}}</code>
              <code class="rounded bg-base-200 px-1">{{cash_paid}}</code>
              <code class="rounded bg-base-200 px-1">{{change}}</code>
            </p>
            <p class="font-semibold text-base-content pt-2">Placeholder baris item (diulang)</p>
            <p>
              <code class="rounded bg-base-200 px-1">{{sku}}</code>
              <code class="rounded bg-base-200 px-1">{{name}}</code>
              <code class="rounded bg-base-200 px-1">{{qty}}</code>
              <code class="rounded bg-base-200 px-1">{{unit_price}}</code>
              <code class="rounded bg-base-200 px-1">{{line_total}}</code>
              <code class="rounded bg-base-200 px-1">{{discount_percent}}</code>
            </p>
          </div>

          <div class="space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <label class="label p-0"><span class="label-text font-medium">Header struk</span></label>
              <button type="button" class="btn btn-ghost btn-xs" @click="applyDefaultTemplates">Isi semua dengan default</button>
            </div>
            <textarea v-model="form.thermal_pos_header_template" class="textarea textarea-bordered w-full font-mono text-sm" rows="5" placeholder="Judul, alamat, no struk…" />
            <p v-if="form.errors.thermal_pos_header_template" class="text-xs text-error">{{ form.errors.thermal_pos_header_template }}</p>
          </div>

          <div class="space-y-2">
            <label class="label p-0"><span class="label-text font-medium">Format satu baris item</span></label>
            <textarea v-model="form.thermal_pos_item_line_template" class="textarea textarea-bordered w-full font-mono text-sm" rows="4" placeholder="Qty, nama, harga…" />
            <p v-if="form.errors.thermal_pos_item_line_template" class="text-xs text-error">{{ form.errors.thermal_pos_item_line_template }}</p>
          </div>

          <div class="space-y-2">
            <label class="label p-0"><span class="label-text font-medium">Footer struk</span></label>
            <textarea v-model="form.thermal_pos_footer_template" class="textarea textarea-bordered w-full font-mono text-sm" rows="5" placeholder="Total, bayar, kembali…" />
            <p v-if="form.errors.thermal_pos_footer_template" class="text-xs text-error">{{ form.errors.thermal_pos_footer_template }}</p>
          </div>

          <div class="rounded-xl border border-base-200 bg-base-200/40 p-4 text-sm text-base-content/80">
            <p class="font-medium text-base-content">Uji cetak struk POS (template)</p>
            <p class="mt-1 text-xs">
              Mengirim data <strong>contoh</strong> ke printer yang sudah disimpan, dengan template yang <strong>tersimpan di database</strong> (bukan teks di form yang belum disimpan). Simpan dulu bila mengubah template.
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
              <button
                type="button"
                class="btn min-w-[10rem]"
                :class="hasSavedPrinter ? 'btn-primary text-white' : 'btn-outline border-base-300 text-base-content/50'"
                :disabled="!hasSavedPrinter || posTestForm.processing || form.processing"
                @click="testPosReceipt"
              >
                {{ posTestForm.processing ? 'Mengirim…' : 'Test struk POS' }}
              </button>
            </div>
            <p v-if="posTestForm.errors.thermal_printer_host" class="mt-2 text-xs text-error">{{ posTestForm.errors.thermal_printer_host }}</p>
            <p v-if="posTestForm.errors.thermal_paper_width" class="mt-2 text-xs text-error">{{ posTestForm.errors.thermal_paper_width }}</p>
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
