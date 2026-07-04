<script setup>
import { ref, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { usePermissions } from '@/Composables/usePermissions'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
// jspdf / html2canvas / xlsx sont chargés dynamiquement au moment de l'export
// pour ne pas alourdir le bundle de la page (voir exportPDF / exportExcel).

defineOptions({ layout: DashboardLayout })

const { t } = useI18n()
const { can } = usePermissions()
const page = usePage()

const props = defineProps({
    lignes_rapport: Array,
    totals_rapport: Object,
    tableau_croise: Array,
    soldes: Object,
    couverture_examens: Object,
    alertes: Array,
    mois_selectionne: String,
    mois_label: String,
    filters: Object,
})

const moisSelectionne = ref(props.filters?.mois || '')
const reportRef = ref(null)
const isExporting = ref(false)

const rafraichir = () => {
    const params = {}
    if (moisSelectionne.value) {
        params.mois = moisSelectionne.value
    }
    router.get(route('finances.rapports-financiers.index'), params, { preserveScroll: true })
}

const formatMontant = (montant) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        minimumFractionDigits: 0,
    }).format(montant || 0)
}

const getBadgeClass = (etat) => {
    switch (etat) {
        case 'actif':
            return 'bg-emerald-100 text-emerald-800'
        case 'en-attente':
            return 'bg-amber-100 text-amber-800'
        case 'clôturé':
            return 'bg-gray-100 text-gray-800'
        default:
            return 'bg-gray-100 text-gray-800'
    }
}

const getSoldeClass = () => {
    if ((props.soldes?.solde_net || 0) < 0) {
        return 'text-red-600'
    }
    return 'text-emerald-600'
}

// EXPORT PDF
const exportPDF = async () => {
    isExporting.value = true
    try {
        // Chargement à la demande des libs lourdes
        const [{ default: jsPDF }, { default: html2canvas }] = await Promise.all([
            import('jspdf'),
            import('html2canvas'),
        ])

        const element = reportRef.value

        // Cloner l'élément et retirer les gradients
        const clone = element.cloneNode(true)

        // Remplacer les gradients par des couleurs solides
        clone.querySelectorAll('[style*="background"]').forEach(el => {
            const style = el.getAttribute('style')
            if (style && style.includes('linear-gradient')) {
                // Remplacer les gradients par bleu principal
                const newStyle = style.replace(/background:\s*linear-gradient[^;]*;?/g, 'background: #0B5697;')
                el.setAttribute('style', newStyle)
            }
        })

        const canvas = await html2canvas(clone, {
            scale: 2,
            backgroundColor: '#ffffff',
            logging: false,
            useCORS: true,
            allowTaint: true
        })

        const imgData = canvas.toDataURL('image/png')
        const pdf = new jsPDF('p', 'mm', 'a4')

        const imgWidth = 190
        const imgHeight = (canvas.height * imgWidth) / canvas.width
        let heightLeft = imgHeight
        let position = 10

        pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight)
        heightLeft -= 277

        while (heightLeft > 0) {
            position = heightLeft - imgHeight + 10
            pdf.addPage()
            pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight)
            heightLeft -= 277
        }

        pdf.save(`Rapport-Financier-${props.mois_selectionne}.pdf`)
    } catch (error) {
        console.error('Erreur export PDF:', error)
        alert('Erreur lors de l\'export PDF')
    } finally {
        isExporting.value = false
    }
}

// EXPORT EXCEL
const exportExcel = async () => {
    try {
        // Chargement à la demande de la lib xlsx
        const XLSX = await import('xlsx')
        const wb = XLSX.utils.book_new()

        // Feuille 1: Tableau Principal
        const ws1_data = [
            ['CODE RECETTE', 'LIBELLÉ', 'ÉTAT', 'MONTANT FACTURÉ', 'MONTANT PAYÉ', 'MONTANT RESTANT', 'DÉPENSES', 'SOLDE NET'],
            ...props.lignes_rapport.map(ligne => [
                ligne.code_recette,
                ligne.libelle_recette,
                ligne.etat_recette,
                ligne.montant_facture,
                ligne.montant_paye,
                ligne.montant_restant,
                ligne.depenses_associees,
                ligne.solde_net
            ]),
            ['TOTAUX', '', '', props.totals_rapport.montant_facture, props.totals_rapport.montant_paye, props.totals_rapport.montant_restant, props.totals_rapport.depenses_associees, props.totals_rapport.solde_net]
        ]
        const ws1 = XLSX.utils.aoa_to_sheet(ws1_data)
        ws1['!cols'] = [
            { wch: 15 }, { wch: 25 }, { wch: 12 }, { wch: 16 }, { wch: 14 }, { wch: 15 }, { wch: 14 }, { wch: 12 }
        ]
        XLSX.utils.book_append_sheet(wb, ws1, 'Tableau Principal')

        // Feuille 2: Résumé Global
        const ws2_data = [
            ['RÉSUMÉ GLOBAL'],
            [''],
            ['RECETTES', props.soldes.total_recettes],
            ['AUTRES REVENUS', props.soldes.autres_revenus],
            ['TOTAL REVENUS', props.soldes.total_revenues],
            [''],
            ['SALAIRES', props.soldes.total_salaires],
            ['ACHATS/DÉPENSES', props.soldes.total_achats],
            ['TOTAL DÉPENSES', props.soldes.total_depenses],
            [''],
            ['SOLDE NET', props.soldes.solde_net],
            ['DÉFICITAIRE', props.soldes.est_deficitaire ? 'OUI' : 'NON']
        ]
        const ws2 = XLSX.utils.aoa_to_sheet(ws2_data)
        ws2['!cols'] = [{ wch: 20 }, { wch: 16 }]
        XLSX.utils.book_append_sheet(wb, ws2, 'Résumé')

        // Feuille 3: Couverture Examens
        const ws3_data = [
            ['COUVERTURE DES EXAMENS'],
            [''],
            ['Total Examens', props.couverture_examens.total_examens],
            ['Examens Financés', props.couverture_examens.examens_finances],
            ['Examens Non Financés', props.couverture_examens.examens_non_finances],
            ['Taux Couverture (%)', props.couverture_examens.taux_couverture],
            ['Statut', props.couverture_examens.status]
        ]
        const ws3 = XLSX.utils.aoa_to_sheet(ws3_data)
        ws3['!cols'] = [{ wch: 20 }, { wch: 16 }]
        XLSX.utils.book_append_sheet(wb, ws3, 'Couverture')

        XLSX.writeFile(wb, `Rapport-Financier-${props.mois_selectionne}.xlsx`)
    } catch (error) {
        console.error('Erreur export Excel:', error)
        alert('Erreur lors de l\'export Excel')
    }
}
</script>

<template>
    <Head title="Rapport Financier Consolidé" />
    <div class="min-h-screen" style="background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);">
        <div class="p-6 max-w-7xl mx-auto" ref="reportRef">
            <!-- HEADER MAGNIFIQUE -->
            <div class="mb-8">
                <div class="relative overflow-hidden rounded-2xl shadow-xl" style="background: linear-gradient(135deg, #0B5697 0%, #0FBCAF 100%);">
                    <div class="absolute top-0 right-0 w-96 h-96 opacity-10">
                        <i class="fas fa-chart-line" style="font-size: 400px; color: white;"></i>
                    </div>
                    <div class="relative z-10 p-8">
                        <div class="flex justify-between items-start">
                            <div>
                                <h1 class="text-4xl font-bold text-white mb-2">📊 Rapport Financier Consolidé</h1>
                                <p class="text-cyan-100 text-lg">{{ mois_label }} - Vue agrégée des recettes et dépenses</p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="exportPDF"
                                    :disabled="isExporting"
                                    class="flex items-center gap-2 bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition disabled:opacity-50"
                                >
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button
                                    @click="exportExcel"
                                    class="flex items-center gap-2 bg-white text-emerald-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition"
                                >
                                    <i class="fas fa-file-excel"></i> EXCEL
                                </button>
                                <button
                                    @click="rafraichir"
                                    class="flex items-center gap-2 bg-white text-orange-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition"
                                >
                                    <i class="fas fa-refresh"></i> Actualiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SÉLECTION MOIS -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex items-end gap-6">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">📅 Sélectionner le mois</label>
                        <input
                            v-model="moisSelectionne"
                            type="month"
                            class="w-full md:w-64 border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none transition"
                            @change="rafraichir"
                        />
                    </div>
                </div>
            </div>

            <!-- CARTES DE SYNTHÈSE -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Card Recettes - BLEU -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition">
                    <div class="h-1" style="background: #0B5697;"></div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-gray-700 text-sm font-semibold">💰 Total Recettes</h3>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background: rgba(11, 86, 151, 0.1);">
                                <i class="fas fa-money-bill-wave" style="color: #0B5697; font-size: 24px;"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold mb-3" style="color: #0B5697;">{{ formatMontant(soldes?.total_revenues) }}</p>
                        <div class="space-y-1 text-xs text-gray-600">
                            <p>📥 Versements: <span class="font-semibold">{{ formatMontant(soldes?.total_recettes) }}</span></p>
                            <p>📦 Autres revenus: <span class="font-semibold">{{ formatMontant(soldes?.autres_revenus) }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Card Dépenses - ORANGE -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition">
                    <div class="h-1" style="background: #E5590C;"></div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-gray-700 text-sm font-semibold">💸 Total Dépenses</h3>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background: rgba(229, 89, 12, 0.1);">
                                <i class="fas fa-credit-card" style="color: #E5590C; font-size: 24px;"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold mb-3" style="color: #E5590C;">{{ formatMontant(soldes?.total_depenses) }}</p>
                        <div class="space-y-1 text-xs text-gray-600">
                            <p>👥 Salaires: <span class="font-semibold">{{ formatMontant(soldes?.total_salaires) }}</span></p>
                            <p>🛒 Achats: <span class="font-semibold">{{ formatMontant(soldes?.total_achats) }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Card Solde Net - CYAN -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition">
                    <div class="h-1" style="background: #0FBCAF;"></div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-gray-700 text-sm font-semibold">📈 Solde Net</h3>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background: rgba(15, 188, 175, 0.1);">
                                <i class="fas fa-chart-pie" style="color: #0FBCAF; font-size: 24px;"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold mb-3" :class="getSoldeClass()">{{ formatMontant(soldes?.solde_net) }}</p>
                        <div class="text-xs" :class="soldes?.est_deficitaire ? 'text-red-600' : 'text-emerald-600'">
                            <p class="font-semibold">
                                {{ soldes?.est_deficitaire ? '⚠️ DÉFICITAIRE' : '✅ EXCÉDENTAIRE' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COUVERTURE EXAMENS -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <span style="color: #0B5697;">🎓</span> Couverture des Examens
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <div class="flex items-baseline gap-2 mb-4">
                            <p class="text-4xl font-bold" style="color: #0FBCAF;">{{ couverture_examens?.taux_couverture }}%</p>
                            <p class="text-sm text-gray-600">de couverture</p>
                        </div>
                        <p class="text-sm text-gray-700 mb-4">
                            <span class="font-semibold">{{ couverture_examens?.examens_finances }}</span> /
                            <span class="font-semibold">{{ couverture_examens?.total_examens }}</span> examens financés
                        </p>
                    </div>
                    <div>
                        <div class="w-full h-3 rounded-full overflow-hidden mb-3" style="background: #f0f0f0;">
                            <div
                                class="h-full transition-all rounded-full"
                                :style="{
                                    width: couverture_examens?.taux_couverture + '%',
                                    background: couverture_examens?.taux_couverture >= 95
                                        ? 'linear-gradient(90deg, #0FBCAF, #0B5697)'
                                        : (couverture_examens?.taux_couverture >= 80
                                            ? 'linear-gradient(90deg, #E5590C, #0B5697)'
                                            : 'linear-gradient(90deg, #ff6b6b, #E5590C)')
                                }"
                            ></div>
                        </div>
                        <p class="text-xs font-semibold text-center" :class="
                            couverture_examens?.status === 'EXCELLENT' ? 'text-emerald-600' :
                            (couverture_examens?.status === 'BON' ? 'text-amber-600' : 'text-red-600')
                        ">
                            {{ couverture_examens?.status === 'EXCELLENT' ? '⭐ EXCELLENT' :
                               couverture_examens?.status === 'BON' ? '⭐ BON' : '⚠️ À AMÉLIORER' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ALERTES -->
            <div v-if="alertes && alertes.length > 0" class="mb-8 space-y-3">
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <span>🚨</span> Alertes Importantes
                </h2>
                <div
                    v-for="alerte in alertes"
                    :key="alerte.type"
                    class="rounded-xl p-4 border-l-4 backdrop-blur-sm"
                    :class="alerte.severite === 'CRITIQUE'
                        ? 'bg-red-50 border-l-red-500 text-red-800'
                        : (alerte.severite === 'HAUTE'
                            ? 'bg-orange-50 border-l-orange-500 text-orange-800'
                            : 'bg-amber-50 border-l-amber-500 text-amber-800')"
                >
                    <p class="font-semibold">{{ alerte.message }}</p>
                </div>
            </div>

            <!-- TABLEAU PRINCIPAL -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                <div class="bg-gradient-to-r" style="background: linear-gradient(135deg, #0B5697, #0FBCAF);" >
                    <h2 class="text-xl font-bold text-white p-6">📋 Tableau Consolidé des Recettes</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b-2" style="border-color: #0B5697;">
                            <tr>
                                <th class="px-6 py-4 text-left font-bold" style="color: #0B5697;">Code</th>
                                <th class="px-6 py-4 text-left font-bold" style="color: #0B5697;">Libellé</th>
                                <th class="px-6 py-4 text-left font-bold" style="color: #0B5697;">État</th>
                                <th class="px-6 py-4 text-right font-bold" style="color: #E5590C;">Facturé</th>
                                <th class="px-6 py-4 text-right font-bold" style="color: #E5590C;">Payé</th>
                                <th class="px-6 py-4 text-right font-bold" style="color: #0FBCAF;">Restant</th>
                                <th class="px-6 py-4 text-right font-bold" style="color: #0FBCAF;">Dépenses</th>
                                <th class="px-6 py-4 text-right font-bold" style="color: #0B5697;">Solde</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="ligne in lignes_rapport" :key="ligne.id" class="hover:bg-blue-50 transition">
                                <td class="px-6 py-4 font-mono text-sm font-semibold">{{ ligne.code_recette }}</td>
                                <td class="px-6 py-4">{{ ligne.libelle_recette }}</td>
                                <td class="px-6 py-4">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-semibold', getBadgeClass(ligne.etat_recette)]">
                                        {{ ligne.etat_recette }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold">{{ formatMontant(ligne.montant_facture) }}</td>
                                <td class="px-6 py-4 text-right font-semibold">{{ formatMontant(ligne.montant_paye) }}</td>
                                <td class="px-6 py-4 text-right font-semibold">{{ formatMontant(ligne.montant_restant) }}</td>
                                <td class="px-6 py-4 text-right font-semibold">{{ formatMontant(ligne.depenses_associees) }}</td>
                                <td class="px-6 py-4 text-right font-bold" :class="ligne.solde_net < 0 ? 'text-red-600' : 'text-emerald-600'">
                                    {{ formatMontant(ligne.solde_net) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gradient-to-r" style="background: linear-gradient(135deg, rgba(11, 86, 151, 0.1), rgba(15, 188, 175, 0.1));">
                            <tr class="font-bold border-t-2" style="border-color: #0B5697;">
                                <td colspan="3" class="px-6 py-4">TOTAUX</td>
                                <td class="px-6 py-4 text-right" style="color: #E5590C;">{{ formatMontant(totals_rapport?.montant_facture) }}</td>
                                <td class="px-6 py-4 text-right" style="color: #E5590C;">{{ formatMontant(totals_rapport?.montant_paye) }}</td>
                                <td class="px-6 py-4 text-right" style="color: #0FBCAF;">{{ formatMontant(totals_rapport?.montant_restant) }}</td>
                                <td class="px-6 py-4 text-right" style="color: #0FBCAF;">{{ formatMontant(totals_rapport?.depenses_associees) }}</td>
                                <td class="px-6 py-4 text-right" :class="totals_rapport?.solde_net < 0 ? 'text-red-600' : 'text-emerald-600'">
                                    {{ formatMontant(totals_rapport?.solde_net) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- TABLEAU CROISÉ -->
            <div v-if="tableau_croise && tableau_croise.length > 0" class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r" style="background: linear-gradient(135deg, #0FBCAF, #E5590C);">
                    <h2 class="text-xl font-bold text-white p-6">🔗 Correspondance Examens × Postes Recettes</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b-2" style="border-color: #0FBCAF;">
                            <tr>
                                <th class="px-6 py-4 text-left font-bold" style="color: #0FBCAF;">Classe</th>
                                <th class="px-6 py-4 text-left font-bold" style="color: #0FBCAF;">Matière</th>
                                <th class="px-6 py-4 text-left font-bold" style="color: #0FBCAF;">Date Examen</th>
                                <th class="px-6 py-4 text-left font-bold" style="color: #0FBCAF;">Postes Recettes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="exam in tableau_croise" :key="exam.exam_id" class="hover:bg-cyan-50 transition">
                                <td class="px-6 py-4 font-semibold">{{ exam.classe }}</td>
                                <td class="px-6 py-4">{{ exam.matiere }}</td>
                                <td class="px-6 py-4 text-sm">{{ exam.date }}</td>
                                <td class="px-6 py-4">
                                    <div v-if="exam.postes && exam.postes.length > 0" class="space-y-2">
                                        <div
                                            v-for="poste in exam.postes"
                                            :key="poste.code"
                                            class="rounded-lg p-3 border-l-4"
                                            style="background: rgba(15, 188, 175, 0.05); border-color: #0FBCAF;"
                                        >
                                            <p class="font-semibold text-sm">{{ poste.code }}</p>
                                            <p class="text-xs text-gray-600 mb-1">{{ poste.libelle }}</p>
                                            <p class="font-bold" style="color: #0B5697;">{{ formatMontant(poste.montant) }}</p>
                                            <span :class="['mt-2 px-2 py-1 rounded text-xs font-semibold inline-block', getBadgeClass(poste.etat)]">
                                                {{ poste.etat }}
                                            </span>
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-500 italic">Non financé</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animations smooth */
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

div {
    animation: slideUp 0.3s ease-out;
}

/* Hover effects */
button {
    transition: all 0.3s ease;
}

button:hover:not(:disabled) {
    transform: translateY(-2px);
}

/* Export mode - replace gradients with solid colors */
:deep(.exporting) {
    background: white !important;
}

:deep(.exporting div[style*="background: linear-gradient"]) {
    background: #0B5697 !important;
}

:deep(.exporting div[style*="background: rgba"]) {
    background: white !important;
}

:deep(.exporting button) {
    transform: none !important;
}
</style>
