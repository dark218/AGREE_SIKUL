<script setup>
import { computed, watch } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    anneesScolaires: { type: Array, default: () => [] },
    niveauxEtudes: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    campuses: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '—';
};

const ecoleLabel = computed(() => autoLabel(props.ecoles, props.form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Auto-fill ecole_id from selected niveau
watch(() => props.form.niveau_id, (niveauId) => {
    if (!niveauId) return;
    const niv = props.niveauxEtudes?.find(n => n.id == niveauId);
    if (niv?.ecole_id) props.form.ecole_id = niv.ecole_id;
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations -->
        <div class="col-12">
            <h6 class="text-muted text-uppercase fw-bold mb-3">{{ t('common.basic_information') }}</h6>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') }}</label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="anneesScolaires"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger">
                    <strong>{{ form.errors.annee_scolaire_id }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.niveau_etude') }}</label>
                <SearchableSelect
                    v-model="form.niveau_id"
                    :options="niveauxEtudes"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.niveau_id" class="text-danger">
                    <strong>{{ form.errors.niveau_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Ecole (hidden when niveau auto-fills it) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole') }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Campus (hidden when niveau auto-fills it) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Section 2: Revenus -->
        <div class="col-12">
            <h6 class="text-muted text-uppercase fw-bold mb-3 mt-3">{{ t('common.autres_revenus') }}</h6>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.uniforme') }}</label>
                <input
                    type="number"
                    v-model="form.uniforme"
                    class="form-control"
                    :placeholder="t('fields.uniforme')"
                    step="0.01"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.uniforme" class="text-danger">
                    <strong>{{ form.errors.uniforme }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.tenue_mercredi') }}</label>
                <input
                    type="number"
                    v-model="form.tenue_mercredi"
                    class="form-control"
                    :placeholder="t('fields.tenue_mercredi')"
                    step="0.01"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.tenue_mercredi" class="text-danger">
                    <strong>{{ form.errors.tenue_mercredi }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.tenue_sport') }}</label>
                <input
                    type="number"
                    v-model="form.tenue_sport"
                    class="form-control"
                    :placeholder="t('fields.tenue_sport')"
                    step="0.01"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.tenue_sport" class="text-danger">
                    <strong>{{ form.errors.tenue_sport }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.autre1') }}</label>
                <input
                    type="number"
                    v-model="form.autre1"
                    class="form-control"
                    :placeholder="t('fields.autre1')"
                    step="0.01"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.autre1" class="text-danger">
                    <strong>{{ form.errors.autre1 }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.autre2') }}</label>
                <input
                    type="number"
                    v-model="form.autre2"
                    class="form-control"
                    :placeholder="t('fields.autre2')"
                    step="0.01"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.autre2" class="text-danger">
                    <strong>{{ form.errors.autre2 }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.autre3') }}</label>
                <input
                    type="number"
                    v-model="form.autre3"
                    class="form-control"
                    :placeholder="t('fields.autre3')"
                    step="0.01"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.autre3" class="text-danger">
                    <strong>{{ form.errors.autre3 }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.autre4') }}</label>
                <input
                    type="number"
                    v-model="form.autre4"
                    class="form-control"
                    :placeholder="t('fields.autre4')"
                    step="0.01"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.autre4" class="text-danger">
                    <strong>{{ form.errors.autre4 }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.autre5') }}</label>
                <input
                    type="number"
                    v-model="form.autre5"
                    class="form-control"
                    :placeholder="t('fields.autre5')"
                    step="0.01"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.autre5" class="text-danger">
                    <strong>{{ form.errors.autre5 }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.autre6') }}</label>
                <input
                    type="number"
                    v-model="form.autre6"
                    class="form-control"
                    :placeholder="t('fields.autre6')"
                    step="0.01"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.autre6" class="text-danger">
                    <strong>{{ form.errors.autre6 }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 3: État -->
        <div class="col-12">
            <h6 class="text-muted text-uppercase fw-bold mb-3 mt-3">{{ t('common.status') }}</h6>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
