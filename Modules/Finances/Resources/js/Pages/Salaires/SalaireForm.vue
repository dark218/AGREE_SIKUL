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
        <!-- Section 1: Scolarité -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.scholarity') || 'Scolarité' }}</h5>
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

        <!-- Mois de paie -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.mois_paie') || 'Mois de paie' }}</label>
                <input type="text" v-model="form.mois_paie" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.mois_paie" class="text-danger">
                    <strong>{{ form.errors.mois_paie }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 2: Identification -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.identification') || 'Identification' }}</h5>
        </div>

        <!-- Nom -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom') || 'Nom' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.nom" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.nom" class="text-danger">
                    <strong>{{ form.errors.nom }}</strong>
                </span>
            </div>
        </div>

        <!-- Institution -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.institution') || 'Institution' }}</label>
                <input type="text" v-model="form.institution" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.institution" class="text-danger">
                    <strong>{{ form.errors.institution }}</strong>
                </span>
            </div>
        </div>

        <!-- Intitulé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.intitule') || 'Intitulé' }}</label>
                <input type="text" v-model="form.intitule" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.intitule" class="text-danger">
                    <strong>{{ form.errors.intitule }}</strong>
                </span>
            </div>
        </div>

        <!-- Noms -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.noms') || 'Noms' }}</label>
                <input type="text" v-model="form.noms" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.noms" class="text-danger">
                    <strong>{{ form.errors.noms }}</strong>
                </span>
            </div>
        </div>

        <!-- Prénoms -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.prenoms') || 'Prénoms' }}</label>
                <input type="text" v-model="form.prenoms" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.prenoms" class="text-danger">
                    <strong>{{ form.errors.prenoms }}</strong>
                </span>
            </div>
        </div>

        <!-- Nom à restituer -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom_restituer') || 'Nom à restituer' }}</label>
                <input type="text" v-model="form.nom_restituer" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.nom_restituer" class="text-danger">
                    <strong>{{ form.errors.nom_restituer }}</strong>
                </span>
            </div>
        </div>

        <!-- Matricule Interne -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.matricule_interne') || 'Matricule Interne' }}</label>
                <input type="text" v-model="form.matricule_interne" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.matricule_interne" class="text-danger">
                    <strong>{{ form.errors.matricule_interne }}</strong>
                </span>
            </div>
        </div>

        <!-- Matricule CNPS -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.matricule_cnps') || 'Matricule CNPS' }}</label>
                <input type="text" v-model="form.matricule_cnps" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.matricule_cnps" class="text-danger">
                    <strong>{{ form.errors.matricule_cnps }}</strong>
                </span>
            </div>
        </div>

        <!-- Numéro Identifiant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.numero_identifiant') || 'Numéro Identifiant' }}</label>
                <input type="text" v-model="form.numero_identifiant" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.numero_identifiant" class="text-danger">
                    <strong>{{ form.errors.numero_identifiant }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 3: Salaire -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.salary') || 'Salaire' }}</h5>
        </div>

        <!-- Salaire Base -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.salaire_base') || 'Salaire Base' }}</label>
                <input type="number" v-model.number="form.salaire_base" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.salaire_base" class="text-danger">
                    <strong>{{ form.errors.salaire_base }}</strong>
                </span>
            </div>
        </div>

        <!-- Primes -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.primes') || 'Primes' }}</label>
                <input type="number" v-model.number="form.primes" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.primes" class="text-danger">
                    <strong>{{ form.errors.primes }}</strong>
                </span>
            </div>
        </div>

        <!-- Indemnités -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.indemnites') || 'Indemnités' }}</label>
                <input type="number" v-model.number="form.indemnites" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.indemnites" class="text-danger">
                    <strong>{{ form.errors.indemnites }}</strong>
                </span>
            </div>
        </div>

        <!-- Salaire Brut -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.salaire_brut') || 'Salaire Brut' }}</label>
                <input type="number" v-model.number="form.salaire_brut" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.salaire_brut" class="text-danger">
                    <strong>{{ form.errors.salaire_brut }}</strong>
                </span>
            </div>
        </div>

        <!-- Retenues Fiscales -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.retenues_fiscales') || 'Retenues Fiscales' }}</label>
                <input type="number" v-model.number="form.retenues_fiscales" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.retenues_fiscales" class="text-danger">
                    <strong>{{ form.errors.retenues_fiscales }}</strong>
                </span>
            </div>
        </div>

        <!-- Retenues Sociales -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.retenues_sociales') || 'Retenues Sociales' }}</label>
                <input type="number" v-model.number="form.retenues_sociales" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.retenues_sociales" class="text-danger">
                    <strong>{{ form.errors.retenues_sociales }}</strong>
                </span>
            </div>
        </div>

        <!-- Autres Retenues -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.autres_retenues') || 'Autres Retenues' }}</label>
                <input type="number" v-model.number="form.autres_retenues" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.autres_retenues" class="text-danger">
                    <strong>{{ form.errors.autres_retenues }}</strong>
                </span>
            </div>
        </div>

        <!-- Saisies Oppositions -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.saisies_oppositions') || 'Saisies/Oppositions' }}</label>
                <input type="number" v-model.number="form.saisies_oppositions" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.saisies_oppositions" class="text-danger">
                    <strong>{{ form.errors.saisies_oppositions }}</strong>
                </span>
            </div>
        </div>

        <!-- Salaire Net -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.salaire_net') || 'Salaire Net' }}</label>
                <input type="number" v-model.number="form.salaire_net" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.salaire_net" class="text-danger">
                    <strong>{{ form.errors.salaire_net }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 4: Paiements -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.payments') || 'Paiements' }}</h5>
        </div>

        <!-- Paiement Intégral -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.paiement_integral') || 'Paiement Intégral' }}</label>
                <input type="number" v-model.number="form.paiement_integral" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.paiement_integral" class="text-danger">
                    <strong>{{ form.errors.paiement_integral }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Paiement Intégral -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_paiement_integral') || 'Date Paiement Intégral' }}</label>
                <input type="date" v-model="form.date_paiement_integral" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_paiement_integral" class="text-danger">
                    <strong>{{ form.errors.date_paiement_integral }}</strong>
                </span>
            </div>
        </div>

        <!-- Avance 1 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.avance1') || 'Avance 1' }}</label>
                <input type="number" v-model.number="form.avance1" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.avance1" class="text-danger">
                    <strong>{{ form.errors.avance1}}</strong>
                </span>
            </div>
        </div>

        <!-- Date Avance 1 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_avance1') || 'Date Avance 1' }}</label>
                <input type="date" v-model="form.date_avance1" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_avance1" class="text-danger">
                    <strong>{{ form.errors.date_avance1}}</strong>
                </span>
            </div>
        </div>

        <!-- Avance 2 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.avance2') || 'Avance 2' }}</label>
                <input type="number" v-model.number="form.avance2" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.avance2" class="text-danger">
                    <strong>{{ form.errors.avance2}}</strong>
                </span>
            </div>
        </div>

        <!-- Date Avance 2 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_avance2') || 'Date Avance 2' }}</label>
                <input type="date" v-model="form.date_avance2" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_avance2" class="text-danger">
                    <strong>{{ form.errors.date_avance2}}</strong>
                </span>
            </div>
        </div>

        <!-- Avance 3 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.avance3') || 'Avance 3' }}</label>
                <input type="number" v-model.number="form.avance3" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.avance3" class="text-danger">
                    <strong>{{ form.errors.avance3 }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Avance 3 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_avance3') || 'Date Avance 3' }}</label>
                <input type="date" v-model="form.date_avance3" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_avance3" class="text-danger">
                    <strong>{{ form.errors.date_avance3 }}</strong>
                </span>
            </div>
        </div>

        <!-- Avance 4 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.avance4') || 'Avance 4' }}</label>
                <input type="number" v-model.number="form.avance4" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.avance4" class="text-danger">
                    <strong>{{ form.errors.avance4 }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Avance 4 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_avance4') || 'Date Avance 4' }}</label>
                <input type="date" v-model="form.date_avance4" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_avance4" class="text-danger">
                    <strong>{{ form.errors.date_avance4 }}</strong>
                </span>
            </div>
        </div>

        <!-- Total Payé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.total_paye') || 'Total Payé' }}</label>
                <input type="number" v-model.number="form.total_paye" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.total_paye" class="text-danger">
                    <strong>{{ form.errors.total_paye }}</strong>
                </span>
            </div>
        </div>

        <!-- Restant à Payer -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.restant_a_payer') || 'Restant à Payer' }}</label>
                <input type="number" v-model.number="form.restant_a_payer" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.restant_a_payer" class="text-danger">
                    <strong>{{ form.errors.restant_a_payer }}</strong>
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
