<template>
  <div class="ss-wrapper" data-searchable-select ref="root">
    <input
      ref="input"
      class="ss-input"
      :class="{ 'ss-open': open, 'ss-has-value': hasValue, 'ss-disabled': disabled, 'ss-clearable': hasValue && !disabled }"
      type="text"
      :value="inputDisplay"
      :placeholder="open ? searchPlaceholder : placeholder"
      autocomplete="off"
      :disabled="disabled"
      @focus="openList"
      @click="openList"
      @input="onInput"
      @blur="onBlur"
    />
    <!-- Bouton vider (X) -->
    <span
      v-if="hasValue && !disabled"
      class="ss-clear"
      title="Vider"
      @mousedown.prevent
      @click.stop="clearSelection"
    >
      <i class="fa fa-times"></i>
    </span>
    <span class="ss-icon" :class="{ 'ss-icon-open': open }">
      <i class="fa fa-chevron-down"></i>
    </span>
    <teleport to="body">
      <div
        v-if="open"
        ref="menu"
        class="ss-dropdown"
        :style="{
          position: 'fixed',
          left: menuStyle.left,
          top: menuStyle.top,
          width: menuStyle.width,
          zIndex: 9999,
          minWidth: '250px',
        }"
      >
        <ul class="ss-options">
          <li
            v-for="opt in filtered"
            :key="getValue(opt)"
            @mousedown.prevent="select(opt)"
            class="ss-option"
            :class="{ 'ss-option-selected': isSelected(opt) }"
          >
            {{ getLabel(opt) }}
          </li>
          <li v-if="filtered.length === 0" class="ss-no-result">
            Aucun résultat
          </li>
        </ul>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import {
  computed,
  ref,
  onMounted,
  onBeforeUnmount,
  watch,
  nextTick,
} from "vue";

const props = defineProps({
  options: { type: Array, default: () => [] },
  optionValue: { type: String, default: "id" },
  optionLabel: { type: [String, Function], default: "nom" },
  modelValue: { default: "" },
  placeholder: { type: String, default: "Sélectionner…" },
  searchPlaceholder: { type: String, default: "Rechercher…" },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const open = ref(false);
const openUp = ref(false);
const query = ref("");
const root = ref(null);
const input = ref(null);
const menu = ref(null);
const menuStyle = ref({ left: "0px", top: "0px", width: "0px" });

const getValue = (o) =>
  typeof o === "object" ? o[props.optionValue] ?? o.id : o;
const getLabel = (o) => {
  if (typeof props.optionLabel === "function") {
    return props.optionLabel(o);
  }
  return typeof o === "object"
    ? o[props.optionLabel] ?? o.label ?? o.name
    : String(o);
};

const selectedLabel = computed(() => {
  if (!props.modelValue && props.modelValue !== 0) return "";
  const found = props.options.find(
    (o) => String(getValue(o)) === String(props.modelValue)
  );
  return found ? getLabel(found) : "";
});

const isSelected = (o) => String(getValue(o)) === String(props.modelValue);

// Vrai si une valeur est sélectionnée (0 est une valeur valide)
const hasValue = computed(
  () => props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== ""
);

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase();
  if (!q) return props.options;
  return props.options.filter((o) => getLabel(o)?.toLowerCase().includes(q));
});

const inputDisplay = computed(() =>
  open.value ? query.value : selectedLabel.value
);
const positionMenu = () => {
  // Utiliser l'input directement plutôt que le conteneur
  const inputEl = input.value || root.value;
  if (!inputEl) {
    // console.warn("SearchableSelect: inputEl not found");
    return;
  }

  // Obtenir les coordonnées de l'input par rapport au viewport
  const rect = inputEl.getBoundingClientRect();

  // Vérifier que l'élément est visible
  if (rect.width === 0 || rect.height === 0) {
    // Réessayer après un petit délai si l'élément n'est pas encore rendu
    setTimeout(() => positionMenu(), 10);
    return;
  }

  const viewportSpaceBelow = window.innerHeight - rect.bottom;
  const viewportSpaceAbove = rect.top;
  const maxMenuHeight = 240; // px

  // TOUJOURS ouvrir vers le bas par défaut, sauf si vraiment pas assez de place
  // On ouvre vers le haut SEULEMENT si :
  // - Il y a moins de place en bas que ce qu'il faut pour le menu
  // - ET il y a plus de place en haut qu'en bas
  openUp.value =
    viewportSpaceBelow < maxMenuHeight &&
    viewportSpaceAbove > viewportSpaceBelow &&
    viewportSpaceBelow < 100;

  // Calculer la position top - TOUJOURS positionner juste en dessous du champ par défaut
  let top = rect.bottom + 8;

  // Si on doit vraiment ouvrir vers le haut, ajuster la position
  if (openUp.value) {
    // Positionner le menu au-dessus du champ
    top = rect.top - maxMenuHeight - 8;
    // S'assurer qu'on ne dépasse pas le haut de l'écran
    if (top < 8) {
      // Si le menu est trop haut, le positionner juste en dessous malgré tout
      top = rect.bottom + 8;
      // Et ajuster si nécessaire pour qu'il rentre dans l'écran
      if (top + maxMenuHeight > window.innerHeight - 8) {
        top = window.innerHeight - maxMenuHeight - 8;
      }
    }
  } else {
    // Menu vers le bas - s'assurer qu'on ne dépasse pas le bas de l'écran
    if (top + maxMenuHeight > window.innerHeight - 8) {
      // Ajuster pour que le menu rentre dans l'écran
      top = window.innerHeight - maxMenuHeight - 8;
      // Mais ne pas le mettre en haut si on peut l'afficher normalement
      if (top < rect.bottom + 8) {
        top = Math.max(rect.bottom + 8, 8);
      }
    }
  }

 

  // S'assurer que les valeurs sont valides
  const finalLeft = Math.max(0, rect.left);
  const finalTop = Math.max(0, top);
  const finalWidth = Math.max(200, rect.width);

  menuStyle.value = {
    left: `${finalLeft}px`,
    top: `${finalTop}px`,
    width: `${finalWidth}px`,
  };

};

const openList = () => {
  if (props.disabled) return; // ne rien faire si disabled

  // Si la liste était fermée, on l'ouvre et on repart d'une recherche vide
  // (affiche toute la liste). Si elle est déjà ouverte, on ne réinitialise
  // pas la recherche en cours.
  if (!open.value) {
    open.value = true;
    query.value = "";
  }
  // Utiliser requestAnimationFrame pour s'assurer que le rendu est terminé
  requestAnimationFrame(() => {
    positionMenu();
  });
};

// Vider la sélection
const clearSelection = () => {
  if (props.disabled) return;
  emit("update:modelValue", null);
  query.value = "";
  open.value = true;
  nextTick(() => {
    input.value?.focus();
    positionMenu();
  });
};
const close = () => (open.value = false);

const onClickOutside = (e) => {
  const inRoot = root.value && root.value.contains(e.target);
  const inMenu = menu.value && menu.value.contains(e.target);
  if (!inRoot && !inMenu) close();
};

const onScrollOrResize = () => {
  if (open.value) positionMenu();
};

// Watcher pour recalculer la position quand le menu s'ouvre
watch(open, (isOpen) => {
  if (isOpen) {
    requestAnimationFrame(() => {
      positionMenu();
    });
  }
});

onMounted(() => {
  document.addEventListener("click", onClickOutside);
  window.addEventListener("scroll", onScrollOrResize, true);
  window.addEventListener("resize", onScrollOrResize, true);
});
onBeforeUnmount(() => {
  document.removeEventListener("click", onClickOutside);
  window.removeEventListener("scroll", onScrollOrResize, true);
  window.removeEventListener("resize", onScrollOrResize, true);
});

const select = (opt) => {
  emit("update:modelValue", getValue(opt));
  query.value = getLabel(opt);
  close();
};

const onInput = (e) => {
  query.value = e.target.value;
  open.value = true;
};

const onBlur = () => {
  // Réinitialiser le query quand on quitte le champ
  // MAIS NE PAS ÉMETTRE si la valeur est vide - garder la valeur précédente
  if (query.value.trim() === "" && !props.modelValue) {
    // Seulement émettre "" si aucune valeur n'était sélectionnée
    // Sinon, laisser la valeur sélectionnée comme elle est
  }
  // Réinitialiser le query pour afficher le label du modèle sélectionné
  query.value = "";
  // Ferme la liste après un tick pour laisser le click dans la liste s'exécuter
  setTimeout(() => close(), 100);
};
</script>

<style scoped>
.ss-wrapper {
  position: relative;
  width: 100%;
}

.ss-input {
  display: block;
  width: 100%;
  padding: 8px 35px 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #1e293b;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.25s ease;
  outline: none;
  cursor: pointer;
}

/* Plus de place à droite quand le bouton "vider" est présent */
.ss-input.ss-clearable {
  padding-right: 56px;
}

/* Bouton vider (X) */
.ss-clear {
  position: absolute;
  right: 30px;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: #94a3b8;
  font-size: 11px;
  cursor: pointer;
  background: #f1f5f9;
  transition: all 0.15s ease;
  z-index: 1;
}

.ss-clear:hover {
  background: #fee2e2;
  color: #ef4444;
}

.ss-input:focus,
.ss-input.ss-open {
  border-color: #0FBCAF;
  box-shadow: 0 0 0 3px rgba(15, 188, 175, 0.12);
}

.ss-input.ss-has-value {
  border-color: #0B5697;
  color: #0B5697;
  font-weight: 600;
}

.ss-input.ss-disabled,
.ss-input.ss-disabled.ss-has-value,
.ss-input.ss-disabled.ss-open {
  background: #f1f5f9 !important;
  color: #94a3b8 !important;
  cursor: not-allowed !important;
  border-color: #e2e8f0 !important;
  box-shadow: none !important;
  font-weight: 500 !important;
  opacity: 0.75;
}

.ss-input::placeholder {
  color: #94a3b8;
  font-weight: 400;
}

.ss-icon {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 11px;
  transition: all 0.25s;
  pointer-events: none;
}

.ss-icon.ss-icon-open {
  transform: translateY(-50%) rotate(180deg);
  color: #0FBCAF;
}

/* Dropdown */
.ss-dropdown {
  background: #fff;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(11, 86, 151, 0.12);
  overflow: hidden;
  animation: ssDropIn 0.2s ease;
}

@keyframes ssDropIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}

.ss-options {
  max-height: 240px;
  overflow-y: auto;
  padding: 4px;
  margin: 0;
  list-style: none;
}

.ss-options::-webkit-scrollbar { width: 5px; }
.ss-options::-webkit-scrollbar-track { background: transparent; }
.ss-options::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

.ss-option {
  padding: 9px 14px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  color: #334155;
  border-radius: 8px;
  transition: all 0.15s ease;
  margin-bottom: 1px;
}

.ss-option:hover {
  background: linear-gradient(135deg, #f0faf9, #e8f0f8);
  color: #0B5697;
}

.ss-option.ss-option-selected {
  background: linear-gradient(135deg, #0B5697, #0FBCAF);
  color: #fff;
  font-weight: 700;
}

.ss-no-result {
  padding: 12px 14px;
  font-size: 12px;
  color: #94a3b8;
  font-style: italic;
  text-align: center;
  list-style: none;
}
</style>
