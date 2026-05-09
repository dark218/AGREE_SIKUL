<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});
const isReadOnly = computed(() => props.mode === 'show');
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.code') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.code"
                    class="form-control"
                    :placeholder="t('fields.code')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Label (French) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.label') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.libelle"
                    class="form-control"
                    :placeholder="t('fields.label')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>
        <!-- Label (English) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.label_en') }}
                </label>
                <input
                    type="text"
                    v-model="form.libelle_en"
                    class="form-control"
                    :placeholder="t('fields.label_en')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle_en" class="text-danger">
                    <strong>{{ form.errors.libelle_en }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
