<script setup>
import { computed, onMounted, watch } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    seances: {
        type: Array,
        default: () => [],
    },
    apprenants: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
    },
});

const isReadOnly = computed(() => props.mode === 'show');
const isEditMode = computed(() => props.mode === 'edit');

// Auto-fill date_presence when seance is selected
watch(
    () => props.form.seance_id,
    () => {
        const selectedSeance = props.seances.find(s => s.id === props.form.seance_id);
        if (selectedSeance?.date) {
            props.form.date_presence = selectedSeance.date;
            console.log('✅ Auto-filled date_presence:', selectedSeance.date);
        }
    }
);

onMounted(() => {
    console.log('🔍 PresenceSeanceForm mounted');
    console.log('📋 Props:', {
        mode: props.mode,
        seances: props.seances,
        seancesCount: props.seances.length,
        apprenants: props.apprenants,
        apprenantsCount: props.apprenants.length,
    });
    console.log('📋 Form:', props.form);

    // Format date_presence if it's in ISO format (from server)
    if (props.form.date_presence && typeof props.form.date_presence === 'string') {
        // If it looks like ISO format (contains T or Z), extract just Y-m-d
        if (props.form.date_presence.includes('T') || props.form.date_presence.includes('Z')) {
            const dateObj = new Date(props.form.date_presence);
            props.form.date_presence = dateObj.toISOString().split('T')[0];
            console.log('✅ Formatted date_presence:', props.form.date_presence);
        }
    }
});
</script>

<template>
    <div>
        <!-- Section: Informations de base -->
        <h5 class="section-title mb-3 mt-4">{{ t('fields.informations_de_base') || 'Informations de base' }}</h5>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('common.seance') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.seance_id"
                    :options="seances"
                    :is-disabled="isReadOnly || isEditMode"
                    option-label="titre"
                    placeholder="Sélectionner une séance"
                />
                <div v-if="form.errors?.seance_id" class="invalid-feedback d-block">
                    {{ form.errors.seance_id[0] }}
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('common.apprenant') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.apprenant_id"
                    :options="apprenants"
                    :is-disabled="isReadOnly || isEditMode"
                    option-value="id"
                    option-label="libelle"
                    placeholder="Sélectionner un apprenant"
                />
                <div v-if="form.errors?.apprenant_id" class="invalid-feedback d-block">
                    {{ form.errors.apprenant_id[0] }}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('fields.date_presence') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_presence"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.date_presence }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="form.errors?.date_presence" class="invalid-feedback d-block">
                    {{ form.errors.date_presence[0] }}
                </div>
            </div>
        </div>

        <!-- Section: Horaires -->
        <h5 class="section-title mb-3 mt-4">{{ t('fields.horaires') || 'Horaires' }}</h5>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('fields.heure_arrivee') }}</label>
                <input
                    v-model="form.heure_arrivee"
                    type="time"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.heure_arrivee }"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors?.heure_arrivee" class="invalid-feedback d-block">
                    {{ form.errors.heure_arrivee[0] }}
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('fields.heure_depart') }}</label>
                <input
                    v-model="form.heure_depart"
                    type="time"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.heure_depart }"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors?.heure_depart" class="invalid-feedback d-block">
                    {{ form.errors.heure_depart[0] }}
                </div>
            </div>
        </div>

        <!-- Section: Remarques -->
        <h5 class="section-title mb-3 mt-4">{{ t('fields.observations') || 'Observations' }}</h5>

        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label">{{ t('fields.remarques') }}</label>
                <textarea
                    v-model="form.remarques"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.remarques }"
                    :disabled="isReadOnly"
                    rows="4"
                ></textarea>
                <div v-if="form.errors?.remarques" class="invalid-feedback d-block">
                    {{ form.errors.remarques[0] }}
                </div>
            </div>
        </div>

        <!-- Section: Statut (EN DERNIER) -->
        <h5 class="section-title mb-3 mt-4">{{ t('fields.status') || 'Statut' }}</h5>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ t('fields.statut') }} <span class="text-danger">*</span></label>
                <select
                    v-model="form.statut"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.statut }"
                    :disabled="isReadOnly"
                    required
                >
                    <option value="">{{ t('common.select') || 'Sélectionner' }}</option>
                    <option value="present">{{ t('common.present') || 'Présent' }}</option>
                    <option value="absent">{{ t('common.absent') || 'Absent' }}</option>
                    <option value="retard">{{ t('common.retard') || 'Retard' }}</option>
                    <option value="justifie">{{ t('common.justifie') || 'Justifié' }}</option>
                </select>
                <div v-if="form.errors?.statut" class="invalid-feedback d-block">
                    {{ form.errors.statut[0] }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 0.5rem;
}
</style>
