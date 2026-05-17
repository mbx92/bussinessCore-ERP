<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, nextTick } from 'vue';
import {ArrowLeftIcon,
  RocketLaunchIcon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  CurrencyDollarIcon,
  TruckIcon,
  InboxArrowDownIcon,
  ArrowUpTrayIcon,
  FlagIcon,
  ChatBubbleBottomCenterTextIcon,
  BellAlertIcon,
  CalendarDaysIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  XMarkIcon,
  MapPinIcon,} from '@heroicons/vue/24/outline';
import { formatDate } from '@/composables/useDateFormat';

const WEEKDAYS_ID = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

const props = defineProps({
  events: Array,
  month: Number,
  year: Number,
});

const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];
const dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const currentMonth = ref(props.month);
const currentYear = ref(props.year);

const navigate = (offset) => {
  let m = currentMonth.value + offset;
  let y = currentYear.value;
  if (m < 1) { m = 12; y--; }
  if (m > 12) { m = 1; y++; }
  currentMonth.value = m;
  currentYear.value = y;
  router.get(route('erp.calendar'), { month: m, year: y }, { preserveState: true, replace: true });
};

const goToday = () => {
  const now = new Date();
  currentMonth.value = now.getMonth() + 1;
  currentYear.value = now.getFullYear();
  router.get(route('erp.calendar'), { month: currentMonth.value, year: currentYear.value }, { preserveState: true, replace: true });
};

const today = new Date();
const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

const calendarDays = computed(() => {
  const firstDay = new Date(currentYear.value, currentMonth.value - 1, 1);
  const lastDay = new Date(currentYear.value, currentMonth.value, 0);
  const daysInMonth = lastDay.getDate();

  let startDow = firstDay.getDay() - 1;
  if (startDow < 0) startDow = 6;

  const days = [];

  const prevMonth = new Date(currentYear.value, currentMonth.value - 1, 0);
  for (let i = startDow - 1; i >= 0; i--) {
    days.push({ day: prevMonth.getDate() - i, current: false, date: null });
  }

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    days.push({ day: d, current: true, date: dateStr });
  }

  const remaining = 7 - (days.length % 7);
  if (remaining < 7) {
    for (let i = 1; i <= remaining; i++) {
      days.push({ day: i, current: false, date: null });
    }
  }

  return days;
});

const eventsByDate = computed(() => {
  const map = {};
  (props.events || []).forEach((ev) => {
    if (!map[ev.date]) map[ev.date] = [];
    map[ev.date].push(ev);
  });
  return map;
});

const selectedDate = ref(null);
const selectedEvents = computed(() => {
  if (!selectedDate.value) return [];
  return eventsByDate.value[selectedDate.value] || [];
});

const openDayModal = (day) => {
  if (!day.current || !day.date) return;
  selectedDate.value = day.date;
  nextTick(() => document.getElementById('modal-day-detail')?.showModal());
};

const closeDayModal = () => {
  document.getElementById('modal-day-detail')?.close();
  selectedDate.value = null;
};

const openDateFromSidebar = (dateStr) => {
  selectedDate.value = dateStr;
  nextTick(() => document.getElementById('modal-day-detail')?.showModal());
};

const stripColor = {
  primary: 'bg-primary/15 text-primary border-primary/20',
  success: 'bg-success/15 text-success border-success/20',
  warning: 'bg-warning/15 text-warning border-warning/20',
  accent: 'bg-accent/15 text-accent border-accent/20',
  info: 'bg-info/15 text-info border-info/20',
  secondary: 'bg-secondary/15 text-secondary border-secondary/20',
  error: 'bg-error/15 text-error border-error/20',
};

const dotColor = {
  primary: 'bg-primary',
  success: 'bg-success',
  warning: 'bg-warning',
  accent: 'bg-accent',
  info: 'bg-info',
  secondary: 'bg-secondary',
  error: 'bg-error',
};

const badgeColorMap = {
  primary: 'badge-primary',
  success: 'badge-success',
  warning: 'badge-warning',
  accent: 'badge-accent',
  info: 'badge-info',
  secondary: 'badge-secondary',
  error: 'badge-error',
};

const accentBorder = {
  primary: 'border-l-primary',
  success: 'border-l-success',
  warning: 'border-l-warning',
  accent: 'border-l-accent',
  info: 'border-l-info',
  secondary: 'border-l-secondary',
  error: 'border-l-error',
};

const typeIconMap = {
  project_start: RocketLaunchIcon,
  project_end: CheckBadgeIcon,
  task_due: ClipboardDocumentCheckIcon,
  payment: CurrencyDollarIcon,
  po_eta: TruckIcon,
  receivable_due: InboxArrowDownIcon,
  payable_due: ArrowUpTrayIcon,
  pipeline_close: FlagIcon,
  activity: ChatBubbleBottomCenterTextIcon,
  follow_up: BellAlertIcon,
};

const iconColor = {
  primary: 'text-primary',
  success: 'text-success',
  warning: 'text-warning',
  accent: 'text-accent',
  info: 'text-info',
  secondary: 'text-secondary',
  error: 'text-error',
};

const iconBg = {
  primary: 'bg-primary/10',
  success: 'bg-success/10',
  warning: 'bg-warning/10',
  accent: 'bg-accent/10',
  info: 'bg-info/10',
  secondary: 'bg-secondary/10',
  error: 'bg-error/10',
};

const formatSelectedDate = computed(() => {
  if (!selectedDate.value) return '';
  const parts = selectedDate.value.slice(0, 10).split('-').map(Number);
  if (parts.length !== 3) return formatDate(selectedDate.value);
  const [year, month, day] = parts;
  const weekday = WEEKDAYS_ID[new Date(year, month - 1, day).getDay()];
  return `${weekday}, ${formatDate(selectedDate.value)}`;
});

const totalEvents = computed(() => (props.events || []).length);
const daysWithEvents = computed(() => Object.keys(eventsByDate.value).length);

const upcomingEvents = computed(() => {
  return (props.events || [])
    .filter((ev) => ev.date >= todayStr)
    .sort((a, b) => a.date.localeCompare(b.date))
    .slice(0, 5);
});

const legendItems = [
  { color: 'bg-primary', icon: RocketLaunchIcon, label: 'Project Mulai' },
  { color: 'bg-success', icon: CheckBadgeIcon, label: 'Project Selesai' },
  { color: 'bg-warning', icon: ClipboardDocumentCheckIcon, label: 'Deadline / ETA PO' },
  { color: 'bg-accent', icon: CurrencyDollarIcon, label: 'Pembayaran / Piutang' },
  { color: 'bg-info', icon: FlagIcon, label: 'Pipeline CRM' },
  { color: 'bg-secondary', icon: ChatBubbleBottomCenterTextIcon, label: 'Aktivitas CRM' },
  { color: 'bg-error', icon: BellAlertIcon, label: 'Follow-up / Hutang' },
];

const formatEventDate = (dateStr) => formatDate(dateStr);
</script>

<template>
  <Head title="ERP — Calendar" />
  <AppLayout>
    <div class="space-y-5">
      <!-- Header -->
      <div class="ocn-panel">
        <div class="ocn-panel__head">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary/70">Calendar Workspace</p>
              <h1 class="ocn-panel__title mt-1">Calendar</h1>
              <p class="ocn-panel__desc mt-1">Jadwal event dari seluruh modul ERP: project, pembayaran termin, purchase order, pipeline CRM, dan follow-up.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
              <Link class="btn btn-ghost btn-sm shrink-0 gap-1.5" :href="route('dashboard')">
              <ArrowLeftIcon class="h-4 w-4" />
              Back
            </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats mini cards -->
      <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-2xl font-bold tabular-nums text-primary">{{ totalEvents }}</p>
          <p class="mt-0.5 text-xs text-base-content/55 font-medium">Total Event</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-2xl font-bold tabular-nums text-info">{{ daysWithEvents }}</p>
          <p class="mt-0.5 text-xs text-base-content/55 font-medium">Hari dgn Event</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-2xl font-bold tabular-nums text-warning">{{ upcomingEvents.length }}</p>
          <p class="mt-0.5 text-xs text-base-content/55 font-medium">Mendatang</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-2xl font-bold tabular-nums text-success">{{ monthNames[currentMonth - 1] }}</p>
          <p class="mt-0.5 text-xs text-base-content/55 font-medium">{{ currentYear }}</p>
        </div>
      </div>

      <!-- Main: Calendar + Sidebar -->
      <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <!-- Calendar Grid -->
        <div class="space-y-0">
          <!-- Navigation bar -->
          <div class="flex items-center justify-between rounded-t-2xl border border-b-0 border-slate-200 bg-white px-5 py-3.5">
            <div class="flex items-center gap-1">
              <button class="btn btn-ghost btn-sm btn-square" @click="navigate(-1)" title="Bulan sebelumnya">
                <ChevronLeftIcon class="h-4 w-4 stroke-2" />
              </button>
              <h2 class="min-w-[11rem] text-center text-lg font-bold tracking-tight">
                {{ monthNames[currentMonth - 1] }} {{ currentYear }}
              </h2>
              <button class="btn btn-ghost btn-sm btn-square" @click="navigate(1)" title="Bulan berikutnya">
                <ChevronRightIcon class="h-4 w-4 stroke-2" />
              </button>
            </div>
            <button class="btn btn-sm btn-primary" @click="goToday">
              <CalendarDaysIcon class="h-4 w-4" />
              Hari ini
            </button>
          </div>

          <!-- Day headers -->
          <div class="grid grid-cols-7 border-x border-slate-200 bg-base-200/60">
            <div
              v-for="(name, i) in dayNames"
              :key="name"
              class="py-2.5 text-center text-[11px] font-bold uppercase tracking-widest"
              :class="i >= 5 ? 'text-error/50' : 'text-base-content/45'"
            >
              {{ name }}
            </div>
          </div>

          <!-- Day cells -->
          <div class="grid grid-cols-7 overflow-hidden rounded-b-2xl border border-t-0 border-slate-200 bg-white">
            <div
              v-for="(day, idx) in calendarDays"
              :key="idx"
              class="group relative min-h-[6.5rem] border-t border-slate-100 p-1.5 transition-colors duration-100"
              :class="[
                idx % 7 !== 0 ? 'border-l border-slate-100' : '',
                !day.current
                  ? 'bg-base-200/30'
                  : day.date === todayStr
                    ? 'bg-primary/[0.06]'
                    : 'cursor-pointer hover:bg-base-200/50',
                day.current && day.date !== todayStr ? 'cursor-pointer' : '',
              ]"
              @click="openDayModal(day)"
            >
              <!-- Day number -->
              <div class="flex items-center justify-between px-0.5">
                <span
                  class="inline-flex h-7 w-7 items-center justify-center rounded-full text-[13px] font-semibold"
                  :class="[
                    day.date === todayStr
                      ? 'bg-primary text-primary-content shadow-sm shadow-primary/30'
                      : !day.current
                        ? 'text-base-content/25'
                        : 'text-base-content/70 group-hover:text-base-content',
                  ]"
                >
                  {{ day.day }}
                </span>
                <span
                  v-if="day.date && eventsByDate[day.date]?.length"
                  class="flex h-5 min-w-5 items-center justify-center rounded-full bg-primary/10 px-1 text-[10px] font-bold tabular-nums text-primary"
                >
                  {{ eventsByDate[day.date].length }}
                </span>
              </div>

              <!-- Event strips -->
              <div v-if="day.date && eventsByDate[day.date]" class="mt-1 flex flex-col gap-[3px] px-0.5">
                <div
                  v-for="(ev, eidx) in eventsByDate[day.date].slice(0, 3)"
                  :key="eidx"
                  class="flex items-center gap-1 rounded-md border px-1.5 py-[2px]"
                  :class="stripColor[ev.color] || 'bg-base-200 text-base-content/60 border-base-300'"
                >
                  <span class="h-[5px] w-[5px] shrink-0 rounded-full" :class="dotColor[ev.color] || 'bg-base-content/30'" />
                  <span class="truncate text-[10px] font-semibold leading-tight">{{ ev.title }}</span>
                </div>
                <span
                  v-if="eventsByDate[day.date].length > 3"
                  class="px-1.5 text-[10px] font-semibold text-primary/60"
                >
                  +{{ eventsByDate[day.date].length - 3 }} lainnya
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-4">
          <!-- Upcoming Events -->
          <div class="ocn-panel">
            <div class="ocn-panel__head">
              <h2 class="ocn-panel__title">Mendatang</h2>
              <p class="ocn-panel__desc">Event terdekat bulan ini</p>
            </div>
            <div class="divide-y divide-base-200">
              <div
                v-for="(ev, idx) in upcomingEvents"
                :key="idx"
                class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-base-200/50 cursor-pointer"
                @click="openDateFromSidebar(ev.date)"
              >
                <div class="mt-0.5 flex flex-col items-center gap-1">
                  <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="iconBg[ev.color] || 'bg-base-200'">
                    <component :is="typeIconMap[ev.type] || MapPinIcon" class="h-4 w-4" :class="iconColor[ev.color] || 'text-base-content/50'" />
                  </div>
                  <span class="text-[10px] font-bold text-base-content/40 tabular-nums">{{ formatEventDate(ev.date) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="truncate text-sm font-semibold leading-tight">{{ ev.title }}</p>
                  <p class="mt-0.5 text-[11px] text-base-content/50 font-medium">{{ ev.label }}</p>
                  <p v-if="ev.subtitle" class="truncate text-[11px] text-base-content/40">{{ ev.subtitle }}</p>
                </div>
              </div>
              <div v-if="!upcomingEvents.length" class="px-4 py-6 text-center text-sm text-base-content/40">
                Tidak ada event mendatang.
              </div>
            </div>
          </div>

          <!-- Legend -->
          <div class="ocn-panel">
            <div class="ocn-panel__head">
              <h2 class="ocn-panel__title">Legenda</h2>
            </div>
            <div class="grid grid-cols-1 gap-0 divide-y divide-base-200">
              <div
                v-for="item in legendItems"
                :key="item.label"
                class="flex items-center gap-2.5 px-4 py-2.5"
              >
                <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="item.color" />
                <component :is="item.icon" class="h-3.5 w-3.5 shrink-0 text-base-content/40" />
                <span class="text-xs font-medium text-base-content/70">{{ item.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Day Detail -->
    <dialog id="modal-day-detail" class="modal modal-bottom sm:modal-middle">
      <div class="modal-box max-w-2xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-primary/70">Detail Tanggal</p>
            <h3 class="mt-1 text-lg font-bold">{{ formatSelectedDate }}</h3>
          </div>
          <button class="btn btn-ghost btn-sm btn-square" @click="closeDayModal">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <div class="mt-4">
          <p class="text-sm text-base-content/60">{{ selectedEvents.length }} event pada tanggal ini</p>
        </div>

        <div v-if="!selectedEvents.length" class="py-10 text-center text-sm text-base-content/40">
          Tidak ada event pada tanggal ini.
        </div>

        <div v-else class="mt-4 space-y-3">
          <div
            v-for="(ev, idx) in selectedEvents"
            :key="idx"
            class="flex gap-3 rounded-xl border border-base-200 border-l-[3px] p-4"
            :class="accentBorder[ev.color] || 'border-l-base-300'"
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
              :class="iconBg[ev.color] || 'bg-base-200'"
            >
              <component
                :is="typeIconMap[ev.type] || MapPinIcon"
                class="h-5 w-5"
                :class="iconColor[ev.color] || 'text-base-content/50'"
              />
            </div>
            <div class="flex-1 min-w-0">
              <span class="badge badge-sm" :class="badgeColorMap[ev.color] || 'badge-ghost'">{{ ev.label }}</span>
              <p class="mt-1.5 text-sm font-semibold leading-snug">{{ ev.title }}</p>
              <p v-if="ev.subtitle" class="mt-0.5 text-xs text-base-content/55">{{ ev.subtitle }}</p>
              <p v-if="ev.meta" class="mt-1 text-[11px] text-base-content/45 font-medium">{{ ev.meta }}</p>
            </div>
          </div>
        </div>

        <div class="modal-action">
          <button class="btn btn-ghost" @click="closeDayModal">Tutup</button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
  </AppLayout>
</template>
