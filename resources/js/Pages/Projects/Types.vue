<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    types: { type: Array, default: () => [] },
});

const addForm = useForm({
    key: '',
    label: '',
    description: '',
    supports_budget_items: false,
    supports_project_board: false,
    is_active: true,
    is_default: false,
    sort_order: 0,
});

const resetAddForm = () => {
    addForm.clearErrors();
    addForm.reset();
    addForm.is_active = true;
    addForm.is_default = false;
    addForm.sort_order = 0;
};

const openAddModal = () => {
    resetAddForm();
    document.getElementById('modal-add-project-type')?.showModal();
};

const submitAdd = () => {
    addForm.post(route('erp.projects.project-types.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetAddForm();
            document.getElementById('modal-add-project-type')?.close();
        },
    });
};

const editing = ref(null);
const editForm = useForm({
    label: '',
    description: '',
    supports_budget_items: false,
    supports_project_board: false,
    is_active: true,
    is_default: false,
    sort_order: 0,
});

const openEditModal = (row) => {
    editing.value = row;
    editForm.clearErrors();
    editForm.label = row.label ?? '';
    editForm.description = row.description ?? '';
    editForm.supports_budget_items = !!row.supports_budget_items;
    editForm.supports_project_board = !!row.supports_project_board;
    editForm.is_active = !!row.is_active;
    editForm.is_default = !!row.is_default;
    editForm.sort_order = Number(row.sort_order ?? 0);
    document.getElementById('modal-edit-project-type')?.showModal();
};

const submitEdit = () => {
    if (!editing.value) return;
    editForm.patch(route('erp.projects.project-types.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = null;
            document.getElementById('modal-edit-project-type')?.close();
        },
    });
};
</script>

<template>
    <Head title="Master Tipe Project" />
    <AppLayout>
        <div class="space-y-5">
            <div class="ocn-panel">
                <div class="ocn-panel__head">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Projects Workspace</p>
                            <h1 class="ocn-panel__title mt-1">Master Tipe Project</h1>
                            <p class="ocn-panel__desc mt-1">Kelola label, tipe default, serta capability seperti board task dan budget item.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <button type="button" class="btn btn-primary btn-sm" @click="openAddModal">+ Tipe Project</button>
                            <Link :href="route('erp.projects')" class="btn btn-ghost btn-sm shrink-0 gap-1.5">
                                <ArrowLeftIcon class="h-4 w-4" />
                                Back
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ocn-panel">
                <div class="ocn-panel__head">
                    <h2 class="ocn-panel__title">Daftar tipe</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Key</th>
                                <th>Capability</th>
                                <th>Status</th>
                                <th>Pemakaian</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in types" :key="row.id">
                                <td>
                                    <div class="font-medium">{{ row.label }}</div>
                                    <div v-if="row.description" class="text-xs text-base-content/60">{{ row.description }}</div>
                                </td>
                                <td class="font-mono text-xs">{{ row.key }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        <span class="badge badge-ghost badge-sm">Budget Item: {{ row.supports_budget_items ? 'On' : 'Off' }}</span>
                                        <span class="badge badge-ghost badge-sm">Board Task: {{ row.supports_project_board ? 'On' : 'Off' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        <span class="badge badge-sm" :class="row.is_active ? 'badge-success' : 'badge-ghost'">{{ row.is_active ? 'Active' : 'Inactive' }}</span>
                                        <span v-if="row.is_default" class="badge badge-primary badge-sm">Default</span>
                                    </div>
                                </td>
                                <td class="text-sm">
                                    <div>{{ row.project_count }} project</div>
                                    <div class="text-base-content/60">{{ row.budget_count }} budget</div>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-ghost btn-xs" @click="openEditModal(row)">Edit</button>
                                </td>
                            </tr>
                            <tr v-if="!types.length">
                                <td colspan="6" class="py-8 text-center text-base-content/50">Belum ada tipe project.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <dialog id="modal-add-project-type" class="modal">
            <div class="modal-box max-w-2xl">
                <h3 class="text-lg font-bold">Tambah Tipe Project</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="label"><span class="label-text">Key</span></label>
                        <input v-model="addForm.key" type="text" class="input input-bordered w-full" placeholder="network_installation" />
                        <p v-if="addForm.errors.key" class="mt-1 text-xs text-error">{{ addForm.errors.key }}</p>
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Label</span></label>
                        <input v-model="addForm.label" type="text" class="input input-bordered w-full" placeholder="Network Installation" />
                        <p v-if="addForm.errors.label" class="mt-1 text-xs text-error">{{ addForm.errors.label }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="label"><span class="label-text">Deskripsi</span></label>
                        <textarea v-model="addForm.description" class="textarea textarea-bordered w-full" rows="3" />
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Urutan</span></label>
                        <input v-model.number="addForm.sort_order" type="number" min="0" class="input input-bordered w-full" />
                    </div>
                    <div class="space-y-2 pt-8">
                        <label class="flex items-center gap-2 text-sm"><input v-model="addForm.is_active" type="checkbox" class="checkbox checkbox-sm" /> Active</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="addForm.is_default" type="checkbox" class="checkbox checkbox-sm" /> Jadikan default</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="addForm.supports_budget_items" type="checkbox" class="checkbox checkbox-sm" /> Pakai budget item</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="addForm.supports_project_board" type="checkbox" class="checkbox checkbox-sm" /> Aktifkan board task</label>
                    </div>
                </div>
                <div class="modal-action">
                    <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
                    <button type="button" class="btn btn-primary" :disabled="addForm.processing" @click="submitAdd">Simpan</button>
                </div>
            </div>
        </dialog>

        <dialog id="modal-edit-project-type" class="modal">
            <div class="modal-box max-w-2xl">
                <h3 class="text-lg font-bold">Edit Tipe Project</h3>
                <p v-if="editing" class="mt-1 text-sm text-base-content/60">Key: <span class="font-mono">{{ editing.key }}</span></p>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="label"><span class="label-text">Label</span></label>
                        <input v-model="editForm.label" type="text" class="input input-bordered w-full" />
                        <p v-if="editForm.errors.label" class="mt-1 text-xs text-error">{{ editForm.errors.label }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="label"><span class="label-text">Deskripsi</span></label>
                        <textarea v-model="editForm.description" class="textarea textarea-bordered w-full" rows="3" />
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Urutan</span></label>
                        <input v-model.number="editForm.sort_order" type="number" min="0" class="input input-bordered w-full" />
                    </div>
                    <div class="space-y-2 pt-8">
                        <label class="flex items-center gap-2 text-sm"><input v-model="editForm.is_active" type="checkbox" class="checkbox checkbox-sm" /> Active</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="editForm.is_default" type="checkbox" class="checkbox checkbox-sm" /> Jadikan default</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="editForm.supports_budget_items" type="checkbox" class="checkbox checkbox-sm" /> Pakai budget item</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="editForm.supports_project_board" type="checkbox" class="checkbox checkbox-sm" /> Aktifkan board task</label>
                    </div>
                </div>
                <div class="modal-action">
                    <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
                    <button type="button" class="btn btn-primary" :disabled="editForm.processing" @click="submitEdit">Simpan perubahan</button>
                </div>
            </div>
        </dialog>
    </AppLayout>
</template>
