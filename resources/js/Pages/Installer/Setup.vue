<script setup>
import {
  CheckIcon,
  CircleStackIcon,
  CloudIcon,
  CodeBracketIcon,
  Cog6ToothIcon,
  ServerStackIcon,
  WrenchScrewdriverIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  moduleOptions: { type: Array, default: () => [] },
  defaults: { type: Object, default: () => ({}) },
});

const formSteps = [
  { key: 'database', title: 'Database', desc: 'Masukkan koneksi database yang akan dipakai installer.' },
  { key: 'identity', title: 'Identitas', desc: 'Atur nama aplikasi dan data usaha awal.' },
  { key: 'admin', title: 'Administrator', desc: 'Buat akun admin pertama untuk login setelah setup.' },
  { key: 'modules', title: 'Modul', desc: 'Pilih modul yang ingin langsung diaktifkan.' },
];

const processingSteps = [
  { key: 'database', title: 'Database', desc: 'Validasi koneksi dan siapkan target database.' },
  { key: 'identity', title: 'Identitas', desc: 'Simpan nama aplikasi dan konteks usaha awal.' },
  { key: 'migrate', title: 'Migrasi', desc: 'Jalankan schema dasar aplikasi secara otomatis.' },
  { key: 'admin', title: 'Admin', desc: 'Buat akun administrator pertama.' },
  { key: 'modules', title: 'Modul', desc: 'Aktifkan modul yang dipilih untuk workspace awal.' },
];

const processingMessages = [
  'Menghubungkan database dan menyiapkan target instalasi...',
  'Menyimpan identitas aplikasi dan entitas usaha awal...',
  'Menjalankan migrasi schema inti aplikasi...',
  'Membuat akun administrator pertama...',
  'Mengaktifkan modul yang dipilih dan merapikan setup akhir...',
];

const form = useForm({
  db_connection: props.defaults.db_connection ?? 'pgsql',
  db_host: props.defaults.db_host ?? '127.0.0.1',
  db_port: props.defaults.db_port ?? '5432',
  db_database: props.defaults.db_database ?? '',
  db_username: props.defaults.db_username ?? '',
  db_password: props.defaults.db_password ?? '',
  app_name: props.defaults.app_name ?? 'BusinessCore ERP',
  app_tagline: props.defaults.app_tagline ?? 'Business Operating Platform',
  company_name: props.defaults.company_name ?? '',
  company_legal_name: props.defaults.company_legal_name ?? '',
  company_tax_id: '',
  admin_name: '',
  admin_email: '',
  admin_password: '',
  admin_password_confirmation: '',
  modules: props.defaults.modules ?? [],
});

const currentStepIndex = ref(0);
const processingStepIndex = ref(0);
const processingMessage = ref('');
const stepError = ref('');
const progressValue = ref(0);
const loaderFrameIndex = ref(0);
const installRuntimeError = ref('');
const connectionTestState = ref({ status: 'idle', message: '', warning: '' });
const installScreenVisible = ref(false);
const isInstalling = ref(false);
const installPreviewMode = ref('');
const hiddenProcessingSteps = ref([]);
const fadingProcessingStepIndex = ref(null);
let processingTimer = null;
let progressTimer = null;
let loaderTimer = null;
let processingFadeTimer = null;
let previewErrorTimer = null;

const loaderIcons = [
  { key: 'code', component: CodeBracketIcon, tone: 'from-cyan-300 via-sky-300 to-blue-400', ring: 'ring-cyan-300/40' },
  { key: 'gear', component: Cog6ToothIcon, tone: 'from-emerald-300 via-teal-300 to-cyan-400', ring: 'ring-emerald-300/40' },
  { key: 'tools', component: WrenchScrewdriverIcon, tone: 'from-amber-200 via-orange-300 to-rose-400', ring: 'ring-amber-300/40' },
  { key: 'cloud', component: CloudIcon, tone: 'from-sky-200 via-cyan-300 to-indigo-400', ring: 'ring-sky-300/40' },
  { key: 'server', component: ServerStackIcon, tone: 'from-violet-200 via-fuchsia-300 to-sky-400', ring: 'ring-violet-300/40' },
  { key: 'database', component: CircleStackIcon, tone: 'from-lime-200 via-emerald-300 to-cyan-400', ring: 'ring-lime-300/40' },
];
const activeLoaderIcon = computed(() => loaderIcons[loaderFrameIndex.value % loaderIcons.length] ?? loaderIcons[0]);
const isPreviewingInstall = computed(() => installPreviewMode.value !== '');
const isProcessingComplete = computed(() => !installRuntimeError.value && progressValue.value >= 100);

const currentStep = computed(() => formSteps[currentStepIndex.value]);
const isFirstStep = computed(() => currentStepIndex.value === 0);
const isLastStep = computed(() => currentStepIndex.value === formSteps.length - 1);
const isProcessingWithError = computed(() => !!installRuntimeError.value);
const normalizedAdminEmail = computed(() => String(form.admin_email ?? '').trim());
const hasValidAdminEmail = computed(() => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizedAdminEmail.value));
const visibleProcessingSteps = computed(() => processingSteps
  .map((step, index) => ({ ...step, originalIndex: index }))
  .filter((step) => !hiddenProcessingSteps.value.includes(step.originalIndex)));
const currentStepIsValid = computed(() => {
  if (currentStep.value.key === 'database') {
    if (!form.db_connection || !String(form.db_database).trim()) return false;
    if (form.db_connection !== 'sqlite' && (!String(form.db_host).trim() || !String(form.db_port).trim() || !String(form.db_username).trim())) return false;
    if (form.db_connection !== 'sqlite' && !String(form.db_password).trim()) return false;
    return true;
  }

  if (currentStep.value.key === 'identity') {
    return !!String(form.app_name).trim() && !!String(form.company_name).trim();
  }

  if (currentStep.value.key === 'admin') {
    return !!String(form.admin_name).trim()
      && hasValidAdminEmail.value
      && !!form.admin_password
      && !!form.admin_password_confirmation
      && form.admin_password === form.admin_password_confirmation;
  }

  if (currentStep.value.key === 'modules') {
    return form.modules.length > 0;
  }

  return false;
});

const progressPercent = computed(() => {
  return progressValue.value;
});

const clearProcessingTimer = () => {
  if (processingTimer) {
    clearInterval(processingTimer);
    processingTimer = null;
  }

  if (progressTimer) {
    clearInterval(progressTimer);
    progressTimer = null;
  }

  if (loaderTimer) {
    clearInterval(loaderTimer);
    loaderTimer = null;
  }

  if (processingFadeTimer) {
    clearTimeout(processingFadeTimer);
    processingFadeTimer = null;
  }

  if (previewErrorTimer) {
    clearTimeout(previewErrorTimer);
    previewErrorTimer = null;
  }
};

const startLoaderCycle = (interval = 900) => {
  if (loaderTimer) {
    clearInterval(loaderTimer);
  }

  loaderTimer = setInterval(() => {
    loaderFrameIndex.value = (loaderFrameIndex.value + 1) % loaderIcons.length;
  }, interval);
};

const advanceProcessingStep = () => {
  if (processingStepIndex.value < processingSteps.length - 1) {
    const completedStep = processingStepIndex.value;
    fadingProcessingStepIndex.value = completedStep;
    if (processingFadeTimer) {
      clearTimeout(processingFadeTimer);
    }

    processingFadeTimer = setTimeout(() => {
      hiddenProcessingSteps.value = [...hiddenProcessingSteps.value, completedStep];
      fadingProcessingStepIndex.value = null;
      processingFadeTimer = null;
    }, 480);

    processingStepIndex.value += 1;
  }

  processingMessage.value = processingMessages[processingStepIndex.value] ?? 'Menyelesaikan instalasi...';
};

const openInstallPreview = (mode = 'loading') => {
  clearProcessingTimer();
  installPreviewMode.value = mode;
  installScreenVisible.value = true;
  loaderFrameIndex.value = 0;
  processingStepIndex.value = 0;
  hiddenProcessingSteps.value = [];
  fadingProcessingStepIndex.value = null;
  progressValue.value = 8;
  processingMessage.value = processingMessages[0];
  installRuntimeError.value = '';

  startLoaderCycle(950);

  progressTimer = setInterval(() => {
    const ceiling = mode === 'error' ? 76 : 98;
    if (progressValue.value < ceiling) {
      progressValue.value += progressValue.value < 45 ? 5 : progressValue.value < 72 ? 3 : 1;
    }
  }, 260);

  processingTimer = setInterval(() => {
    advanceProcessingStep();

    if (mode === 'loading' && processingStepIndex.value >= processingSteps.length - 1) {
      clearInterval(processingTimer);
      processingTimer = null;
      progressValue.value = 100;
      hiddenProcessingSteps.value = processingSteps.map((_, index) => index);
      fadingProcessingStepIndex.value = null;
      processingMessage.value = 'Preview selesai. Halaman ini hanya simulasi dan tidak akan redirect.';
    }

    if (mode === 'error' && processingStepIndex.value >= 2) {
      clearInterval(processingTimer);
      processingTimer = null;
      previewErrorTimer = setTimeout(() => {
        progressValue.value = 74;
        processingMessage.value = 'Preview error installer. Detail kegagalan ditampilkan di bawah.';
        installRuntimeError.value = 'Simulasi error: koneksi database berhasil, tetapi proses migrasi gagal karena schema target belum siap. Pada install sungguhan, pesan backend asli akan muncul di area ini.';
        previewErrorTimer = null;
      }, 420);
    }
  }, 1350);
};

watch(
  () => isInstalling.value,
  (processing) => {
    clearProcessingTimer();

    if (!processing) {
      if (!installRuntimeError.value) {
        processingStepIndex.value = 0;
        hiddenProcessingSteps.value = [];
        fadingProcessingStepIndex.value = null;
        processingMessage.value = '';
        progressValue.value = 0;
        loaderFrameIndex.value = 0;
        installScreenVisible.value = false;
      }
      return;
    }

    installScreenVisible.value = true;
    processingStepIndex.value = 0;
    hiddenProcessingSteps.value = [];
    fadingProcessingStepIndex.value = null;
    progressValue.value = 6;
    installRuntimeError.value = '';
    processingMessage.value = 'Memvalidasi koneksi database dan menyiapkan proses instalasi...';

    progressTimer = setInterval(() => {
      if (progressValue.value < 94) {
        progressValue.value += progressValue.value < 60 ? 4 : progressValue.value < 80 ? 2 : 1;
      }
    }, 240);

    startLoaderCycle(850);

    processingTimer = setInterval(() => {
      advanceProcessingStep();
    }, 1300);
  },
);

onMounted(() => {
  const preview = new URLSearchParams(window.location.search).get('preview');
  if (preview === 'loading' || preview === 'error') {
    openInstallPreview(preview);
  }
});

onBeforeUnmount(() => {
  clearProcessingTimer();
});

const validateCurrentStep = () => {
  stepError.value = '';

  if (currentStep.value.key === 'database') {
    if (!form.db_connection || !form.db_database) {
      stepError.value = 'Driver dan nama database wajib diisi.';
      return false;
    }
    if (form.db_connection !== 'sqlite' && (!form.db_host || !form.db_port || !form.db_username)) {
      stepError.value = 'Host, port, dan username database wajib diisi.';
      return false;
    }
    if (form.db_connection !== 'sqlite' && !String(form.db_password).trim()) {
      stepError.value = 'Password database wajib diisi.';
      return false;
    }
  }

  if (currentStep.value.key === 'identity') {
    if (!form.app_name || !form.company_name) {
      stepError.value = 'Nama aplikasi dan nama usaha wajib diisi.';
      return false;
    }
  }

  if (currentStep.value.key === 'admin') {
    if (!form.admin_name || !form.admin_email || !form.admin_password || !form.admin_password_confirmation) {
      stepError.value = 'Semua field administrator wajib diisi.';
      return false;
    }
    if (!hasValidAdminEmail.value) {
      stepError.value = 'Format email administrator belum valid.';
      return false;
    }
    if (form.admin_password !== form.admin_password_confirmation) {
      stepError.value = 'Konfirmasi password belum cocok.';
      return false;
    }
  }

  if (currentStep.value.key === 'modules' && form.modules.length === 0) {
    stepError.value = 'Pilih minimal satu modul.';
    return false;
  }

  return true;
};

const goNext = () => {
  if (!validateCurrentStep()) return;
  if (currentStepIndex.value < formSteps.length - 1) {
    currentStepIndex.value += 1;
    stepError.value = '';
  }
};

const goBack = () => {
  if (currentStepIndex.value > 0) {
    currentStepIndex.value -= 1;
    stepError.value = '';
  }
};

const toggleModule = (key) => {
  if (form.modules.includes(key)) {
    form.modules = form.modules.filter((item) => item !== key);
    return;
  }

  form.modules = [...form.modules, key];
};

const testConnection = async () => {
  stepError.value = '';
  connectionTestState.value = { status: 'idle', message: '', warning: '' };

  if (!form.db_connection || !String(form.db_database).trim()) {
    connectionTestState.value = { status: 'error', message: 'Driver dan nama database wajib diisi sebelum test koneksi.' };
    return;
  }

  if (form.db_connection !== 'sqlite' && (!String(form.db_host).trim() || !String(form.db_port).trim() || !String(form.db_username).trim())) {
    connectionTestState.value = { status: 'error', message: 'Host, port, dan username database wajib diisi sebelum test koneksi.', warning: '' };
    return;
  }
  if (form.db_connection !== 'sqlite' && !String(form.db_password).trim()) {
    connectionTestState.value = { status: 'error', message: 'Password database wajib diisi sebelum test koneksi.', warning: '' };
    return;
  }

  connectionTestState.value = { status: 'loading', message: 'Sedang mengetes koneksi database...', warning: '' };

  try {
    const response = await window.axios.post(route('install.test-connection'), {
      db_connection: form.db_connection,
      db_host: form.db_host,
      db_port: form.db_port,
      db_database: form.db_database,
      db_username: form.db_username,
      db_password: form.db_password,
    });

    connectionTestState.value = {
      status: 'success',
      message: response?.data?.message ?? 'Koneksi database berhasil.',
      warning: response?.data?.warning ?? '',
    };
  } catch (error) {
    connectionTestState.value = {
      status: 'error',
      message: error?.response?.data?.message
        ?? error?.response?.data?.errors?.db_database?.[0]
        ?? error?.response?.data?.errors?.db_connection?.[0]
        ?? error?.response?.data?.errors?.db_username?.[0]
        ?? 'Test koneksi gagal.',
      warning: '',
    };
  }
};

const submit = async () => {
  if (!validateCurrentStep()) return;
  installScreenVisible.value = true;
  installRuntimeError.value = '';
  isInstalling.value = true;

  if (typeof form.clearErrors === 'function') {
    form.clearErrors();
  }

  try {
    const response = await window.axios.post(route('install.store'), {
      db_connection: form.db_connection,
      db_host: form.db_host,
      db_port: form.db_port,
      db_database: form.db_database,
      db_username: form.db_username,
      db_password: form.db_password,
      app_name: form.app_name,
      app_tagline: form.app_tagline,
      company_name: form.company_name,
      company_legal_name: form.company_legal_name,
      company_tax_id: form.company_tax_id,
      admin_name: form.admin_name,
      admin_email: form.admin_email,
      admin_password: form.admin_password,
      admin_password_confirmation: form.admin_password_confirmation,
      modules: form.modules,
    }, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    progressValue.value = 100;
    processingMessage.value = 'Instalasi selesai. Menyiapkan halaman akhir installer...';

    setTimeout(() => {
      window.location.href = response?.data?.next_url ?? route('install.complete');
    }, 250);
  } catch (error) {
    console.error('Installer failed:', error);

    const errors = error?.response?.data?.errors ?? {};
    if (typeof form.setError === 'function') {
      form.setError(errors);
    }

    installRuntimeError.value = error?.response?.data?.message
      ?? errors.db_connection?.[0]
      ?? errors.db_database?.[0]
      ?? errors.db_username?.[0]
      ?? errors.admin_email?.[0]
      ?? Object.values(errors)?.[0]?.[0]
      ?? 'Instalasi gagal. Periksa kembali konfigurasi Anda.';
    processingMessage.value = 'Instalasi gagal. Silakan periksa detail error di bawah.';
    progressValue.value = Math.min(progressValue.value, 96);
    installScreenVisible.value = true;
  } finally {
    isInstalling.value = false;
  }
};

const retryInstall = () => {
  installPreviewMode.value = '';
  clearProcessingTimer();
  processingStepIndex.value = 0;
  hiddenProcessingSteps.value = [];
  fadingProcessingStepIndex.value = null;
  processingMessage.value = '';
  progressValue.value = 0;
  loaderFrameIndex.value = 0;
  installRuntimeError.value = '';
  installScreenVisible.value = false;

  setTimeout(() => {
    submit();
  }, 50);
};

const closeInstallScreen = () => {
  installPreviewMode.value = '';
  clearProcessingTimer();
  processingStepIndex.value = 0;
  hiddenProcessingSteps.value = [];
  fadingProcessingStepIndex.value = null;
  processingMessage.value = '';
  progressValue.value = 0;
  loaderFrameIndex.value = 0;
  installRuntimeError.value = '';
  installScreenVisible.value = false;
};
</script>

<template>
  <Head title="Installer" />

  <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.12),_transparent_30%),linear-gradient(160deg,#07111d_0%,#0f172a_48%,#f8fafc_48%,#f8fafc_100%)] p-3 md:p-5">
    <div class="mx-auto max-w-6xl overflow-hidden rounded-[28px] bg-white shadow-2xl ring-1 ring-slate-200 lg:grid lg:min-h-[calc(100vh-2.5rem)] lg:grid-cols-[0.92fr,1.18fr]">
      <section class="bg-slate-950 px-6 py-6 text-white md:px-8 md:py-7">
        <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-400/20 text-lg font-black text-cyan-200">
          BC
        </div>
        <p class="mt-4 text-[11px] font-bold uppercase tracking-[0.18em] text-cyan-200/80">First Run Installer</p>
        <h1 class="mt-2 text-3xl font-bold leading-tight">Siapkan aplikasi sekali.</h1>
        <p class="mt-3 max-w-md text-sm leading-6 text-slate-300">
          Wizard ini akan membuat identitas aplikasi, entitas usaha awal, akun administrator pertama, dan modul yang ingin diaktifkan.
        </p>

        <div class="mt-5 rounded-[24px] border border-cyan-300/10 bg-white/5 p-4">
          <p class="text-xs font-bold uppercase tracking-[0.16em] text-cyan-200/80">Wizard Step</p>
          <div class="mt-3 rounded-2xl border border-white/10 bg-white/5 p-4">
            <div class="flex items-center gap-3">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-cyan-300 bg-cyan-300 text-xs font-bold text-slate-950">
                {{ currentStepIndex + 1 }}
              </div>
              <p class="text-sm font-semibold text-white">{{ currentStep.title }}</p>
            </div>
            <p class="mt-2 text-sm leading-6 text-slate-300">{{ currentStep.desc }}</p>
          </div>
        </div>
      </section>

      <section class="flex flex-col px-5 py-5 md:px-7 md:py-6">
        <div class="mb-5">
          <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Step {{ currentStepIndex + 1 }} / {{ formSteps.length }}</p>
          <div class="mt-1.5 flex items-center justify-between gap-3">
            <h2 class="text-2xl font-bold tracking-tight">{{ currentStep.title }}</h2>
            <div class="hidden min-w-[180px] md:block">
              <progress class="progress progress-primary h-2 w-full" :value="currentStepIndex + 1" :max="formSteps.length" />
            </div>
          </div>
          <p class="mt-1 text-sm text-base-content/70">{{ currentStep.desc }}</p>
        </div>

        <form class="flex flex-1 flex-col" @submit.prevent="submit">
          <div v-if="currentStep.key === 'database'" class="space-y-4">
            <div>
              <h3 class="text-base font-semibold">Koneksi database</h3>
              <p class="text-sm text-base-content/60">Installer akan tes koneksi, menyimpan environment, lalu menjalankan migrasi otomatis.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Driver database</span>
                <select v-model="form.db_connection" class="select select-bordered w-full" :disabled="isInstalling">
                  <option value="pgsql">PostgreSQL</option>
                  <option value="mysql">MySQL</option>
                  <option value="sqlite">SQLite</option>
                </select>
                <span v-if="form.errors.db_connection" class="mt-1 text-xs text-error">{{ form.errors.db_connection }}</span>
              </label>
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">{{ form.db_connection === 'sqlite' ? 'Path file database' : 'Nama database' }}</span>
                <input
                  v-model="form.db_database"
                  type="text"
                  class="input input-bordered w-full"
                  :disabled="isInstalling"
                  :placeholder="form.db_connection === 'sqlite' ? 'database/database.sqlite' : 'business_core_erp'"
                />
                <span v-if="form.errors.db_database" class="mt-1 text-xs text-error">{{ form.errors.db_database }}</span>
              </label>
            </div>

            <div v-if="form.db_connection !== 'sqlite'" class="grid gap-3 md:grid-cols-2">
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Host</span>
                <input v-model="form.db_host" type="text" class="input input-bordered w-full" placeholder="127.0.0.1" :disabled="isInstalling" />
                <span v-if="form.errors.db_host" class="mt-1 text-xs text-error">{{ form.errors.db_host }}</span>
              </label>
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Port</span>
                <input v-model="form.db_port" type="text" class="input input-bordered w-full" :disabled="isInstalling" :placeholder="form.db_connection === 'pgsql' ? '5432' : '3306'" />
                <span v-if="form.errors.db_port" class="mt-1 text-xs text-error">{{ form.errors.db_port }}</span>
              </label>
            </div>

            <div v-if="form.db_connection !== 'sqlite'" class="grid gap-3 md:grid-cols-2">
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Username</span>
                <input v-model="form.db_username" type="text" class="input input-bordered w-full" :disabled="isInstalling" />
                <span v-if="form.errors.db_username" class="mt-1 text-xs text-error">{{ form.errors.db_username }}</span>
              </label>
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Password</span>
                <input v-model="form.db_password" type="password" class="input input-bordered w-full" :disabled="isInstalling" />
                <span v-if="form.errors.db_password" class="mt-1 text-xs text-error">{{ form.errors.db_password }}</span>
              </label>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <button
                type="button"
                class="btn btn-outline btn-sm"
                :disabled="isInstalling || connectionTestState.status === 'loading'"
                @click="testConnection"
              >
                <span v-if="connectionTestState.status === 'loading'" class="loading loading-spinner loading-xs" />
                Test Koneksi
              </button>
              <p
                v-if="connectionTestState.message"
                class="text-sm"
                :class="connectionTestState.status === 'success' ? 'text-success' : connectionTestState.status === 'error' ? 'text-error' : 'text-base-content/70'"
              >
                {{ connectionTestState.message }}
              </p>
              <p v-if="connectionTestState.warning" class="w-full text-sm text-warning">
                {{ connectionTestState.warning }}
              </p>
            </div>
          </div>

          <div v-if="currentStep.key === 'identity'" class="space-y-4">
            <div>
              <h3 class="text-base font-semibold">Identitas aplikasi</h3>
              <p class="text-sm text-base-content/60">Nama dan tagline default yang akan tampil di login, sidebar, dan dokumen.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Nama aplikasi</span>
                <input v-model="form.app_name" type="text" class="input input-bordered w-full" :disabled="isInstalling" />
                <span v-if="form.errors.app_name" class="mt-1 text-xs text-error">{{ form.errors.app_name }}</span>
              </label>
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Tagline</span>
                <input v-model="form.app_tagline" type="text" class="input input-bordered w-full" :disabled="isInstalling" />
                <span v-if="form.errors.app_tagline" class="mt-1 text-xs text-error">{{ form.errors.app_tagline }}</span>
              </label>
            </div>

            <div class="border-t border-slate-200 pt-4">
              <div>
                <h3 class="text-base font-semibold">Company awal</h3>
                <p class="text-sm text-base-content/60">Satu entitas usaha pertama untuk konteks ERP dan laporan.</p>
              </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Nama usaha</span>
                <input v-model="form.company_name" type="text" class="input input-bordered w-full" :disabled="isInstalling" />
                <span v-if="form.errors.company_name" class="mt-1 text-xs text-error">{{ form.errors.company_name }}</span>
              </label>
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Nama legal</span>
                <input v-model="form.company_legal_name" type="text" class="input input-bordered w-full" :disabled="isInstalling" />
                <span v-if="form.errors.company_legal_name" class="mt-1 text-xs text-error">{{ form.errors.company_legal_name }}</span>
              </label>
            </div>

            <label class="form-control">
              <span class="label-text mb-1.5 text-sm font-medium">NPWP</span>
              <input v-model="form.company_tax_id" type="text" class="input input-bordered w-full" placeholder="Opsional" :disabled="isInstalling" />
              <span v-if="form.errors.company_tax_id" class="mt-1 text-xs text-error">{{ form.errors.company_tax_id }}</span>
            </label>
          </div>

          <div v-if="currentStep.key === 'admin'" class="space-y-4">
            <div>
              <h3 class="text-base font-semibold">Administrator pertama</h3>
              <p class="text-sm text-base-content/60">Akun ini akan dipakai untuk login pertama kali setelah setup selesai.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Nama admin</span>
                <input v-model="form.admin_name" type="text" class="input input-bordered w-full" :disabled="isInstalling" />
                <span v-if="form.errors.admin_name" class="mt-1 text-xs text-error">{{ form.errors.admin_name }}</span>
              </label>
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Email admin</span>
                <input v-model="form.admin_email" type="email" class="input input-bordered w-full" :disabled="isInstalling" />
                <span v-if="form.errors.admin_email" class="mt-1 text-xs text-error">{{ form.errors.admin_email }}</span>
              </label>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Password</span>
                <input v-model="form.admin_password" type="password" class="input input-bordered w-full" :disabled="isInstalling" />
                <span v-if="form.errors.admin_password" class="mt-1 text-xs text-error">{{ form.errors.admin_password }}</span>
              </label>
              <label class="form-control">
                <span class="label-text mb-1.5 text-sm font-medium">Konfirmasi password</span>
                <input v-model="form.admin_password_confirmation" type="password" class="input input-bordered w-full" :disabled="isInstalling" />
              </label>
            </div>
          </div>

          <div v-if="currentStep.key === 'modules'" class="space-y-4">
            <div>
              <h3 class="text-base font-semibold">Pilih modul yang dipakai</h3>
              <p class="text-sm text-base-content/60">Modul yang tidak dipilih akan disembunyikan dari sidebar dan route-nya dinonaktifkan sampai diaktifkan lagi.</p>
            </div>

            <div class="grid gap-2.5 md:grid-cols-2">
              <button
                v-for="module in moduleOptions"
                :key="module.key"
                type="button"
                class="rounded-2xl border p-3 text-left transition"
                :class="form.modules.includes(module.key) ? 'border-primary bg-primary/5 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'"
                :disabled="isInstalling"
                @click="toggleModule(module.key)"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold">{{ module.label }}</p>
                    <p class="mt-0.5 text-sm leading-5 text-base-content/65">{{ module.description }}</p>
                  </div>
                  <input
                    :checked="form.modules.includes(module.key)"
                    type="checkbox"
                    class="checkbox checkbox-sm mt-1"
                    @change.prevent
                  />
                </div>
              </button>
            </div>
            <p v-if="form.errors.modules" class="text-xs text-error">{{ form.errors.modules }}</p>
          </div>

          <div v-if="stepError" class="alert alert-warning text-sm">
            {{ stepError }}
          </div>

          <div class="mt-auto flex flex-col gap-3 border-t border-slate-200 pt-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-base-content/60">Setup ini hanya bisa dibuka saat aplikasi belum terinstal.</p>
            <div class="flex flex-wrap items-center gap-3">
              <button v-if="!isFirstStep" type="button" class="btn btn-ghost btn-lg" :disabled="isInstalling" @click="goBack">
                Back
              </button>
              <button
                v-if="!isLastStep"
                type="button"
                class="btn btn-primary btn-lg"
                :disabled="isInstalling || !currentStepIsValid"
                @click="goNext"
              >
                Next
              </button>
              <button v-else type="submit" class="btn btn-primary btn-lg" :disabled="isInstalling || !currentStepIsValid">
                <span v-if="isInstalling" class="loading loading-spinner loading-sm" />
                {{ isInstalling ? 'Menjalankan setup...' : 'Install Sekarang' }}
              </button>
            </div>
          </div>
        </form>
      </section>
    </div>

    <div v-if="installScreenVisible" class="fixed inset-0 z-50 overflow-y-auto bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.2),_transparent_35%),linear-gradient(160deg,#020617_0%,#0f172a_100%)] px-4 py-6 md:py-8">
      <div class="flex min-h-full items-start justify-center">
        <div class="w-full max-w-3xl text-white">
        <div class="mx-auto flex max-w-xl flex-col items-center pt-2 text-center md:pt-4">
          <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-200/80">Installing BusinessCore ERP</p>
          <div
            class="relative mt-6 flex h-28 w-28 items-center justify-center rounded-full border bg-white/5 shadow-[0_0_80px_rgba(34,211,238,0.15)] ring-1 backdrop-blur-sm"
            :class="isProcessingWithError ? 'border-rose-300/30 ring-rose-300/30' : isProcessingComplete ? 'border-emerald-300/30 ring-emerald-300/40' : `border-cyan-300/20 ${activeLoaderIcon.ring}`"
          >
            <div
              class="absolute inset-3 rounded-full bg-gradient-to-br opacity-20 blur-xl"
              :class="isProcessingWithError ? 'from-rose-300 via-rose-400 to-red-500' : isProcessingComplete ? 'from-emerald-300 via-teal-300 to-cyan-400' : activeLoaderIcon.tone"
            />
            <div v-if="isProcessingWithError" class="relative flex h-14 w-14 items-center justify-center text-rose-200">
              <XMarkIcon class="h-14 w-14 stroke-[1.9]" />
            </div>
            <div v-else-if="isProcessingComplete" class="relative flex h-14 w-14 items-center justify-center text-emerald-200">
              <CheckIcon class="h-14 w-14 stroke-[1.9]" />
            </div>
            <div v-else class="relative h-14 w-14">
              <component
                v-for="(icon, index) in loaderIcons"
                :is="icon.component"
                :key="icon.key"
                class="absolute inset-0 h-14 w-14 stroke-[1.6] transition-all duration-500 ease-out"
                :class="index === loaderFrameIndex
                  ? 'scale-100 rotate-0 opacity-100 text-white'
                  : 'pointer-events-none scale-75 -rotate-12 opacity-0 text-white/0'"
              />
            </div>
          </div>
          <h3 class="mt-6 text-3xl font-bold tracking-tight">{{ isProcessingWithError ? 'Setup mengalami error' : isProcessingComplete ? 'Setup selesai' : 'Setup sedang berjalan' }}</h3>
          <p class="mt-3 max-w-lg text-sm leading-7 text-slate-300">
            {{ isProcessingWithError
              ? 'Proses instalasi berhenti karena ada kesalahan konfigurasi atau koneksi. Silakan periksa detail error di bawah.'
              : isProcessingComplete
                ? 'Semua tahap preview sudah selesai. Tampilan ini berhenti di sini agar Anda bisa menikmati state akhir tanpa redirect otomatis.'
              : 'Jangan tutup halaman ini. Installer sedang menyiapkan database, migrasi, akun admin, dan aktivasi modul awal Anda.' }}
          </p>
          <p v-if="isPreviewingInstall" class="mt-3 rounded-full border border-amber-300/20 bg-amber-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-amber-100">
            Preview Mode
          </p>
        </div>

        <div class="mx-auto mt-6 max-w-2xl rounded-[28px] border border-white/10 bg-white/5 p-6 backdrop-blur-sm md:mt-8">
          <div class="flex items-center justify-between gap-4 text-sm font-medium">
            <span>{{ processingMessage }}</span>
            <span :class="isProcessingWithError ? 'text-rose-200' : 'text-cyan-200'" class="text-base font-bold">{{ progressPercent }}%</span>
          </div>
          <div class="mt-4 h-3 overflow-hidden rounded-full bg-white/10">
            <div
              class="h-full rounded-full transition-all duration-300"
              :class="isProcessingWithError ? 'bg-gradient-to-r from-rose-300 via-rose-400 to-red-500' : 'bg-gradient-to-r from-cyan-300 via-sky-400 to-blue-500'"
              :style="{ width: `${progressPercent}%` }"
            />
          </div>
          <div v-if="installRuntimeError" class="mt-4 rounded-2xl border border-rose-300/20 bg-rose-500/10 p-4 text-left">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-rose-200/90">Error</p>
            <p class="mt-2 text-sm leading-6 text-rose-100">{{ installRuntimeError }}</p>
            <div class="mt-4 flex flex-wrap items-center gap-3">
              <button v-if="!isPreviewingInstall" type="button" class="btn btn-sm border-0 bg-rose-200 text-slate-950 hover:bg-rose-100" @click="retryInstall">
                Ulangi
              </button>
              <button type="button" class="btn btn-sm btn-ghost text-white hover:bg-white/10" @click="closeInstallScreen">
                {{ isPreviewingInstall ? 'Tutup preview' : 'Kembali ke form' }}
              </button>
            </div>
          </div>
        </div>

        <TransitionGroup v-if="visibleProcessingSteps.length > 0" name="processing-step" tag="div" class="mx-auto mt-5 max-w-2xl space-y-3 md:mt-6">
          <div
            v-for="step in visibleProcessingSteps"
            :key="step.key"
            class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 text-white transition duration-500"
            :class="step.originalIndex === processingStepIndex
              ? 'ring-1 ring-cyan-300/40'
              : fadingProcessingStepIndex === step.originalIndex
                ? 'processing-step-fading ring-1 ring-emerald-300/30'
                : ''"
          >
            <div
              class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-all duration-500"
              :class="isProcessingWithError && step.originalIndex === processingStepIndex
                ? 'bg-rose-300 text-slate-950'
                : fadingProcessingStepIndex === step.originalIndex
                ? 'bg-emerald-400 text-slate-950'
                : step.originalIndex === processingStepIndex
                  ? 'bg-cyan-300 text-slate-950'
                  : 'bg-white/10 text-slate-200'"
            >
              <XMarkIcon v-if="isProcessingWithError && step.originalIndex === processingStepIndex" class="h-4 w-4 stroke-[2.4]" />
              <CheckIcon v-else-if="fadingProcessingStepIndex === step.originalIndex" class="h-4 w-4 stroke-[2.4]" />
              <span v-else>{{ step.originalIndex + 1 }}</span>
            </div>
            <div>
              <p class="text-sm font-semibold">{{ step.title }}</p>
              <p class="mt-1 text-sm leading-6 text-slate-300">{{ step.desc }}</p>
            </div>
          </div>
        </TransitionGroup>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.processing-step-fading {
  opacity: 0.32;
  transform: translateY(-10px) scale(0.985);
}

.processing-step-leave-active {
  transition: opacity 0.45s ease, transform 0.45s ease, max-height 0.45s ease, margin 0.45s ease, padding 0.45s ease;
  overflow: hidden;
}

.processing-step-leave-to {
  opacity: 0;
  transform: translateY(-18px) scale(0.96);
  max-height: 0;
  margin-top: 0;
  margin-bottom: 0;
  padding-top: 0;
  padding-bottom: 0;
}

.processing-step-move {
  transition: transform 0.45s ease;
}
</style>
