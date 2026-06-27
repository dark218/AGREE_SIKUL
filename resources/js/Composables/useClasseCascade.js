import { watch } from 'vue';

/**
 * Composable : héritage automatique des champs liés à la Classe.
 *
 * Quand `form.classe_id` change, met à jour automatiquement (si la Classe a la valeur)
 * et si la clé existe sur `form` :
 *   - ecole_id, campus_id, institution_id, niveau_id, section_id, cycle_id
 *   - annee_scolaire_id, pays_id
 *
 * Usage :
 *   import { useClasseCascade } from '@/Composables/useClasseCascade';
 *   useClasseCascade(form, () => props.classes);
 *
 * Les classes passées doivent contenir les FK (ecole_id, campus_id, niveau_id, ...)
 * pour que la cascade fonctionne. Le contrôleur backend doit les inclure dans le select.
 *
 * @param {object} form - Inertia useForm() object
 * @param {() => Array} getClasses - getter retournant la liste des classes
 * @param {object} [options]
 * @param {string} [options.classeKey='classe_id']
 * @param {string[]} [options.fields] - liste des champs à hériter
 */
export function useClasseCascade(form, getClasses, options = {}) {
    const classeKey = options.classeKey || 'classe_id';
    const fields = options.fields || [
        'ecole_id',
        'campus_id',
        'institution_id',
        'niveau_id',
        'section_id',
        'cycle_id',
        'annee_scolaire_id',
        'pays_id',
    ];

    watch(() => form[classeKey], (newId) => {
        if (!newId) return;
        const classes = typeof getClasses === 'function' ? getClasses() : (getClasses || []);
        const classe = classes?.find(c => String(c.id) === String(newId));
        if (!classe) return;

        fields.forEach(field => {
            if (classe[field] !== undefined && classe[field] !== null && field in form) {
                form[field] = classe[field];
            }
        });
    });
}
