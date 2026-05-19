<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  title: String,
  submitLabel: String,
  submitRoute: String,
  method: { type: String, default: 'post' },
  project: Object,
  users: Array,
  statusOptions: Array,
});

const form = useForm({
  name: props.project?.name ?? '',
  description: props.project?.description ?? '',
  category: props.project?.category ?? '',
  status: props.project?.status ?? 'idea',
  pic_user_id: props.project?.pic_user_id ?? '',
  start_date: props.project?.start_date ?? '',
  notes: props.project?.notes ?? '',
});

const submit = () => {
  const options = {
    preserveScroll: true,
  };

  if (props.method === 'put') {
    form.put(props.submitRoute, options);
    return;
  }

  form.post(props.submitRoute, options);
};
</script>

<template>
  <Head :title="title" />
  <AppLayout>
    <div class="space-y-5">
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">R&amp;D Workspace</p>
              <h1 class="ocn-panel__title mt-1">{{ title }}</h1>
              <p class="ocn-panel__desc mt-1">Simpan data dasar project R&amp;D sebelum mulai riset, belanja, dan pelaporan.</p>
            </div>
            <Link class="btn btn-ghost btn-sm shrink-0 gap-1.5" :href="route('rnd.dashboard')">
              <ArrowLeftIcon class="h-4 w-4" />
              Back
            </Link>
          </div>
        </div>
      </div>

      <div class="ocn-panel">
        <div class="card-body grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Nama Project</span></label>
            <input v-model="form.name" class="input input-bordered w-full" />
            <InputError :message="form.errors.name" class="mt-1" />
          </div>

          <div>
            <label class="label"><span class="label-text">Kategori</span></label>
            <input v-model="form.category" class="input input-bordered w-full" placeholder="Contoh: Prototipe, Formula, Elektronik" />
            <InputError :message="form.errors.category" class="mt-1" />
          </div>

          <div>
            <label class="label"><span class="label-text">Status</span></label>
            <select v-model="form.status" class="select select-bordered w-full">
              <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
            </select>
            <InputError :message="form.errors.status" class="mt-1" />
          </div>

          <div>
            <label class="label"><span class="label-text">PIC</span></label>
            <select v-model="form.pic_user_id" class="select select-bordered w-full">
              <option value="">Pilih PIC</option>
              <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
            </select>
            <InputError :message="form.errors.pic_user_id" class="mt-1" />
          </div>

          <div>
            <label class="label"><span class="label-text">Tanggal Mulai</span></label>
            <input v-model="form.start_date" type="date" class="input input-bordered w-full" />
            <InputError :message="form.errors.start_date" class="mt-1" />
          </div>

          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Deskripsi</span></label>
            <textarea v-model="form.description" class="textarea textarea-bordered min-h-28 w-full" />
            <InputError :message="form.errors.description" class="mt-1" />
          </div>

          <div class="md:col-span-2">
            <label class="label"><span class="label-text">Catatan Internal</span></label>
            <textarea v-model="form.notes" class="textarea textarea-bordered min-h-28 w-full" />
            <InputError :message="form.errors.notes" class="mt-1" />
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-base-200 px-6 py-4">
          <Link class="btn btn-ghost" :href="route('rnd.dashboard')">Batal</Link>
          <button class="btn btn-primary" :disabled="form.processing" @click="submit">{{ submitLabel }}</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
