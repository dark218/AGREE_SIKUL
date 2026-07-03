<!--
  ApprenantsBadges.vue — affichage compact d'une liste d'apprenants sous
  forme de "badges" cliquables (renvoient vers la fiche apprenant).

  Utilisé dans les pages Show de Parent/Tuteur/Accompagnateur et dans les
  colonnes des listes Index (via mode="inline").

  Props :
    - apprenants : Array<{id, nom, prenoms, matricule, pivot?}>
    - mode : 'card' (par défaut, gros badges pour Show)
           | 'inline' (bulles compactes pour Index avec tooltip si nombreux)
    - showLien : booléen, affiche le lien de parenté depuis pivot.lien_parente
    - linkRoute : fonction (id) => route Inertia vers la fiche apprenant
    - emptyLabel : message si liste vide
-->
<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    apprenants: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'card',
        validator: (v) => ['card', 'inline'].includes(v),
    },
    showLien: { type: Boolean, default: false },
    linkRoute: {
        type: Function,
        default: (id) => (typeof route === 'function' ? route('academique.apprenants.show', id) : '#'),
    },
    emptyLabel: { type: String, default: 'Aucun apprenant rattaché' },
    maxInlineVisible: { type: Number, default: 3 },
});

const list = computed(() => Array.isArray(props.apprenants) ? props.apprenants : []);

const displayName = (a) => {
    const nom = a?.nom ?? '';
    const prenoms = a?.prenoms ?? '';
    return (prenoms + ' ' + nom).trim() || (a?.libelle ?? '—');
};

const lienLabel = (a) => {
    const lien = a?.pivot?.lien_parente ?? a?.pivot?.relation ?? null;
    if (!lien) return null;
    const map = {
        pere: 'Père',
        mere: 'Mère',
        tuteur_legal: 'Tuteur légal',
        autre: 'Autre',
    };
    return map[lien] || lien;
};

const inlineVisible = computed(() => list.value.slice(0, props.maxInlineVisible));
const inlineOverflow = computed(() => Math.max(0, list.value.length - props.maxInlineVisible));
const inlineTooltip = computed(() => list.value.map(displayName).join(' • '));
</script>

<template>
    <!-- Cartes détaillées pour les pages Show -->
    <div v-if="mode === 'card'" class="apprenants-badges-card">
        <div v-if="list.length === 0" class="empty-state">
            <i class="fa fa-user-graduate text-muted me-2"></i>
            <span class="text-muted">{{ emptyLabel }}</span>
        </div>
        <div v-else class="badges-grid">
            <Link
                v-for="a in list"
                :key="a.id"
                :href="linkRoute(a.id)"
                class="apprenant-card"
            >
                <div class="avatar">
                    <i class="fa fa-user-graduate"></i>
                    <span v-if="a?.pivot?.est_principal" class="principal-dot" title="Apprenant principal"></span>
                </div>
                <div class="info">
                    <div class="name">{{ displayName(a) }}</div>
                    <div class="matricule" v-if="a.matricule">{{ a.matricule }}</div>
                    <div class="lien" v-if="showLien && lienLabel(a)">
                        <i class="fa fa-heart me-1"></i>{{ lienLabel(a) }}
                    </div>
                </div>
                <i class="fa fa-chevron-right chevron"></i>
            </Link>
        </div>
    </div>

    <!-- Bulles compactes pour Index -->
    <div v-else class="apprenants-badges-inline" :title="inlineTooltip">
        <span v-if="list.length === 0" class="text-muted small">—</span>
        <template v-else>
            <span
                v-for="a in inlineVisible"
                :key="a.id"
                class="pill"
            >
                {{ displayName(a) }}
            </span>
            <span v-if="inlineOverflow > 0" class="pill pill-more">
                +{{ inlineOverflow }}
            </span>
        </template>
    </div>
</template>

<style scoped>
/* === Mode card === */
.badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 12px;
}

.apprenant-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}
.apprenant-card:hover {
    background: #eef2f7;
    border-color: #0b5697;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(11, 86, 151, 0.08);
}

.avatar {
    position: relative;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0b5697, #0fbcaf);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}
.principal-dot {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #f59e0b;
    border: 2px solid white;
}

.info {
    flex: 1;
    min-width: 0;
}
.name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.matricule {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 2px;
}
.lien {
    font-size: 0.7rem;
    color: #0fbcaf;
    margin-top: 4px;
    font-weight: 500;
}
.chevron {
    color: #cbd5e1;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.empty-state {
    padding: 20px;
    text-align: center;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px dashed #cbd5e1;
}

/* === Mode inline (Index) === */
.apprenants-badges-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: center;
}
.pill {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    background: #e0f2fe;
    color: #075985;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 500;
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pill-more {
    background: #f59e0b;
    color: white;
}
</style>
