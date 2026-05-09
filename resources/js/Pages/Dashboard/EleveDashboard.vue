<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });
const page = usePage();
const props = defineProps({
    apprenant: { type: Object, default: () => ({}) },
    user: { type: Object, default: () => ({}) },
});

const a = computed(() => props.apprenant || {});

const moyenneColor = (moy) => {
    if (moy >= 16) return '#2E7D32';
    if (moy >= 14) return '#0B5697';
    if (moy >= 10) return '#F57F17';
    return '#D32F2F';
};
</script>

<template>
    <div class="eleve-dashboard">
        <!-- Header -->
        <div class="ed-header">
            <div class="ed-header-left">
                <div class="ed-avatar">{{ (a.prenoms || 'E')[0] }}</div>
                <div>
                    <h1 class="ed-title">Bonjour, {{ a.prenoms }} {{ a.nom }}</h1>
                    <p class="ed-subtitle">{{ a.classe }} — {{ a.ecole }} — {{ a.matricule }}</p>
                </div>
            </div>
        </div>

        <div v-if="a.id" class="ed-content">
            <!-- Stats -->
            <div class="ed-stats-row">
                <div class="ed-stat" style="border-left: 4px solid #0B5697;">
                    <div class="ed-stat-icon" style="background:#EBF5FF;"><i class="bx bxs-bar-chart-alt-2" style="color:#0B5697;"></i></div>
                    <div><div class="ed-stat-val" :style="{ color: moyenneColor(a.moyenne_generale) }">{{ a.moyenne_generale || '—' }}/20</div><div class="ed-stat-lbl">Moyenne</div></div>
                </div>
                <div class="ed-stat" style="border-left: 4px solid #0FBCAF;">
                    <div class="ed-stat-icon" style="background:#E6FAF8;"><i class="bx bxs-trophy" style="color:#0FBCAF;"></i></div>
                    <div><div class="ed-stat-val" style="color:#0FBCAF;">{{ a.rang || '—' }}</div><div class="ed-stat-lbl">Rang</div></div>
                </div>
                <div class="ed-stat" style="border-left: 4px solid #E5590C;">
                    <div class="ed-stat-icon" style="background:#FFF4ED;"><i class="bx bxs-calendar-x" style="color:#E5590C;"></i></div>
                    <div><div class="ed-stat-val" style="color:#E5590C;">{{ a.absences_count || 0 }}</div><div class="ed-stat-lbl">Absences</div></div>
                </div>
                <div class="ed-stat" style="border-left: 4px solid #8B5CF6;">
                    <div class="ed-stat-icon" style="background:#F3EEFF;"><i class="bx bxs-book-open" style="color:#8B5CF6;"></i></div>
                    <div><div class="ed-stat-val" style="color:#8B5CF6;">{{ a.notes_count || 0 }}</div><div class="ed-stat-lbl">Notes</div></div>
                </div>
            </div>

            <!-- Notes -->
            <div class="ed-card">
                <div class="ed-card-header"><i class="bx bxs-edit"></i><h3>Mes notes</h3></div>
                <div v-if="a.dernieres_notes?.length" class="ed-table-wrap">
                    <table class="ed-table">
                        <thead><tr><th>Matière</th><th>Note</th><th>Sur</th><th>Date</th></tr></thead>
                        <tbody>
                            <tr v-for="n in a.dernieres_notes" :key="n.id">
                                <td><strong>{{ n.matiere }}</strong></td>
                                <td :style="{ color: moyenneColor(n.note), fontWeight: 700 }">{{ n.note }}</td>
                                <td>/{{ n.note_sur }}</td>
                                <td>{{ n.date }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="ed-empty">Aucune note</div>
            </div>

            <!-- Bulletins -->
            <div class="ed-card">
                <div class="ed-card-header"><i class="bx bxs-report"></i><h3>Mes bulletins</h3></div>
                <div v-if="a.bulletins?.length" class="ed-bulletins">
                    <div v-for="b in a.bulletins" :key="b.id" class="ed-bulletin">
                        <div class="ed-bul-periode">{{ b.periode }}</div>
                        <div class="ed-bul-moy" :style="{ color: moyenneColor(b.moyenne) }">{{ b.moyenne }}/20</div>
                        <div class="ed-bul-rang">Rang : {{ b.rang }}</div>
                        <span class="pd-badge" :class="b.decision === 'admis' ? 'pd-badge-green' : 'pd-badge-orange'">{{ b.decision }}</span>
                    </div>
                </div>
                <div v-else class="ed-empty">Aucun bulletin</div>
            </div>

            <!-- Emploi du temps -->
            <div class="ed-card">
                <div class="ed-card-header"><i class="bx bxs-time"></i><h3>Mon emploi du temps</h3></div>
                <div v-if="a.emploi_du_temps?.length" class="ed-table-wrap">
                    <table class="ed-table">
                        <thead><tr><th>Jour</th><th>Horaire</th><th>Matière</th><th>Enseignant</th></tr></thead>
                        <tbody>
                            <tr v-for="edt in a.emploi_du_temps" :key="edt.id">
                                <td><strong>{{ edt.jour }}</strong></td>
                                <td>{{ edt.heure_debut }} - {{ edt.heure_fin }}</td>
                                <td>{{ edt.matiere }}</td>
                                <td>{{ edt.enseignant }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="ed-empty">Aucun emploi du temps</div>
            </div>

            <!-- Absences -->
            <div class="ed-card">
                <div class="ed-card-header"><i class="bx bxs-calendar-x"></i><h3>Mes absences</h3></div>
                <div v-if="a.absences?.length" class="ed-table-wrap">
                    <table class="ed-table">
                        <thead><tr><th>Date</th><th>Matière</th><th>Heures</th><th>Motif</th><th>Statut</th></tr></thead>
                        <tbody>
                            <tr v-for="ab in a.absences" :key="ab.id">
                                <td>{{ ab.date }}</td>
                                <td>{{ ab.matiere }}</td>
                                <td>{{ ab.heures }}h</td>
                                <td>{{ ab.motif }}</td>
                                <td><span class="pd-badge" :class="ab.statut === 'justifiee' ? 'pd-badge-green' : 'pd-badge-red'">{{ ab.statut }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="ed-empty">Aucune absence</div>
            </div>
        </div>

        <div v-else class="ed-empty-state">
            <i class="bx bx-info-circle"></i>
            <p>Votre compte n'est pas encore lié à un profil apprenant. Contactez l'administration.</p>
        </div>
    </div>
</template>

<style scoped>
.ed-header { background: linear-gradient(135deg, #8B5CF6, #6D28D9); padding: 28px; border-radius: 16px; margin-bottom: 24px; color: white; display: flex; align-items: center; }
.ed-header-left { display: flex; align-items: center; gap: 16px; }
.ed-avatar { width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 800; }
.ed-title { font-size: 22px; font-weight: 800; }
.ed-subtitle { font-size: 13px; opacity: 0.85; }

.ed-content { display: flex; flex-direction: column; gap: 20px; }

.ed-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.ed-stat { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px; }
.ed-stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.ed-stat-icon i { font-size: 22px; }
.ed-stat-val { font-size: 22px; font-weight: 800; }
.ed-stat-lbl { font-size: 12px; color: #94a3b8; }

.ed-card { background: white; border-radius: 14px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.ed-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; }
.ed-card-header i { font-size: 20px; color: #8B5CF6; }
.ed-card-header h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }

.ed-table-wrap { overflow-x: auto; }
.ed-table { width: 100%; border-collapse: collapse; }
.ed-table th { background: #f8fafc; padding: 10px 14px; font-size: 12px; font-weight: 700; color: #64748b; text-align: left; }
.ed-table td { padding: 10px 14px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }

.ed-bulletins { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.ed-bulletin { background: #faf5ff; border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #e9d5ff; }
.ed-bul-periode { font-weight: 700; color: #7c3aed; font-size: 12px; text-transform: uppercase; margin-bottom: 6px; }
.ed-bul-moy { font-size: 26px; font-weight: 900; }
.ed-bul-rang { font-size: 13px; color: #64748b; margin: 4px 0 8px; }

.pd-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.pd-badge-green { background: #dcfce7; color: #16a34a; }
.pd-badge-orange { background: #fef3c7; color: #d97706; }
.pd-badge-red { background: #fee2e2; color: #dc2626; }

.ed-empty { text-align: center; padding: 30px; color: #94a3b8; }
.ed-empty-state { text-align: center; padding: 60px; background: white; border-radius: 14px; }
.ed-empty-state i { font-size: 48px; color: #cbd5e1; }

@media (max-width: 768px) {
    .ed-stats-row { grid-template-columns: 1fr 1fr; }
    .ed-bulletins { grid-template-columns: 1fr; }
}
</style>
