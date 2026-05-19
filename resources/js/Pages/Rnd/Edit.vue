<script setup>
import ProjectForm from './Partials/ProjectForm.vue';

const props = defineProps({
  project: Object,
  users: Array,
  statusOptions: Array,
});
</script>

<template>
  <ProjectForm
    title="Edit Project R&D"
    submit-label="Simpan Perubahan"
    :submit-route="route('rnd.projects.update', props.project.id)"
    method="put"
    :project="project"
    :users="users"
    :status-options="statusOptions"
  />
</template>
