<script setup>
import { computed } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    anneesScolaires: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    campuses: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';
const ecoleSelected = computed(() => !!props.form.ecole_id);

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '\u2014';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '\u2014';
};

const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations Scolaires -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.schooling') || 'Scolarité' }}</h5>
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
                <label>{{ t('fields.ecole') }}</label>
                <SearchableSelect
                    v-model="form.ecole_id"
                    :options="ecoles"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ecole_id" class="text-danger">
                    <strong>{{ form.errors.ecole_id }}</strong>
                </span>
            </div>
        </div>

        <!-- HierarchyContextBar: shows auto-derived hierarchy when ecole is selected -->
        <HierarchyContextBar
            :form="form"
            :ecoles="ecoles"
            :campuses="campuses"
        />

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Section 2: Informations du Transport -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('fields.transport') || 'Transport' }}</h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.zone') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.zone"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.zone }"
                    :disabled="isReadOnly"
                    required
                    placeholder="Zone de transport"
                />
                <span v-if="form.errors?.zone" class="text-danger">
                    <strong>{{ form.errors.zone }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ligne') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.ligne"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.ligne }"
                    :disabled="isReadOnly"
                    required
                    placeholder="Ligne de transport"
                />
                <span v-if="form.errors?.ligne" class="text-danger">
                    <strong>{{ form.errors.ligne }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.point_depart') }}</label>
                <input
                    v-model="form.point_depart"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.point_depart }"
                    :disabled="isReadOnly"
                    placeholder="Point de départ"
                />
                <span v-if="form.errors?.point_depart" class="text-danger">
                    <strong>{{ form.errors.point_depart }}</strong>
                </span>
            </div>
        </div>

        <!-- Points d'arrêt -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('fields.waypoints') || 'Points d\'arrêt' }}</h5>
        </div>

        <div v-for="i in 10" :key="'point-' + i" class="col-sm-6">
            <div class="mb-3">
                <label>{{ t(`fields.point_${i}`) }}</label>
                <input
                    v-model="form[`point_${i}`]"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.[`point_${i}`] }"
                    :disabled="isReadOnly"
                    :placeholder="`Point d'arrêt ${i}`"
                />
                <span v-if="form.errors?.[`point_${i}`]" class="text-danger">
                    <strong>{{ form.errors[`point_${i}`] }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 3: Tarifs -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.pricing') || 'Tarification' }}</h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.tarif_mensuel') }}</label>
                <input
                    v-model.number="form.tarif_mensuel"
                    type="number"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.tarif_mensuel }"
                    :disabled="isReadOnly"
                    placeholder="0"
                />
                <span v-if="form.errors?.tarif_mensuel" class="text-danger">
                    <strong>{{ form.errors.tarif_mensuel }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.tarif_trimestriel') }}</label>
                <input
                    v-model.number="form.tarif_trimestriel"
                    type="number"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.tarif_trimestriel }"
                    :disabled="isReadOnly"
                    placeholder="0"
                />
                <span v-if="form.errors?.tarif_trimestriel" class="text-danger">
                    <strong>{{ form.errors.tarif_trimestriel }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.tarif_semestriel') }}</label>
                <input
                    v-model.number="form.tarif_semestriel"
                    type="number"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.tarif_semestriel }"
                    :disabled="isReadOnly"
                    placeholder="0"
                />
                <span v-if="form.errors?.tarif_semestriel" class="text-danger">
                    <strong>{{ form.errors.tarif_semestriel }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.tarif_annuel') }}</label>
                <input
                    v-model.number="form.tarif_annuel"
                    type="number"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.tarif_annuel }"
                    :disabled="isReadOnly"
                    placeholder="0"
                />
                <span v-if="form.errors?.tarif_annuel" class="text-danger">
                    <strong>{{ form.errors.tarif_annuel }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 4: Dates -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.dates') || 'Dates' }}</h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_debut') }}</label>
                <input
                    v-model="form.date_debut"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.date_debut }"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_debut" class="text-danger">
                    <strong>{{ form.errors.date_debut }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_fin') }}</label>
                <input
                    v-model="form.date_fin"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.date_fin }"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_fin" class="text-danger">
                    <strong>{{ form.errors.date_fin }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 5: État -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.status') || 'État' }}</h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.etat') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>

    </div>
</template>

<style scoped>
.custom-input {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.section-title {
    font-weight: 700;
    color: #2c3e50;
    font-size: 1.15rem;
    border-bottom: 3px solid #007bff;
    padding-bottom: 0.75rem;
    margin-bottom: 1.5rem !important;
    margin-top: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 24px;
    background: linear-gradient(180deg, #007bff 0%, #0056b3 100%);
    border-radius: 2px;
}

.section-title.mt-0 {
    margin-top: 0 !important;
}

label {
    font-weight: 600;
    color: #495057;
    font-size: 0.95rem;
    margin-bottom: 0.6rem !important;
    display: block;
}

label span {
    color: #dc3545;
    font-weight: 700;
}

.form-control,
.form-select {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.65rem 0.875rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background-color: #fff;
}

.form-control:focus,
.form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
}

.form-control:disabled,
.form-control[disabled] {
    background-color: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
    border-color: #dee2e6;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.form-control.is-invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15);
}

.text-danger {
    color: #dc3545;
    font-size: 0.85rem;
    margin-top: 0.35rem;
    display: block;
    font-weight: 500;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

.form-control:hover:not(:disabled),
.form-select:hover:not(:disabled) {
    border-color: #b3d9ff;
}
</style>
