<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
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

const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations générales -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.basic_information') || 'Informations générales' }}</h5>
        </div>

        <!-- Année Scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') || 'Année Scolaire' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="anneesScolaires"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger">
                    <strong>{{ form.errors.annee_scolaire_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Section -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }}</label>
                <SearchableSelect
                    v-model="form.section_id"
                    :options="sections"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.section_id" class="text-danger">
                    <strong>{{ form.errors.section_id }}</strong>
                </span>
            </div>
        </div>

        <!-- École -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole') || 'École' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.ecole_id"
                    :options="ecoles"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.ecole_id" class="text-danger">
                    <strong>{{ form.errors.ecole_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Campus (auto — déduit de l'école) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Date Dépense -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_depense') || 'Date de dépense' }}</label>
                <input type="date" v-model="form.date_depense" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_depense" class="text-danger">
                    <strong>{{ form.errors.date_depense }}</strong>
                </span>
            </div>
        </div>

        <!-- Nature de Dépense -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nature_depense') || 'Nature de dépense' }}</label>
                <input type="text" v-model="form.nature_depense" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.nature_depense" class="text-danger">
                    <strong>{{ form.errors.nature_depense }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 2: Détails de l'opération -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.operation_details') || 'Détails de l\'opération' }}</h5>
        </div>

        <!-- Tiers / Fournisseur -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.tiers_fournisseur') || 'Tiers / Fournisseur' }}</label>
                <input type="text" v-model="form.tiers_fournisseur" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.tiers_fournisseur" class="text-danger">
                    <strong>{{ form.errors.tiers_fournisseur }}</strong>
                </span>
            </div>
        </div>

        <!-- N° Identifiant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.numero_identifiant') || 'N° Identifiant' }}</label>
                <input type="text" v-model="form.numero_identifiant" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.numero_identifiant" class="text-danger">
                    <strong>{{ form.errors.numero_identifiant }}</strong>
                </span>
            </div>
        </div>

        <!-- Type de Pièce -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_piece') || 'Type de pièce' }}</label>
                <input type="text" v-model="form.type_piece" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.type_piece" class="text-danger">
                    <strong>{{ form.errors.type_piece }}</strong>
                </span>
            </div>
        </div>

        <!-- Référence Pièce -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.reference_piece') || 'Référence pièce' }}</label>
                <input type="text" v-model="form.reference_piece" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.reference_piece" class="text-danger">
                    <strong>{{ form.errors.reference_piece }}</strong>
                </span>
            </div>
        </div>

        <!-- Intitulé Opération -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.intitule_operation') || 'Intitulé opération' }}</label>
                <input type="text" v-model="form.intitule_operation" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.intitule_operation" class="text-danger">
                    <strong>{{ form.errors.intitule_operation }}</strong>
                </span>
            </div>
        </div>

        <!-- Mode de Paiement -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.mode_paiement') || 'Mode de paiement' }}</label>
                <input type="text" v-model="form.mode_paiement" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.mode_paiement" class="text-danger">
                    <strong>{{ form.errors.mode_paiement }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 3: Montants -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.amounts') || 'Montants' }}</h5>
        </div>

        <!-- Montant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant') || 'Montant' }}</label>
                <input type="number" v-model.number="form.montant" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.montant" class="text-danger">
                    <strong>{{ form.errors.montant }}</strong>
                </span>
            </div>
        </div>

        <!-- Montant Total Payé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant_total_paye') || 'Montant total payé' }}</label>
                <input type="number" v-model.number="form.montant_total_paye" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.montant_total_paye" class="text-danger">
                    <strong>{{ form.errors.montant_total_paye }}</strong>
                </span>
            </div>
        </div>

        <!-- Restant à Payer -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.restant_a_payer') || 'Restant à payer' }}</label>
                <input type="number" v-model.number="form.restant_a_payer" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.restant_a_payer" class="text-danger">
                    <strong>{{ form.errors.restant_a_payer }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 4: Paiements -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.payments') || 'Paiements' }}</h5>
        </div>

        <!-- Paiement 1 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_paiement') || 'Date paiement' }} 1</label>
                <input type="date" v-model="form.date_paiement_1" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_paiement_1" class="text-danger">
                    <strong>{{ form.errors.date_paiement_1}}</strong>
                </span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant_paiement') || 'Montant paiement' }} 1</label>
                <input type="number" v-model.number="form.montant_paiement_1" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.montant_paiement_1" class="text-danger">
                    <strong>{{ form.errors.montant_paiement_1}}</strong>
                </span>
            </div>
        </div>

        <!-- Paiement 2 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_paiement') || 'Date paiement' }} 2</label>
                <input type="date" v-model="form.date_paiement_2" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_paiement_2" class="text-danger">
                    <strong>{{ form.errors.date_paiement_2}}</strong>
                </span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant_paiement') || 'Montant paiement' }} 2</label>
                <input type="number" v-model.number="form.montant_paiement_2" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.montant_paiement_2" class="text-danger">
                    <strong>{{ form.errors.montant_paiement_2}}</strong>
                </span>
            </div>
        </div>

        <!-- Paiement 3 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_paiement') || 'Date paiement' }} 3</label>
                <input type="date" v-model="form.date_paiement_3" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_paiement_3" class="text-danger">
                    <strong>{{ form.errors.date_paiement_3}}</strong>
                </span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant_paiement') || 'Montant paiement' }} 3</label>
                <input type="number" v-model.number="form.montant_paiement_3" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.montant_paiement_3" class="text-danger">
                    <strong>{{ form.errors.montant_paiement_3}}</strong>
                </span>
            </div>
        </div>

        <!-- Paiement 4 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_paiement') || 'Date paiement' }} 4</label>
                <input type="date" v-model="form.date_paiement_4" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_paiement_4" class="text-danger">
                    <strong>{{ form.errors.date_paiement_4}}</strong>
                </span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant_paiement') || 'Montant paiement' }} 4</label>
                <input type="number" v-model.number="form.montant_paiement_4" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.montant_paiement_4" class="text-danger">
                    <strong>{{ form.errors.montant_paiement_4}}</strong>
                </span>
            </div>
        </div>

        <!-- Paiement 5 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_paiement') || 'Date paiement' }} 5</label>
                <input type="date" v-model="form.date_paiement_5" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_paiement_5" class="text-danger">
                    <strong>{{ form.errors.date_paiement_5}}</strong>
                </span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant_paiement') || 'Montant paiement' }} 5</label>
                <input type="number" v-model.number="form.montant_paiement_5" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.montant_paiement_5" class="text-danger">
                    <strong>{{ form.errors.montant_paiement_5}}</strong>
                </span>
            </div>
        </div>

        <!-- Paiement 6 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_paiement') || 'Date paiement' }} 6</label>
                <input type="date" v-model="form.date_paiement_6" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_paiement_6" class="text-danger">
                    <strong>{{ form.errors.date_paiement_6}}</strong>
                </span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant_paiement') || 'Montant paiement' }} 6</label>
                <input type="number" v-model.number="form.montant_paiement_6" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.montant_paiement_6" class="text-danger">
                    <strong>{{ form.errors.montant_paiement_6}}</strong>
                </span>
            </div>
        </div>

        <!-- Section 5: État -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.state') || 'État' }}</h5>
        </div>

        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'État' }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="etatOptions"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
