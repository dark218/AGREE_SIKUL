<template>
  <!--  COMPOSANT POUR AFFICHAGE CONDITIONNEL DE CHAMPS -->
  <div v-if="shouldShow" :class="wrapperClass">
    <!-- Label conditionnel -->
    <label v-if="label" :for="fieldId" class="form-label">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- Input simple -->
    <input
      v-if="
        type === 'text' ||
        type === 'email' ||
        type === 'tel' ||
        type === 'number'
      "
      :id="fieldId"
      :type="type"
      :value="modelValue"
      @input="
        type === 'number'
          ? onNumberInput($event)
          : $emit('update:modelValue', $event.target.value)
      "
      :class="inputClass"
      :placeholder="placeholder"
      :required="required"
      :readonly="readonly"
      :disabled="disabled"
      :min="min"
      :max="max"
      :step="step"
    />

    <!-- Textarea -->
    <textarea
      v-else-if="type === 'textarea'"
      :id="fieldId"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :class="inputClass"
      :placeholder="placeholder"
      :required="required"
      :readonly="readonly"
      :disabled="disabled"
      :rows="rows || 3"
    ></textarea>

    <!-- Select dropdown -->
    <select
      v-else-if="type === 'select'"
      :id="fieldId"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
      :class="inputClass"
      :required="required"
      :disabled="disabled"
    >
      <option value="" v-if="!required">
        {{ placeholder || "Sélectionner..." }}
      </option>
      <option
        v-for="option in options"
        :key="getOptionValue(option)"
        :value="getOptionValue(option)"
      >
        {{ getOptionLabel(option) }}
      </option>
    </select>

    <!-- Searchable select (select2-like) -->
    <div v-else-if="type === 'searchable-select'">
      <SearchableSelect
        :options="options"
        :option-value="optionValue"
        :option-label="optionLabel"
        :model-value="modelValue"
        @update:modelValue="$emit('update:modelValue', $event)"
        :placeholder="placeholder || 'Sélectionner…'"
        :search-placeholder="'Rechercher…'"
      />
    </div>

    <!-- Checkbox -->
    <div v-else-if="type === 'checkbox'" class="flex items-center">
      <input
        :id="fieldId"
        type="checkbox"
        :checked="modelValue"
        @change="$emit('update:modelValue', $event.target.checked)"
        class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500"
        :disabled="disabled"
      />
      <label
        v-if="checkboxLabel"
        :for="fieldId"
        class="ml-2 text-sm text-gray-700"
      >
        {{ checkboxLabel }}
      </label>
    </div>

    <!-- Radio buttons -->
    <div v-else-if="type === 'radio'" class="space-y-2">
      <div
        v-for="option in options"
        :key="getOptionValue(option)"
        class="flex items-center"
      >
        <input
          :id="`${fieldId}-${getOptionValue(option)}`"
          type="radio"
          :name="fieldId"
          :value="getOptionValue(option)"
          :checked="modelValue === getOptionValue(option)"
          @change="$emit('update:modelValue', getOptionValue(option))"
          class="border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500"
          :disabled="disabled"
        />
        <label
          :for="`${fieldId}-${getOptionValue(option)}`"
          class="ml-2 text-sm text-gray-700"
        >
          {{ getOptionLabel(option) }}
        </label>
      </div>
    </div>

    <!-- Date -->
    <input
      v-else-if="type === 'date' || type === 'datetime-local'"
      :id="fieldId"
      :type="type"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :class="inputClass"
      :required="required"
      :readonly="readonly"
      :disabled="disabled"
      :min="min"
      :max="max"
    />

    <!-- File upload -->
    <input
      v-else-if="type === 'file'"
      :id="fieldId"
      type="file"
      @change="handleFileChange"
      :class="inputClass"
      :accept="accept"
      :multiple="multiple"
      :disabled="disabled"
    />

    <!-- Message d'erreur -->
    <div v-if="error" class="mt-2 text-sm text-red-600">
      {{ displayError }}
    </div>

    <!-- Message d'aide -->
    <div v-if="helpText" class="mt-1 text-sm text-gray-500">
      {{ helpText }}
    </div>

    <!-- Champ en lecture seule avec style spécial -->
    <div
      v-if="readonly && showReadonlyValue"
      class="mt-1 p-2 bg-gray-50 rounded text-sm text-gray-600"
    >
      {{ readonlyDisplayValue || modelValue }}
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import SearchableSelect from "@/Components/SearchableSelect.vue";

const props = defineProps({
  // Contrôle d'affichage
  showForRoles: {
    type: Array,
    default: () => [],
  },
  hideForRoles: {
    type: Array,
    default: () => [],
  },
  hideForTypeUser: {
    type: Array,
    default: () => [],
  },
  showForPermissions: {
    type: Array,
    default: () => [],
  },
  showWhen: {
    type: Function,
    default: null,
  },

  // Propriétés du champ
  fieldName: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    default: "text",
  },
  modelValue: {
    default: "",
  },
  label: String,
  placeholder: String,
  required: {
    type: Boolean,
    default: false,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },

  // Options pour select/radio
  options: {
    type: Array,
    default: () => [],
  },
  optionValue: {
    type: String,
    default: "value",
  },
  optionLabel: {
    type: String,
    default: "label",
  },

  // Styles
  wrapperClass: {
    type: String,
    default: "",
  },
  inputClass: {
    type: String,
    default: "form-input",
  },

  // Spécifique aux types
  rows: Number,
  checkboxLabel: String,
  accept: String,
  multiple: Boolean,
  // Contraintes numériques
  min: [Number, String],
  max: [Number, String],
  step: [Number, String],

  // Messages
  error: String,
  helpText: String,

  // Lecture seule
  showReadonlyValue: {
    type: Boolean,
    default: false,
  },
  readonlyDisplayValue: String,
});

const emit = defineEmits(["update:modelValue", "file-change"]);

const page = usePage();

// 🎯 LOGIQUE D'AFFICHAGE CONDITIONNEL
const shouldShow = computed(() => {
  const user = page.props.auth.user;
  if (!user) return false;

  const userRoles = user.roles || [];
  const userPermissions = user.permissions || [];

  // Vérifier les rôles autorisés
  if (props.showForRoles.length > 0) {
    const hasRole = userRoles.some((role) => props.showForRoles.includes(role));
    if (!hasRole) return false;
  }

  // Vérifier les rôles interdits
  if (props.hideForRoles.length > 0) {
    const hasRestrictedRole = userRoles.some((role) =>
      props.hideForRoles.includes(role)
    );
    if (hasRestrictedRole) return false;
  }

  // Vérifier le type d'utilisateur
  if (props.hideForTypeUser.length > 0) {
    const hasRestrictedType = props.hideForTypeUser.includes(
      user.typeutilisateur_id
    );

    if (hasRestrictedType) return false;
  }

  // Vérifier les permissions
  if (props.showForPermissions.length > 0) {
    const hasPermission = userPermissions.some((permission) =>
      props.showForPermissions.includes(permission)
    );
    if (!hasPermission) return false;
  }

  // Condition personnalisée
  if (props.showWhen && typeof props.showWhen === "function") {
    return props.showWhen(user, page.props);
  }

  return true;
});

const fieldId = computed(() => `field-${props.fieldName}`);

// 🔧 HELPERS POUR OPTIONS
const getOptionValue = (option) => {
  if (typeof option === "object") {
    return option[props.optionValue] || option.id || option.value;
  }
  return option;
};

const getOptionLabel = (option) => {
  if (typeof option === "object") {
    return (
      option[props.optionLabel] || option.label || option.name || option.libelle
    );
  }
  return option;
};

const handleFileChange = (event) => {
  const file = event.target.files[0];
  emit("update:modelValue", file);
  emit("file-change", file);
};

// Empêche les valeurs inférieures à min et supérieures à max pour les inputs number
const onNumberInput = (event) => {
  const raw = event.target.value;
  // Normalise les décimales FR ("," -> ".") avant conversion
  const normalized = typeof raw === "string" ? raw.replace(",", ".") : raw;
  let num = parseFloat(normalized);
  if (isNaN(num)) {
    emit("update:modelValue", raw);
    return;
  }
  if (props.min !== undefined && num < Number(props.min)) {
    num = Number(props.min);
  }
  if (props.max !== undefined && num > Number(props.max)) {
    num = Number(props.max);
  }
  event.target.value = num;
  emit("update:modelValue", num);
};

// Message d'erreur utilisateur lisible
const displayError = computed(() => {
  const raw = props.error || "";
  if (!raw) return "";
  const baseLabel = props.label || "Ce champ";
  const key = String(raw).toLowerCase();
  if (key.includes("required")) return `${baseLabel} est requis.`;
  if (key.includes("invalid") || key.includes("format"))
    return `${baseLabel} est invalide.`;
  if (key.includes("date")) return `Veuillez saisir une date valide.`;
  return raw; // fallback au message fourni si déjà lisible
});
</script>
