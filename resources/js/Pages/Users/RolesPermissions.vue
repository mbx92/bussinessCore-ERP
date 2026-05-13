<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    roles: Array,
    selectedRoleId: Number,
    menuByGroup: Object,
    selectedPermissions: Array,
    allowedMenuPermissionNames: Array,
});

/** Draft terpisah dari useForm: array permission sering tidak tersinkron dari props jika hanya init di useForm. */
const permissionDraft = ref([]);

watch(
    () => [props.selectedRoleId, props.selectedPermissions],
    () => {
        permissionDraft.value = Array.isArray(props.selectedPermissions)
            ? [...props.selectedPermissions]
            : [];
    },
    { deep: true, immediate: true },
);

const form = useForm({ permissions: [] });

const selectedRole = computed(() =>
    props.roles.find((r) => Number(r.id) === Number(props.selectedRoleId)),
);
const permissionGroups = computed(() => Object.entries(props.menuByGroup ?? {}));
const selectedCount = computed(() => permissionDraft.value.length);
const totalAvailablePermissions = computed(() => props.allowedMenuPermissionNames?.length ?? 0);

const toggle = (name) => {
    const set = new Set(permissionDraft.value);
    if (set.has(name)) {
        set.delete(name);
    } else {
        set.add(name);
    }
    permissionDraft.value = [...set];
};

const isChecked = (name) => permissionDraft.value.includes(name);

const changeRole = (roleId) => {
    router.get(route('users.roles-permissions'), { role: roleId }, { replace: true });
};

const submit = () => {
    if (!selectedRole.value) return;
    form.permissions = [...permissionDraft.value];
    form.patch(route('users.roles-permissions.update', selectedRole.value.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Kelola User - Role & Permission" />
    <AppLayout>
        <div class="space-y-5">
            <div class="ocn-panel">
                <div class="ocn-panel__head">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Kelola User</p>
                            <h1 class="ocn-panel__title mt-1">Role &amp; Permission</h1>
                            <p class="ocn-panel__desc mt-1">Atur akses menu sidebar per role tanpa mengubah permission ERP non-menu yang sudah ada.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <Link class="btn btn-ghost btn-sm shrink-0 gap-1.5" :href="route('users.index')">
                                <ArrowLeftIcon class="h-4 w-4" />
                                Back
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!roles.length" class="alert alert-warning">
                Tidak ada role yang dapat dikelola. Pastikan role admin, manajer, dan anggota ada di database.
            </div>

            <template v-else>
                <div class="ocn-panel">
                    <div class="ocn-panel__head flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="ocn-panel__title">Pilih role</h2>
                            <p class="ocn-panel__desc">Role di sini mengikuti opsi yang dipakai saat membuat user baru.</p>
                        </div>
                        <span class="badge badge-ghost badge-sm">{{ roles.length }} role tersedia</span>
                    </div>
                    <div class="card-body">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="r in roles"
                                :key="r.id"
                                type="button"
                                class="btn btn-sm normal-case"
                                :class="Number(selectedRoleId) === Number(r.id) ? 'btn-primary' : 'btn-outline border-base-300'"
                                @click="changeRole(Number(r.id))"
                            >
                                {{ r.name }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="selectedRole" class="ocn-panel">
                    <div class="ocn-panel__head flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="ocn-panel__title">Hak akses menu</h2>
                            <p class="ocn-panel__desc">
                                Role aktif: <span class="font-semibold text-base-content">{{ selectedRole.name }}</span>.
                                Hanya permission <code class="rounded bg-base-200 px-1 text-xs">menu.*</code> yang diatur di halaman ini.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="badge badge-primary badge-sm">{{ selectedCount }} dipilih</span>
                            <span class="badge badge-ghost badge-sm">{{ totalAvailablePermissions }} total menu</span>
                        </div>
                    </div>
                    <div class="border-b border-base-200 bg-base-100/60 px-4 py-3 text-sm text-base-content/70 md:px-6">
                        Permission selain <code class="rounded bg-base-200 px-1 text-xs">menu.*</code> tetap dipertahankan saat penyimpanan.
                    </div>
                    <div :key="`perm-${selectedRoleId}`" class="grid gap-4 p-4 md:grid-cols-2 md:p-6 xl:grid-cols-3">
                        <section
                            v-for="[group, rows] in permissionGroups"
                            :key="group"
                            class="overflow-hidden rounded-xl border border-base-200 bg-base-100/70"
                        >
                            <div class="border-b border-base-200 px-4 py-3">
                                <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-primary/80">{{ group }}</h3>
                                <p class="mt-1 text-xs text-base-content/55">{{ rows.length }} menu</p>
                            </div>
                            <ul class="divide-y divide-base-200">
                                <li
                                    v-for="row in rows"
                                    :key="row.name"
                                    class="px-4 py-3 transition hover:bg-base-200/30"
                                >
                                    <label class="flex min-w-0 cursor-pointer items-start gap-3">
                                        <input
                                            type="checkbox"
                                            class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                            :checked="isChecked(row.name)"
                                            @change="toggle(row.name)"
                                        >
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-medium">{{ row.label }}</span>
                                            <span class="mt-1 block font-mono text-[11px] text-base-content/50">{{ row.name }}</span>
                                        </span>
                                    </label>
                                </li>
                            </ul>
                        </section>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-base-300 px-4 py-4 md:px-6">
                        <button
                            type="button"
                            class="btn btn-primary btn-sm md:btn-md"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            Simpan
                        </button>
                    </div>
                </div>

                <div v-else class="ocn-panel">
                    <div class="card-body py-10 text-center text-sm text-base-content/60">
                        Role tidak ditemukan atau belum dipilih.
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
