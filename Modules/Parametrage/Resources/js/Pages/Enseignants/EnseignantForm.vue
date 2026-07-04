<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    mode: { type: String, default: 'create' },
    civilityTitles: { type: Array, default: () => [] },
    communes: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    pays: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    niveaux: { type: Array, default: () => [] },
    classes: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
});
const isReadOnly = props.mode === 'show';
const genderOptions = [
    { id: 'M', libelle: 'Masculin' },
    { id: 'F', libelle: 'Féminin' },
    { id: 'Autre', libelle: 'Autre' },
];
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'non_actif', libelle: 'Non Actif' },
    { id: 'suspendu', libelle: 'Suspendu' },
    { id: 'retraite', libelle: 'Retraité' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Basic Information -->
        <div class="col-12"><h5 class="section-title">👤 Informations Personnelles</h5></div>
        <div class="col-md-4"><label>N. Enseignant *</label><input v-model="form.num_enseignant" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Nom *</label><input v-model="form.nom" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Prénoms</label><input v-model="form.prenoms" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Email</label><input v-model="form.email" type="email" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Téléphone</label><input v-model="form.telephone" type="text" class="form-control" :disabled="isReadOnly" maxlength="20" /></div>
        <div class="col-md-4"><label>Photo</label><input v-model="form.photo" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <!-- Section 2: État Civil -->
        <div class="col-12"><h5 class="section-title">📋 État Civil</h5></div>
        <div class="col-md-4"><label>Titre Civilité</label><SearchableSelect v-model="form.civility_title_id" :options="civilityTitles" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" :placeholder="'Sélectionner...'" /></div>
        <div class="col-md-4"><label>Genre</label><SearchableSelect v-model="form.gender" :options="genderOptions" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" :placeholder="'Sélectionner...'" /></div>
        <div class="col-md-4"><label>Statut Marital</label><input v-model="form.marital_status" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Date de Naissance</label><input v-model="form.date_of_birth" type="date" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Lieu de Naissance</label><input v-model="form.place_of_birth" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Commune</label><SearchableSelect v-model="form.commune_id" :options="communes" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" :placeholder="'Sélectionner...'" /></div>
        <div class="col-md-4"><label>Département</label><SearchableSelect v-model="form.department_id" :options="departments" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" :placeholder="'Sélectionner...'" /></div>
        <div class="col-md-4"><label>Région</label><SearchableSelect v-model="form.region_id" :options="regions" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" :placeholder="'Sélectionner...'" /></div>
        <div class="col-md-4"><label>Pays</label><SearchableSelect v-model="form.country_id" :options="pays" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" :placeholder="'Sélectionner...'" /></div>
        <!-- Section 3: Formation -->
        <div class="col-12"><h5 class="section-title">🎓 Formation et Diplômes</h5></div>
        <div class="col-md-4"><label>Plus Haut Diplôme</label><input v-model="form.highest_diploma" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Spécialité</label><input v-model="form.speciality" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Année d'Obtention</label><input v-model="form.year_obtained" type="number" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Langues</label><input v-model="form.languages" type="text" class="form-control" :disabled="isReadOnly" placeholder="Ex: Français, Anglais" /></div>
        <div class="col-md-4"><label>Catégorie Enseignant</label><input v-model="form.teacher_category" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Spécialité Pédagogique</label><input v-model="form.teaching_speciality" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <!-- Section 4: Professionnel -->
        <div class="col-12"><h5 class="section-title">💼 Informations Professionnelles</h5></div>
        <div class="col-md-4"><label>Diplôme Professionnel</label><input v-model="form.diplome" type="text" class="form-control" :disabled="isReadOnly" /></div>
        <div class="col-md-4"><label>Date d'Embauche</label><input v-model="form.date_embauche" type="date" class="form-control" :disabled="isReadOnly" /></div>
        <!-- Section 5: Assignations Académiques -->
        <div class="col-12"><h5 class="section-title">📚 Assignations Académiques</h5></div>
        <div class="col-12">
            <label>Cycles d'Enseignement</label>
            <div class="checkbox-group" :style="{ pointerEvents: isReadOnly ? 'none' : 'auto', opacity: isReadOnly ? 0.6 : 1 }">
                <div v-for="cycle in cycles" :key="cycle.id" class="form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        :id="'cycle-' + cycle.id"
                        :checked="form.cycles_ids?.includes(cycle.id)"
                        @change="(e) => {
                            if (!form.cycles_ids) form.cycles_ids = [];
                            if (e.target.checked) {
                                if (!form.cycles_ids.includes(cycle.id)) form.cycles_ids.push(cycle.id);
                            } else {
                                form.cycles_ids = form.cycles_ids.filter(id => id !== cycle.id);
                            }
                        }"
                        :disabled="isReadOnly"
                    />
                    <label class="form-check-label" :for="'cycle-' + cycle.id">{{ cycle.libelle }}</label>
                </div>
            </div>
        </div>
        <div class="col-12">
            <label>Niveaux</label>
            <div class="checkbox-group" :style="{ pointerEvents: isReadOnly ? 'none' : 'auto', opacity: isReadOnly ? 0.6 : 1 }">
                <div v-for="niveau in niveaux" :key="niveau.id" class="form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        :id="'niveau-' + niveau.id"
                        :checked="form.niveaux_ids?.includes(niveau.id)"
                        @change="(e) => {
                            if (!form.niveaux_ids) form.niveaux_ids = [];
                            if (e.target.checked) {
                                if (!form.niveaux_ids.includes(niveau.id)) form.niveaux_ids.push(niveau.id);
                            } else {
                                form.niveaux_ids = form.niveaux_ids.filter(id => id !== niveau.id);
                            }
                        }"
                        :disabled="isReadOnly"
                    />
                    <label class="form-check-label" :for="'niveau-' + niveau.id">{{ niveau.libelle }}</label>
                </div>
            </div>
        </div>
        <div class="col-12">
            <label>Classes</label>
            <div class="checkbox-group" :style="{ pointerEvents: isReadOnly ? 'none' : 'auto', opacity: isReadOnly ? 0.6 : 1 }">
                <div v-for="classe in classes" :key="classe.id" class="form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        :id="'classe-' + classe.id"
                        :checked="form.classes_ids?.includes(classe.id)"
                        @change="(e) => {
                            if (!form.classes_ids) form.classes_ids = [];
                            if (e.target.checked) {
                                if (!form.classes_ids.includes(classe.id)) form.classes_ids.push(classe.id);
                            } else {
                                form.classes_ids = form.classes_ids.filter(id => id !== classe.id);
                            }
                        }"
                        :disabled="isReadOnly"
                    />
                    <label class="form-check-label" :for="'classe-' + classe.id">{{ classe.libelle }}</label>
                </div>
            </div>
        </div>
        <!-- Section 6: Matières (up to 7) -->
        <div class="col-12"><h5 class="section-title">📖 Matières d'Enseignement</h5></div>
        <div v-for="i in 7" :key="i" class="col-md-4">
            <label>Matière {{ i }}</label>
            <SearchableSelect v-model="form.matieres_data[i - 1]" :options="matieres" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" :placeholder="'Sélectionner une matière...'" />
        </div>
        <!-- Section 7: Statut -->
        <div class="col-12"><h5 class="section-title">🔖 Statut</h5></div>
        <div class="col-md-4"><label>Statut</label><SearchableSelect v-model="form.statut" :options="statusOptions" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" /></div>
    </div>
</template>
<style scoped>
.section-title {
    font-weight: 700;
    margin-top: 1.5rem;
    color: #2c3e50;
    border-bottom: 3px solid #007bff;
    padding-bottom: 0.75rem;
    font-size: 1.1rem;
}
.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 0.5rem;
}
.form-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 0 1 calc(50% - 0.5rem);
}
.form-check-input {
    margin: 0;
    cursor: pointer;
}
.form-check-label {
    margin: 0;
    cursor: pointer;
    font-size: 0.95rem;
}
.form-check-input:disabled + .form-check-label {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
