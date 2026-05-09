<template>
  <div class="row g-3 custom-input">
    <!-- Section 1: Informations Générales -->
    <div class="col-12">
      <h6 class="mb-3">{{ $t("common.basic_information") }}</h6>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.sujet") }}</label>
      <input
        v-model="form.sujet"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': errors.sujet }"
        :disabled="disabled"
      />
      <div v-if="errors.sujet" class="invalid-feedback">
        {{ errors.sujet }}
      </div>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.langue") }}</label>
      <input
        v-model="form.langue"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': errors.langue }"
        :disabled="disabled"
      />
      <div v-if="errors.langue" class="invalid-feedback">
        {{ errors.langue }}
      </div>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.niveau") }}</label>
      <SearchableSelect
        v-model="form.niveau_id"
        :options="niveaux"
        option-label="libelle"
        option-value="id"
        :placeholder="$t('fields.select_niveau') || 'Sélectionner un niveau'"
        :disabled="disabled"
      />
      <div v-if="errors.niveau_id" class="invalid-feedback d-block">
        {{ errors.niveau_id }}
      </div>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.type_manuel") }}</label>
      <input
        v-model="form.type_manuel"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': errors.type_manuel }"
        :disabled="disabled"
      />
      <div v-if="errors.type_manuel" class="invalid-feedback">
        {{ errors.type_manuel }}
      </div>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.titre_manuel") }}</label>
      <input
        v-model="form.titre_manuel"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': errors.titre_manuel }"
        :disabled="disabled"
      />
      <div v-if="errors.titre_manuel" class="invalid-feedback">
        {{ errors.titre_manuel }}
      </div>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.auteurs") }}</label>
      <input
        v-model="form.auteurs"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': errors.auteurs }"
        :disabled="disabled"
      />
      <div v-if="errors.auteurs" class="invalid-feedback">
        {{ errors.auteurs }}
      </div>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.editeurs") }}</label>
      <input
        v-model="form.editeurs"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': errors.editeurs }"
        :disabled="disabled"
      />
      <div v-if="errors.editeurs" class="invalid-feedback">
        {{ errors.editeurs }}
      </div>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.annee_edition") }}</label>
      <input
        v-model="form.annee_edition"
        type="number"
        min="1900"
        :max="currentYear + 10"
        class="form-control"
        :class="{ 'is-invalid': errors.annee_edition }"
        :disabled="disabled"
      />
      <div v-if="errors.annee_edition" class="invalid-feedback">
        {{ errors.annee_edition }}
      </div>
    </div>

    <!-- Section 2: Stocks -->
    <div class="col-12">
      <h6 class="mb-3">{{ $t("common.stocks_section") }}</h6>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.quantite") }}</label>
      <input
        v-model="form.quantite"
        type="number"
        min="0"
        class="form-control"
        :class="{ 'is-invalid': errors.quantite }"
        :disabled="disabled"
      />
      <div v-if="errors.quantite" class="invalid-feedback">
        {{ errors.quantite }}
      </div>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.sorties") }}</label>
      <input
        v-model="form.sorties"
        type="number"
        min="0"
        class="form-control"
        :class="{ 'is-invalid': errors.sorties }"
        :disabled="disabled"
      />
      <div v-if="errors.sorties" class="invalid-feedback">
        {{ errors.sorties }}
      </div>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.disponibles") }}</label>
      <input
        v-model="form.disponibles"
        type="number"
        min="0"
        class="form-control"
        :class="{ 'is-invalid': errors.disponibles }"
        :disabled="disabled"
      />
      <div v-if="errors.disponibles" class="invalid-feedback">
        {{ errors.disponibles }}
      </div>
    </div>

    <!-- Section 3: État -->
    <div class="col-12">
      <h6 class="mb-3">{{ $t("common.status_section") }}</h6>
    </div>

    <div class="col-sm-6">
      <label class="form-label">{{ $t("fields.etat") }}</label>
      <SearchableSelect
        v-model="form.etat"
        :options="[
          { id: 'actif', label: $t('common.actif') },
          { id: 'inactif', label: $t('common.inactif') }
        ]"
        option-label="label"
        option-value="id"
        :disabled="disabled"
      />
      <div v-if="errors.etat" class="invalid-feedback d-block">
        {{ errors.etat }}
      </div>
    </div>
  </div>
</template>

<script setup>
import SearchableSelect from "@/Components/Common/SearchableSelect.vue";
import { computed } from "vue";

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  niveaux: {
    type: Array,
    default: () => [],
  },
});

const currentYear = computed(() => new Date().getFullYear());
</script>
