<!--
  ApprenantContactsSection.vue — bloc "Contacts" affiché sur la fiche
  Apprenant (Show + PDF). Résume en cartes visuelles :
    • Parents rattachés (avec père/mère si renseignés)
    • Tuteurs (avec relation)
    • Accompagnateurs (transporteurs / surveillants)

  Chaque entrée est cliquable et renvoie vers la fiche du contact
  correspondant.

  Props :
    - parents : Array<{id, pere_nom, pere_prenoms, mere_nom, mere_prenoms, ...}>
    - tuteurs : Array<{id, nom, prenoms, relation, telephone, ...}>
    - accompagnateurs : Array<{id, accompagnant1_nom, accompagnant1_prenoms, ...}>
-->
<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    parents: { type: Array, default: () => [] },
    tuteurs: { type: Array, default: () => [] },
    accompagnateurs: { type: Array, default: () => [] },
});

// Aplatir parents en couples (père + mère) affichables
const parentEntries = computed(() =>
    props.parents.flatMap((p) => {
        const entries = [];
        if (p.pere_nom || p.pere_prenoms) {
            entries.push({
                id: p.id,
                type: 'Père',
                nom: `${p.pere_prenoms ?? ''} ${p.pere_nom ?? ''}`.trim(),
                telephone: p.pere_telephone_1,
                email: p.pere_email_1,
                icon: 'fa-user-tie',
                color: '#0b5697',
            });
        }
        if (p.mere_nom || p.mere_prenoms) {
            entries.push({
                id: p.id,
                type: 'Mère',
                nom: `${p.mere_prenoms ?? ''} ${p.mere_nom ?? ''}`.trim(),
                telephone: p.mere_telephone_1,
                email: p.mere_email_1,
                icon: 'fa-user',
                color: '#e5590c',
            });
        }
        return entries;
    })
);

const tuteurEntries = computed(() =>
    props.tuteurs.map((t) => ({
        id: t.id,
        type: t.relation || 'Tuteur',
        nom: `${t.prenoms ?? t.user?.prenoms ?? ''} ${t.nom ?? t.user?.nom ?? ''}`.trim(),
        telephone: t.telephone,
        email: t.email ?? t.user?.email,
        icon: 'fa-user-shield',
        color: '#8b5cf6',
    }))
);

const accompEntries = computed(() =>
    props.accompagnateurs.flatMap((a) => {
        const list = [];
        for (let i = 1; i <= 3; i++) {
            const nom = a[`accompagnant${i}_nom`];
            const prenoms = a[`accompagnant${i}_prenoms`];
            if (nom || prenoms) {
                list.push({
                    id: a.id,
                    type: `Accompagnant ${i}`,
                    nom: `${prenoms ?? ''} ${nom ?? ''}`.trim(),
                    icon: 'fa-user-friends',
                    color: '#10b981',
                });
            }
        }
        return list;
    })
);

const totalContacts = computed(() => parentEntries.value.length + tuteurEntries.value.length + accompEntries.value.length);

const parentRoute = (id) => (typeof route === 'function' ? route('parents.show', id) : '#');
const tuteurRoute = (id) => (typeof route === 'function' ? route('tuteurs.show', id) : '#');
const accompRoute = (id) => (typeof route === 'function' ? route('accompagnateurs.show', id) : '#');
</script>

<template>
    <div class="contacts-section">
        <div class="section-title">
            <i class="fa fa-address-book me-2"></i>
            Contacts de l'apprenant
            <span class="badge bg-primary ms-2">{{ totalContacts }}</span>
        </div>

        <div v-if="totalContacts === 0" class="empty">
            <i class="fa fa-info-circle me-2 text-muted"></i>
            <span class="text-muted">Aucun contact rattaché à cet apprenant</span>
        </div>

        <div v-else class="contacts-grid">
            <!-- Parents -->
            <Link
                v-for="(entry, idx) in parentEntries"
                :key="'p-' + entry.id + '-' + idx"
                :href="parentRoute(entry.id)"
                class="contact-card"
            >
                <div class="contact-avatar" :style="{ background: entry.color }">
                    <i class="fa" :class="entry.icon"></i>
                </div>
                <div class="contact-body">
                    <div class="contact-type">{{ entry.type }}</div>
                    <div class="contact-name">{{ entry.nom }}</div>
                    <div class="contact-meta">
                        <span v-if="entry.telephone" class="meta-item">
                            <i class="fa fa-phone me-1"></i>{{ entry.telephone }}
                        </span>
                        <span v-if="entry.email" class="meta-item">
                            <i class="fa fa-envelope me-1"></i>{{ entry.email }}
                        </span>
                    </div>
                </div>
                <i class="fa fa-external-link-alt chevron"></i>
            </Link>

            <!-- Tuteurs -->
            <Link
                v-for="(entry, idx) in tuteurEntries"
                :key="'t-' + entry.id + '-' + idx"
                :href="tuteurRoute(entry.id)"
                class="contact-card"
            >
                <div class="contact-avatar" :style="{ background: entry.color }">
                    <i class="fa" :class="entry.icon"></i>
                </div>
                <div class="contact-body">
                    <div class="contact-type">{{ entry.type }}</div>
                    <div class="contact-name">{{ entry.nom || '—' }}</div>
                    <div class="contact-meta">
                        <span v-if="entry.telephone" class="meta-item">
                            <i class="fa fa-phone me-1"></i>{{ entry.telephone }}
                        </span>
                        <span v-if="entry.email" class="meta-item">
                            <i class="fa fa-envelope me-1"></i>{{ entry.email }}
                        </span>
                    </div>
                </div>
                <i class="fa fa-external-link-alt chevron"></i>
            </Link>

            <!-- Accompagnateurs -->
            <Link
                v-for="(entry, idx) in accompEntries"
                :key="'a-' + entry.id + '-' + idx"
                :href="accompRoute(entry.id)"
                class="contact-card"
            >
                <div class="contact-avatar" :style="{ background: entry.color }">
                    <i class="fa" :class="entry.icon"></i>
                </div>
                <div class="contact-body">
                    <div class="contact-type">{{ entry.type }}</div>
                    <div class="contact-name">{{ entry.nom }}</div>
                </div>
                <i class="fa fa-external-link-alt chevron"></i>
            </Link>
        </div>
    </div>
</template>

<style scoped>
.contacts-section {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px 22px;
    border: 1px solid #e2e8f0;
    margin-bottom: 20px;
}

.section-title {
    color: #0b5697;
    font-weight: 700;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #0b5697;
    font-size: 1.05rem;
    display: flex;
    align-items: center;
}

.empty {
    padding: 24px;
    text-align: center;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px dashed #cbd5e1;
}

.contacts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
}

.contact-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}
.contact-card:hover {
    background: #eef2f7;
    border-color: #0b5697;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(11, 86, 151, 0.08);
}

.contact-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

.contact-body {
    flex: 1;
    min-width: 0;
}

.contact-type {
    font-size: 0.7rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.contact-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9rem;
    margin: 2px 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.contact-meta {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 4px;
}

.meta-item {
    font-size: 0.72rem;
    color: #64748b;
}

.chevron {
    color: #cbd5e1;
    font-size: 0.75rem;
    flex-shrink: 0;
}
</style>
