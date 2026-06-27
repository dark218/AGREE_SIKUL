import { watch } from 'vue';

/**
 * Composable : cascade géographique complète.
 *
 * Watch Quartier → Commune → Département → Région → Pays.
 *
 * Quand un niveau est sélectionné, les niveaux supérieurs se remplissent automatiquement
 * (si la clé existe sur form ET si les données de cascade sont présentes sur les lookups).
 *
 * Usage :
 *   import { useGeoCascade } from '@/Composables/useGeoCascade';
 *   useGeoCascade(form, {
 *       quartiers: () => props.quartiers,
 *       communes: () => props.communes,
 *       departements: () => props.departements,
 *       regions: () => props.regions,
 *   });
 *
 * Les Communes doivent contenir `departement_id`.
 * Les Départements doivent contenir `region_id` ET `pays_id`.
 * Les Régions doivent contenir `pays_id`.
 * Les Quartiers peuvent contenir directement la hiérarchie (commune_id, departement_id, ...).
 *
 * @param {object} form - Inertia useForm() object
 * @param {object} lookups - { quartiers, communes, departements, regions } getters
 */
export function useGeoCascade(form, lookups = {}) {
    const get = (key) => {
        const v = lookups[key];
        return typeof v === 'function' ? v() : (v || []);
    };

    const setIfExists = (key, value) => {
        if (value !== undefined && value !== null && key in form) {
            form[key] = value;
        }
    };

    // Quartier → Commune + Dept + Région + Pays
    watch(() => form.quartier_id, (newId) => {
        if (!newId) return;
        const quartier = get('quartiers').find(q => String(q.id) === String(newId));
        if (!quartier) return;
        // Cas 1 : le quartier a déjà la hiérarchie pré-calculée (LocalisationBlock style)
        if (quartier.commune_id) setIfExists('commune_id', quartier.commune_id);
        if (quartier.departement_id) setIfExists('departement_id', quartier.departement_id);
        if (quartier.region_id) setIfExists('region_id', quartier.region_id);
        if (quartier.pays_id) setIfExists('pays_id', quartier.pays_id);
        // Cas 2 : sinon, on remonte via les autres tables
        if (!quartier.departement_id && quartier.commune_id) {
            const commune = get('communes').find(c => String(c.id) === String(quartier.commune_id));
            if (commune?.departement_id) {
                setIfExists('departement_id', commune.departement_id);
                const dept = get('departements').find(d => String(d.id) === String(commune.departement_id));
                if (dept) {
                    if (dept.region_id) setIfExists('region_id', dept.region_id);
                    if (dept.pays_id) setIfExists('pays_id', dept.pays_id);
                }
            }
        }
    });

    // Commune → Département + Région + Pays
    watch(() => form.commune_id, (newId) => {
        if (!newId) return;
        const commune = get('communes').find(c => String(c.id) === String(newId));
        if (!commune?.departement_id) return;
        setIfExists('departement_id', commune.departement_id);
        const dept = get('departements').find(d => String(d.id) === String(commune.departement_id));
        if (dept) {
            if (dept.region_id) setIfExists('region_id', dept.region_id);
            if (dept.pays_id) setIfExists('pays_id', dept.pays_id);
        }
    });

    // Département → Région + Pays
    watch(() => form.departement_id, (newId) => {
        if (!newId) return;
        const dept = get('departements').find(d => String(d.id) === String(newId));
        if (!dept) return;
        if (dept.region_id) setIfExists('region_id', dept.region_id);
        if (dept.pays_id) setIfExists('pays_id', dept.pays_id);
    });

    // Région → Pays
    watch(() => form.region_id, (newId) => {
        if (!newId) return;
        const region = get('regions').find(r => String(r.id) === String(newId));
        if (region?.pays_id) setIfExists('pays_id', region.pays_id);
    });
}
