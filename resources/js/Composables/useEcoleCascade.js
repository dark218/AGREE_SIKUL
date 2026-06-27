import { watch } from 'vue';

/**
 * Composable : héritage automatique des champs liés à l'École.
 *
 * Quand `form.ecole_id` change, met à jour automatiquement (si l'École a la valeur)
 * et si la clé existe sur `form` :
 *   - institution_id
 *   - campus_id
 *   - pays_id
 *
 * Usage minimal :
 *   import { useEcoleCascade } from '@/Composables/useEcoleCascade';
 *   useEcoleCascade(form, () => props.ecoles);
 *
 * Les écoles passées doivent contenir `pays_id`, `institution_id`, `campus_id`
 * pour que la cascade fonctionne. Si les FK ne sont pas présentes, rien ne se passe.
 *
 * @param {object} form - Inertia useForm() object (réactif)
 * @param {() => Array} getEcoles - getter retournant la liste des écoles (réactif)
 * @param {object} [options]
 * @param {string} [options.ecoleKey='ecole_id']
 * @param {string[]} [options.fields=['institution_id','campus_id','pays_id']]
 */
export function useEcoleCascade(form, getEcoles, options = {}) {
    const ecoleKey = options.ecoleKey || 'ecole_id';
    const fields = options.fields || ['institution_id', 'campus_id', 'pays_id'];

    watch(() => form[ecoleKey], (newId) => {
        if (!newId) return;
        const ecoles = typeof getEcoles === 'function' ? getEcoles() : (getEcoles || []);
        const ecole = ecoles?.find(e => String(e.id) === String(newId));
        if (!ecole) return;

        fields.forEach(field => {
            if (ecole[field] !== undefined && ecole[field] !== null && field in form) {
                form[field] = ecole[field];
            }
        });
    });
}
