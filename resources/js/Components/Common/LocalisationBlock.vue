<!--
  LocalisationBlock.vue
  Composant réutilisable pour saisir une adresse géographique avec cascade auto-fill.

  Comportement :
  - Quartier sélectionné → fetch /parametrage/api/quartier/{id}/hierarchy → auto-remplit commune/dept/region/pays
  - Commune sélectionnée → fetch hierarchy → auto-remplit dept/region/pays
  - Département sélectionné → auto-remplit region/pays
  - Région sélectionnée → auto-remplit pays
  - L'utilisateur peut TOUJOURS modifier manuellement chaque champ après l'auto-fill.

  Props attendues sur `form` :
    code_postal, boite_postale, ville (strings)
    quartier_id, commune_id, departement_id, region_id, pays_id (FK)

  Layout (selon spec Orchidée) :
    Ligne 1 : Code postal | Boîte postale | Quartier | Commune
    Ligne 2 : Ville | Département | Région | Pays
-->
<script setup>
import { watch } from 'vue';
import axios from 'axios';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const props = defineProps({
    form: { type: Object, required: true },
    paysList: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    departements: { type: Array, default: () => [] },
    communes: { type: Array, default: () => [] },
    quartiers: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
    showAddressLine: { type: Boolean, default: false }, // afficher l'input "Adresse" en haut
    addressField: { type: String, default: 'adresse_siege' }, // nom du champ adresse libre
    addressLabel: { type: String, default: 'Adresse du siège' },
});

/**
 * Cascade auto-fill : quand un nœud est sélectionné, on appelle l'API
 * pour récupérer toute la hiérarchie ascendante.
 */
async function cascadeFromQuartier(id) {
    if (!id || props.disabled) return;
    try {
        const { data } = await axios.get(`/parametrage/api/quartier/${id}/hierarchy`);
        if (data.commune_id) props.form.commune_id = data.commune_id;
        if (data.departement_id) props.form.departement_id = data.departement_id;
        if (data.region_id) props.form.region_id = data.region_id;
        if (data.pays_id) props.form.pays_id = data.pays_id;
    } catch (e) {
        // En cas d'erreur réseau on ne casse pas le form, l'user complétera manuellement
        console.warn('Cascade quartier failed', e);
    }
}

async function cascadeFromCommune(id) {
    if (!id || props.disabled) return;
    try {
        const { data } = await axios.get(`/parametrage/api/commune/${id}/hierarchy`);
        if (data.departement_id) props.form.departement_id = data.departement_id;
        if (data.region_id) props.form.region_id = data.region_id;
        if (data.pays_id) props.form.pays_id = data.pays_id;
    } catch (e) { console.warn('Cascade commune failed', e); }
}

async function cascadeFromDepartement(id) {
    if (!id || props.disabled) return;
    try {
        const { data } = await axios.get(`/parametrage/api/departement/${id}/hierarchy`);
        if (data.region_id) props.form.region_id = data.region_id;
        if (data.pays_id) props.form.pays_id = data.pays_id;
    } catch (e) { console.warn('Cascade departement failed', e); }
}

async function cascadeFromRegion(id) {
    if (!id || props.disabled) return;
    try {
        const { data } = await axios.get(`/parametrage/api/region/${id}/hierarchy`);
        if (data.pays_id) props.form.pays_id = data.pays_id;
    } catch (e) { console.warn('Cascade region failed', e); }
}

watch(() => props.form.quartier_id, (id) => cascadeFromQuartier(id));
watch(() => props.form.commune_id, (id) => cascadeFromCommune(id));
watch(() => props.form.departement_id, (id) => cascadeFromDepartement(id));
watch(() => props.form.region_id, (id) => cascadeFromRegion(id));
</script>

<template>
    <div class="row g-3">
        <!-- Adresse libre (optionnelle) -->
        <div v-if="showAddressLine" class="col-12">
            <label class="form-label fw-medium">{{ addressLabel }}</label>
            <input
                type="text"
                v-model="form[addressField]"
                class="form-control"
                :placeholder="addressLabel"
                :disabled="disabled"
            />
            <span v-if="form.errors?.[addressField]" class="text-danger small">{{ form.errors[addressField] }}</span>
        </div>

        <!-- LIGNE 1 : Code postal | Boîte postale | Quartier | Commune -->
        <div class="col-sm-3">
            <label class="form-label fw-medium">Code postal</label>
            <input type="text" v-model="form.code_postal" class="form-control" placeholder="Code postal" :disabled="disabled" />
            <span v-if="form.errors?.code_postal" class="text-danger small">{{ form.errors.code_postal }}</span>
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Boîte postale</label>
            <input type="text" v-model="form.boite_postale" class="form-control" placeholder="BP" :disabled="disabled" />
            <span v-if="form.errors?.boite_postale" class="text-danger small">{{ form.errors.boite_postale }}</span>
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">
                Quartier
                <small class="text-muted ms-1" style="font-size:11px;">(cascade auto)</small>
            </label>
            <SearchableSelect
                v-model="form.quartier_id"
                :options="quartiers"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="disabled"
            />
            <span v-if="form.errors?.quartier_id" class="text-danger small">{{ form.errors.quartier_id }}</span>
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Commune</label>
            <SearchableSelect
                v-model="form.commune_id"
                :options="communes"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="disabled"
            />
            <span v-if="form.errors?.commune_id" class="text-danger small">{{ form.errors.commune_id }}</span>
        </div>

        <!-- LIGNE 2 : Ville | Département | Région | Pays -->
        <div class="col-sm-3">
            <label class="form-label fw-medium">Ville</label>
            <input type="text" v-model="form.ville" class="form-control" placeholder="Ville" :disabled="disabled" />
            <span v-if="form.errors?.ville" class="text-danger small">{{ form.errors.ville }}</span>
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Département</label>
            <SearchableSelect
                v-model="form.departement_id"
                :options="departements"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="disabled"
            />
            <span v-if="form.errors?.departement_id" class="text-danger small">{{ form.errors.departement_id }}</span>
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Région</label>
            <SearchableSelect
                v-model="form.region_id"
                :options="regions"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="disabled"
            />
            <span v-if="form.errors?.region_id" class="text-danger small">{{ form.errors.region_id }}</span>
        </div>
        <div class="col-sm-3">
            <label class="form-label fw-medium">Pays</label>
            <SearchableSelect
                v-model="form.pays_id"
                :options="paysList"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="disabled"
            />
            <span v-if="form.errors?.pays_id" class="text-danger small">{{ form.errors.pays_id }}</span>
        </div>
    </div>
</template>
