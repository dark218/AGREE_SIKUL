<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Pagination from '@/Components/Common/Pagination.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();

const props = defineProps({
    statistiques: { type: Object, default: () => ({ data: [] }) },
});

const stats = computed(() => props.statistiques?.data || []);
const maxEffectif = computed(() => Math.max(...stats.value.map(s => s.effectif_total), 1));

const totaux = computed(() => {
    const data = stats.value;
    return {
        inscrits: data.reduce((s, c) => s + (c.effectif_total || 0), 0),
        filles: data.reduce((s, c) => s + (c.nombre_filles || 0), 0),
        garcons: data.reduce((s, c) => s + (c.nombre_garcons || 0), 0),
        enseignants: data.reduce((s, c) => s + (c.nombre_enseignants || 0), 0),
    };
});

const exportCSV = () => {
    const headers = ['Classe', 'École', 'Effectif', 'Filles', 'Garçons', 'Enseignants', '% Filles'];
    const rows = stats.value.map(s => [s.nom, s.ecole?.nom || '-', s.effectif_total, s.nombre_filles, s.nombre_garcons, s.nombre_enseignants, s.taux_filles + '%']);
    const csv = [headers.join(';'), ...rows.map(r => r.join(';'))].join('\n');
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'statistiques_classes_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
};
</script>

<template>
    <Head title="Statistiques Classes" />
    <div class="body-wrapper">
        <div class="stats-page">
            <div class="stats-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1"><i class="fa fa-chart-bar me-2"></i> Statistiques par Classe</h4>
                        <small class="opacity-75">Effectifs et répartitions par classe</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm" @click="window.print()"><i class="fa fa-print me-1"></i> Imprimer</button>
                        <button class="btn btn-light btn-sm" @click="exportCSV"><i class="fa fa-file-csv me-1"></i> Export CSV</button>
                    </div>
                </div>
            </div>

            <div class="mini-cards">
                <div class="m-card m-blue"><i class="fa fa-users"></i><div><strong>{{ totaux.inscrits }}</strong><span>Inscrits</span></div></div>
                <div class="m-card m-blue-light"><i class="fa fa-female"></i><div><strong>{{ totaux.filles }}</strong><span>Filles</span></div></div>
                <div class="m-card m-blue-dark"><i class="fa fa-male"></i><div><strong>{{ totaux.garcons }}</strong><span>Garçons</span></div></div>
                <div class="m-card m-cyan"><i class="fa fa-chalkboard-teacher"></i><div><strong>{{ totaux.enseignants }}</strong><span>Enseignants</span></div></div>
            </div>

            <div class="stats-table-card mt-3">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead><tr>
                            <th style="text-align:left; padding-left:12px;">Classe</th>
                            <th>École</th>
                            <th class="text-center">Effectif</th>
                            <th class="text-center">Filles</th>
                            <th class="text-center">Garçons</th>
                            <th class="text-center">% Filles</th>
                            <th class="text-center">Enseignants</th>
                            <th style="width:22%;">Répartition</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="s in stats" :key="s.id">
                                <td style="padding-left:12px;"><strong>{{ s.nom }}</strong></td>
                                <td>{{ s.ecole?.nom || '-' }}</td>
                                <td class="text-center"><span class="badge bg-primary">{{ s.effectif_total }}</span></td>
                                <td class="text-center" style="color:#e91e63; font-weight:700;">{{ s.nombre_filles }}</td>
                                <td class="text-center" style="color:#0B5697; font-weight:700;">{{ s.nombre_garcons }}</td>
                                <td class="text-center">
                                    <div class="pct-circle" :style="{ '--pct': s.taux_filles }"><span>{{ s.taux_filles }}%</span></div>
                                </td>
                                <td class="text-center">{{ s.nombre_enseignants }}</td>
                                <td>
                                    <div class="bar-container">
                                        <div class="bar-filles" :style="{ width: (s.effectif_total > 0 ? (s.nombre_filles / s.effectif_total * 100) : 0) + '%' }"></div>
                                        <div class="bar-garcons" :style="{ width: (s.effectif_total > 0 ? (s.nombre_garcons / s.effectif_total * 100) : 0) + '%' }"></div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="stats.length === 0"><td colspan="8" class="text-center py-4 text-muted">Aucune donnée</td></tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :data="statistiques" />
            </div>

            <div class="chart-card mt-3" v-if="stats.length > 0">
                <h6 class="chart-title"><i class="fa fa-signal me-2"></i> Effectifs par Classe</h6>
                <div class="v-bars">
                    <div v-for="s in stats" :key="'vbar-' + s.id" class="v-bar-col">
                        <div class="v-bar-wrapper"><div class="v-bar" :style="{ height: (s.effectif_total / maxEffectif * 100) + '%' }"><span class="v-bar-val">{{ s.effectif_total }}</span></div></div>
                        <span class="v-bar-label">{{ s.nom }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.stats-header { background: linear-gradient(135deg, #0B5697 0%, #094578 50%, #0FBCAF 100%); border-radius: 12px; padding: 1.2rem 1.5rem; color: white; margin-bottom: 12px; }
.mini-cards { display: flex; gap: 10px; flex-wrap: wrap; }
.m-card { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 10px; color: white; flex: 1; min-width: 140px; }
.m-card i { font-size: 22px; opacity: 0.8; }
.m-card strong { font-size: 20px; font-weight: 900; display: block; line-height: 1; }
.m-card span { font-size: 10px; opacity: 0.85; }
.m-blue { background: linear-gradient(135deg, #0B5697, #1a6bb5); }
.m-blue-light { background: linear-gradient(135deg, #1a6bb5, #2980d0); }
.m-blue-dark { background: linear-gradient(135deg, #094578, #0B5697); }
.m-cyan { background: linear-gradient(135deg, #0FBCAF, #0da89d); }
.stats-table-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 15px rgba(0,0,0,0.06); }
.bar-container { display: flex; height: 12px; border-radius: 6px; overflow: hidden; background: #f0f0f0; }
.bar-filles { background: linear-gradient(90deg, #e91e63, #f06292); height: 100%; }
.bar-garcons { background: linear-gradient(90deg, #0B5697, #1a6bb5); height: 100%; }
.pct-circle { width: 40px; height: 40px; border-radius: 50%; background: conic-gradient(#e91e63 calc(var(--pct) * 1%), #e8e8e8 0); display: flex; align-items: center; justify-content: center; margin: 0 auto; }
.pct-circle span { font-size: 9px; font-weight: 800; background: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.chart-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.06); }
.chart-title { font-weight: 700; color: #0B5697; border-bottom: 2px solid #0FBCAF; padding-bottom: 8px; margin-bottom: 15px; }
.v-bars { display: flex; gap: 8px; align-items: flex-end; height: 200px; padding: 0 10px; }
.v-bar-col { flex: 1; text-align: center; display: flex; flex-direction: column; align-items: center; }
.v-bar-wrapper { width: 100%; height: 180px; display: flex; align-items: flex-end; justify-content: center; }
.v-bar { width: 70%; max-width: 50px; background: linear-gradient(180deg, #0B5697, #0FBCAF); border-radius: 6px 6px 0 0; display: flex; align-items: flex-start; justify-content: center; padding-top: 4px; transition: height 0.8s ease; min-height: 20px; }
.v-bar-val { color: white; font-size: 10px; font-weight: 800; }
.v-bar-label { font-size: 9px; font-weight: 600; color: #555; margin-top: 4px; max-width: 60px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
