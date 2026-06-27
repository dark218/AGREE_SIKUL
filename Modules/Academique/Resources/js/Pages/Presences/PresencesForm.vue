<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useApprenantCascade } from '@/Composables/useApprenantCascade';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    apprenants: {
        type: Array,
        required: true,
    },
    seances: {
        type: Array,
        required: true,
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});
const isReadOnly = props.mode === 'show';

// Cascade : Apprenant → Classe + École + Campus + Niveau + ...
useApprenantCascade(props.form, () => props.apprenants);

const statusOptions = [
    { id: 'present', libelle: 'Présent' },
    { id: 'retard', libelle: 'En retard' },
    { id: 'absent', libelle: 'Absent' },
    { id: 'malade', libelle: 'Malade' },
    { id: 'permis', libelle: 'Permis' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.apprenant') || 'Apprenant' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.apprenant_id"
                    :options="apprenants"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly || mode === 'edit'"
                />
                <span v-if="form.errors?.apprenant_id" class="text-danger">
                    <strong>{{ form.errors.apprenant_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Contexte hérité de l'Apprenant -->
        <InheritedContextBar
            :source="apprenants?.find(a => String(a.id) === String(form.apprenant_id)) || null"
            title="Hérité de l'apprenant"
        />

        <div class="d-none"><!-- Cell-break -->
        </div>
       
        <!-- Séance -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.seance') || 'Séance' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.seance_id"
                    :options="seances"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly || mode === 'edit'"
                />
                <span v-if="form.errors?.seance_id" class="text-danger">
                    <strong>{{ form.errors.seance_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Heure Arrivée -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.heure_arrivee') || 'Heure d\'arrivée' }}</label>
                <input
                    v-model="form.heure_arrivee"
                    type="time"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">{{ t('common.optional') || 'Optionnel' }}</small>
                <span v-if="form.errors?.heure_arrivee" class="text-danger">
                    <strong>{{ form.errors.heure_arrivee }}</strong>
                </span>
            </div>
        </div>
        <!-- Remarques -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('fields.remarques') || 'Remarques' }}</label>
                <textarea
                    v-model="form.remarques"
                    class="form-control"
                    rows="3"
                    :placeholder="t('common.optional_notes') || 'Justification, notes...'"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">{{ t('common.optional') || 'Optionnel' }}</small>
                <span v-if="form.errors?.remarques" class="text-danger">
                    <strong>{{ form.errors.remarques }}</strong>
                </span>
            </div>
        </div>

         <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.statut"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
