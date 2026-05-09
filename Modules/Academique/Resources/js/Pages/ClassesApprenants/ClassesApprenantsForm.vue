<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    apprenants: {
        type: Array,
        default: () => [],
    },
    classes: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
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

// Handle classe selection to auto-fill dependent fields
const handleClasseChange = async (newClasseId) => {
    if (!newClasseId) return;

    try {
        console.log('[Auto-fill] Fetching classe data for ID:', newClasseId);
        const response = await fetch(`/api/classes/${newClasseId}`);
        if (!response.ok) {
            console.error('[Auto-fill] API error:', response.status);
            return;
        }
        const data = await response.json();
        console.log('[Auto-fill] Data received:', data);

        // Auto-fill dependent fields
        props.form.ecole_id = data.ecole_id || null;
        props.form.campus_id = data.campus_id || null;
        props.form.section_id = data.section_id || null;
        props.form.cycle_id = data.cycle_id || null;
        props.form.annee_scolaire_id = data.annee_scolaire_id || null;

        console.log('[Auto-fill] Form updated:', {
            ecole_id: props.form.ecole_id,
            campus_id: props.form.campus_id,
            section_id: props.form.section_id,
            cycle_id: props.form.cycle_id,
            annee_scolaire_id: props.form.annee_scolaire_id
        });
    } catch (error) {
        console.error('[Auto-fill] Error:', error);
    }
};

const selectedApprenant = computed(() => {
    return props.apprenants.find(a => a.id == props.form.apprenant_id);
});

const selectedClasse = computed(() => {
    return props.classes.find(c => c.id == props.form.classe_id);
});

</script>

<template>
    <div class="row g-3 custom-input">
        <!-- SECTION 1: SÉLECTION DES APPRENANTS ET CLASSE -->

        <!-- Apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Apprenant <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.apprenant_id"
                    :options="apprenants"
                    optionValue="id"
                    :optionLabel="(a) => `${a.matricule} - ${a.prenoms} ${a.nom}`"
                    :placeholder="t('fields.select_apprenant') || 'Sélectionner un apprenant'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.apprenant_id" class="text-danger">
                    <strong>{{ form.errors.apprenant_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Classe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Classe <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.classe_id"
                    @update:modelValue="handleClasseChange"
                    :options="classes"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('fields.select_classe') || 'Sélectionner une classe'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.classe_id" class="text-danger">
                    <strong>{{ form.errors.classe_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Contexte hiérarchique (auto-rempli par la classe) -->
        <HierarchyContextBar :form="form" />

        <!-- SECTION 2: INFORMATIONS DE L'APPRENANT -->

        <!-- Nom(s) de l'apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Nom(s) de l'apprenant</label>
                <input
                    type="text"
                    :value="selectedApprenant?.nom || '-'"
                    class="form-control"
                    readonly
                >
            </div>
        </div>

        <!-- Prénom(s) de l'apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Prénom(s) de l'apprenant</label>
                <input
                    type="text"
                    :value="selectedApprenant?.prenoms || '-'"
                    class="form-control"
                    readonly
                >
            </div>
        </div>

        <!-- Nom complet de l'apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Nom complet de l'apprenant</label>
                <input
                    type="text"
                    :value="selectedApprenant ? `${selectedApprenant.prenoms} ${selectedApprenant.nom}` : '-'"
                    class="form-control"
                    readonly
                >
            </div>
        </div>

        <!-- Sexe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Sexe</label>
                <input
                    type="text"
                    :value="selectedApprenant?.sexe || '-'"
                    class="form-control"
                    readonly
                >
            </div>
        </div>

        <!-- Classe actuelle (détail apprenant) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Classe actuelle</label>
                <input
                    type="text"
                    :value="selectedApprenant?.classe?.nom || '-'"
                    class="form-control"
                    readonly
                >
            </div>
        </div>

        <!-- Niveau -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Niveau</label>
                <input
                    type="text"
                    :value="selectedApprenant?.classe?.niveau?.libelle || '-'"
                    class="form-control"
                    readonly
                >
            </div>
        </div>

        <!-- Cycle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Cycle</label>
                <input
                    type="text"
                    :value="selectedApprenant?.cycle?.libelle || '-'"
                    class="form-control"
                    readonly
                >
            </div>
        </div>

        <!-- Ecole -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Ecole</label>
                <input
                    type="text"
                    :value="selectedApprenant?.ecole?.nom || '-'"
                    class="form-control"
                    readonly
                >
            </div>
        </div>

        <!-- Institution -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Institution</label>
                <input
                    type="text"
                    :value="selectedApprenant?.ecole?.institution?.nom || '-'"
                    class="form-control"
                    readonly
                >
            </div>
        </div>

        <!-- Campus -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Campus</label>
                <input
                    type="text"
                    :value="selectedApprenant?.campus?.nom || '-'"
                    class="form-control"
                    readonly
                >
            </div>
        </div>

        <!-- Année académique courante -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Année académique courante</label>
                <SearchableSelect
                    v-model="form.annee_academique_courante"
                    :options="anneesScolaires"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('fields.select_year') || 'Sélectionner une année'"
                />
            </div>
        </div>

        <!-- Années académiques antérieures -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Années académiques antérieures</label>
                <SearchableSelect
                    v-model="form.annees_academiques_anterieures"
                    :options="anneesScolaires"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('fields.select_year') || 'Sélectionner une année'"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-input .col-sm-6 label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:disabled,
.form-control[disabled] {
    background-color: #f5f5f5 !important;
    cursor: not-allowed;
    border: none !important;
    padding: 8px 12px !important;
    color: #666 !important;
}

input:disabled {
    border: none !important;
    background-color: #f5f5f5 !important;
}

.text-danger {
    color: #dc3545;
    font-size: 12px;
    margin-top: 4px;
    display: block;
}
</style>
