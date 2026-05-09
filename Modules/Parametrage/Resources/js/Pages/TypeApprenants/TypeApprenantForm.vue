<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import { usePage } from '@inertiajs/vue3';
const { t } = useI18n();
const page = usePage();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});
const isReadOnly = computed(() => props.mode === 'show');
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const niveaux = computed(() => page.props.niveaux || []);
const sections = computed(() => page.props.sections || []);
const cycles = computed(() => page.props.cycles || []);
const pays = computed(() => page.props.pays || []);
const ecoles = computed(() => page.props.ecoles || []);
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.code') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.code"
                    class="form-control"
                    :placeholder="t('fields.code')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Libellé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.label') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.libelle"
                    class="form-control"
                    :placeholder="t('fields.label')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Ecole -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('common.ecole') || 'École' }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <SearchableSelect
                    v-if="!isReadOnly"
                    v-model="form.ecole_id"
                    :options="ecoles"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('common.ecole') || '-- Sélectionner --'"
                />
                <input
                    v-else
                    type="text"
                    :value="ecoles.find(e => e.id === form.ecole_id)?.nom || '-'"
                    class="form-control"
                    disabled
                />
                <span v-if="form.errors?.ecole_id" class="text-danger">
                    <strong>{{ form.errors.ecole_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Niveau -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.niveau') || 'Niveau' }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <SearchableSelect
                    v-if="!isReadOnly"
                    v-model="form.niveau_id"
                    :options="niveaux"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('fields.niveau') || '-- Sélectionner --'"
                />
                <input
                    v-else
                    type="text"
                    :value="niveaux.find(n => n.id === form.niveau_id)?.libelle || '-'"
                    class="form-control"
                    disabled
                />
                <span v-if="form.errors?.niveau_id" class="text-danger">
                    <strong>{{ form.errors.niveau_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Section -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.section') || 'Section' }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <SearchableSelect
                    v-if="!isReadOnly"
                    v-model="form.section_id"
                    :options="sections"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('fields.section') || '-- Sélectionner --'"
                />
                <input
                    v-else
                    type="text"
                    :value="sections.find(s => s.id === form.section_id)?.libelle || '-'"
                    class="form-control"
                    disabled
                />
                <span v-if="form.errors?.section_id" class="text-danger">
                    <strong>{{ form.errors.section_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Cycle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.cycle') || 'Cycle' }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <SearchableSelect
                    v-if="!isReadOnly"
                    v-model="form.cycle_id"
                    :options="cycles"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('fields.cycle') || '-- Sélectionner --'"
                />
                <input
                    v-else
                    type="text"
                    :value="cycles.find(c => c.id === form.cycle_id)?.libelle || '-'"
                    class="form-control"
                    disabled
                />
                <span v-if="form.errors?.cycle_id" class="text-danger">
                    <strong>{{ form.errors.cycle_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Pays -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.country') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <SearchableSelect
                    v-if="!isReadOnly"
                    v-model="form.pays_id"
                    :options="pays"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('fields.country') || '-- Sélectionner --'"
                />
                <input
                    v-else
                    type="text"
                    :value="pays.find(p => p.id === form.pays_id)?.libelle || '-'"
                    class="form-control"
                    disabled
                />
                <span v-if="form.errors?.pays_id" class="text-danger">
                    <strong>{{ form.errors.pays_id }}</strong>
                </span>
            </div>
        </div>
        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.status') }}
                </label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
