<template>
  <div v-if="items.length > 0" class="col-12 mb-3">
    <div class="hcb">
      <div class="hcb-label">
        <i class="bx bx-sitemap"></i> Contexte hiérarchique
      </div>
      <div class="hcb-items">
        <span v-for="item in items" :key="item.label" class="hcb-chip">
          <i :class="item.icon"></i>
          {{ item.label }}: <strong>{{ item.value }}</strong>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  form:     { type: Object, required: true },
  ecoles:   { type: Array, default: () => [] },
  campuses: { type: Array, default: () => [] },
  sections: { type: Array, default: () => [] },
  cycles:   { type: Array, default: () => [] },
  niveaux:  { type: Array, default: () => [] },
});

const find = (list, id) => {
  if (!id || !list?.length) return '';
  const f = list.find(i => String(i.id) === String(id));
  return f?.nom || f?.libelle || f?.label || f?.name || '';
};

const items = computed(() => {
  const out = [];
  const e = find(props.ecoles, props.form.ecole_id);
  const c = find(props.campuses, props.form.campus_id);
  const s = find(props.sections, props.form.section_id);
  const y = find(props.cycles, props.form.cycle_id);
  const n = find(props.niveaux, props.form.niveau_id);
  if (e) out.push({ icon: 'bx bx-building', label: 'École', value: e });
  if (c) out.push({ icon: 'bx bx-map',      label: 'Campus', value: c });
  if (s) out.push({ icon: 'bx bx-bookmark',  label: 'Section', value: s });
  if (y) out.push({ icon: 'bx bx-layer',     label: 'Cycle', value: y });
  if (n) out.push({ icon: 'bx bx-bar-chart', label: 'Niveau', value: n });
  return out;
});
</script>

<style scoped>
.hcb {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 10px;
  padding: 10px 14px;
}
.hcb-label {
  font-size: 11px;
  font-weight: 700;
  color: #0369a1;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.hcb-items {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.hcb-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: white;
  border: 1px solid #e0f2fe;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 12px;
  color: #334155;
}
.hcb-chip i {
  color: #0ea5e9;
  font-size: 13px;
}
.hcb-chip strong {
  color: #0c4a6e;
}
</style>
