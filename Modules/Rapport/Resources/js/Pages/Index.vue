<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    apprenantParEcole: { type: Array, default: () => [] },
    enseignantParEcole: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    classesParEcole: { type: Array, default: () => [] },
});

const exportCSV = () => {
    const headers = ['École', 'Statut', 'Apprenants', 'Enseignants', 'Classes'];
    const rows = props.ecoles.map(e => [e.nom, e.statut, e.apprenants, e.enseignants, e.classes]);
    const csv = [headers.join(';'), ...rows.map(r => r.join(';'))].join('\n');
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'rapport_general_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
};

const maxApprenants = Math.max(...(props.apprenantParEcole?.map(e => e.nombre) || [1]), 1);
</script>

<template>
    <Head title="Rapports" />
    <div class="body-wrapper">
        <div class="rapport-page">
            <!-- Header -->
            <div class="rapport-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><i class="fa fa-chart-line me-2"></i> Tableau de Bord — Rapports</h3>
                        <small class="opacity-75">Vue d'ensemble de l'établissement</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm" @click="window.print()"><i class="fa fa-print me-1"></i> Imprimer</button>
                        <button class="btn btn-light btn-sm" @click="exportCSV"><i class="fa fa-file-csv me-1"></i> Export CSV</button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stat-cards">
                <div class="s-card s-blue">
                    <div class="s-card-content">
                        <span class="s-card-value">{{ (stats.apprenants || 0).toLocaleString('fr-FR') }}</span>
                        <span class="s-card-label">Apprenants</span>
                    </div>
                    <div class="s-card-icon"><i class="fa fa-graduation-cap"></i></div>
                    <div class="s-card-wave"></div>
                </div>
                <div class="s-card s-blue-light">
                    <div class="s-card-content">
                        <span class="s-card-value">{{ stats.enseignants || 0 }}</span>
                        <span class="s-card-label">Enseignants</span>
                    </div>
                    <div class="s-card-icon"><i class="fa fa-chalkboard-teacher"></i></div>
                    <div class="s-card-wave"></div>
                </div>
                <div class="s-card s-cyan">
                    <div class="s-card-content">
                        <span class="s-card-value">{{ stats.classes || 0 }}</span>
                        <span class="s-card-label">Classes</span>
                    </div>
                    <div class="s-card-icon"><i class="fa fa-door-open"></i></div>
                    <div class="s-card-wave"></div>
                </div>
                <div class="s-card s-blue-dark">
                    <div class="s-card-content">
                        <span class="s-card-value">{{ stats.ecoles || 0 }}</span>
                        <span class="s-card-label">Écoles</span>
                    </div>
                    <div class="s-card-icon"><i class="fa fa-school"></i></div>
                    <div class="s-card-wave"></div>
                </div>
                <div class="s-card s-cyan-dark">
                    <div class="s-card-content">
                        <span class="s-card-value">{{ stats.utilisateurs || 0 }}</span>
                        <span class="s-card-label">Utilisateurs</span>
                    </div>
                    <div class="s-card-icon"><i class="fa fa-users"></i></div>
                    <div class="s-card-wave"></div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="charts-row">
                <!-- Apprenants par école -->
                <div class="chart-box">
                    <h6 class="chart-box-title"><i class="fa fa-bar-chart me-2"></i> Apprenants par École</h6>
                    <div class="h-bars" v-if="apprenantParEcole.length > 0">
                        <div v-for="e in apprenantParEcole" :key="e.ecole" class="h-bar-row">
                            <div class="h-bar-label">{{ e.ecole }}</div>
                            <div class="h-bar-track">
                                <div class="h-bar-fill" :style="{ width: (e.nombre / maxApprenants * 100) + '%' }">
                                    <span class="h-bar-value">{{ e.nombre }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-muted py-3">Aucune donnée</div>
                </div>

                <!-- Enseignants par école -->
                <div class="chart-box">
                    <h6 class="chart-box-title"><i class="fa fa-users me-2"></i> Enseignants par École</h6>
                    <div class="h-bars" v-if="enseignantParEcole.length > 0">
                        <div v-for="e in enseignantParEcole" :key="e.ecole" class="h-bar-row">
                            <div class="h-bar-label">{{ e.ecole }}</div>
                            <div class="h-bar-track">
                                <div class="h-bar-fill h-bar-orange" :style="{ width: (e.nombre / Math.max(...enseignantParEcole.map(x => x.nombre), 1) * 100) + '%' }">
                                    <span class="h-bar-value">{{ e.nombre }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-muted py-3">Aucune donnée</div>
                </div>
            </div>

            <!-- Tableau détaillé écoles -->
            <div class="detail-table mt-3">
                <h6 class="chart-box-title"><i class="fa fa-table me-2"></i> Détail par École</h6>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead><tr>
                            <th style="text-align:left; padding-left:12px;">École</th>
                            <th class="text-center">Apprenants</th>
                            <th class="text-center">Enseignants</th>
                            <th class="text-center">Classes</th>
                            <th class="text-center">Ratio Élèves/Classe</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="e in ecoles" :key="e.nom">
                                <td style="padding-left:12px;"><strong>{{ e.nom }}</strong></td>
                                <td class="text-center"><span class="badge bg-primary">{{ e.apprenants }}</span></td>
                                <td class="text-center"><span class="badge bg-success">{{ e.enseignants }}</span></td>
                                <td class="text-center">{{ e.classes }}</td>
                                <td class="text-center">
                                    <strong>{{ e.classes > 0 ? Math.round(e.apprenants / e.classes) : 0 }}</strong>
                                    <small class="text-muted"> élèves/classe</small>
                                </td>
                            </tr>
                            <tr v-if="ecoles.length === 0"><td colspan="5" class="text-center py-4 text-muted">Aucune donnée</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Accès rapides -->
            <div class="quick-links mt-3">
                <h6 class="chart-box-title"><i class="fa fa-link me-2"></i> Accès Rapides</h6>
                <div class="links-grid">
                    <Link :href="route('rapport.statistiques-ecole.index')" class="q-link">
                        <i class="fa fa-school"></i><span>Statistiques École</span>
                    </Link>
                    <Link :href="route('rapport.statistiques-classes.index')" class="q-link">
                        <i class="fa fa-chalkboard"></i><span>Statistiques Classes</span>
                    </Link>
                    <a href="/academique/resultats-examens" class="q-link">
                        <i class="fa fa-chart-line"></i><span>Résultats Examens</span>
                    </a>
                    <a href="/academique/bulletins" class="q-link">
                        <i class="fa fa-file-alt"></i><span>Bulletins</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rapport-header { background: linear-gradient(135deg, #0B5697 0%, #094578 40%, #0FBCAF 80%, #E5590C 100%); border-radius: 16px; padding: 1.5rem 2rem; color: white; margin-bottom: 18px; }

/* Stat cards with wave */
.stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 18px; }
.s-card { border-radius: 14px; padding: 20px; color: white; position: relative; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; }
.s-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
.s-blue { background: linear-gradient(135deg, #0B5697, #1a6bb5); }
.s-blue-light { background: linear-gradient(135deg, #1a6bb5, #2980d0); }
.s-blue-dark { background: linear-gradient(135deg, #094578, #0B5697); }
.s-cyan { background: linear-gradient(135deg, #0FBCAF, #0da89d); }
.s-cyan-dark { background: linear-gradient(135deg, #0a8e83, #0FBCAF); }

.s-card-content { position: relative; z-index: 1; }
.s-card-value { font-size: 28px; font-weight: 900; display: block; line-height: 1.1; }
.s-card-label { font-size: 12px; opacity: 0.85; }
.s-card-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 40px; opacity: 0.15; }
.s-card-wave { position: absolute; bottom: -20px; left: -20px; right: -20px; height: 60px; background: rgba(255,255,255,0.08); border-radius: 50% 50% 0 0; }

/* Charts row */
.charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
@media (max-width: 768px) { .charts-row { grid-template-columns: 1fr; } }

.chart-box { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.06); }
.chart-box-title { font-weight: 700; color: #0B5697; border-bottom: 2px solid #0FBCAF; padding-bottom: 8px; margin-bottom: 15px; }

.h-bars { display: flex; flex-direction: column; gap: 10px; }
.h-bar-row { display: flex; align-items: center; gap: 10px; }
.h-bar-label { width: 120px; font-size: 11px; font-weight: 600; color: #333; text-align: right; flex-shrink: 0; }
.h-bar-track { flex: 1; height: 26px; background: #f0f2f5; border-radius: 13px; overflow: hidden; }
.h-bar-fill { height: 100%; background: linear-gradient(90deg, #0B5697, #0FBCAF); border-radius: 13px; display: flex; align-items: center; justify-content: flex-end; padding-right: 8px; transition: width 0.8s ease; min-width: 35px; }
.h-bar-fill.h-bar-orange { background: linear-gradient(90deg, #E5590C, #fd7e14); }
.h-bar-value { color: white; font-weight: 800; font-size: 11px; }

.detail-table { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.06); }

/* Quick links */
.quick-links { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.06); }
.links-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }
.q-link { display: flex; align-items: center; gap: 10px; padding: 14px 16px; border-radius: 10px; border: 2px solid #e8ecf0; text-decoration: none; color: #333; font-weight: 600; font-size: 13px; transition: all 0.3s; }
.q-link i { color: #0B5697; font-size: 18px; }
.q-link:hover { border-color: #0FBCAF; background: #f0faf9; transform: translateX(4px); color: #0B5697; }

@media print { .rapport-header .btn, .quick-links { display: none !important; } }
</style>
