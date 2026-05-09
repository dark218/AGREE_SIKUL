<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();

const props = defineProps({
    statistiques: { type: Array, default: () => [] },
    totaux: { type: Object, default: () => ({}) },
});

const stats = computed(() => Array.isArray(props.statistiques) ? props.statistiques : []);
const showExportMenu = ref(false);

// Export CSV
const exportCSV = () => {
    const headers = ['École', 'Inscrits', 'Filles', 'Garçons', 'Enseignants', 'Classes', '% Filles'];
    const rows = stats.value.map(s => [s.nom, s.nombre_inscrits, s.nombre_filles, s.nombre_garcons, s.nombre_enseignants, s.nombre_classes, s.taux_filles + '%']);
    const csv = [headers.join(';'), ...rows.map(r => r.join(';'))].join('\n');
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'statistiques_ecole_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
    showExportMenu.value = false;
};

// Print
const printPage = () => {
    window.print();
    showExportMenu.value = false;
};

// Barre de pourcentage max
const maxInscrits = computed(() => Math.max(...stats.value.map(s => s.nombre_inscrits), 1));
</script>

<template>
    <Head title="Statistiques École" />
    <div class="body-wrapper">
        <div class="stats-page">
            <!-- Header -->
            <div class="stats-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1"><i class="fa fa-chart-pie me-2"></i> Statistiques par École</h4>
                        <small class="opacity-75">Vue d'ensemble des effectifs et répartitions</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm" @click="printPage"><i class="fa fa-print me-1"></i> Imprimer</button>
                        <button class="btn btn-light btn-sm" @click="exportCSV"><i class="fa fa-file-csv me-1"></i> Export CSV</button>
                    </div>
                </div>
            </div>

            <!-- Cards globales -->
            <div class="global-cards">
                <div class="g-card g-blue">
                    <div class="g-card-icon"><i class="fa fa-users"></i></div>
                    <div class="g-card-info">
                        <span class="g-card-value">{{ (totaux.total_inscrits || 0).toLocaleString('fr-FR') }}</span>
                        <span class="g-card-label">Total Inscrits</span>
                    </div>
                </div>
                <div class="g-card g-blue-light">
                    <div class="g-card-icon"><i class="fa fa-female"></i></div>
                    <div class="g-card-info">
                        <span class="g-card-value">{{ (totaux.total_filles || 0).toLocaleString('fr-FR') }}</span>
                        <span class="g-card-label">Filles ({{ totaux.taux_filles_global || 0 }}%)</span>
                    </div>
                </div>
                <div class="g-card g-blue-dark">
                    <div class="g-card-icon"><i class="fa fa-male"></i></div>
                    <div class="g-card-info">
                        <span class="g-card-value">{{ (totaux.total_garcons || 0).toLocaleString('fr-FR') }}</span>
                        <span class="g-card-label">Garçons</span>
                    </div>
                </div>
                <div class="g-card g-cyan">
                    <div class="g-card-icon"><i class="fa fa-chalkboard-teacher"></i></div>
                    <div class="g-card-info">
                        <span class="g-card-value">{{ totaux.total_enseignants || 0 }}</span>
                        <span class="g-card-label">Enseignants</span>
                    </div>
                </div>
                <div class="g-card g-cyan-light">
                    <div class="g-card-icon"><i class="fa fa-school"></i></div>
                    <div class="g-card-info">
                        <span class="g-card-value">{{ totaux.total_ecoles || 0 }}</span>
                        <span class="g-card-label">Écoles</span>
                    </div>
                </div>
                <div class="g-card g-blue">
                    <div class="g-card-icon"><i class="fa fa-door-open"></i></div>
                    <div class="g-card-info">
                        <span class="g-card-value">{{ totaux.total_classes || 0 }}</span>
                        <span class="g-card-label">Classes</span>
                    </div>
                </div>
            </div>

            <!-- Tableau détaillé par école -->
            <div class="stats-table-card mt-3">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th style="text-align:left; padding-left:12px;">École</th>
                                <th class="text-center">Inscrits</th>
                                <th class="text-center">Filles</th>
                                <th class="text-center">Garçons</th>
                                <th class="text-center">% Filles</th>
                                <th class="text-center">Enseignants</th>
                                <th class="text-center">Classes</th>
                                <th style="width:20%;">Répartition</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in stats" :key="s.id">
                                <td style="padding-left:12px;"><strong>{{ s.nom }}</strong></td>
                                <td class="text-center"><span class="badge bg-primary fs-6">{{ s.nombre_inscrits }}</span></td>
                                <td class="text-center"><span style="color:#e91e63; font-weight:700;">{{ s.nombre_filles }}</span></td>
                                <td class="text-center"><span style="color:#0B5697; font-weight:700;">{{ s.nombre_garcons }}</span></td>
                                <td class="text-center">{{ s.taux_filles }}%</td>
                                <td class="text-center">{{ s.nombre_enseignants }}</td>
                                <td class="text-center">{{ s.nombre_classes }}</td>
                                <td>
                                    <div class="bar-container">
                                        <div class="bar-fill bar-filles" :style="{ width: (s.nombre_inscrits > 0 ? (s.nombre_filles / s.nombre_inscrits * 100) : 0) + '%' }"></div>
                                        <div class="bar-fill bar-garcons" :style="{ width: (s.nombre_inscrits > 0 ? (s.nombre_garcons / s.nombre_inscrits * 100) : 0) + '%' }"></div>
                                    </div>
                                    <div class="bar-legend">
                                        <small style="color:#e91e63;">F: {{ s.nombre_filles }}</small>
                                        <small style="color:#0B5697;">G: {{ s.nombre_garcons }}</small>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="stats.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">Aucune donnée disponible</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Graphique visuel — barres horizontales -->
            <div class="chart-card mt-3" v-if="stats.length > 0">
                <h6 class="chart-title"><i class="fa fa-bar-chart me-2"></i> Effectifs par École</h6>
                <div class="h-bars">
                    <div v-for="s in stats" :key="'bar-' + s.id" class="h-bar-row">
                        <div class="h-bar-label">{{ s.nom }}</div>
                        <div class="h-bar-track">
                            <div class="h-bar-fill" :style="{ width: (s.nombre_inscrits / maxInscrits * 100) + '%' }">
                                <span class="h-bar-value">{{ s.nombre_inscrits }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.stats-header {
    background: linear-gradient(135deg, #0B5697 0%, #094578 50%, #0FBCAF 100%);
    border-radius: 12px;
    padding: 1.2rem 1.5rem;
    color: white;
    margin-bottom: 15px;
}

.global-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
}

.g-card {
    border-radius: 12px;
    padding: 16px;
    color: white;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: transform 0.3s, box-shadow 0.3s;
}
.g-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
.g-blue { background: linear-gradient(135deg, #0B5697, #1a6bb5); }
.g-blue-light { background: linear-gradient(135deg, #1a6bb5, #2980d0); }
.g-blue-dark { background: linear-gradient(135deg, #094578, #0B5697); }
.g-cyan { background: linear-gradient(135deg, #0FBCAF, #0da89d); }
.g-cyan-light { background: linear-gradient(135deg, #0da89d, #0FBCAF); }

.g-card-icon { font-size: 28px; opacity: 0.85; }
.g-card-value { font-size: 22px; font-weight: 900; display: block; line-height: 1; }
.g-card-label { font-size: 11px; opacity: 0.85; }

.stats-table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0,0,0,0.06);
}

.bar-container { display: flex; height: 14px; border-radius: 7px; overflow: hidden; background: #f0f0f0; }
.bar-fill { height: 100%; transition: width 0.5s; }
.bar-filles { background: linear-gradient(90deg, #e91e63, #f06292); }
.bar-garcons { background: linear-gradient(90deg, #0B5697, #1a6bb5); }
.bar-legend { display: flex; justify-content: space-between; margin-top: 2px; }

/* Chart horizontal bars */
.chart-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.06);
}
.chart-title {
    font-weight: 700;
    color: #0B5697;
    border-bottom: 2px solid #0FBCAF;
    padding-bottom: 8px;
    margin-bottom: 15px;
}
.h-bars { display: flex; flex-direction: column; gap: 10px; }
.h-bar-row { display: flex; align-items: center; gap: 12px; }
.h-bar-label { width: 150px; font-size: 12px; font-weight: 600; color: #333; text-align: right; flex-shrink: 0; }
.h-bar-track { flex: 1; height: 28px; background: #f0f2f5; border-radius: 14px; overflow: hidden; }
.h-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #0B5697, #0FBCAF);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 10px;
    transition: width 0.8s ease;
    min-width: 40px;
}
.h-bar-value { color: white; font-weight: 800; font-size: 12px; }

@media print {
    .stats-header .btn { display: none !important; }
    .body-wrapper { padding: 0 !important; }
}
</style>
