<script setup>
import { Head, Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'

const props = defineProps({
    reports: Object,
    filters: Object,
})

const etatClass = (estDeficitaire) => {
    return estDeficitaire ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
}

const getDeficitText = (solde) => {
    return solde < 0 ? 'Déficitaire' : 'Équilibré'
}
</script>

<template>
    <Head title="Rapports Mensuels - Financement d'Examens" />
    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Rapports Mensuels</h1>
                    <p class="text-gray-600 mt-1">Suivi des financements d'examens par mois</p>
                </div>
                <Link :href="route('exam-finance-report.dashboard')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    📊 Tableau de Bord
                </Link>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Mois</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Examens Planifiés</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Examens Financés</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Taux Couverture</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Solde Net</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Statut</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="report in reports.data" :key="report.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ report.mois }}</td>
                            <td class="px-6 py-4">{{ report.total_examens_planifies }}</td>
                            <td class="px-6 py-4">{{ report.total_examens_finances }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                        <div
                                            class="bg-blue-600 h-2 rounded-full"
                                            :style="{ width: report.taux_couverture + '%' }"
                                        ></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ report.taux_couverture }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="report.solde_net >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'">
                                    {{ report.solde_net >= 0 ? '+' : '' }}{{ report.solde_net.toFixed(2) }} €
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['px-3 py-1 rounded-full text-xs font-medium', etatClass(report.est_deficitaire)]">
                                    {{ getDeficitText(report.solde_net) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <Link :href="route('exam-finance-report.show', report.id)" class="text-blue-600 hover:text-blue-800">
                                    Voir
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="reports.data.length === 0" class="text-center py-6 text-gray-500">
                    Aucun rapport disponible
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="reports.links" class="flex justify-center">
                <Pagination :links="reports.links" />
            </div>
        </div>
    </DashboardLayout>
</template>
