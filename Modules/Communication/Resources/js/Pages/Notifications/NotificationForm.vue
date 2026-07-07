<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
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
        <!-- Destinataire -->
        <div class="col-md-6">
            <label class="mb-10">{{ t('fields.destinataire') || 'Destinataire' }} <span class="text-danger">*</span></label>
            <SearchableSelect
                v-model.number="form.user_id"
                :options="users"
                optionValue="id"
                :optionLabel="(u) => `${u.nom || ''} ${u.prenoms || ''}`.trim() || u.email"
                :placeholder="t('actions.select') || '-- Sélectionner --'"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.user_id" class="text-danger"><strong>{{ form.errors.user_id }}</strong></span>
        </div>

        <!-- Type -->
        <div class="col-md-6">
            <label class="mb-10">{{ t('fields.type') || 'Type' }} <span class="text-danger">*</span></label>
            <input v-model="form.type" type="text" class="form-control" placeholder="Ex: info, alerte, message" :disabled="isReadOnly" />
            <span v-if="form.errors?.type" class="text-danger"><strong>{{ form.errors.type }}</strong></span>
        </div>

        <!-- Titre -->
        <div class="col-md-12">
            <label class="mb-10">{{ t('fields.titre') || 'Titre' }} <span class="text-danger">*</span></label>
            <input v-model="form.titre" type="text" class="form-control" :placeholder="t('placeholders.titre') || 'Titre'" :disabled="isReadOnly" />
            <span v-if="form.errors?.titre" class="text-danger"><strong>{{ form.errors.titre }}</strong></span>
        </div>

        <!-- Message -->
        <div class="col-md-12">
            <label class="mb-10">{{ t('fields.message') || 'Message' }} <span class="text-danger">*</span></label>
            <textarea v-model="form.message" class="form-control" rows="4" :placeholder="t('placeholders.message') || 'Contenu du message'" :disabled="isReadOnly"></textarea>
            <span v-if="form.errors?.message" class="text-danger"><strong>{{ form.errors.message }}</strong></span>
        </div>

        <!-- Lien action (optionnel) -->
        <div class="col-md-6">
            <label class="mb-10">{{ t('fields.action_url') || 'Lien action' }}</label>
            <input v-model="form.action_url" type="text" class="form-control" placeholder="https://..." :disabled="isReadOnly" />
            <span v-if="form.errors?.action_url" class="text-danger"><strong>{{ form.errors.action_url }}</strong></span>
        </div>

        <!-- Date lecture (read-only info, auto-remplie serveur) -->
        <div class="col-md-6" v-if="mode !== 'create'">
            <label class="mb-10">{{ t('fields.lu_at') || 'Date de lecture' }}</label>
            <input v-model="form.lu_at" type="datetime-local" class="form-control" disabled />
        </div>
    </div>
</template>
