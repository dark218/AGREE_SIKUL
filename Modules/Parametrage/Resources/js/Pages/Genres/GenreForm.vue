<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    mode: {
        type: String, default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const isReadOnly = props.mode === 'show';

const statutOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const presetColors = [
    { id: '#0b5697', libelle: 'Bleu (M)' },
    { id: '#e5590c', libelle: 'Orange (F)' },
    { id: '#64748b', libelle: 'Gris (Autre)' },
    { id: '#8b5cf6', libelle: 'Violet' },
    { id: '#10b981', libelle: 'Vert' },
    { id: '#f59e0b', libelle: 'Ambre' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <div class="col-sm-6">
            <label class="form-label fw-medium">Code <span class="text-danger">*</span></label>
            <input
                v-model="form.code"
                type="text"
                class="form-control"
                :disabled="isReadOnly"
                placeholder="Ex: M, F, AUTRE"
                maxlength="20"
                style="text-transform: uppercase;"
            />
            <small class="text-muted">Identifiant unique (converti en majuscules)</small>
            <span v-if="form.errors?.code" class="text-danger small mt-1 d-block">{{ form.errors.code }}</span>
        </div>

        <div class="col-sm-6">
            <label class="form-label fw-medium">Libellé <span class="text-danger">*</span></label>
            <input
                v-model="form.libelle"
                type="text"
                class="form-control"
                :disabled="isReadOnly"
                placeholder="Ex: Masculin, Féminin, Autre"
            />
            <span v-if="form.errors?.libelle" class="text-danger small mt-1 d-block">{{ form.errors.libelle }}</span>
        </div>

        <div class="col-sm-4">
            <label class="form-label fw-medium">Symbole</label>
            <input
                v-model="form.symbole"
                type="text"
                class="form-control"
                :disabled="isReadOnly"
                placeholder="M / F / X"
                maxlength="5"
            />
            <small class="text-muted">Affichage compact dans les tableaux</small>
        </div>

        <div class="col-sm-4">
            <label class="form-label fw-medium">Couleur</label>
            <div class="d-flex gap-2 align-items-center">
                <SearchableSelect
                    v-model="form.couleur"
                    :options="presetColors"
                    optionValue="id"
                    optionLabel="libelle"
                    placeholder="Choisir une couleur"
                    :disabled="isReadOnly"
                    class="flex-grow-1"
                />
                <div
                    v-if="form.couleur"
                    :style="{ background: form.couleur, width: '32px', height: '32px', borderRadius: '8px', border: '1px solid #cbd5e1' }"
                ></div>
            </div>
        </div>

        <div class="col-sm-4">
            <label class="form-label fw-medium">Ordre d'affichage</label>
            <input
                v-model.number="form.ordre"
                type="number"
                class="form-control"
                :disabled="isReadOnly"
                min="0"
                placeholder="1"
            />
        </div>

        <div class="col-sm-6">
            <label class="form-label fw-medium">Statut <span class="text-danger">*</span></label>
            <SearchableSelect
                v-model="form.etat"
                :options="statutOptions"
                optionValue="id"
                optionLabel="libelle"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.etat" class="text-danger small mt-1 d-block">{{ form.errors.etat }}</span>
        </div>
    </div>
</template>
