<!--
  SalaireForm.vue — Refonte Phase 4.3 (Steppers).
  Historique : 478 lignes / 35 champs saisis → 4 steps avec dérivations auto.

  Steps :
    1. Contexte    (année, école → campus auto, mois de paie, intitulé)
    2. Employé     (nom, prenoms, matricule interne, matricule CNPS, numéro identifiant)
    3. Rémunération (base, primes, indemnités → brut auto — retenues → net auto)
    4. Paiement    (intégral OU avances 1-4 → total_paye auto → restant_a_payer auto)

  Dérivations automatiques (calcul côté client, à valider serveur) :
    - salaire_brut  = salaire_base + primes + indemnites
    - salaire_net   = salaire_brut - retenues_fiscales - retenues_sociales
                                    - autres_retenues - saisies_oppositions
    - total_paye    = paiement_integral || (avance1 + avance2 + avance3 + avance4)
    - restant       = salaire_net - total_paye

  Campus auto-rempli depuis l'école sélectionnée.
-->

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';

const { t } = useI18n();

const props = defineProps({
    form:            { type: Object, required: true },
    anneesScolaires: { type: Array,  default: () => [] },
    ecoles:          { type: Array,  default: () => [] },
    campuses:        { type: Array,  default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const emit = defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

// Auto-fill campus depuis l'école.
watch(() => props.form.ecole_id, (id) => {
    if (!id) return;
    const e = props.ecoles.find(x => String(x.id) === String(id));
    if (e?.campus_id) props.form.campus_id = e.campus_id;
});

const autoLabel = (list, id, keyLibelle = 'libelle', keyNom = 'nom') => {
    if (!id || !list?.length) return '—';
    const f = list.find(x => String(x.id) === String(id));
    return f?.[keyLibelle] || f?.[keyNom] || '—';
};
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

const n = (v) => Number(v) || 0;

// Dérivations client.
const salaireBrut = computed(() => n(props.form.salaire_base) + n(props.form.primes) + n(props.form.indemnites));
const salaireNet  = computed(() =>
    salaireBrut.value
    - n(props.form.retenues_fiscales) - n(props.form.retenues_sociales)
    - n(props.form.autres_retenues)   - n(props.form.saisies_oppositions)
);
const totalPaye   = computed(() =>
    n(props.form.paiement_integral) > 0
        ? n(props.form.paiement_integral)
        : n(props.form.avance1) + n(props.form.avance2) + n(props.form.avance3) + n(props.form.avance4)
);
const restantAPayer = computed(() => Math.max(0, salaireNet.value - totalPaye.value));

// Push dérivés dans le form pour submit.
watch(salaireBrut,    (v) => { props.form.salaire_brut = v; });
watch(salaireNet,     (v) => { props.form.salaire_net = v; });
watch(totalPaye,      (v) => { props.form.total_paye = v; });
watch(restantAPayer,  (v) => { props.form.restant_a_payer = v; });

const etatOptions = [
    { id: 'actif',   libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const steps = [
    { key: 'contexte',    label: 'Contexte',      icon: 'fas fa-calendar-check',  requiredFields: ['annee_scolaire_id', 'ecole_id'] },
    { key: 'employe',     label: 'Employé',       icon: 'fas fa-user-tie',         requiredFields: ['nom'] },
    { key: 'remuneration',label: 'Rémunération',  icon: 'fas fa-coins',            requiredFields: ['salaire_base'] },
    { key: 'paiement',    label: 'Paiement',      icon: 'fas fa-money-check-alt' },
];

// Init champs numériques à 0 pour éviter les null qui cassent les dérivations.
['salaire_base', 'primes', 'indemnites',
 'retenues_fiscales', 'retenues_sociales', 'autres_retenues', 'saisies_oppositions',
 'paiement_integral', 'avance1', 'avance2', 'avance3', 'avance4'].forEach((k) => {
    if (props.form[k] === null || props.form[k] === undefined) props.form[k] = 0;
});
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="salaire-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : CONTEXTE -->
        <template #contexte>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Année scolaire <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.annee_scolaire_id"
                        :options="anneesScolaires"
                        option-value="id"
                        option-label="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.annee_scolaire_id" class="text-danger small">{{ form.errors.annee_scolaire_id }}</span>
                </div>
                <div class="col-md-4">
                    <label>École <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.ecole_id"
                        :options="ecoles"
                        option-value="id"
                        option-label="nom"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.ecole_id" class="text-danger small">{{ form.errors.ecole_id }}</span>
                </div>
                <div class="col-md-4">
                    <label>Campus <span class="badge bg-secondary">auto</span></label>
                    <input :value="campusLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-6">
                    <label>Mois de paie</label>
                    <input v-model="form.mois_paie" :disabled="isReadOnly" type="month" class="form-control" placeholder="YYYY-MM" />
                </div>
                <div class="col-md-6">
                    <label>Intitulé</label>
                    <input v-model="form.intitule" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ex : Bulletin de paie" />
                </div>
                <div class="col-md-6">
                    <label>Institution</label>
                    <input v-model="form.institution" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label>État</label>
                    <SearchableSelect
                        v-model="form.etat"
                        :options="etatOptions"
                        option-value="id"
                        option-label="libelle"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>
            </div>
        </template>

        <!-- STEP 2 : EMPLOYÉ -->
        <template #employe>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Nom <span class="text-danger">*</span></label>
                    <input v-model="form.nom" :disabled="isReadOnly" type="text" class="form-control" />
                    <span v-if="form.errors?.nom" class="text-danger small">{{ form.errors.nom }}</span>
                </div>
                <div class="col-md-6">
                    <label>Prénoms</label>
                    <input v-model="form.prenoms" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label>Noms complets</label>
                    <input v-model="form.noms" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label>Nom à restituer</label>
                    <input v-model="form.nom_restituer" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Matricule interne</label>
                    <input v-model="form.matricule_interne" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Matricule CNPS</label>
                    <input v-model="form.matricule_cnps" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>N° identifiant</label>
                    <input v-model="form.numero_identifiant" :disabled="isReadOnly" type="text" class="form-control" />
                </div>
            </div>
        </template>

        <!-- STEP 3 : RÉMUNÉRATION (brut/net auto) -->
        <template #remuneration>
            <div class="row g-3">
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-plus-circle me-1"></i> Éléments positifs</h6>
                </div>
                <div class="col-md-4">
                    <label>Salaire de base <span class="text-danger">*</span></label>
                    <input v-model.number="form.salaire_base" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" />
                    <span v-if="form.errors?.salaire_base" class="text-danger small">{{ form.errors.salaire_base }}</span>
                </div>
                <div class="col-md-4">
                    <label>Primes</label>
                    <input v-model.number="form.primes" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Indemnités</label>
                    <input v-model.number="form.indemnites" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" />
                </div>
                <div class="col-12">
                    <div class="p-3 bg-info bg-opacity-10 rounded border border-info">
                        <strong class="text-info">Salaire brut = base + primes + indemnités : <span class="fs-5">{{ salaireBrut.toFixed(2) }}</span></strong>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="text-danger"><i class="fa fa-minus-circle me-1"></i> Retenues</h6>
                </div>
                <div class="col-md-3">
                    <label>Retenues fiscales</label>
                    <input v-model.number="form.retenues_fiscales" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Retenues sociales</label>
                    <input v-model.number="form.retenues_sociales" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Autres retenues</label>
                    <input v-model.number="form.autres_retenues" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" />
                </div>
                <div class="col-md-3">
                    <label>Saisies / oppositions</label>
                    <input v-model.number="form.saisies_oppositions" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" />
                </div>
                <div class="col-12">
                    <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                        <strong class="text-success">Salaire net à payer : <span class="fs-4">{{ salaireNet.toFixed(2) }}</span></strong>
                    </div>
                </div>
            </div>
        </template>

        <!-- STEP 4 : PAIEMENT (intégral OU avances) -->
        <template #paiement>
            <div class="row g-3">
                <div class="col-12">
                    <div class="alert alert-info d-flex align-items-center py-2">
                        <i class="fa fa-info-circle me-2"></i>
                        <div class="small">
                            Renseignez <strong>soit le paiement intégral</strong>, <strong>soit une ou plusieurs avances</strong>.
                            Le restant à payer est calculé automatiquement.
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-hand-holding-usd me-1"></i> Paiement intégral</h6>
                </div>
                <div class="col-md-6">
                    <label>Montant paiement intégral</label>
                    <input v-model.number="form.paiement_integral" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label>Date paiement intégral</label>
                    <input v-model="form.date_paiement_integral" :disabled="isReadOnly" type="date" class="form-control" />
                </div>

                <div class="col-12 mt-3">
                    <h6 class="text-primary"><i class="fa fa-list-ol me-1"></i> Avances (jusqu'à 4)</h6>
                </div>
                <div v-for="i in 4" :key="`avance-${i}`" class="col-md-6">
                    <div class="p-2 border rounded">
                        <div class="row g-2">
                            <div class="col-7">
                                <label>Avance {{ i }}</label>
                                <input v-model.number="form[`avance${i}`]" :disabled="isReadOnly" type="number" min="0" step="0.01" class="form-control form-control-sm" />
                            </div>
                            <div class="col-5">
                                <label>Date</label>
                                <input v-model="form[`date_avance${i}`]" :disabled="isReadOnly" type="date" class="form-control form-control-sm" />
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="mt-3" />
                <div class="col-md-6">
                    <div class="p-3 bg-primary bg-opacity-10 rounded border border-primary">
                        <strong class="text-primary">Total payé : <span class="fs-5">{{ totalPaye.toFixed(2) }}</span></strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning">
                        <strong class="text-warning">Restant à payer : <span class="fs-5">{{ restantAPayer.toFixed(2) }}</span></strong>
                    </div>
                </div>
            </div>
        </template>
    </FormStepper>
</template>

<style scoped>
.form-control {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.55rem 0.85rem;
    font-size: 0.95rem;
}
.form-control:focus {
    border-color: #0b5697;
    box-shadow: 0 0 0 0.2rem rgba(11, 86, 151, 0.15);
}
label {
    font-weight: 500;
    color: #374151;
    font-size: 0.9rem;
    margin-bottom: 0.4rem;
    display: block;
}
</style>
