<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    articles: {
        type: Array,
        default: () => [],
    },
    pointsVente: {
        type: Array,
        default: () => [],
    },
    typesMouvement: {
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
// Article selectionne
const selectedArticle = computed(() => {
    if (!props.form.article_id) return null;
    return props.articles.find(a => a.value === props.form.article_id);
});
// Determiner si on a besoin des emplacements selon le type
const needsSourceEmplacement = computed(() => {
    return ['transfert', 'sortie_stock'].includes(props.form.type_mouvement);
});
const needsDestinationEmplacement = computed(() => {
    return ['transfert', 'entree_stock'].includes(props.form.type_mouvement);
});
// Reset emplacements si le type change
watch(() => props.form.type_mouvement, () => {
    if (!isReadOnly.value) {
        if (!needsSourceEmplacement.value) {
            props.form.emplacement_source_id = '';
        }
        if (!needsDestinationEmplacement.value) {
            props.form.emplacement_destination_id = '';
        }
    }
});
const getTypeMouvementDescription = (type) => {
    const descriptions = {
        'entree_stock': t('modules.business.mouvementStock.descriptions.entree_stock'),
        'sortie_stock': t('modules.business.mouvementStock.descriptions.sortie_stock'),
        'transfert': t('modules.business.mouvementStock.descriptions.transfert'),
        'inventaire': t('modules.business.mouvementStock.descriptions.inventaire'),
        'correction': t('modules.business.mouvementStock.descriptions.correction')
    };
    return descriptions[type] || '';
};
</script>
<template>
    <div class="custom-input" @keydown.enter.prevent>
        <div class="row g-3">
            <!-- Type de mouvement -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>
                        {{ t('modules.business.mouvementStock.fields.typeMouvement') }}
                        <span v-if="!isReadOnly" class="text-danger"> *</span>
                    </label>
                    <StylishSelect
                        v-model="form.type_mouvement"
                        :options="typesMouvement"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('modules.business.mouvementStock.labels.selectType')"
                        :disabled="isReadOnly"
                    />
                    <small v-if="form.type_mouvement && !isReadOnly" class="text-muted">
                        {{ getTypeMouvementDescription(form.type_mouvement) }}
                    </small>
                    <span v-if="form.errors?.type_mouvement" class="text-danger d-block">
                        <strong>{{ form.errors.type_mouvement }}</strong>
                    </span>
                </div>
            </div>
            <!-- Article -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>
                        {{ t('modules.business.mouvementStock.fields.article') }}
                        <span v-if="!isReadOnly" class="text-danger"> *</span>
                    </label>
                    <StylishSelect
                        v-model="form.article_id"
                        :options="articles"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('modules.business.mouvementStock.labels.selectArticle')"
                        :disabled="isReadOnly"
                    />
                    <small v-if="selectedArticle && !isReadOnly" class="text-muted">
                        {{ t('modules.business.mouvementStock.labels.stockActuel') }}: {{ selectedArticle.stock }}
                        <span v-if="selectedArticle.point_vente"> - {{ selectedArticle.point_vente }}</span>
                    </small>
                    <span v-if="form.errors?.article_id" class="text-danger d-block">
                        <strong>{{ form.errors.article_id }}</strong>
                    </span>
                </div>
            </div>
            <!-- Quantite -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>
                        {{ t('modules.business.mouvementStock.fields.quantite') }}
                        <span v-if="!isReadOnly" class="text-danger"> *</span>
                        <small v-if="form.type_mouvement === 'inventaire' && !isReadOnly" class="text-muted">
                            ({{ t('modules.business.mouvementStock.labels.quantiteReelleComptee') }})
                        </small>
                    </label>
                    <input
                        type="number"
                        v-model="form.quantite"
                        class="form-control"
                        :placeholder="t('modules.business.mouvementStock.placeholders.quantite')"
                        :disabled="isReadOnly"
                        min="1"
                    />
                    <span v-if="form.errors?.quantite" class="text-danger">
                        <strong>{{ form.errors.quantite }}</strong>
                    </span>
                </div>
            </div>
            <!-- Emplacement Source -->
            <div class="col-md-6" v-if="needsSourceEmplacement || isReadOnly">
                <div class="mb-3">
                    <label>
                        {{ t('modules.business.mouvementStock.fields.emplacementSource') }}
                        <span v-if="!isReadOnly && needsSourceEmplacement" class="text-danger"> *</span>
                    </label>
                    <StylishSelect
                        v-model="form.emplacement_source_id"
                        :options="pointsVente"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('modules.business.mouvementStock.labels.selectEmplacement')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.emplacement_source_id" class="text-danger d-block">
                        <strong>{{ form.errors.emplacement_source_id }}</strong>
                    </span>
                </div>
            </div>
            <!-- Emplacement Destination -->
            <div class="col-md-6" v-if="needsDestinationEmplacement || isReadOnly">
                <div class="mb-3">
                    <label>
                        {{ t('modules.business.mouvementStock.fields.emplacementDestination') }}
                        <span v-if="!isReadOnly && needsDestinationEmplacement" class="text-danger"> *</span>
                    </label>
                    <StylishSelect
                        v-model="form.emplacement_destination_id"
                        :options="pointsVente"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('modules.business.mouvementStock.labels.selectEmplacement')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.emplacement_destination_id" class="text-danger d-block">
                        <strong>{{ form.errors.emplacement_destination_id }}</strong>
                    </span>
                </div>
            </div>
            <!-- Commentaire -->
            <div class="col-12">
                <div class="mb-3">
                    <label>{{ t('modules.business.mouvementStock.fields.commentaire') }}</label>
                    <textarea
                        v-model="form.commentaire"
                        class="form-control"
                        :placeholder="t('modules.business.mouvementStock.placeholders.commentaire')"
                        :disabled="isReadOnly"
                        rows="3"
                    ></textarea>
                    <span v-if="form.errors?.commentaire" class="text-danger">
                        <strong>{{ form.errors.commentaire }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
