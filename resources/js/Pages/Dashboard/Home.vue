<script setup>
import { computed, ref } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Doughnut, Bar, Line } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, PointElement, LineElement, Filler } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, PointElement, LineElement, Filler);

defineOptions({ layout: DashboardLayout });

const page = usePage();
const user = computed(() => page.props.auth?.user);

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    parClasse: { type: Array, default: () => [] },
    parEcole: { type: Array, default: () => [] },
    recentActivities: { type: Array, default: () => [] },
    evolutionMensuelle: { type: Array, default: () => [] },
    parMatiere: { type: Array, default: () => [] },
    resultatsExamens: { type: Object, default: () => ({ admis: 0, refuses: 0 }) },
});

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Bonjour';
    if (h < 18) return 'Bon après-midi';
    return 'Bonsoir';
});

// Doughnut — Filles / Garçons
const genderData = computed(() => ({
    labels: ['Filles', 'Garçons'],
    datasets: [{
        data: [props.stats.filles || 0, props.stats.garcons || 0],
        backgroundColor: ['#0FBCAF', '#0B5697'],
        borderWidth: 0,
        cutout: '72%',
    }],
}));

// Doughnut — Résultats Examens
const resultatsData = computed(() => ({
    labels: ['Admis', 'Refusés'],
    datasets: [{
        data: [props.resultatsExamens.admis || 0, props.resultatsExamens.refuses || 0],
        backgroundColor: ['#0FBCAF', '#094578'],
        borderWidth: 0,
        cutout: '72%',
    }],
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle', font: { size: 11, weight: '600' } } },
    },
};

// Bar — Apprenants par Classe
const barClasseData = computed(() => ({
    labels: props.parClasse.map(c => c.label),
    datasets: [{
        label: 'Effectif',
        data: props.parClasse.map(c => c.value),
        backgroundColor: props.parClasse.map((_, i) => i % 2 === 0 ? '#0B5697' : '#0FBCAF'),
        borderRadius: 8,
        borderSkipped: false,
        barThickness: 35,
    }],
}));

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { font: { size: 11 } } },
        x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
    },
};

// Line — Évolution Mensuelle
const lineData = computed(() => ({
    labels: props.evolutionMensuelle.map(e => e.mois),
    datasets: [{
        label: 'Inscriptions',
        data: props.evolutionMensuelle.map(e => e.value),
        borderColor: '#0B5697',
        backgroundColor: 'rgba(11, 86, 151, 0.08)',
        borderWidth: 3,
        pointBackgroundColor: '#0FBCAF',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 6,
        pointHoverRadius: 9,
        tension: 0.4,
        fill: true,
    }],
}));

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: '#f0f2f5' }, ticks: { font: { size: 11 } } },
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
    },
};

// Bar horizontal — Notes par Matière
const barMatiereData = computed(() => ({
    labels: props.parMatiere.map(m => m.label),
    datasets: [{
        label: 'Notes',
        data: props.parMatiere.map(m => m.value),
        backgroundColor: '#0FBCAF',
        borderRadius: 6,
        barThickness: 20,
    }],
}));

const barHorizOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
        x: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { font: { size: 10 } } },
        y: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' } } },
    },
};
</script>

<template>
    <Head title="Tableau de bord" />
    <div class="body-wrapper">
        <div class="home-page">
            <!-- HERO -->
            <div class="hero">
                <div class="hero-content">
                    <div>
                        <h2 class="hero-title">{{ greeting }}, {{ user?.prenoms || user?.nom || 'Admin' }} !</h2>
                        <p class="hero-sub">Bienvenue sur votre tableau de bord AGREE SIKUL</p>
                    </div>
                    <div class="hero-date">
                        <i class="fa fa-calendar-alt me-1"></i>
                        {{ new Date().toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </div>
                </div>
                <div class="hero-wave"></div>
            </div>

            <!-- STAT CARDS -->
            <div class="stat-cards">
                <div class="sc"><div class="sc-icon sc-blue"><i class="fa fa-graduation-cap"></i></div><div class="sc-info"><span class="sc-val">{{ stats.totalApprenants || 0 }}</span><span class="sc-lbl">Apprenants</span></div></div>
                <div class="sc"><div class="sc-icon sc-cyan"><i class="fa fa-chalkboard-teacher"></i></div><div class="sc-info"><span class="sc-val">{{ stats.totalEnseignants || 0 }}</span><span class="sc-lbl">Enseignants</span></div></div>
                <div class="sc"><div class="sc-icon sc-blue-dark"><i class="fa fa-door-open"></i></div><div class="sc-info"><span class="sc-val">{{ stats.totalClasses || 0 }}</span><span class="sc-lbl">Classes</span></div></div>
                <div class="sc"><div class="sc-icon sc-cyan"><i class="fa fa-school"></i></div><div class="sc-info"><span class="sc-val">{{ stats.totalEcoles || 0 }}</span><span class="sc-lbl">Écoles</span></div></div>
                <div class="sc"><div class="sc-icon sc-blue"><i class="fa fa-laptop"></i></div><div class="sc-info"><span class="sc-val">{{ stats.totalExamens || 0 }}</span><span class="sc-lbl">Examens</span></div></div>
                <div class="sc"><div class="sc-icon sc-blue-dark"><i class="fa fa-users"></i></div><div class="sc-info"><span class="sc-val">{{ stats.totalUsers || 0 }}</span><span class="sc-lbl">Utilisateurs</span></div></div>
            </div>

            <!-- ROW 1: Doughnuts + Evolution -->
            <div class="row-3">
                <div class="chart-box">
                    <h6 class="chart-title"><i class="fa fa-venus-mars me-2"></i> Répartition Filles / Garçons</h6>
                    <div class="chart-container" style="height: 220px;">
                        <Doughnut :data="genderData" :options="doughnutOptions" />
                    </div>
                    <div class="chart-center-text">
                        <span class="center-big">{{ stats.totalApprenants || 0 }}</span>
                        <span class="center-small">Total</span>
                    </div>
                </div>
                <div class="chart-box">
                    <h6 class="chart-title"><i class="fa fa-chart-line me-2"></i> Évolution des Inscriptions</h6>
                    <div class="chart-container" style="height: 220px;">
                        <Line :data="lineData" :options="lineOptions" />
                    </div>
                </div>
                <div class="chart-box">
                    <h6 class="chart-title"><i class="fa fa-trophy me-2"></i> Résultats Examens</h6>
                    <div class="chart-container" style="height: 220px;">
                        <Doughnut :data="resultatsData" :options="doughnutOptions" />
                    </div>
                    <div class="chart-center-text">
                        <span class="center-big">{{ stats.totalCompositions || 0 }}</span>
                        <span class="center-small">Compositions</span>
                    </div>
                </div>
            </div>

            <!-- ROW 2: Bar classe + Bar matière -->
            <div class="row-2">
                <div class="chart-box">
                    <h6 class="chart-title"><i class="fa fa-chart-bar me-2"></i> Effectifs par Classe</h6>
                    <div class="chart-container" style="height: 240px;">
                        <Bar :data="barClasseData" :options="barOptions" />
                    </div>
                </div>
                <div class="chart-box">
                    <h6 class="chart-title"><i class="fa fa-book me-2"></i> Notes par Matière</h6>
                    <div class="chart-container" style="height: 240px;">
                        <Bar v-if="parMatiere.length > 0" :data="barMatiereData" :options="barHorizOptions" />
                        <div v-else class="text-center text-muted py-5">Aucune donnée</div>
                    </div>
                </div>
            </div>

            <!-- ROW 3: Examens résumé + Activités -->
            <div class="row-2">
                <div class="chart-box">
                    <h6 class="chart-title"><i class="fa fa-file-alt me-2"></i> Examens en Ligne</h6>
                    <div class="exam-stats">
                        <div class="exam-stat"><div class="exam-stat-val">{{ stats.totalExamens || 0 }}</div><div class="exam-stat-lbl">Créés</div></div>
                        <div class="exam-stat"><div class="exam-stat-val" style="color:#0FBCAF;">{{ stats.examensPublies || 0 }}</div><div class="exam-stat-lbl">En cours</div></div>
                        <div class="exam-stat"><div class="exam-stat-val" style="color:#0B5697;">{{ stats.totalCompositions || 0 }}</div><div class="exam-stat-lbl">Compositions</div></div>
                        <div class="exam-stat"><div class="exam-stat-val" style="color:#0FBCAF;">{{ stats.moyenneExamens || 0 }}/20</div><div class="exam-stat-lbl">Moyenne</div></div>
                    </div>
                    <div class="exam-extra">
                        <div><i class="fa fa-sticky-note me-1"></i> {{ stats.totalNotes || 0 }} notes saisies</div>
                        <div><i class="fa fa-file-alt me-1"></i> {{ stats.totalBulletins || 0 }} bulletins</div>
                    </div>
                </div>
                <div class="chart-box">
                    <h6 class="chart-title"><i class="fa fa-clock me-2"></i> Activités Récentes</h6>
                    <div v-if="recentActivities.length > 0" class="activities">
                        <div v-for="(a, i) in recentActivities" :key="i" class="activity-item">
                            <div class="activity-icon"><i class="fa fa-pen"></i></div>
                            <div class="activity-info">
                                <strong>{{ a.apprenant }}</strong> a composé <em>{{ a.examen }}</em>
                                <div class="activity-meta">
                                    <span class="badge" :class="a.score >= 10 ? 'bg-success' : 'bg-danger'">{{ a.score }}/20</span>
                                    <small class="text-muted">{{ a.date }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-muted py-4 small">Aucune activité récente</div>
                </div>
            </div>

            <!-- QUICK LINKS -->
            <div class="quick-section">
                <h6 class="chart-title"><i class="fa fa-bolt me-2"></i> Accès Rapides</h6>
                <div class="quick-grid">
                    <Link href="/academique/apprenants" class="q-card"><i class="fa fa-users"></i><span>Apprenants</span></Link>
                    <Link href="/academique/enseignants" class="q-card"><i class="fa fa-chalkboard-teacher"></i><span>Enseignants</span></Link>
                    <Link href="/academique/examens-en-ligne" class="q-card"><i class="fa fa-laptop"></i><span>Examens</span></Link>
                    <Link href="/academique/resultats-examens" class="q-card"><i class="fa fa-chart-line"></i><span>Résultats</span></Link>
                    <Link href="/academique/bulletins" class="q-card"><i class="fa fa-file-alt"></i><span>Bulletins</span></Link>
                    <Link href="/academique/notes" class="q-card"><i class="fa fa-star"></i><span>Notes</span></Link>
                    <Link href="/rapport/statistiques-ecole" class="q-card"><i class="fa fa-chart-pie"></i><span>Statistiques</span></Link>
                    <Link href="/rapports" class="q-card"><i class="fa fa-chart-bar"></i><span>Rapports</span></Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.home-page { width: 100%; }

/* HERO */
.hero { background: linear-gradient(135deg, #0B5697 0%, #094578 40%, #0FBCAF 100%); border-radius: 16px; padding: 28px 30px; color: white; position: relative; overflow: hidden; margin-bottom: 20px; }
.hero-content { display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1; }
.hero-title { font-size: 24px; font-weight: 800; margin-bottom: 4px; }
.hero-sub { font-size: 13px; opacity: 0.8; }
.hero-date { font-size: 12px; opacity: 0.75; background: rgba(255,255,255,0.12); padding: 6px 14px; border-radius: 20px; }
.hero-wave { position: absolute; bottom: -25px; left: -20px; right: -20px; height: 60px; background: rgba(255,255,255,0.06); border-radius: 50% 50% 0 0; }

/* STAT CARDS */
.stat-cards { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 20px; }
.sc { background: white; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); transition: all 0.3s; border: 1px solid #f0f0f0; }
.sc:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(11,86,151,0.1); border-color: #0FBCAF; }
.sc-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; flex-shrink: 0; }
.sc-blue { background: linear-gradient(135deg, #0B5697, #1a6bb5); }
.sc-cyan { background: linear-gradient(135deg, #0FBCAF, #0da89d); }
.sc-blue-dark { background: linear-gradient(135deg, #094578, #0B5697); }
.sc-val { font-size: 24px; font-weight: 900; color: #1e293b; display: block; line-height: 1; }
.sc-lbl { font-size: 11px; color: #94a3b8; }

/* ROWS */
.row-3 { display: grid; grid-template-columns: 1fr 1.5fr 1fr; gap: 16px; margin-bottom: 16px; }
.row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }

.chart-box { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; position: relative; }
.chart-title { font-weight: 700; color: #0B5697; font-size: 13px; border-bottom: 2px solid #0FBCAF; padding-bottom: 8px; margin-bottom: 15px; }
.chart-container { position: relative; }

/* Center text for doughnuts */
.chart-center-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none; margin-top: -15px; }
.center-big { font-size: 22px; font-weight: 900; color: #0B5697; display: block; line-height: 1; }
.center-small { font-size: 9px; color: #94a3b8; text-transform: uppercase; }

/* Exam stats */
.exam-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 12px; }
.exam-stat { text-align: center; padding: 12px 8px; background: #f8fbff; border-radius: 10px; }
.exam-stat-val { font-size: 22px; font-weight: 900; color: #1e293b; }
.exam-stat-lbl { font-size: 10px; color: #94a3b8; }
.exam-extra { display: flex; gap: 20px; font-size: 12px; color: #64748b; }
.exam-extra i { color: #0FBCAF; }

/* Activities */
.activities { display: flex; flex-direction: column; gap: 8px; }
.activity-item { display: flex; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f5f5f5; }
.activity-icon { width: 32px; height: 32px; border-radius: 50%; background: #e8f0f8; color: #0B5697; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
.activity-info { font-size: 12px; color: #333; }
.activity-info em { color: #0B5697; font-style: normal; font-weight: 600; }
.activity-meta { display: flex; gap: 8px; align-items: center; margin-top: 3px; }

/* Quick links */
.quick-section { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; }
.quick-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 10px; }
.q-card { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 14px 8px; border-radius: 12px; border: 2px solid #e8ecf0; text-decoration: none; color: #333; font-size: 11px; font-weight: 600; transition: all 0.3s; }
.q-card i { font-size: 20px; color: #0B5697; }
.q-card:hover { border-color: #0FBCAF; background: #f0faf9; transform: translateY(-3px); box-shadow: 0 6px 15px rgba(15,188,175,0.12); color: #0B5697; }

@media (max-width: 1200px) { .stat-cards { grid-template-columns: repeat(3, 1fr); } .row-3 { grid-template-columns: 1fr; } .quick-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 768px) { .stat-cards { grid-template-columns: repeat(2, 1fr); } .row-2 { grid-template-columns: 1fr; } .hero-content { flex-direction: column; gap: 10px; text-align: center; } .quick-grid { grid-template-columns: repeat(2, 1fr); } }
</style>
