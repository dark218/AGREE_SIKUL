<script setup>
import { Head, Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    report: Object,
})

const getStatusColor = (etat) => {
    if (etat === 'actif') return 'text-green-600'
    if (etat === 'en-attente') return 'text-yellow-600'
    if (etat === 'clôturé') return 'text-gray-600'
    return 'text-gray-600'
}
</script>

<template>
    <Head title="Rapport Mensuel" />
    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Rapport Mensuel</h1>
                    <p class="text-gray-600 mt-1">{{ report.mois }}</p>
                </div>
                <Link :href="route('exam-finance-report.index')" class="text-blue-600 hover:text-blue-800">
                    ← Retour aux Rapports
                </Link>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Examens Planifiés</p>
                    <p class="text-3xl font-bold text-gray-900">{{ report.total_examens_planifies }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Examens Financés</p>
                    <p class="text-3xl font-bold text-green-600">{{ report.total_examens_finances }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Taux Couverture</p>
                    <p class="text-3xl font-bold text-blue-600">{{ report.taux_couverture }}%</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600">Solde Net</p>
                    <p :class="['text-3xl font-bold', report.solde_net >= 0 ? 'text-green-600' : 'text-red-600']">
                        {{ report.solde_net >= 0 ? '+' : '' }}{{ report.solde_net.toFixed(2) }} €
                    </p>
                </div>
            </div>

            <!-- Detailed Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Exam Status -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">État des Examens</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Financés (Actif)</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div
                                        class="bg-green-600 h-2 rounded-full"
                                        :style="{ width: report.taux_examens_finances + '%' }"
                                    ></div>
                                </div>
                                <span class="font-semibold">{{ report.total_examens_finances }} ({{ report.taux_examens_finances }}%)</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">En Attente</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div
                                        class="bg-yellow-600 h-2 rounded-full"
                                        :style="{ width: report.taux_examens_en_attente + '%' }"
                                    ></div>
                                </div>
                                <span class="font-semibold">{{ report.total_examens_en_attente }} ({{ report.taux_examens_en_attente }}%)</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Non Financés</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div
                                        class="bg-red-600 h-2 rounded-full"
                                        :style="{ width: report.taux_examens_non_finances + '%' }"
                                    ></div>
                                </div>
                                <span class="font-semibold">{{ report.total_examens_non_finances }} ({{ report.taux_examens_non_finances }}%)</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Clôturés</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div
                                        class="bg-gray-600 h-2 rounded-full"
                                        :style="{ width: report.taux_examens_clotured + '%' }"
                                    ></div>
                                </div>
                                <span class="font-semibold">{{ report.total_examens_clotured }} ({{ report.taux_examens_clotured }}%)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Synthèse Financière</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center border-b pb-3">
                            <span class="text-gray-600">Total Revenus</span>
                            <span class="font-semibold">{{ report.total_revenues.toFixed(2) }} €</span>
                        </div>
                        <div class="flex justify-between items-center border-b pb-3">
                            <span class="text-gray-600">Total Dépenses</span>
                            <span class="font-semibold">{{ report.total_expenses.toFixed(2) }} €</span>
                        </div>
                        <div class="flex justify-between items-center border-b pb-3">
                            <span class="text-gray-600">Montant Facturé</span>
                            <span class="font-semibold">{{ report.montant_total_facture.toFixed(2) }} €</span>
                        </div>
                        <div class="flex justify-between items-center border-b pb-3">
                            <span class="text-gray-600">Montant Payé</span>
                            <span class="font-semibold text-green-600">{{ report.montant_total_paye.toFixed(2) }} €</span>
                        </div>
                        <div class="flex justify-between items-center border-b pb-3">
                            <span class="text-gray-600">Montant Impayé</span>
                            <span class="font-semibold text-orange-600">{{ report.montant_total_impaye.toFixed(2) }} €</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 bg-blue-50 px-3 py-2 rounded">
                            <span class="font-semibold">Solde Net</span>
                            <span :class="['font-bold text-lg', report.solde_net >= 0 ? 'text-green-600' : 'text-red-600']">
                                {{ report.solde_net >= 0 ? '+' : '' }}{{ report.solde_net.toFixed(2) }} €
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Indicators -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600 mb-2">État du Rapport</p>
                    <p class="text-lg font-semibold">{{ report.est_complet ? 'Complet' : 'Incomplet' }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600 mb-2">Statut Financier</p>
                    <p :class="['text-lg font-semibold', report.est_deficitaire ? 'text-red-600' : 'text-green-600']">
                        {{ report.est_deficitaire ? '⚠️ Déficitaire' : '✓ Équilibré' }}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600 mb-2">Qualité Financement</p>
                    <p :class="['text-lg font-semibold', report.est_bien_finance ? 'text-green-600' : 'text-orange-600']">
                        {{ report.est_bien_finance ? '✓ Bien Financé' : '⚠️ Partiellement' }}
                    </p>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-lg shadow p-6" v-if="report.notes_mois">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Notes du Mois</h2>
                <p class="text-gray-700 whitespace-pre-wrap">{{ report.notes_mois }}</p>
            </div>

            <!-- Generation Info -->
            <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
                <p>Généré par {{ report.generated_by }} le {{ report.generated_at }}</p>
            </div>
        </div>
    </DashboardLayout>
</template>
