<script setup>
import { computed, ref } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    apprenant: { type: Object, default: null },
    paiements: { type: Array, default: () => [] },
    facturations: { type: Array, default: () => [] },
    totalFacture: { type: Number, default: 0 },
    totalPaye: { type: Number, default: 0 },
    solde: { type: Number, default: 0 },
});

const a = computed(() => props.apprenant || {});
const activeTab = ref('facturations');

function fmt(val) {
    return new Intl.NumberFormat('fr-FR').format(val || 0) + ' FCFA';
}

const statutStyle = {
    'confirmé': { bg: '#dcfce7', color: '#16a34a', icon: 'bx bxs-check-circle' },
    'en_attente': { bg: '#fef3c7', color: '#d97706', icon: 'bx bxs-time' },
    'rejeté': { bg: '#fee2e2', color: '#dc2626', icon: 'bx bxs-x-circle' },
    'en_cours': { bg: '#dbeafe', color: '#2563eb', icon: 'bx bxs-hourglass' },
    'payé': { bg: '#dcfce7', color: '#16a34a', icon: 'bx bxs-check-circle' },
    'impayé': { bg: '#fee2e2', color: '#dc2626', icon: 'bx bxs-error' },
};

function getSt(statut) {
    return statutStyle[statut] || { bg: '#f1f5f9', color: '#64748b', icon: 'bx bxs-circle' };
}

const modeIcons = {
    especes: 'bx bxs-wallet',
    virement: 'bx bx-transfer',
    carte_bancaire: 'bx bxs-credit-card',
    cheque: 'bx bxs-bank',
};
</script>

<template>
    <div class="eleve-dashboard">
        <!-- Header -->
        <div class="ed-header" style="background: linear-gradient(135deg, #0FBCAF, #0a9e93);">
            <div class="ed-header-left">
                <div class="ed-avatar"><i class="bx bxs-wallet"></i></div>
                <div>
                    <h1 class="ed-title">Mes Paiements & Factures</h1>
                    <p class="ed-subtitle" v-if="a.id">{{ a.prenoms }} {{ a.nom }} — {{ a.classe }} — {{ a.matricule }}</p>
                </div>
            </div>
        </div>

        <div v-if="a.id" class="ed-content">
            <!-- Stats financiers -->
            <div class="ed-stats-row">
                <div class="ed-stat" style="border-left: 4px solid #0B5697;">
                    <div class="ed-stat-icon" style="background:#EBF5FF;"><i class="bx bxs-receipt" style="color:#0B5697;"></i></div>
                    <div><div class="ed-stat-val" style="color:#0B5697;">{{ fmt(totalFacture) }}</div><div class="ed-stat-lbl">Total facturé</div></div>
                </div>
                <div class="ed-stat" style="border-left: 4px solid #16a34a;">
                    <div class="ed-stat-icon" style="background:#dcfce7;"><i class="bx bxs-check-shield" style="color:#16a34a;"></i></div>
                    <div><div class="ed-stat-val" style="color:#16a34a;">{{ fmt(totalPaye) }}</div><div class="ed-stat-lbl">Total payé</div></div>
                </div>
                <div class="ed-stat" :style="{ borderLeft: '4px solid ' + (solde > 0 ? '#dc2626' : '#16a34a') }">
                    <div class="ed-stat-icon" :style="{ background: solde > 0 ? '#fee2e2' : '#dcfce7' }">
                        <i class="bx bxs-wallet-alt" :style="{ color: solde > 0 ? '#dc2626' : '#16a34a' }"></i>
                    </div>
                    <div>
                        <div class="ed-stat-val" :style="{ color: solde > 0 ? '#dc2626' : '#16a34a' }">{{ fmt(solde) }}</div>
                        <div class="ed-stat-lbl">{{ solde > 0 ? 'Reste à payer' : 'Soldé' }}</div>
                    </div>
                </div>
            </div>

            <!-- Barre de progression paiement -->
            <div v-if="totalFacture > 0" class="pay-progress-card">
                <div class="pay-progress-header">
                    <span class="pay-progress-label">Progression du paiement</span>
                    <span class="pay-progress-pct" :style="{ color: solde > 0 ? '#d97706' : '#16a34a' }">
                        {{ Math.round((totalPaye / totalFacture) * 100) }}%
                    </span>
                </div>
                <div class="pay-progress-bar">
                    <div
                        class="pay-progress-fill"
                        :style="{ width: Math.min((totalPaye / totalFacture) * 100, 100) + '%', background: solde > 0 ? 'linear-gradient(90deg, #f59e0b, #d97706)' : 'linear-gradient(90deg, #22c55e, #16a34a)' }"
                    ></div>
                </div>
                <div class="pay-progress-legend">
                    <span><i class="bx bxs-circle" style="color:#16a34a; font-size:8px;"></i> Payé : {{ fmt(totalPaye) }}</span>
                    <span v-if="solde > 0"><i class="bx bxs-circle" style="color:#dc2626; font-size:8px;"></i> Reste : {{ fmt(solde) }}</span>
                </div>
            </div>

            <!-- Onglets -->
            <div class="pay-tabs">
                <button class="pay-tab" :class="{ active: activeTab === 'facturations' }" @click="activeTab = 'facturations'">
                    <i class="bx bxs-file-doc"></i> Mes facturations <span class="tab-count">{{ facturations.length }}</span>
                </button>
                <button class="pay-tab" :class="{ active: activeTab === 'paiements' }" @click="activeTab = 'paiements'">
                    <i class="bx bxs-credit-card-front"></i> Historique paiements <span class="tab-count">{{ paiements.length }}</span>
                </button>
            </div>

            <!-- Facturations -->
            <div v-show="activeTab === 'facturations'" class="ed-card">
                <div class="ed-card-header"><i class="bx bxs-file-doc" style="color:#E5590C;"></i><h3>Mes facturations</h3></div>

                <div v-if="facturations.length" class="ed-table-wrap">
                    <table class="ed-table">
                        <thead>
                            <tr><th>Libellé</th><th>Montant</th><th>Payé</th><th>Reste</th><th>Échéance</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="f in facturations" :key="f.id">
                                <td><strong>{{ f.libelle }}</strong></td>
                                <td>{{ fmt(f.montant) }}</td>
                                <td style="color:#16a34a; font-weight:700;">{{ fmt(f.montant_paye) }}</td>
                                <td :style="{ color: f.reste > 0 ? '#dc2626' : '#16a34a', fontWeight: 700 }">{{ fmt(f.reste) }}</td>
                                <td>{{ f.echeance }}</td>
                                <td>
                                    <span class="pd-badge" :style="{ background: getSt(f.statut).bg, color: getSt(f.statut).color }">
                                        <i :class="getSt(f.statut).icon" style="font-size:12px;"></i> {{ f.statut }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Vue mobile cards -->
                <div v-if="facturations.length" class="mobile-cards">
                    <div v-for="f in facturations" :key="'m-'+f.id" class="mobile-pay-card" :style="{ borderLeft: '4px solid ' + getSt(f.statut).color }">
                        <div class="mpc-row mpc-title">
                            <strong>{{ f.libelle }}</strong>
                            <span class="pd-badge" :style="{ background: getSt(f.statut).bg, color: getSt(f.statut).color }">{{ f.statut }}</span>
                        </div>
                        <div class="mpc-row"><span class="mpc-label">Montant</span><span>{{ fmt(f.montant) }}</span></div>
                        <div class="mpc-row"><span class="mpc-label">Payé</span><span style="color:#16a34a; font-weight:700;">{{ fmt(f.montant_paye) }}</span></div>
                        <div class="mpc-row"><span class="mpc-label">Reste</span><span :style="{ color: f.reste > 0 ? '#dc2626' : '#16a34a', fontWeight: 700 }">{{ fmt(f.reste) }}</span></div>
                        <div class="mpc-row"><span class="mpc-label">Échéance</span><span>{{ f.echeance }}</span></div>
                    </div>
                </div>

                <div v-if="!facturations.length" class="ed-empty">
                    <i class="bx bx-file" style="font-size:48px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                    Aucune facturation enregistrée
                </div>
            </div>

            <!-- Paiements -->
            <div v-show="activeTab === 'paiements'" class="ed-card">
                <div class="ed-card-header"><i class="bx bxs-credit-card-front" style="color:#0FBCAF;"></i><h3>Historique des paiements</h3></div>

                <div v-if="paiements.length" class="ed-table-wrap">
                    <table class="ed-table">
                        <thead>
                            <tr><th>Date</th><th>Montant</th><th>Mode</th><th>N° Reçu</th><th>Référence</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in paiements" :key="p.id">
                                <td>{{ p.date }}</td>
                                <td style="font-weight:800;">{{ fmt(p.montant) }}</td>
                                <td>
                                    <span class="mode-tag">
                                        <i :class="modeIcons[p.mode] || 'bx bxs-wallet'" style="font-size:15px;"></i>
                                        {{ p.mode }}
                                    </span>
                                </td>
                                <td>{{ p.numero_recu }}</td>
                                <td><small style="color:#94a3b8;">{{ p.reference }}</small></td>
                                <td>
                                    <span class="pd-badge" :style="{ background: getSt(p.statut).bg, color: getSt(p.statut).color }">
                                        <i :class="getSt(p.statut).icon" style="font-size:12px;"></i> {{ p.statut }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Vue mobile cards -->
                <div v-if="paiements.length" class="mobile-cards">
                    <div v-for="p in paiements" :key="'mp-'+p.id" class="mobile-pay-card" :style="{ borderLeft: '4px solid ' + getSt(p.statut).color }">
                        <div class="mpc-row mpc-title">
                            <strong>{{ fmt(p.montant) }}</strong>
                            <span class="pd-badge" :style="{ background: getSt(p.statut).bg, color: getSt(p.statut).color }">{{ p.statut }}</span>
                        </div>
                        <div class="mpc-row"><span class="mpc-label">Date</span><span>{{ p.date }}</span></div>
                        <div class="mpc-row"><span class="mpc-label">Mode</span><span><i :class="modeIcons[p.mode] || 'bx bxs-wallet'"></i> {{ p.mode }}</span></div>
                        <div class="mpc-row"><span class="mpc-label">Reçu</span><span>{{ p.numero_recu }}</span></div>
                    </div>
                </div>

                <div v-if="!paiements.length" class="ed-empty">
                    <i class="bx bx-credit-card" style="font-size:48px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                    Aucun paiement enregistré
                </div>
            </div>
        </div>

        <div v-else class="ed-empty-state">
            <i class="bx bx-info-circle"></i>
            <p>Votre compte n'est pas encore lié à un profil apprenant. Contactez l'administration.</p>
        </div>
    </div>
</template>

<style scoped>
/* ===== Header ===== */
.ed-header { padding: 28px; border-radius: 16px; margin-bottom: 24px; color: white; display: flex; align-items: center; }
.ed-header-left { display: flex; align-items: center; gap: 16px; }
.ed-avatar { width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; }
.ed-title { font-size: 22px; font-weight: 800; margin: 0; }
.ed-subtitle { font-size: 13px; opacity: 0.85; margin: 4px 0 0; }

.ed-content { display: flex; flex-direction: column; gap: 20px; }

/* ===== Stats ===== */
.ed-stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.ed-stat { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px; transition: transform 0.2s, box-shadow 0.2s; }
.ed-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.ed-stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.ed-stat-icon i { font-size: 22px; }
.ed-stat-val { font-size: 18px; font-weight: 800; }
.ed-stat-lbl { font-size: 12px; color: #94a3b8; }

/* ===== Barre de progression ===== */
.pay-progress-card { background: white; border-radius: 14px; padding: 20px 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.pay-progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.pay-progress-label { font-size: 14px; font-weight: 700; color: #1e293b; }
.pay-progress-pct { font-size: 20px; font-weight: 900; }
.pay-progress-bar { height: 12px; background: #f1f5f9; border-radius: 20px; overflow: hidden; }
.pay-progress-fill { height: 100%; border-radius: 20px; transition: width 1s cubic-bezier(0.4,0,0.2,1); }
.pay-progress-legend { display: flex; gap: 20px; margin-top: 10px; font-size: 12px; color: #64748b; }
.pay-progress-legend span { display: flex; align-items: center; gap: 6px; }

/* ===== Onglets ===== */
.pay-tabs { display: flex; gap: 10px; }
.pay-tab { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 12px; border: 2px solid #e2e8f0; background: white; color: #64748b; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.25s; font-family: inherit; }
.pay-tab:hover { border-color: #0FBCAF; color: #0FBCAF; }
.pay-tab.active { background: #0FBCAF; color: white; border-color: #0FBCAF; box-shadow: 0 4px 14px rgba(15,188,175,0.3); }
.pay-tab.active .tab-count { background: rgba(255,255,255,0.25); color: white; }
.tab-count { background: #f1f5f9; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; transition: all 0.25s; }

/* ===== Card ===== */
.ed-card { background: white; border-radius: 14px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); animation: fadeSlideIn 0.3s ease both; }
.ed-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; }
.ed-card-header i { font-size: 20px; }
.ed-card-header h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }

/* ===== Table ===== */
.ed-table-wrap { overflow-x: auto; }
.ed-table { width: 100%; border-collapse: collapse; }
.ed-table th { background: #f8fafc; padding: 10px 14px; font-size: 12px; font-weight: 700; color: #64748b; text-align: left; white-space: nowrap; }
.ed-table td { padding: 12px 14px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }
.ed-table tbody tr { transition: background 0.15s; }
.ed-table tbody tr:hover { background: #fafafa; }

.mode-tag { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #0B5697; background: #EBF5FF; padding: 4px 10px; border-radius: 8px; font-weight: 600; text-transform: capitalize; }

/* ===== Badges ===== */
.pd-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: capitalize; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }

/* ===== Mobile Cards ===== */
.mobile-cards { display: none; }
.mobile-pay-card { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 10px; }
.mpc-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 13px; color: #334155; }
.mpc-row + .mpc-row { border-top: 1px solid #f1f5f9; }
.mpc-title { padding-bottom: 10px; margin-bottom: 4px; border-bottom: 1px solid #e2e8f0 !important; }
.mpc-label { color: #94a3b8; font-size: 12px; font-weight: 600; }

/* ===== Empty ===== */
.ed-empty { text-align: center; padding: 40px; color: #94a3b8; font-size: 14px; }
.ed-empty-state { text-align: center; padding: 60px; background: white; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.ed-empty-state i { font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 10px; }
.ed-empty-state p { color: #94a3b8; }

@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
    .ed-stats-row { grid-template-columns: 1fr; }
    .ed-stat-val { font-size: 15px; }
    .pay-tabs { flex-direction: column; }
    .ed-table-wrap { display: none; }
    .mobile-cards { display: block; }
}
</style>
