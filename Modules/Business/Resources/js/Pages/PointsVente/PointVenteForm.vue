<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import ProfileUpload from '@/Components/Common/ProfileUpload.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    zones: {
        type: Array,
        default: () => [],
    },
    marchands: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
    existingFiles: {
        type: Object,
        default: () => ({}),
    },
    isMarchand: {
        type: Boolean,
        default: false,
    },
    marchandCurrent: {
        type: Object,
        default: null,
    },
});
const isReadOnly = computed(() => props.mode === 'show');
const zoneOptions = computed(() => {
    return props.zones.map(z => ({ id: z.id, label: z.libelle }));
});
// Computed pour vérifier si on doit afficher le select marchand
const showMarchandSelect = computed(() => !props.isMarchand);
// Computed pour le nom du marchand actuel (si is_marchand)
const marchandCurrentLabel = computed(() => {
    return props.marchandCurrent?.raison_sociale || props.marchandCurrent?.label || '';
});
// Computed pour vérifier si les coordonnées sont disponibles
const hasCoordinates = computed(() => {
    return props.form.latitude && props.form.longitude;
});
// URL de la carte OpenStreetMap
const mapUrl = computed(() => {
    if (!hasCoordinates.value) return '';
    const lat = props.form.latitude;
    const lng = props.form.longitude;
    return `https://www.openstreetmap.org/export/embed.html?bbox=${lng - 0.005},${lat - 0.005},${lng + 0.005},${lat + 0.005}&layer=mapnik&marker=${lat},${lng}`;
});
</script>
<template>
    <div class="custom-input">
        <!-- Photo du Point de Vente - Style Profile Upload -->
        <ProfileUpload
            v-model="form.photo_id"
            :preview="existingFiles.photo"
            :disabled="isReadOnly"
            inputId="pointVentePhoto"
        />
        <div class="row g-3">
        <!-- Marchand (masqué si l'utilisateur est un marchand) -->
        <div v-if="showMarchandSelect" class="col-md-6">
            <div class="mb-3">
                <label>{{ t('fields.merchant') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <StylishSelect
                    v-model="form.marchand_id"
                    :options="marchands"
                    option-value="id"
                    option-label="label"
                    :placeholder="t('actions.select') + ' ' + t('fields.merchant')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.marchand_id" class="text-danger">
                    <strong>{{ form.errors.marchand_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Affichage du marchand actuel en lecture seule (si is_marchand) -->
        <div v-else class="col-md-6">
            <div class="mb-3">
                <label>{{ t('fields.merchant') }}</label>
                <input
                    type="text"
                    :value="marchandCurrentLabel"
                    class="form-control"
                    disabled
                />
            </div>
        </div>
        <!-- Zone -->
        <div class="col-md-6">
            <div class="mb-3">
                <label>{{ t('fields.zone') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <StylishSelect
                    v-model="form.zone_id"
                    :options="zoneOptions"
                    option-value="id"
                    option-label="label"
                    :placeholder="t('actions.select') + ' ' + t('fields.zone')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.zone_id" class="text-danger">
                    <strong>{{ form.errors.zone_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Nom -->
        <div class="col-md-6">
            <div class="mb-3">
                <label>{{ t('fields.name') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <input
                    type="text"
                    v-model="form.nom"
                    class="form-control"
                    :placeholder="t('fields.name')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom" class="text-danger">
                    <strong>{{ form.errors.nom }}</strong>
                </span>
            </div>
        </div>
        <!-- Adresse -->
        <div class="col-md-6">
            <div class="mb-3">
                <label>{{ t('fields.address') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <input
                    type="text"
                    v-model="form.adresse"
                    class="form-control"
                    :placeholder="t('fields.address')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.adresse" class="text-danger">
                    <strong>{{ form.errors.adresse }}</strong>
                </span>
            </div>
        </div>
        <!-- Telephone -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.phone') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <input
                    type="text"
                    v-model="form.telephone"
                    class="form-control"
                    :placeholder="t('fields.phone')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.telephone" class="text-danger">
                    <strong>{{ form.errors.telephone }}</strong>
                </span>
            </div>
        </div>
        <!-- Longitude -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.longitude') }}</label>
                <input
                    type="number"
                    step="any"
                    v-model="form.longitude"
                    class="form-control"
                    :placeholder="t('fields.longitude')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.longitude" class="text-danger">
                    <strong>{{ form.errors.longitude }}</strong>
                </span>
            </div>
        </div>
        <!-- Latitude -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.latitude') }}</label>
                <input
                    type="number"
                    step="any"
                    v-model="form.latitude"
                    class="form-control"
                    :placeholder="t('fields.latitude')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.latitude" class="text-danger">
                    <strong>{{ form.errors.latitude }}</strong>
                </span>
            </div>
        </div>
        <!-- Carte de localisation (affichée en mode show/edit si coordonnées disponibles) -->
        <div v-if="hasCoordinates && (mode === 'show' || mode === 'edit')" class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.location') }}</label>
                <div class="map-container">
                    <iframe
                        :src="mapUrl"
                        width="100%"
                        height="300"
                        style="border: 1px solid #ddd; border-radius: 8px;"
                        allowfullscreen
                        loading="lazy"
                    ></iframe>
                </div>
                <small class="text-muted">
                    {{ t('fields.coordinates') }}: {{ form.latitude }}, {{ form.longitude }}
                </small>
            </div>
        </div>
        </div>
    </div>
</template>
