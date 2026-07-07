<!--
  FormStepper.vue — Composant wizard multi-étapes réutilisable.

  Contrat :
    props.steps       : Array<{ key: string, label: string, icon?: string,
                                requiredFields?: string[], validator?: fn(form) => bool | Promise }>
    props.modelValue  : index de l'étape courante (v-model)
    props.form        : objet useForm() Inertia (utilisé pour requiredFields check)
    props.persistKey  : (optionnel) clé localStorage — persiste l'étape courante
                        et le snapshot du form entre reloads.

  Émets :
    update:modelValue : nouvelle étape courante
    submit            : quand l'utilisateur clique "Valider" sur la dernière étape

  Slot par step, nommé par step.key. Contenu = les inputs du step.
    <template #identite>...</template>
    <template #contact>...</template>

  Slot par défaut : optionnel (les steps déclarés ont priorité).

  Comportement :
    - Barre de progression + icônes cliquables (skip vers étape déjà validée)
    - Boutons Précédent / Suivant en pied
    - Bouton Valider affiché uniquement sur la dernière étape
    - Ne bloque pas la navigation si validation soft échoue — juste un badge
      warning sur l'étape non complète.
-->

<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    steps: {
        type: Array,
        required: true,
        validator: (steps) =>
            Array.isArray(steps) && steps.length > 0 &&
            steps.every((s) => s.key && s.label),
    },
    modelValue: {
        type: Number,
        default: 0,
    },
    form: {
        type: Object,
        default: () => ({}),
    },
    persistKey: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'submit']);

// État interne
const current = ref(props.modelValue);
const visitedSteps = ref(new Set([props.modelValue]));

watch(() => props.modelValue, (v) => {
    current.value = v;
    visitedSteps.value.add(v);
});

watch(current, (v) => {
    emit('update:modelValue', v);
    visitedSteps.value.add(v);
    persist();
});

// Persist step + éventuel snapshot form dans localStorage.
function persist() {
    if (!props.persistKey) return;
    try {
        localStorage.setItem(`${props.persistKey}:step`, String(current.value));
    } catch (e) { /* quota / accès refusé — silencieux */ }
}

onMounted(() => {
    if (!props.persistKey) return;
    try {
        const saved = localStorage.getItem(`${props.persistKey}:step`);
        if (saved !== null) {
            const n = Number(saved);
            if (Number.isInteger(n) && n >= 0 && n < props.steps.length) {
                current.value = n;
                emit('update:modelValue', n);
                for (let i = 0; i <= n; i++) visitedSteps.value.add(i);
            }
        }
    } catch (e) { /* silencieux */ }
});

const currentStep = computed(() => props.steps[current.value] || props.steps[0]);
const isFirst = computed(() => current.value === 0);
const isLast = computed(() => current.value === props.steps.length - 1);

// Validation soft : vérifie que les champs requis du step ont une valeur.
// Retourne true si le step est "complet" (vert), false sinon (orange).
function isStepComplete(index) {
    const step = props.steps[index];
    if (!step?.requiredFields || step.requiredFields.length === 0) return true;
    return step.requiredFields.every((field) => {
        const v = props.form?.[field];
        if (Array.isArray(v)) return v.length > 0;
        return v !== null && v !== undefined && v !== '';
    });
}

// Progression : combien de steps sont complets.
const completionPercent = computed(() => {
    const done = props.steps.reduce(
        (acc, _, i) => acc + (isStepComplete(i) ? 1 : 0),
        0
    );
    return Math.round((done / props.steps.length) * 100);
});

function goTo(index) {
    // On autorise le skip vers un step déjà visité.
    // Vers un step non visité, on laisse passer aussi (pas de blocage strict —
    // le validator custom peut être appelé par le parent avant submit).
    if (index >= 0 && index < props.steps.length) {
        current.value = index;
    }
}

function next() {
    if (!isLast.value) goTo(current.value + 1);
}

function prev() {
    if (!isFirst.value) goTo(current.value - 1);
}

function onSubmit() {
    emit('submit');
    // On nettoie la persistance (on est allé au bout).
    if (props.persistKey) {
        try { localStorage.removeItem(`${props.persistKey}:step`); } catch (e) {}
    }
}
</script>

<template>
    <div class="form-stepper">
        <!-- Barre de progression et étapes cliquables -->
        <div class="stepper-header">
            <div class="stepper-progress-bar">
                <div class="stepper-progress-fill" :style="{ width: `${completionPercent}%` }"></div>
            </div>

            <div class="stepper-steps">
                <div
                    v-for="(step, index) in steps"
                    :key="step.key"
                    class="stepper-step"
                    :class="{
                        active: index === current,
                        visited: visitedSteps.has(index) && index !== current,
                        complete: isStepComplete(index) && index !== current,
                        incomplete: !isStepComplete(index) && visitedSteps.has(index) && index !== current,
                    }"
                    @click="goTo(index)"
                >
                    <div class="stepper-step-marker">
                        <i v-if="step.icon" :class="step.icon"></i>
                        <span v-else>{{ index + 1 }}</span>
                    </div>
                    <div class="stepper-step-label">{{ step.label }}</div>
                </div>
            </div>
        </div>

        <!-- Contenu de l'étape courante (slot dynamique) -->
        <div class="stepper-content">
            <slot :name="currentStep.key" :form="form" :step="currentStep">
                <!-- Fallback : slot par défaut -->
                <slot :form="form" :step="currentStep">
                    <p class="text-muted">Aucun contenu pour le step "{{ currentStep.label }}"</p>
                </slot>
            </slot>
        </div>

        <!-- Boutons de navigation -->
        <div class="stepper-footer">
            <button
                type="button"
                class="btn btn-outline-secondary"
                :disabled="isFirst"
                @click="prev"
            >
                <i class="fa fa-arrow-left"></i>
                {{ t('actions.previous') || 'Précédent' }}
            </button>

            <div class="stepper-step-indicator text-muted small">
                {{ t('common.step') || 'Étape' }} {{ current + 1 }} / {{ steps.length }}
            </div>

            <button
                v-if="!isLast"
                type="button"
                class="btn btn-primary"
                @click="next"
            >
                {{ t('actions.next') || 'Suivant' }}
                <i class="fa fa-arrow-right"></i>
            </button>

            <button
                v-else
                type="button"
                class="btn btn-success"
                :disabled="form?.processing"
                @click="onSubmit"
            >
                <span v-if="form?.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i class="fa fa-check"></i>
                {{ t('actions.validate') || 'Valider' }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.form-stepper {
    background: #fff;
    border-radius: 8px;
    /* §UI : padding global pour éviter que les inputs collent aux bords
       de la card parente. Responsive : 16px mobile → 32px desktop. */
    padding: 20px 24px;
}

@media (min-width: 992px) {
    .form-stepper {
        padding: 24px 32px;
    }
}

@media (max-width: 640px) {
    .form-stepper {
        padding: 16px 12px;
    }
}

.stepper-header {
    margin-bottom: 24px;
}

.stepper-progress-bar {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 20px;
}

.stepper-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0b5697, #1e88e5);
    transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 2px;
}

.stepper-steps {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    flex-wrap: wrap;
}

.stepper-step {
    flex: 1 1 auto;
    min-width: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s;
    padding: 8px 4px;
    border-radius: 6px;
}

.stepper-step:hover {
    background: #f3f4f6;
}

.stepper-step-marker {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e5e7eb;
    color: #6b7280;
    font-weight: 700;
    font-size: 16px;
    transition: all 0.2s;
}

.stepper-step.active .stepper-step-marker {
    background: #0b5697;
    color: white;
    box-shadow: 0 0 0 4px rgba(11, 86, 151, 0.2);
    transform: scale(1.05);
}

.stepper-step.complete .stepper-step-marker {
    background: #16a34a;
    color: white;
}

.stepper-step.incomplete .stepper-step-marker {
    background: #f59e0b;
    color: white;
}

.stepper-step-label {
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    text-align: center;
    line-height: 1.3;
}

.stepper-step.active .stepper-step-label {
    color: #0b5697;
    font-weight: 600;
}

.stepper-step.complete .stepper-step-label {
    color: #16a34a;
}

.stepper-content {
    padding: 20px 0;
    min-height: 200px;
}

.stepper-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
    margin-top: 20px;
}

.stepper-footer .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s;
}

.stepper-step-indicator {
    font-weight: 500;
}

@media (max-width: 640px) {
    .stepper-step-label {
        display: none;
    }
    .stepper-step {
        min-width: 60px;
    }
}
</style>
