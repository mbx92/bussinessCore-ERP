<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  setting: Object,
});

const form = useForm({
  app_name: props.setting?.app_name ?? 'BusinessCore ERP',
  app_tagline: props.setting?.app_tagline ?? 'Integrated Business Platform',
  app_logo: null,
  remove_logo: false,
  module_menu_layout: props.setting?.module_menu_layout ?? 'grid',
});

const onFileChange = (event) => {
  form.app_logo = event.target.files?.[0] ?? null;
  if (form.app_logo) form.remove_logo = false;
};

const submit = () => {
  form.post(route('erp.admin.erp-settings.update'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.app_logo = null;
      form.remove_logo = false;
    },
  });
};
</script>

<template>
  <Head title="Administration - ERP Setting" />
  <AppLayout>
    <div class="space-y-5">
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Administration Workspace</p>
              <h1 class="ocn-panel__title mt-1">ERP Setting</h1>
              <p class="ocn-panel__desc mt-1">Atur branding aplikasi dan layout global untuk halaman menu modul.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
              <Link class="btn btn-ghost btn-sm shrink-0 gap-1.5" :href="route('erp.administration')">
                <ArrowLeftIcon class="h-4 w-4" />
                Back
              </Link>
            </div>
          </div>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Identitas Aplikasi</h2>
          <p class="ocn-panel__desc">Perubahan akan tampil di sidebar ERP.</p>
        </div>
        <div class="card-body space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <label class="label p-0"><span class="label-text">Nama Aplikasi</span></label>
              <input v-model="form.app_name" type="text" class="input input-bordered w-full" placeholder="BusinessCore ERP">
              <p v-if="form.errors.app_name" class="text-xs text-error">{{ form.errors.app_name }}</p>
            </div>
            <div class="space-y-2">
              <label class="label p-0"><span class="label-text">Tagline</span></label>
              <input v-model="form.app_tagline" type="text" class="input input-bordered w-full" placeholder="Integrated Business Platform">
              <p v-if="form.errors.app_tagline" class="text-xs text-error">{{ form.errors.app_tagline }}</p>
            </div>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <label class="label p-0"><span class="label-text">Logo Aplikasi</span></label>
              <input type="file" accept="image/*" class="file-input file-input-bordered w-full" @change="onFileChange">
              <p class="text-xs text-base-content/60">Format gambar (png/jpg/webp), maksimal 2MB.</p>
              <p v-if="form.errors.app_logo" class="text-xs text-error">{{ form.errors.app_logo }}</p>
            </div>
            <div class="space-y-2">
              <label class="label p-0"><span class="label-text">Preview</span></label>
              <div class="flex min-h-24 items-center gap-3 rounded-xl border border-base-300 bg-base-100 p-3">
                <div v-if="setting?.app_logo_url && !form.remove_logo" class="h-12 w-12 overflow-hidden rounded-lg border border-base-300 bg-white">
                  <img :src="setting.app_logo_url" alt="Logo App" class="h-full w-full object-contain">
                </div>
                <div v-else class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-xs font-bold text-primary">ERP</div>
                <div>
                  <p class="text-sm font-semibold">{{ form.app_name || 'BusinessCore ERP' }}</p>
                  <p class="text-xs text-base-content/60">{{ form.app_tagline || 'Integrated Business Platform' }}</p>
                </div>
              </div>
              <label class="label cursor-pointer justify-start gap-2 p-0">
                <input v-model="form.remove_logo" type="checkbox" class="checkbox checkbox-sm" :disabled="!setting?.app_logo_url">
                <span class="label-text">Hapus logo saat ini</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <h2 class="ocn-panel__title">Layout Menu Modul</h2>
          <p class="ocn-panel__desc">Global setting untuk tampilan submenu pada workspace ERP, Personal, dan Kelola User.</p>
        </div>
        <div class="card-body space-y-4">
          <div class="grid gap-4 lg:grid-cols-2">
            <label class="cursor-pointer rounded-2xl border p-4 transition"
              :class="form.module_menu_layout === 'grid' ? 'border-primary bg-primary/5' : 'border-base-300 bg-base-100'">
              <input v-model="form.module_menu_layout" type="radio" class="sr-only" value="grid">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-base-content">Grid view</p>
                  <p class="mt-1 text-sm text-base-content/65">Memakai layout kartu seperti tampilan menu modul saat ini.</p>
                </div>
                <span class="badge" :class="form.module_menu_layout === 'grid' ? 'badge-primary' : 'badge-ghost'">Current style</span>
              </div>
              <div class="mt-4 grid grid-cols-2 gap-2">
                <div class="rounded-xl border border-base-300 bg-base-100 p-3">
                  <div class="h-3 w-3 rounded-full bg-primary/20" />
                  <div class="mt-3 h-3 w-2/3 rounded bg-base-300" />
                  <div class="mt-2 h-2 w-full rounded bg-base-200" />
                  <div class="mt-1 h-2 w-5/6 rounded bg-base-200" />
                </div>
                <div class="rounded-xl border border-base-300 bg-base-100 p-3">
                  <div class="h-3 w-3 rounded-full bg-primary/20" />
                  <div class="mt-3 h-3 w-3/4 rounded bg-base-300" />
                  <div class="mt-2 h-2 w-full rounded bg-base-200" />
                  <div class="mt-1 h-2 w-4/6 rounded bg-base-200" />
                </div>
              </div>
            </label>

            <label class="cursor-pointer rounded-2xl border p-4 transition"
              :class="form.module_menu_layout === 'list' ? 'border-primary bg-primary/5' : 'border-base-300 bg-base-100'">
              <input v-model="form.module_menu_layout" type="radio" class="sr-only" value="list">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-base-content">List view</p>
                  <p class="mt-1 text-sm text-base-content/65">Tampilkan submenu modul sebagai daftar vertikal yang lebih ringkas.</p>
                </div>
                <span class="badge" :class="form.module_menu_layout === 'list' ? 'badge-primary' : 'badge-ghost'">List layout</span>
              </div>
              <div class="mt-4 rounded-xl border border-base-300 bg-base-100">
                <div class="flex items-center gap-3 border-b border-base-200 px-3 py-3">
                  <div class="h-10 w-10 rounded-xl bg-primary/10" />
                  <div class="min-w-0 flex-1">
                    <div class="h-3 w-1/3 rounded bg-base-300" />
                    <div class="mt-2 h-2 w-5/6 rounded bg-base-200" />
                  </div>
                </div>
                <div class="flex items-center gap-3 px-3 py-3">
                  <div class="h-10 w-10 rounded-xl bg-primary/10" />
                  <div class="min-w-0 flex-1">
                    <div class="h-3 w-2/5 rounded bg-base-300" />
                    <div class="mt-2 h-2 w-4/6 rounded bg-base-200" />
                  </div>
                </div>
              </div>
            </label>
          </div>

          <p v-if="form.errors.module_menu_layout" class="text-xs text-error">{{ form.errors.module_menu_layout }}</p>

          <div class="flex justify-end">
            <button class="btn btn-primary" :disabled="form.processing" @click="submit">
              Simpan ERP Setting
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
