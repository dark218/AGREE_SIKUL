<script setup>
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    planComptes: {
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

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Plan des comptes form
const newCompte = ref({
    numero_compte: '',
    libelle_compte: '',
    libelle_court: '',
    compte_parent_id: null,
});

const planComptesData = ref(props.planComptes || []);

const emit = defineEmits(['update:plan-comptes']);

const addCompte = () => {
    if (newCompte.value.numero_compte && newCompte.value.libelle_compte) {
        planComptesData.value.push({
            ...newCompte.value,
            id: Date.now(),
            etat: 'actif',
        });
        newCompte.value = {
            numero_compte: '',
            libelle_compte: '',
            libelle_court: '',
            compte_parent_id: null,
        };
        emit('update:plan-comptes', planComptesData.value);
    }
};

const removeCompte = (id) => {
    planComptesData.value = planComptesData.value.filter(c => c.id !== id);
    emit('update:plan-comptes', planComptesData.value);
};

// Formater les comptes pour affichage
const compteParentOptions = () => {
    return planComptesData.value.map(c => ({
        id: c.id,
        libelle: `${c.numero_compte} - ${c.libelle_compte}`
    }));
};
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Code Groupe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code_groupe" class="form-control" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly">
                <span v-if="form.errors?.code_groupe" class="text-danger">
                    <strong>{{ form.errors.code_groupe }}</strong>
                </span>
            </div>
        </div>

        <!-- Libellé Groupes -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.label') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle_groupes" class="form-control" :placeholder="t('fields.label') || 'Libellé'" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle_groupes" class="text-danger">
                    <strong>{{ form.errors.libelle_groupes }}</strong>
                </span>
            </div>
        </div>

        <!-- Nombre Comptes -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nombre_comptes') || 'Nombre Comptes' }}</label>
                <input type="number" v-model.number="form.nombre_comptes" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.nombre_comptes" class="text-danger">
                    <strong>{{ form.errors.nombre_comptes }}</strong>
                </span>
            </div>
        </div>

        <!-- Liste Comptes -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.liste_comptes') || 'Liste Comptes' }}</label>
                <textarea v-model="form.liste_comptes" class="form-control" rows="3" :placeholder="t('fields.liste_comptes')" :disabled="isReadOnly"></textarea>
                <span v-if="form.errors?.liste_comptes" class="text-danger">
                    <strong>{{ form.errors.liste_comptes }}</strong>
                </span>
            </div>
        </div>

        <!-- Description -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('fields.description') || 'Description' }}</label>
                <textarea v-model="form.description" class="form-control" rows="3" :placeholder="t('fields.description')" :disabled="isReadOnly"></textarea>
                <span v-if="form.errors?.description" class="text-danger">
                    <strong>{{ form.errors.description }}</strong>
                </span>
            </div>
        </div>

        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('fields.status')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>

        <!-- Plan des Comptes Section -->
        <div class="col-12 mt-4">
            <hr>
            <h5 class="mb-3">{{ t('modules.finances.plan_comptes.title') || 'Plan des Comptes' }}</h5>
        </div>

        <!-- Formulaire Ajout Compte -->
        <div v-if="!isReadOnly" class="col-12">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">{{ t('actions.add') || 'Ajouter' }} {{ t('common.compte') || 'Compte' }}</h6>
                    <div class="row g-2">
                        <!-- Numéro Compte -->
                        <div class="col-sm-3">
                            <div class="mb-2">
                                <label>{{ t('fields.numero_compte') || 'N° Compte' }} <span class="text-danger">*</span></label>
                                <input type="text" v-model="newCompte.numero_compte" class="form-control" :placeholder="t('fields.numero_compte')" />
                            </div>
                        </div>

                        <!-- Libellé Compte -->
                        <div class="col-sm-3">
                            <div class="mb-2">
                                <label>{{ t('fields.libelle_compte') || 'Libellé Compte' }} <span class="text-danger">*</span></label>
                                <input type="text" v-model="newCompte.libelle_compte" class="form-control" :placeholder="t('fields.libelle_compte')" />
                            </div>
                        </div>

                        <!-- Libellé Court -->
                        <div class="col-sm-2">
                            <div class="mb-2">
                                <label>{{ t('fields.libelle_court') || 'Libellé Court' }}</label>
                                <input type="text" v-model="newCompte.libelle_court" class="form-control" :placeholder="t('fields.libelle_court')" />
                            </div>
                        </div>

                        <!-- Compte Parent -->
                        <div class="col-sm-2">
                            <div class="mb-2">
                                <label>{{ t('fields.compte_parent') || 'Compte Parent' }}</label>
                                <SearchableSelect
                                    v-model="newCompte.compte_parent_id"
                                    :options="compteParentOptions()"
                                    optionValue="id"
                                    optionLabel="libelle"
                                    :placeholder="t('fields.compte_parent')"
                                />
                            </div>
                        </div>

                        <!-- Bouton Ajouter -->
                        <div class="col-sm-2">
                            <div class="mb-2">
                                <label>&nbsp;</label>
                                <button type="button" @click="addCompte" class="btn btn-primary w-100">
                                    <i class="fa fa-plus"></i> {{ t('actions.add') || 'Ajouter' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
