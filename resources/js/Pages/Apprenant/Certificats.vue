<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    apprenant: { type: Object, default: null },
    certificats: { type: Array, default: () => [] },
});

const a = computed(() => props.apprenant || {});

const filterType = ref('all');

const typeConfig = {
    attestation: { bg: '#EBF5FF', color: '#0B5697', border: '#0B5697', icon: 'bx bxs-certification', label: 'Attestation' },
    fiche: { bg: '#F3EEFF', color: '#8B5CF6', border: '#8B5CF6', icon: 'bx bxs-id-card', label: 'Fiche' },
    bulletin: { bg: '#FFF4ED', color: '#E5590C', border: '#E5590C', icon: 'bx bxs-file-pdf', label: 'Bulletin' },
};

function getType(type) {
    return typeConfig[type] || typeConfig.attestation;
}

const filteredCertificats = computed(() => {
    if (filterType.value === 'all') return props.certificats;
    return props.certificats.filter(c => c.type === filterType.value);
});

const countByType = computed(() => ({
    all: props.certificats.length,
    attestation: props.certificats.filter(c => c.type === 'attestation').length,
    fiche: props.certificats.filter(c => c.type === 'fiche').length,
    bulletin: props.certificats.filter(c => c.type === 'bulletin').length,
}));

function downloadCertificat(cert) {
    window.open(cert.url, '_blank');
}
</script>

<template>
    <div class="eleve-dashboard">
        <!-- Header -->
        <div class="ed-header">
            <div class="ed-header-left">
                <div class="ed-avatar"><i class="bx bxs-certification"></i></div>
                <div>
                    <h1 class="ed-title">Mes Certificats & Documents</h1>
                    <p class="ed-subtitle" v-if="a.id">{{ a.prenoms }} {{ a.nom }} — {{ a.classe }} — {{ a.ecole }}</p>
                </div>
            </div>
        </div>

        <div v-if="a.id" class="ed-content">
            <!-- Stats -->
            <div class="ed-stats-row">
                <div class="ed-stat" style="border-left: 4px solid #8B5CF6;">
                    <div class="ed-stat-icon" style="background:#F3EEFF;"><i class="bx bxs-folder-open" style="color:#8B5CF6;"></i></div>
                    <div><div class="ed-stat-val" style="color:#8B5CF6;">{{ countByType.all }}</div><div class="ed-stat-lbl">Total documents</div></div>
                </div>
                <div class="ed-stat" style="border-left: 4px solid #0B5697;">
                    <div class="ed-stat-icon" style="background:#EBF5FF;"><i class="bx bxs-certification" style="color:#0B5697;"></i></div>
                    <div><div class="ed-stat-val" style="color:#0B5697;">{{ countByType.attestation }}</div><div class="ed-stat-lbl">Attestations</div></div>
                </div>
                <div class="ed-stat" style="border-left: 4px solid #E5590C;">
                    <div class="ed-stat-icon" style="background:#FFF4ED;"><i class="bx bxs-file-pdf" style="color:#E5590C;"></i></div>
                    <div><div class="ed-stat-val" style="color:#E5590C;">{{ countByType.bulletin }}</div><div class="ed-stat-lbl">Bulletins</div></div>
                </div>
                <div class="ed-stat" style="border-left: 4px solid #0FBCAF;">
                    <div class="ed-stat-icon" style="background:#E6FAF8;"><i class="bx bxs-id-card" style="color:#0FBCAF;"></i></div>
                    <div><div class="ed-stat-val" style="color:#0FBCAF;">{{ countByType.fiche }}</div><div class="ed-stat-lbl">Fiches</div></div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="cert-filters">
                <button class="cert-filter-btn" :class="{ active: filterType === 'all' }" @click="filterType = 'all'">
                    <i class="bx bx-grid-alt"></i> Tous <span class="filter-count">{{ countByType.all }}</span>
                </button>
                <button class="cert-filter-btn" :class="{ active: filterType === 'attestation' }" @click="filterType = 'attestation'">
                    <i class="bx bxs-certification"></i> Attestations <span class="filter-count">{{ countByType.attestation }}</span>
                </button>
                <button class="cert-filter-btn" :class="{ active: filterType === 'bulletin' }" @click="filterType = 'bulletin'">
                    <i class="bx bxs-file-pdf"></i> Bulletins <span class="filter-count">{{ countByType.bulletin }}</span>
                </button>
                <button class="cert-filter-btn" :class="{ active: filterType === 'fiche' }" @click="filterType = 'fiche'">
                    <i class="bx bxs-id-card"></i> Fiches <span class="filter-count">{{ countByType.fiche }}</span>
                </button>
            </div>

            <!-- Liste des certificats -->
            <div class="ed-card">
                <div class="ed-card-header"><i class="bx bxs-download"></i><h3>Documents disponibles</h3></div>

                <div v-if="filteredCertificats.length" class="cert-grid">
                    <div
                        v-for="(cert, idx) in filteredCertificats"
                        :key="cert.id"
                        class="cert-card"
                        :style="{ '--delay': idx * 0.06 + 's', borderLeft: '4px solid ' + getType(cert.type).border }"
                    >
                        <div class="cert-card-top">
                            <div class="cert-icon" :style="{ background: getType(cert.type).bg }">
                                <i :class="getType(cert.type).icon" :style="{ color: getType(cert.type).color }"></i>
                            </div>
                            <span class="pd-badge" :style="{ background: getType(cert.type).bg, color: getType(cert.type).color }">
                                {{ getType(cert.type).label }}
                            </span>
                        </div>
                        <h4 class="cert-title">{{ cert.titre }}</h4>
                        <p class="cert-desc">{{ cert.description }}</p>
                        <button class="cert-download-btn" @click="downloadCertificat(cert)" :disabled="!cert.disponible">
                            <i class="bx bxs-download"></i>
                            <span>Télécharger le PDF</span>
                        </button>
                    </div>
                </div>

                <div v-else class="ed-empty">
                    <i class="bx bx-folder-open" style="font-size:48px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                    Aucun document disponible pour ce filtre
                </div>
            </div>
        </div>

        <!-- Pas de profil -->
        <div v-else class="ed-empty-state">
            <i class="bx bx-info-circle"></i>
            <p>Votre compte n'est pas encore lié à un profil apprenant. Contactez l'administration.</p>
        </div>
    </div>
</template>

<style scoped>
/* ===== Header (identique EleveDashboard) ===== */
.ed-header { background: linear-gradient(135deg, #8B5CF6, #6D28D9); padding: 28px; border-radius: 16px; margin-bottom: 24px; color: white; display: flex; align-items: center; }
.ed-header-left { display: flex; align-items: center; gap: 16px; }
.ed-avatar { width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; }
.ed-title { font-size: 22px; font-weight: 800; margin: 0; }
.ed-subtitle { font-size: 13px; opacity: 0.85; margin: 4px 0 0; }

.ed-content { display: flex; flex-direction: column; gap: 20px; }

/* ===== Stats (identique EleveDashboard) ===== */
.ed-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.ed-stat { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px; transition: transform 0.2s, box-shadow 0.2s; }
.ed-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.ed-stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.ed-stat-icon i { font-size: 22px; }
.ed-stat-val { font-size: 22px; font-weight: 800; }
.ed-stat-lbl { font-size: 12px; color: #94a3b8; }

/* ===== Filtres ===== */
.cert-filters { display: flex; gap: 10px; flex-wrap: wrap; }
.cert-filter-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 12px; border: 2px solid #e2e8f0; background: white; color: #64748b; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.25s; font-family: inherit; }
.cert-filter-btn:hover { border-color: #8B5CF6; color: #8B5CF6; background: #faf5ff; }
.cert-filter-btn.active { background: #8B5CF6; color: white; border-color: #8B5CF6; box-shadow: 0 4px 14px rgba(139,92,246,0.3); }
.cert-filter-btn.active .filter-count { background: rgba(255,255,255,0.25); color: white; }
.filter-count { background: #f1f5f9; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; transition: all 0.25s; }

/* ===== Card (identique EleveDashboard) ===== */
.ed-card { background: white; border-radius: 14px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.ed-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; }
.ed-card-header i { font-size: 20px; color: #8B5CF6; }
.ed-card-header h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }

/* ===== Grille de certificats ===== */
.cert-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }

.cert-card { background: #f8fafc; border-radius: 14px; padding: 20px; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); animation: fadeSlideIn 0.4s ease both; animation-delay: var(--delay, 0s); }
.cert-card:hover { background: #f1f5f9; transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }

.cert-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.cert-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }
.cert-icon i { font-size: 24px; }

.cert-title { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 6px; line-height: 1.3; }
.cert-desc { font-size: 12px; color: #64748b; margin: 0 0 16px; line-height: 1.5; }

.cert-download-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 11px 0; background: linear-gradient(135deg, #8B5CF6, #7c3aed); color: white; border: none; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 700; font-family: inherit; transition: all 0.25s; }
.cert-download-btn:hover { background: linear-gradient(135deg, #7c3aed, #6D28D9); transform: scale(1.02); box-shadow: 0 4px 14px rgba(139,92,246,0.35); }
.cert-download-btn:disabled { background: #cbd5e1; cursor: not-allowed; box-shadow: none; transform: none; }
.cert-download-btn i { font-size: 18px; }

/* ===== Badges (identique EleveDashboard) ===== */
.pd-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }

/* ===== Empty ===== */
.ed-empty { text-align: center; padding: 40px; color: #94a3b8; font-size: 14px; }
.ed-empty-state { text-align: center; padding: 60px; background: white; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.ed-empty-state i { font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 10px; }
.ed-empty-state p { color: #94a3b8; }

/* ===== Animation ===== */
@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
    .ed-stats-row { grid-template-columns: 1fr 1fr; }
    .cert-grid { grid-template-columns: 1fr; }
    .cert-filters { gap: 6px; }
    .cert-filter-btn { padding: 8px 12px; font-size: 12px; }
}
@media (max-width: 480px) {
    .ed-stats-row { grid-template-columns: 1fr; }
}
</style>
