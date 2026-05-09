<script setup>
import { useI18n } from 'vue-i18n';
import { onMounted, watch, computed } from 'vue';

const { t } = useI18n();
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
    cours: {
        type: Array,
        default: () => [],
    },
    salles: {
        type: Array,
        default: () => [],
    },
});
const isReadOnly = props.mode === 'show';

// Calcul automatique de la durée
watch([() => props.form.heure_debut, () => props.form.heure_fin], () => {
    if (props.form.heure_debut && props.form.heure_fin) {
        const debut = new Date(`2000-01-01T${props.form.heure_debut}`);
        const fin = new Date(`2000-01-01T${props.form.heure_fin}`);
        const durationMs = fin - debut;

        if (durationMs > 0) {
            const minutes = Math.floor(durationMs / 60000);
            const hours = minutes / 60;

            // Durée en heures décimales (ex: 1.5 pour 1h30m, 0.5 pour 30m)
            props.form.duree = parseFloat(hours.toFixed(2));
        }
    }
}, { deep: true });

onMounted(() => {
    console.log('🎯 SeanceForm mounted!');
    console.log('📊 Mode:', props.mode);
    console.log('📋 Form object keys:', Object.keys(props.form));
    console.log('📚 Cours data:', props.cours);
    console.log('🏢 Salles data:', props.salles);
    console.log('✅ Form is ready, readonly:', isReadOnly);
    if (props.salles.length === 0) {
        console.warn('⚠️ AUCUNE SALLE CHARGÉE - Créez des salles avec statut actif');
    }
});
const statutOptions = [
    { id: 'planifiee', libelle: 'Planifiée' },
    { id: 'realisee', libelle: 'Réalisée' },
    { id: 'annulee', libelle: 'Annulée' },
];
</script>
<template>
    <div class="row">
        <!-- Section 1: Références (Cours) -->
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ t('common.cours') }} <span class="text-danger">*</span></label>
                <select v-model="form.cours_id" class="form-control" :disabled="isReadOnly" :class="{ 'is-invalid': form.errors?.cours_id }">
                    <option value="">{{ t('actions.select') || '-- Sélectionner --' }}</option>
                    <option v-for="c in cours" :key="c.id" :value="c.id">
                        {{ c.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.cours_id" class="text-danger">
                    <strong>{{ form.errors.cours_id }}</strong>
                </span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ t('common.salle') }}</label>
                <select v-model="form.salle_id" class="form-control" :disabled="isReadOnly" :class="{ 'is-invalid': form.errors?.salle_id }">
                    <option value="">{{ t('actions.select') || '-- Sélectionner --' }}</option>
                    <option v-for="salle in salles" :key="salle.id" :value="salle.id">
                        {{ salle.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.salle_id" class="text-danger">
                    <strong>{{ form.errors.salle_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 2: Contenu (Titre + Sujet) -->
        <div class="col-md-12">
            <div class="form-group">
                <label>{{ t('common.titre') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.titre"
                    type="text"
                    maxlength="255"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.titre }"
                    :disabled="isReadOnly"
                    :placeholder="t('common.titre')"
                />
                <div v-if="form.errors?.titre" class="invalid-feedback d-block">
                    {{ form.errors.titre[0] || form.errors.titre }}
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label>{{ t('common.sujet') }}</label>
                <textarea
                    v-model="form.sujet"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.sujet }"
                    :disabled="isReadOnly"
                    :placeholder="t('common.sujet')"
                    rows="3"
                ></textarea>
                <div v-if="form.errors?.sujet" class="invalid-feedback d-block">
                    {{ form.errors.sujet[0] || form.errors.sujet }}
                </div>
            </div>
        </div>

        <!-- Section 3: Timing (Date + Durée + Heures) -->
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ t('common.date') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.date }"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors?.date" class="invalid-feedback d-block">
                    {{ form.errors.date[0] || form.errors.date }}
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ t('common.duree') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.duree"
                    type="number"
                    step="0.5"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.duree }"
                    :disabled="isReadOnly"
                    :placeholder="t('common.duree')"
                />
                <div v-if="form.errors?.duree" class="invalid-feedback d-block">
                    {{ form.errors.duree[0] || form.errors.duree }}
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ t('common.heure_debut') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.heure_debut"
                    type="time"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.heure_debut }"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors?.heure_debut" class="invalid-feedback d-block">
                    {{ form.errors.heure_debut[0] || form.errors.heure_debut }}
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ t('common.heure_fin') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.heure_fin"
                    type="time"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.heure_fin }"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors?.heure_fin" class="invalid-feedback d-block">
                    {{ form.errors.heure_fin[0] || form.errors.heure_fin }}
                </div>
            </div>
        </div>

        <!-- Section 4: Statut (EN DERNIER) -->
        <div class="col-md-12">
            <div class="form-group">
                <label>{{ t('common.statut') }} <span class="text-danger">*</span></label>
                <select
                    v-model="form.statut"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.statut }"
                    :disabled="isReadOnly"
                >
                    <option value="">{{ t('common.select') }}</option>
                    <option value="planifiee">{{ t('common.planifiee') }}</option>
                    <option value="realisee">{{ t('common.realisee') }}</option>
                    <option value="annulee">{{ t('common.annulee') }}</option>
                </select>
                <div v-if="form.errors?.statut" class="invalid-feedback d-block">
                    {{ form.errors.statut[0] || form.errors.statut }}
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.seance-form {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    font-weight: 500;
    margin-bottom: 8px;
    display: block;
}
.form-control:disabled {
    background-color: #f8f9fa;
    color: #212529;
    border-color: #dee2e6;
    cursor: text;
}

.form-control:disabled::placeholder {
    color: #6c757d;
}
.form-actions {
    display: flex;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}
.form-actions button,
.form-actions a {
    padding: 10px 20px;
    font-weight: 500;
}
</style>
