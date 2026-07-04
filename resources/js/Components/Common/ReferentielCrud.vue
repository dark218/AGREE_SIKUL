<!--
  ReferentielCrud.vue — CRUD générique pour les référentiels Paramétrage
  simples (code, libelle, ordre, etat). Utilisé par TypeContrat,
  StatutEmploye, SituationMatrimoniale, LienParente, Civilite,
  StatutApprenant, TypeInscription, GroupeSanguin, Langue.

  Props :
    - mode        : 'index' | 'create' | 'edit' | 'show'
    - routeName   : nom de route Laravel (ex: 'parametrage.types_contrats')
    - title       : titre affiché
    - items       : (pour mode index) Object paginated
    - item        : (pour create/edit/show) Object courant
    - filters     : filtres initiaux
-->
<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, showUpdateLoader, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    mode: { type: String, required: true, validator: v => ['index', 'create', 'edit', 'show'].includes(v) },
    routeName: { type: String, required: true },
    title: { type: String, default: 'Référentiel' },
    items: { type: Object, default: null },
    item: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

// ============= INDEX =============
const searchFilters = ref({
    search: props.filters?.search || '',
    etat: props.filters?.etat || '',
});
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const search = () => {
    router.get(route(`${props.routeName}.index`), searchFilters.value, {
        preserveState: true, preserveScroll: true,
    });
};
const resetFilters = () => {
    searchFilters.value = { search: '', etat: '' };
    router.get(route(`${props.routeName}.index`));
};
const confirmDelete = (item) => { itemToDelete.value = item; showDeleteModal.value = true; };
const deleteItem = () => {
    if (!itemToDelete.value) return;
    showDeleteLoader();
    router.visit(route(`${props.routeName}.destroy`, itemToDelete.value.id), {
        method: 'delete', preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; },
        onFinish: () => hideLoader(),
    });
};
const toggleStatut = (item) => {
    router.visit(route(`${props.routeName}.statut`, item.id), { method: 'put', preserveScroll: true });
};
watch(() => searchFilters.value, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(search, 400);
}, { deep: true });

// ============= CREATE / EDIT / SHOW =============
const form = useForm({
    code: props.item?.code || '',
    libelle: props.item?.libelle || '',
    ordre: props.item?.ordre ?? 0,
    etat: props.item?.etat || 'actif',
});

const submitCreate = () => {
    showStoreLoader();
    form.post(route(`${props.routeName}.store`), {
        onError: () => hideLoader(),
        onFinish: () => hideLoader(),
    });
};
const submitEdit = () => {
    showUpdateLoader();
    form.put(route(`${props.routeName}.update`, props.item.id), {
        onError: () => hideLoader(),
        onFinish: () => hideLoader(),
    });
};

const isReadOnly = props.mode === 'show';
</script>

<template>
    <Head :title="title" />

    <!-- ============= INDEX ============= -->
    <div v-if="mode === 'index'" class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <Link :href="route(`${routeName}.create`)" class="btn btn-primary">
                        {{ t('actions.add') || 'Ajouter' }}
                    </Link>
                </div>
            </div>

            <AlertMessage />

            <div class="row m-0">
                <form @submit.prevent="search" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap; margin-bottom: 12px;">
                    <div style="width: 220px;">
                        <input v-model="searchFilters.search" type="text" class="form-control form-control-sm" placeholder="Code ou libellé…" style="height: 32px;" />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect v-model="searchFilters.etat" :options="statusOptions" optionValue="id" optionLabel="libelle" placeholder="Statut" class="form-control-sm" style="height: 32px; width: 100%;" />
                    </div>
                    <button type="button" @click="resetFilters" class="btn btn-secondary btn-sm" style="height: 32px;">
                        <i class="fa fa-redo"></i>
                    </button>
                </form>

                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Libellé</th>
                                        <th>Ordre</th>
                                        <th>Statut</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="items?.data?.length > 0">
                                        <tr v-for="row in items.data" :key="row.id">
                                            <td><strong>{{ row.code }}</strong></td>
                                            <td>{{ row.libelle }}</td>
                                            <td>{{ row.ordre }}</td>
                                            <td><span class="badge" :class="row.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ row.etat === 'actif' ? 'Actif' : 'Inactif' }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route(`${routeName}.show`, row.id)" class="btn btn-secondary" title="Voir"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route(`${routeName}.edit`, row.id)" class="btn btn-primary" title="Modifier"><span class="fa fa-edit"></span></Link>
                                                    <button @click="toggleStatut(row)" class="btn" :class="row.etat === 'actif' ? 'btn-warning' : 'btn-success'" :title="row.etat === 'actif' ? 'Désactiver' : 'Activer'">
                                                        <span class="fa" :class="row.etat === 'actif' ? 'fa-ban' : 'fa-check'"></span>
                                                    </button>
                                                    <button @click="confirmDelete(row)" class="btn btn-danger" title="Supprimer"><span class="fa fa-trash"></span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else><td colspan="5" class="text-center">Aucune entrée</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :data="items" />
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            @update:show="showDeleteModal = $event"
            title="Confirmer la suppression"
            message="Êtes-vous sûr de vouloir supprimer cet élément ?"
            confirm-text="Supprimer"
            confirm-class="btn-danger"
            @confirm="deleteItem"
        />
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>

    <!-- ============= CREATE / EDIT / SHOW ============= -->
    <div v-else class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa" :class="mode === 'create' ? 'fa-plus' : (mode === 'edit' ? 'fa-edit' : 'fa-eye')"></i>
                                </span>
                                <h5 class="title mb-0">{{ title }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="mode === 'create' ? submitCreate() : (mode === 'edit' ? submitEdit() : null)">
                                <div class="row g-3 custom-input">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-medium">Code <span class="text-danger">*</span></label>
                                        <input v-model="form.code" type="text" class="form-control" :disabled="isReadOnly" placeholder="Ex: CDI" maxlength="50" style="text-transform: uppercase;" />
                                        <small class="text-muted">Identifiant unique (majuscules)</small>
                                        <span v-if="form.errors?.code" class="text-danger d-block">{{ form.errors.code }}</span>
                                    </div>

                                    <div class="col-sm-6">
                                        <label class="form-label fw-medium">Libellé <span class="text-danger">*</span></label>
                                        <input v-model="form.libelle" type="text" class="form-control" :disabled="isReadOnly" placeholder="Ex: Contrat à durée indéterminée" />
                                        <span v-if="form.errors?.libelle" class="text-danger d-block">{{ form.errors.libelle }}</span>
                                    </div>

                                    <div class="col-sm-6">
                                        <label class="form-label fw-medium">Ordre d'affichage</label>
                                        <input v-model.number="form.ordre" type="number" class="form-control" :disabled="isReadOnly" min="0" />
                                    </div>

                                    <div class="col-sm-6">
                                        <label class="form-label fw-medium">Statut <span class="text-danger">*</span></label>
                                        <SearchableSelect v-model="form.etat" :options="statusOptions" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" />
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col text-end">
                                        <Link :href="route(`${routeName}.index`)" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <button v-if="mode !== 'show'" type="submit" class="btn btn-primary ms-2" :disabled="form.processing">
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                            <i class="fa fa-save"></i> {{ t('actions.validate') }}
                                        </button>
                                        <Link v-if="mode === 'show'" :href="route(`${routeName}.edit`, item.id)" class="btn btn-warning ms-2">
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </Link>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
