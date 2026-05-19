<script setup>
import ProjectForm from './Partials/ProjectForm.vue';

defineProps({
  project: Object,
  users: Array,
  statusOptions: Array,
});
</script>

<template>
  <ProjectForm
    title="Tambah Project R&D"
    submit-label="Simpan Project"
    :submit-route="route('rnd.projects.store')"
    method="post"
    :project="project"
    :users="users"
    :status-options="statusOptions"
  />
</template>
