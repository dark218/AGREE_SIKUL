<script setup>
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const props = defineProps({
    form: { type: Object, required: true },
    groupes: { type: Array, default: () => [] },
    comptesParents: { type: Array, default: () => [] },
});
const statusOptions = [{ id: 'actif', libelle: 'Actif' }, { id: 'inactif', libelle: 'Inactif' }];
</script>
<template>
    <div class="row g-3 custom-input">
        <div class="col-md-4">
            <label>N° de compte <span class="text-danger">*</span></label>
            <input v-model="form.numero_compte" type="text" class="form-control" placeholder="Ex : 6011" />
            <span v-if="form.errors?.numero_compte" class="text-danger small">{{ form.errors.numero_compte }}</span>
        </div>
        <div class="col-md-8">
            <label>Intitulé du compte <span class="text-danger">*</span></label>
            <input v-model="form.libelle_compte" type="text" class="form-control" />
            <span v-if="form.errors?.libelle_compte" class="text-danger small">{{ form.errors.libelle_compte }}</span>
        </div>
        <div class="col-md-6">
            <label>Libellé court</label>
            <input v-model="form.libelle_court" type="text" class="form-control" maxlength="100" />
        </div>
        <div class="col-md-6">
            <label>Compte parent</label>
            <SearchableSelect v-model="form.compte_parent_id" :options="comptesParents" option-value="id" option-label="libelle" placeholder="-- Aucun --" />
        </div>
        <div class="col-md-6">
            <label>Groupe de comptes</label>
            <SearchableSelect v-model="form.groupe_comptes_id" :options="groupes" option-value="id" option-label="libelle" placeholder="--" />
        </div>
        <div class="col-md-6">
            <label>Statut</label>
            <SearchableSelect v-model="form.etat" :options="statusOptions" option-value="id" option-label="libelle" placeholder="--" />
        </div>
    </div>
</template>
