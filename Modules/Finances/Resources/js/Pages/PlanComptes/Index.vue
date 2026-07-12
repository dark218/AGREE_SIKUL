<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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
const props = defineProps({ title: String, comptes: Object, filters: Object });
const searchFilters = ref({ search: props.filters?.search || '', etat: props.filters?.etat || '' });
const statusOptions = [{ id: 'actif', libelle: 'Actif' }, { id: 'inactif', libelle: 'Inactif' }];
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'N° ou libellé du compte', icon: 'fa-search', width: '300px' },
    { key: 'etat', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '180px' },
];
const search = () => router.get(route('finances.plan-comptes.index'), searchFilters.value, { preserveState: true, preserveScroll: true });
const resetFilters = () => { searchFilters.value = { search: '', etat: '' }; router.get(route('finances.plan-comptes.index')); };
let timer;
watch(searchFilters, () => { clearTimeout(timer); timer = setTimeout(search, 400); }, { deep: true });

const showModal = ref(false);
const target = ref(null);
const confirmDelete = (c) => { target.value = c; showModal.value = true; };
const doDelete = () => {
    if (!target.value) return;
    router.visit(route('finances.plan-comptes.destroy', target.value.id), { method: 'delete', preserveScroll: true,
        onSuccess: () => { showModal.value = false; target.value = null; } });
};
</script>
<template>
    <Head title="Plan comptable" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title || 'Plan comptable OHADA' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('plan-comptes-create')">
                        <Link :href="route('finances.plan-comptes.create')" class="btn btn-primary"><i class="fa fa-plus"></i> {{ t('actions.add') }}</Link>
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
                                        <th style="width:130px">N° compte</th>
                                        <th>Intitulé</th>
                                        <th>Compte parent</th>
                                        <th>Statut</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="comptes?.data?.length">
                                        <tr v-for="c in comptes.data" :key="c.id">
                                            <td><span class="fw-bold" :style="{ paddingLeft: ((c.niveau - 1) * 14) + 'px' }">{{ c.numero_compte }}</span></td>
                                            <td>{{ c.libelle_compte }}</td>
                                            <td><small class="text-muted">{{ c.parent }}</small></td>
                                            <td><span class="badge" :class="c.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ c.etat === 'actif' ? 'Actif' : 'Inactif' }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link v-if="can('plan-comptes-edit')" :href="route('finances.plan-comptes.edit', c.id)" class="btn btn-primary" :title="t('actions.edit')"><span class="fa fa-edit"></span></Link>
                                                    <button v-if="can('plan-comptes-delete')" @click="confirmDelete(c)" class="btn btn-danger" :title="t('actions.delete')"><span class="fa fa-trash"></span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else><td colspan="5" class="text-center">{{ t('common.emptyList') }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :data="comptes" />
                    </div>
                </div>
            </div>
        </div>
        <ConfirmModal :show="showModal" @update:show="showModal = $event" :title="t('messages.confirm.delete.title')"
            message="Supprimer ce compte ?" :sub-message="t('messages.confirm.delete.warning')"
            @confirm="doDelete" confirm-text="Supprimer" confirm-class="btn-danger" />
    </div>
</template>
