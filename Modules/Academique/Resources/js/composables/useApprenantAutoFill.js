import { watch } from 'vue';

export function useApprenantAutoFill(form) {
    /**
     * Watch apprenant_id change and auto-fill classe, ecole, campus, etc
     */
    watch(
        () => form.apprenant_id,
        async (newApprenantId) => {
            if (!newApprenantId) {
                // Reset if no apprenant selected
                form.classe_id = '';
                form.ecole_id = '';
                form.campus_id = '';
                form.section_id = '';
                form.cycle_id = '';
                form.annee_scolaire_id = '';
                return;
            }

            try {
                console.log('🔵 useApprenantAutoFill - Fetching apprenant:', newApprenantId);

                const response = await fetch(route('academique.apprenants.api_show', newApprenantId));

                if (!response.ok) {
                    console.error('❌ Failed to fetch apprenant:', response.statusText);
                    return;
                }

                const data = await response.json();

                if (data.success && data.data) {
                    console.log('✅ useApprenantAutoFill - Auto-filling:', data.data);

                    // Auto-fill classe
                    if (data.data.classe_id) {
                        form.classe_id = data.data.classe_id;
                    }

                    // Auto-fill ecole
                    if (data.data.ecole_id) {
                        form.ecole_id = data.data.ecole_id;
                    }

                    // Auto-fill campus
                    if (data.data.campus_id) {
                        form.campus_id = data.data.campus_id;
                    }

                    // Auto-fill section
                    if (data.data.section_id) {
                        form.section_id = data.data.section_id;
                    }

                    // Auto-fill cycle
                    if (data.data.cycle_id) {
                        form.cycle_id = data.data.cycle_id;
                    }

                    // Auto-fill annee_scolaire
                    if (data.data.annee_scolaire_id) {
                        form.annee_scolaire_id = data.data.annee_scolaire_id;
                    }
                } else {
                    console.error('❌ No data returned:', data);
                }
            } catch (error) {
                console.error('❌ Error fetching apprenant:', error);
            }
        }
    );
}
