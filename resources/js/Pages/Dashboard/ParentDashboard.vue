<script setup>
import { ref, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });
const page = usePage();
const props = defineProps({
    enfants: { type: Array, default: () => [] },
    user: { type: Object, default: () => ({}) },
});

const selectedEnfant = ref(props.enfants[0] || null);
const selectEnfant = (enfant) => { selectedEnfant.value = enfant; };

const moyenneColor = (moy) => {
    if (moy >= 16) return '#2E7D32';
    if (moy >= 14) return '#0B5697';
    if (moy >= 10) return '#F57F17';
    return '#D32F2F';
};
</script>

<template>
    <div class="parent-dashboard">
        <!-- Header -->
        <div class="pd-header">
            <div class="pd-header-content">
                <div>
                    <h1 class="pd-title">Bonjour, {{ user.prenoms }} {{ user.nom }}</h1>
                    <p class="pd-subtitle">Espace Parent — Suivi de vos enfants</p>
                </div>
                <div class="pd-enfants-count">
                    <i class="bx bxs-group"></i>
                    <span>{{ enfants.length }} enfant{{ enfants.length > 1 ? 's' : '' }}</span>
                </div>
            </div>
        </div>

        <!-- Sélecteur d'enfant -->
        <div class="pd-enfant-selector" v-if="enfants.length > 1">
            <button
                v-for="enfant in enfants"
                :key="enfant.id"
                class="pd-enfant-btn"
                :class="{ active: selectedEnfant?.id === enfant.id }"
                @click="selectEnfant(enfant)"
            >
                <i class="bx bxs-user"></i>
                <div>
                    <strong>{{ enfant.prenoms }} {{ enfant.nom }}</strong>
                    <small>{{ enfant.classe }} — {{ enfant.matricule }}</small>
                </div>
            </button>
        </div>

        <!-- Contenu principal -->
        <div v-if="selectedEnfant" class="pd-content">
            <!-- Carte info élève -->
            <div class="pd-card pd-student-card">
                <div class="pd-card-header">
                    <i class="bx bxs-graduation"></i>
                    <h3>Informations de l'élève</h3>
                </div>
                <div class="pd-student-grid">
                    <div class="pd-info"><span class="pd-label">Nom complet</span><span class="pd-value">{{ selectedEnfant.prenoms }} {{ selectedEnfant.nom }}</span></div>
                    <div class="pd-info"><span class="pd-label">Matricule</span><span class="pd-value">{{ selectedEnfant.matricule }}</span></div>
                    <div class="pd-info"><span class="pd-label">Classe</span><span class="pd-value pd-badge-blue">{{ selectedEnfant.classe }}</span></div>
                    <div class="pd-info"><span class="pd-label">École</span><span class="pd-value">{{ selectedEnfant.ecole }}</span></div>
                    <div class="pd-info"><span class="pd-label">Date de naissance</span><span class="pd-value">{{ selectedEnfant.date_naissance }}</span></div>
                    <div class="pd-info"><span class="pd-label">Sexe</span><span class="pd-value">{{ selectedEnfant.sexe === 'M' ? 'Masculin' : 'Féminin' }}</span></div>
                </div>
            </div>

            <!-- Stats rapides -->
            <div class="pd-stats-row">
                <div class="pd-stat-card" style="border-left: 4px solid #0B5697;">
                    <div class="pd-stat-icon" style="background: #EBF5FF;"><i class="bx bxs-bar-chart-alt-2" style="color:#0B5697;"></i></div>
                    <div><div class="pd-stat-value" :style="{ color: moyenneColor(selectedEnfant.moyenne_generale) }">{{ selectedEnfant.moyenne_generale || '—' }}/20</div><div class="pd-stat-label">Moyenne Générale</div></div>
                </div>
                <div class="pd-stat-card" style="border-left: 4px solid #0FBCAF;">
                    <div class="pd-stat-icon" style="background: #E6FAF8;"><i class="bx bxs-trophy" style="color:#0FBCAF;"></i></div>
                    <div><div class="pd-stat-value" style="color:#0FBCAF;">{{ selectedEnfant.rang || '—' }}</div><div class="pd-stat-label">Rang</div></div>
                </div>
                <div class="pd-stat-card" style="border-left: 4px solid #E5590C;">
                    <div class="pd-stat-icon" style="background: #FFF4ED;"><i class="bx bxs-calendar-x" style="color:#E5590C;"></i></div>
                    <div><div class="pd-stat-value" style="color:#E5590C;">{{ selectedEnfant.absences_count || 0 }}</div><div class="pd-stat-label">Absences</div></div>
                </div>
                <div class="pd-stat-card" style="border-left: 4px solid #8B5CF6;">
                    <div class="pd-stat-icon" style="background: #F3EEFF;"><i class="bx bxs-book-open" style="color:#8B5CF6;"></i></div>
                    <div><div class="pd-stat-value" style="color:#8B5CF6;">{{ selectedEnfant.notes_count || 0 }}</div><div class="pd-stat-label">Notes</div></div>
                </div>
            </div>

            <!-- Dernières notes -->
            <div class="pd-card">
                <div class="pd-card-header">
                    <i class="bx bxs-edit"></i>
                    <h3>Dernières notes</h3>
                </div>
                <div v-if="selectedEnfant.dernieres_notes?.length" class="pd-table-wrap">
                    <table class="pd-table">
                        <thead>
                            <tr><th>Matière</th><th>Note</th><th>Sur</th><th>Date</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="note in selectedEnfant.dernieres_notes" :key="note.id">
                                <td><strong>{{ note.matiere }}</strong></td>
                                <td :style="{ color: moyenneColor(note.note), fontWeight: 700 }">{{ note.note }}</td>
                                <td>/{{ note.note_sur }}</td>
                                <td>{{ note.date }}</td>
                                <td><span class="pd-badge" :class="note.statut === 'valide' ? 'pd-badge-green' : 'pd-badge-orange'">{{ note.statut }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="pd-empty">Aucune note enregistrée</div>
            </div>

            <!-- Bulletins -->
            <div class="pd-card">
                <div class="pd-card-header">
                    <i class="bx bxs-report"></i>
                    <h3>Bulletins</h3>
                </div>
                <div v-if="selectedEnfant.bulletins?.length" class="pd-bulletins-grid">
                    <div v-for="b in selectedEnfant.bulletins" :key="b.id" class="pd-bulletin-item">
                        <div class="pd-bulletin-periode">{{ b.periode }}</div>
                        <div class="pd-bulletin-moy" :style="{ color: moyenneColor(b.moyenne) }">{{ b.moyenne }}/20</div>
                        <div class="pd-bulletin-rang">Rang : {{ b.rang }}</div>
                        <div class="pd-bulletin-decision">
                            <span class="pd-badge" :class="b.decision === 'admis' ? 'pd-badge-green' : 'pd-badge-orange'">{{ b.decision }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="pd-empty">Aucun bulletin disponible</div>
            </div>

            <!-- Absences -->
            <div class="pd-card">
                <div class="pd-card-header">
                    <i class="bx bxs-calendar-x"></i>
                    <h3>Absences récentes</h3>
                </div>
                <div v-if="selectedEnfant.absences?.length" class="pd-table-wrap">
                    <table class="pd-table">
                        <thead>
                            <tr><th>Date</th><th>Matière</th><th>Heures</th><th>Motif</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="a in selectedEnfant.absences" :key="a.id">
                                <td>{{ a.date }}</td>
                                <td>{{ a.matiere }}</td>
                                <td>{{ a.heures }}h</td>
                                <td>{{ a.motif }}</td>
                                <td><span class="pd-badge" :class="a.statut === 'justifiee' ? 'pd-badge-green' : 'pd-badge-red'">{{ a.statut }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="pd-empty">Aucune absence</div>
            </div>

            <!-- Emploi du temps -->
            <div class="pd-card">
                <div class="pd-card-header">
                    <i class="bx bxs-time"></i>
                    <h3>Emploi du temps</h3>
                </div>
                <div v-if="selectedEnfant.emploi_du_temps?.length" class="pd-table-wrap">
                    <table class="pd-table">
                        <thead>
                            <tr><th>Jour</th><th>Horaire</th><th>Matière</th><th>Enseignant</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="edt in selectedEnfant.emploi_du_temps" :key="edt.id">
                                <td><strong>{{ edt.jour }}</strong></td>
                                <td>{{ edt.heure_debut }} - {{ edt.heure_fin }}</td>
                                <td>{{ edt.matiere }}</td>
                                <td>{{ edt.enseignant }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="pd-empty">Aucun emploi du temps</div>
            </div>
        </div>

        <div v-else class="pd-empty-state">
            <i class="bx bx-info-circle"></i>
            <p>Aucun enfant lié à votre compte. Veuillez contacter l'administration.</p>
        </div>
    </div>
</template>

<style scoped>
.parent-dashboard { padding: 0; }

.pd-header { background: linear-gradient(135deg, #0B5697, #0FBCAF); padding: 30px; border-radius: 16px; margin-bottom: 24px; color: white; }
.pd-header-content { display: flex; justify-content: space-between; align-items: center; }
.pd-title { font-size: 24px; font-weight: 800; margin-bottom: 4px; }
.pd-subtitle { font-size: 14px; opacity: 0.85; }
.pd-enfants-count { background: rgba(255,255,255,0.2); padding: 12px 20px; border-radius: 12px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.pd-enfants-count i { font-size: 20px; }

.pd-enfant-selector { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
.pd-enfant-btn { display: flex; align-items: center; gap: 12px; padding: 14px 20px; background: white; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.3s; text-align: left; }
.pd-enfant-btn:hover { border-color: #0FBCAF; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.pd-enfant-btn.active { border-color: #0B5697; background: #f0f7ff; }
.pd-enfant-btn i { font-size: 28px; color: #0B5697; }
.pd-enfant-btn strong { display: block; font-size: 14px; color: #1e293b; }
.pd-enfant-btn small { color: #94a3b8; font-size: 12px; }

.pd-content { display: flex; flex-direction: column; gap: 20px; }

.pd-card { background: white; border-radius: 14px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.pd-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; }
.pd-card-header i { font-size: 22px; color: #0B5697; }
.pd-card-header h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }

.pd-student-card { border-left: 4px solid #0B5697; }
.pd-student-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.pd-info { display: flex; flex-direction: column; gap: 2px; }
.pd-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
.pd-value { font-size: 14px; font-weight: 600; color: #1e293b; }

.pd-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.pd-stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px; }
.pd-stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.pd-stat-icon i { font-size: 24px; }
.pd-stat-value { font-size: 22px; font-weight: 800; }
.pd-stat-label { font-size: 12px; color: #94a3b8; }

.pd-table-wrap { overflow-x: auto; }
.pd-table { width: 100%; border-collapse: collapse; }
.pd-table th { background: #f8fafc; padding: 10px 14px; font-size: 12px; font-weight: 700; color: #64748b; text-align: left; text-transform: uppercase; letter-spacing: 0.5px; }
.pd-table td { padding: 10px 14px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }

.pd-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.pd-badge-green { background: #dcfce7; color: #16a34a; }
.pd-badge-orange { background: #fef3c7; color: #d97706; }
.pd-badge-red { background: #fee2e2; color: #dc2626; }
.pd-badge-blue { background: #dbeafe; color: #2563eb; padding: 4px 10px; border-radius: 6px; font-size: 13px; }

.pd-bulletins-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.pd-bulletin-item { background: #f8fafc; border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #e2e8f0; }
.pd-bulletin-periode { font-weight: 700; color: #0B5697; margin-bottom: 8px; text-transform: uppercase; font-size: 12px; }
.pd-bulletin-moy { font-size: 28px; font-weight: 900; }
.pd-bulletin-rang { font-size: 13px; color: #64748b; margin: 4px 0; }

.pd-empty { text-align: center; padding: 30px; color: #94a3b8; font-size: 14px; }
.pd-empty-state { text-align: center; padding: 60px; background: white; border-radius: 14px; }
.pd-empty-state i { font-size: 48px; color: #cbd5e1; }
.pd-empty-state p { color: #94a3b8; margin-top: 12px; }

@media (max-width: 768px) {
    .pd-student-grid { grid-template-columns: 1fr 1fr; }
    .pd-stats-row { grid-template-columns: 1fr 1fr; }
    .pd-bulletins-grid { grid-template-columns: 1fr; }
}
</style>
