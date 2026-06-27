import { watch } from 'vue';

/**
 * Composable : héritage automatique des champs liés à l'Apprenant.
 *
 * Quand `form.apprenant_id` change, met à jour automatiquement (si l'Apprenant a la valeur)
 * et si la clé existe sur `form` :
 *   - classe_id, ecole_id, campus_id, institution_id
 *   - niveau_id, section_id, cycle_id
 *   - pays_id
 *
 * Usage :
 *   import { useApprenantCascade } from '@/Composables/useApprenantCascade';
 *   useApprenantCascade(form, () => props.apprenants);
 *
 * Les apprenants passés doivent contenir les FK (classe_id, ecole_id, ...)
 * pour que la cascade fonctionne. Le contrôleur backend doit les inclure dans le select.
 *
 * @param {object} form - Inertia useForm() object
 * @param {() => Array} getApprenants - getter retournant la liste des apprenants
 * @param {object} [options]
 * @param {string} [options.apprenantKey='apprenant_id']
 * @param {string[]} [options.fields] - liste des champs à hériter
 */
export function useApprenantCascade(form, getApprenants, options = {}) {
    const apprenantKey = options.apprenantKey || 'apprenant_id';
    const fields = options.fields || [
        'classe_id',
        'ecole_id',
        'campus_id',
        'institution_id',
        'niveau_id',
        'section_id',
        'cycle_id',
        'pays_id',
    ];

    watch(() => form[apprenantKey], (newId) => {
        if (!newId) return;
        const apprenants = typeof getApprenants === 'function' ? getApprenants() : (getApprenants || []);
        const apprenant = apprenants?.find(a => String(a.id) === String(newId));
        if (!apprenant) return;

        fields.forEach(field => {
            if (apprenant[field] !== undefined && apprenant[field] !== null && field in form) {
                form[field] = apprenant[field];
            }
        });
    });
}
