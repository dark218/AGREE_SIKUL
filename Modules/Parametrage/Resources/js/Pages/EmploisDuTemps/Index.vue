<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, showDeleteLoader, hideLoader } = useLoader();
const props = defineProps({ emplois: Object, filters: Object });
const searchFilters = ref({ search: props.filters?.search || '', jour_semaine: props.filters?.jour_semaine || '', statut: props.filters?.statut || '' });
let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const search = () => router.get(route('parametrage.emplois-du-temps.index'), searchFilters.value, { preserveState: true });
const resetFilters = () => { searchFilters.value = { search: '', jour_semaine: '', statut: '' }; router.get(route('parametrage.emplois-du-temps.index')); };
const confirmDelete = (item) => { itemToDelete.value = item; showDeleteModal.value = true; };
const deleteItem = () => { if (itemToDelete.value) { showDeleteLoader(); router.visit(route('parametrage.emplois-du-temps.destroy', itemToDelete.value?.id), { method: 'delete', onSuccess: () => { showDeleteModal.value = false; }, onFinish: () => hideLoader() }); } };
const page = usePage();
watch(() => searchFilters.value, () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => search(), 300); }, { deep: true });
</script>
<template>
    <Head :title="t('entities.emplois_du_temps') || 'Emplois du Temps'" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('entities.emplois_du_temps') }}</h4>
            <Link :href="route('parametrage.emplois-du-temps.create')" class="btn btn-primary"><i class="fa fa-plus"></i> {{ t('actions.add') }}</Link>
        </div>
        <AlertMessage />
        <div class="row m-0" style="gap: 8px; margin-bottom: 1rem;">
            <input v-model="searchFilters.search" type="text" class="form-control form-control-sm" :placeholder="t('fields.search')" style="width: 150px; height: 32px;" />
            <select v-model="searchFilters.jour_semaine" class="form-control form-control-sm" style="width: 150px; height: 32px;">
                <option value="">{{ t('fields.jour_semaine') }}</option>
                <option v-for="j of ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']" :key="j" :value="j">{{ j.charAt(0).toUpperCase() + j.slice(1) }}</option>
            </select>
            <button @click="search" class="btn btn-sm btn-dark"><i class="fa fa-search"></i></button>
            <button @click="resetFilters" class="btn btn-sm btn-dark"><i class="fa fa-refresh"></i></button>
        </div>
        <table class="table table-sm" v-if="page.props.emplois?.data?.length">
            <thead><tr><th>{{ t('fields.classe') }}</th><th>{{ t('fields.jour_semaine') }}</th><th>{{ t('fields.matiere') }}</th><th>{{ t('fields.heure_debut') }}</th><th>{{ t('fields.statut') }}</th><th>{{ t('actions.actions') }}</th></tr></thead>
            <tbody>
                <tr v-for="item in page.props.emplois.data" :key="item.id">
                    <td>{{ item.classe?.nom }}</td>
                    <td>{{ item.jour_semaine }}</td>
                    <td>{{ item.matiere?.titre }}</td>
                    <td>{{ item.heure_debut }}</td>
                    <td><span :class="'badge badge-' + (item.statut === 'actif' ? 'success' : 'danger')">{{ t('common.' + item.statut) }}</span></td>
                    <td>
                        <Link :href="route('parametrage.emplois-du-temps.show', item?.id)" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> {{ t('actions.view') }}</Link>
                        <Link :href="route('parametrage.emplois-du-temps.edit', item?.id)" class="btn btn-xs btn-warning"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</Link>
                        <button @click="confirmDelete(item)" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> {{ t('actions.delete') }}</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <div v-else class="alert alert-info">{{ t('messages.no_data') }}</div>
        <Pagination :paginator="page.props.emplois" />
        <ConfirmModal :show="showDeleteModal" @update:show="showDeleteModal = $event" :title="t('messages.confirm.delete.title')" @confirm="deleteItem" />
        <FullPageLoader :show="isLoading" />
    </div>
</template>
