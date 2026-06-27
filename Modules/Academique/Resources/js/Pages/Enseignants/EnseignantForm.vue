<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import { useGeoCascade } from '@/Composables/useGeoCascade';

const { t } = useI18n();

const props = defineProps({
    form: Object,
    mode: { type: String, default: 'create' },
    communes: Array,
    departements: Array,
    regions: Array,
    pays: Array,
    categoriesEnseignant: Array,
    matieres: Array,
    cycles: Array,
    niveaux: Array,
    classes: Array,
});

const photoPreview = ref(null);
const photoLoadError = ref(false);
const isReadOnly = computed(() => props.mode === 'show');

// Cascade géographique : Commune → Département → Région → Pays
useGeoCascade(props.form, {
    quartiers: () => [],
    communes: () => props.communes,
    departements: () => props.departements,
    regions: () => props.regions,
});

// Handle photo file change with preview using FileReader
const handlePhotoChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        props.form.photo = file;
        // Create preview using FileReader
        const reader = new FileReader();
        reader.onload = (event) => {
            photoPreview.value = event.target?.result || null;
        };
        reader.readAsDataURL(file);
    } else {
        props.form.photo = null;
        photoPreview.value = null;
    }
};

// Calculate age from date_of_birth
const age = computed(() => {
    if (!props.form.date_of_birth) return null;
    const birthDate = new Date(props.form.date_of_birth);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age >= 0 ? age : null;
});
</script>

<template>
    <div class="enseignant-form">
        <!-- Section 1: Identité Personnelle -->
        <h5 class="section-title mb-30">{{ t('fields.identity') }}</h5>
        <div class="row">
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.nom') }} *</label>
                <input v-model="form.nom" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.prenoms') }} *</label>
                <input v-model="form.prenoms" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.nom_restituer') }}</label>
                <input v-model="form.nom_restituer" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.nom_jeune_fille') }}</label>
                <input v-model="form.nom_jeune_fille" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.gender') }}</label>
                <select v-model="form.gender" class="form-control" :disabled="isReadOnly">
                    <option value="">{{ t('actions.select') }}</option>
                    <option value="M">Masculin</option>
                    <option value="F">Féminin</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.marital_status') }}</label>
                <select v-model="form.marital_status" class="form-control" :disabled="isReadOnly">
                    <option value="">{{ t('actions.select') }}</option>
                    <option value="celibataire">Célibataire</option>
                    <option value="marie">Marié</option>
                    <option value="divorce">Divorcé</option>
                    <option value="veuf">Veuf</option>
                </select>
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.date_of_birth') }}</label>
                <input v-model="form.date_of_birth" type="date" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.age') }}</label>
                <input type="number" :value="age" class="form-control" disabled />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.place_of_birth') }}</label>
                <input v-model="form.place_of_birth" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.commune_naissance') }}</label>
                <SearchableSelect
                    v-model="form.commune_id"
                    :options="communes"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select')"
                    :disabled="isReadOnly"
                />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.departement_naissance') }}</label>
                <SearchableSelect
                    v-model="form.department_id"
                    :options="departements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select')"
                    :disabled="isReadOnly"
                />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.region_naissance') }}</label>
                <SearchableSelect
                    v-model="form.region_id"
                    :options="regions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select')"
                    :disabled="isReadOnly"
                />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.pays_naissance') }}</label>
                <SearchableSelect
                    v-model="form.country_id"
                    :options="pays"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select')"
                    :disabled="isReadOnly"
                />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.nationalite') }}</label>
                <input v-model="form.nationalite" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
        </div>

        <!-- Section 2: Formation Académique -->
        <h5 class="section-title mb-30 mt-40">{{ t('fields.academic_formation') }}</h5>
        <div class="row">
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.highest_diploma') }}</label>
                <input v-model="form.highest_diploma" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.speciality') }}</label>
                <input v-model="form.speciality" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.year_obtained') }}</label>
                <input v-model="form.year_obtained" type="number" min="1900" :max="new Date().getFullYear()" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.languages') }}</label>
                <select v-model="form.languages" multiple class="form-control" :disabled="isReadOnly">
                    <option value="Français">Français</option>
                    <option value="Anglais">Anglais</option>
                    <option value="Arabe">Arabe</option>
                    <option value="Portugais">Portugais</option>
                    <option value="Espagnol">Espagnol</option>
                    <option value="Autre">Autre</option>
                </select>
            </div>
            <div class="col-md-12 mb-20">
                <label class="mb-10">{{ t('fields.teaching_speciality') }}</label>
                <textarea v-model="form.teaching_speciality" class="form-control" rows="3" :disabled="isReadOnly"></textarea>
            </div>
        </div>

        <!-- Section 3: Informations Professionnelles -->
        <h5 class="section-title mb-30 mt-40">{{ t('fields.professional_info') }}</h5>
        <div class="row">
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.matricule') }}</label>
                <input v-model="form.matricule" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.num_enseignant') }}</label>
                <input v-model="form.num_enseignant" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.type_contrat') }}</label>
                <select v-model="form.type_contrat" class="form-control" :disabled="isReadOnly">
                    <option value="">{{ t('actions.select') }}</option>
                    <option value="cdi">CDI</option>
                    <option value="cdd">CDD</option>
                    <option value="vacataire">Vacataire</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.date_embauche') }}</label>
                <input v-model="form.date_embauche" type="date" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.categorie_enseignant') }}</label>
                <SearchableSelect
                    v-model="form.categorie_enseignant_id"
                    :options="categoriesEnseignant"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select')"
                    :disabled="isReadOnly"
                />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.email') }}</label>
                <input v-model="form.email" type="email" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.telephone') }}</label>
                <input v-model="form.telephone" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.teacher_category') }}</label>
                <input v-model="form.teacher_category" type="text" class="form-control" :disabled="isReadOnly" />
            </div>
        </div>

        <!-- Section 4: Domaines d'Enseignement -->
        <h5 class="section-title mb-30 mt-40">{{ t('fields.teaching_domains') }}</h5>
        <div class="row">
            <div class="col-md-12 mb-20">
                <h6 class="mb-20">Matières (jusqu'à 7)</h6>
            </div>
            <div v-for="i in 7" :key="`matiere-${i}`" class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.matiere') }} {{ i }}</label>
                <SearchableSelect
                    v-model="form[`matiere_${i}_id`]"
                    :options="matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select')"
                    :disabled="isReadOnly"
                />
            </div>
            <div class="col-md-12 mb-20 mt-20">
                <h6 class="mb-20">Cycles (jusqu'à 2)</h6>
            </div>
            <div v-for="i in 2" :key="`cycle-${i}`" class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.cycle') }} {{ i }}</label>
                <SearchableSelect
                    v-model="form[`cycle_${i}_id`]"
                    :options="cycles"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select')"
                    :disabled="isReadOnly"
                />
            </div>
            <div class="col-md-12 mb-20 mt-20">
                <h6 class="mb-20">Niveaux (jusqu'à 4)</h6>
            </div>
            <div v-for="i in 4" :key="`niveau-${i}`" class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.niveau') }} {{ i }}</label>
                <SearchableSelect
                    v-model="form[`niveau_${i}_id`]"
                    :options="niveaux"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select')"
                    :disabled="isReadOnly"
                />
            </div>
            <div class="col-md-12 mb-20 mt-20">
                <h6 class="mb-20">Salles de cours (jusqu'à 5)</h6>
            </div>
            <div v-for="i in 5" :key="`classe-${i}`" class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.classe') }} (Salle) {{ i }}</label>
                <SearchableSelect
                    v-model="form[`classe_${i}_id`]"
                    :options="classes"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select')"
                    :disabled="isReadOnly"
                />
            </div>
        </div>

        <!-- Section 5: Photo & Statut -->
        <h5 class="section-title mb-30 mt-40">{{ t('fields.status') }}</h5>
        <div class="row">
            <div class="col-md-6 mb-20">
                <label class="mb-10">Photo</label>
                <div v-if="!isReadOnly" class="mb-10">
                    <input
                        type="file"
                        class="form-control"
                        accept="image/*"
                        @change="handlePhotoChange"
                    />
                    <small v-if="form.photo?.name" class="text-muted d-block mt-2">
                        <i class="fa fa-file"></i> {{ form.photo.name }}
                    </small>
                    <small v-else-if="form.photo && typeof form.photo === 'string'" class="text-muted d-block mt-2">
                        <i class="fa fa-check-circle text-success"></i> Photo existante: {{ form.photo }}
                    </small>
                    <small v-else class="text-muted d-block mt-2">
                        <i class="fa fa-camera"></i> Aucun fichier sélectionné
                    </small>
                </div>
                <div v-else class="mb-10">
                    <small v-if="form.photo" class="text-muted d-block">
                        <i class="fa fa-check-circle text-success"></i> Photo: {{ form.photo }}
                    </small>
                    <small v-else class="text-muted d-block">
                        <i class="fa fa-image"></i> Aucune photo
                    </small>
                </div>

                <!-- Photo Preview Container -->
                <div class="photo-preview-container mt-3 p-3 bg-light rounded">
                    <!-- New Photo Preview (from file upload) -->
                    <div v-if="photoPreview" class="text-center">
                        <img
                            :src="photoPreview"
                            :alt="t('fields.photo')"
                            class="img-thumbnail"
                            style="max-width: 180px; max-height: 180px; object-fit: cover;"
                        />
                        <small class="d-block mt-2 text-success">Nouvelle photo prête à être sauvegardée</small>
                    </div>

                    <!-- Existing Photo from Database -->
                    <div v-else-if="form.photo && typeof form.photo === 'string'" class="text-center">
                        <img
                            :src="'/storage/' + form.photo"
                            :alt="t('fields.photo')"
                            class="img-thumbnail"
                            style="max-width: 180px; max-height: 180px; object-fit: cover;"
                            @error="photoLoadError = true"
                        />
                        <small class="d-block mt-2 text-info">Photo actuelle</small>
                    </div>

                    <!-- Placeholder when no photo -->
                    <div v-else class="text-center">
                        <div class="placeholder-image">
                            <i class="fa fa-image fa-5x text-muted"></i>
                        </div>
                        <small class="d-block mt-2 text-muted">Aucune photo</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-20">
                <label class="mb-10">{{ t('fields.statut') }} *</label>
                <select v-model="form.statut" class="form-control" :disabled="isReadOnly">
                    <option value="">{{ t('actions.select') }}</option>
                    <option value="actif">{{ t('common.active') }}</option>
                    <option value="suspendu">{{ t('common.suspended') }}</option>
                    <option value="conge">Congé</option>
                    <option value="retraite">Retraite</option>
                    <option value="demission">Démission</option>
                </select>
            </div>
        </div>
    </div>
</template>

<style scoped>
.enseignant-form {
    padding: 20px 0;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.form-control,
select {
    padding: 8px 12px;
    border-radius: 4px;
}

label {
    font-weight: 500;
    color: #555;
}

h6 {
    font-weight: 600;
    color: #666;
}

.img-thumbnail {
    border: 1px solid #ddd;
    padding: 8px;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.photo-preview-container {
    border: 2px dashed #e0e0e0;
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.placeholder-image {
    padding: 20px;
    text-align: center;
}
</style>
