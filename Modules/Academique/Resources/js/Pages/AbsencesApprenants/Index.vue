<script setup>
import { ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const page = usePage();
const props = defineProps({
    title: String,
    absencesApprenants: Object,
    filters: Object,
});
const searchFilters = ref({
    apprenant: props.filters?.apprenant || '',
    statut: props.filters?.statut || '',
    etat: props.filters?.etat || '',
});
const statutOptions = [
    { id: 'en_attente', libelle: 'En attente' },
    { id: 'validee', libelle: 'Validée' },
    { id: 'rejetee', libelle: 'Rejetée' },
];
const filterFields = [
    { key: 'apprenant', type: 'text', placeholder: 'Apprenant / matricule', icon: 'fa-search', width: '260px' },
    { key: 'statut', type: 'select', placeholder: 'Statut', options: statutOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];
const search = () => router.get(route('academique.absences_apprenants.index'), searchFilters.value, { preserveState: true, preserveScroll: true });
const resetFilters = () => { searchFilters.value = { apprenant: '', statut: '', etat: '' }; router.get(route('academique.absences_apprenants.index')); };
let timer;
watch(searchFilters, () => { clearTimeout(timer); timer = setTimeout(search, 500); }, { deep: true });

const showModal = ref(false);
const target = ref(null);
const confirmDelete = (item) => { target.value = item; showModal.value = true; };
const doDelete = () => {
    if (!target.value) return;
    router.visit(route('academique.absences_apprenants.destroy', target.value.id), {
        method: 'delete', preserveScroll: true,
        onSuccess: () => { showModal.value = false; target.value = null; },
    });
};
const statutBadge = (s) => s === 'validee' ? 'bg-success' : s === 'rejetee' ? 'bg-danger' : 'bg-warning';
const statutLabel = (s) => s === 'validee' ? 'Validée' : s === 'rejetee' ? 'Rejetée' : 'En attente';
</script>
<template>
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title || 'Absences des apprenants' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('absences_apprenants-create')">
                        <Link :href="route('academique.absences_apprenants.create')" class="btn btn-primary"><i class="fa fa-plus"></i> {{ t('actions.add') }}</Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0">
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters" />
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Apprenant</th>
                                        <th>Classe</th>
                                        <th>Début</th>
                                        <th>Fin</th>
                                        <th>Heures</th>
                                        <th>Statut</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="absencesApprenants?.data?.length">
                                        <tr v-for="item in absencesApprenants.data" :key="item.id">
                                            <td>{{ item.apprenant.nom }} {{ item.apprenant.prenoms }} <small class="text-muted">{{ item.apprenant.matricule }}</small></td>
                                            <td><small>{{ item.classe.nom }}</small></td>
                                            <td><small>{{ item.date_debut }}</small></td>
                                            <td><small>{{ item.date_fin }}</small></td>
                                            <td><small>{{ item.nombre_heures ?? '-' }}</small></td>
                                            <td><span class="badge" :class="statutBadge(item.statut)">{{ statutLabel(item.statut) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.absences_apprenants.show', item.id)" class="btn btn-secondary" :title="t('actions.view')"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route('academique.absences_apprenants.edit', item.id)" class="btn btn-primary" :title="t('actions.edit')"><span class="fa fa-edit"></span></Link>
                                                    <button @click="confirmDelete(item)" class="btn btn-danger" :title="t('actions.delete')"><span class="fa fa-trash"></span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else><td colspan="7" class="text-center">{{ t('common.emptyList') }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :data="absencesApprenants" />
                    </div>
                </div>
            </div>
        </div>
        <ConfirmModal
            :show="showModal"
            @update:show="showModal = $event"
            :title="t('messages.confirm.delete.title')"
            message="Êtes-vous sûr de vouloir supprimer cette absence ?"
            :sub-message="t('messages.confirm.delete.warning')"
            @confirm="doDelete"
            confirm-text="Supprimer"
            confirm-class="btn-danger"
        />
    </div>
</template>
