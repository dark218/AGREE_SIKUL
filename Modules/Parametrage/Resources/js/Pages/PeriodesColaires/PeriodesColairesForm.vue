<!--
  PeriodesColairesForm.vue — Formulaire simplifié (Phase UX 2026-07-07).

  Le user a demandé un formulaire minimal avec les 3 champs strictement
  nécessaires : Code, Libellé, Statut. Les colonnes complémentaires
  (annee_scolaire_id, dates, durée, cycle_id, ...) sont TOUTES nullable
  en DB — on peut donc les omettre en création rapide et les compléter
  plus tard via un autre écran (calendrier ou paramètres avancés).

  Rationale : les périodes scolaires servent uniquement de référentiel
  discriminant (T1/T2/T3, S1/S2, ...) pour les bulletins et évaluations.
  L'année scolaire est déjà portée par la Bulletin/Evaluation elle-même.
-->
<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const isReadOnly = computed(() => props.mode === 'show');

const statusOptions = [
    { id: 'actif',   libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.code"
                    type="text"
                    class="form-control"
                    placeholder="Ex : T1, S1, ANN"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>

        <!-- Libellé -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.libelle') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.libelle"
                    type="text"
                    class="form-control"
                    placeholder="Ex : Trimestre 1"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>

        <!-- Statut de disponibilité -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut de disponibilité' }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
