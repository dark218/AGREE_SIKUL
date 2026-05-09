import { watch } from 'vue';

export function useClasseAutoFill(form) {
    /**
     * Watch classe_id change and auto-fill ecole, campus, section, cycle, annee_scolaire
     */
    watch(
        () => form.classe_id,
        async (newClasseId) => {
            if (!newClasseId) {
                // Reset if no classe selected
                form.ecole_id = '';
                form.campus_id = '';
                form.section_id = '';
                form.cycle_id = '';
                form.annee_scolaire_id = '';
                return;
            }

            try {
                console.log('🔵 useClasseAutoFill - Fetching classe:', newClasseId);

                const response = await fetch(route('classes.api-show', newClasseId));

                if (!response.ok) {
                    console.error('❌ Failed to fetch classe:', response.statusText);
                    return;
                }

                const data = await response.json();

                if (data) {
                    console.log('✅ useClasseAutoFill - Auto-filling:', data);

                    // Auto-fill ecole
                    if (data.ecole_id) {
                        form.ecole_id = data.ecole_id;
                    }

                    // Auto-fill campus
                    if (data.campus_id) {
                        form.campus_id = data.campus_id;
                    }

                    // Auto-fill section
                    if (data.section_id) {
                        form.section_id = data.section_id;
                    }

                    // Auto-fill cycle
                    if (data.cycle_id) {
                        form.cycle_id = data.cycle_id;
                    }

                    // Auto-fill niveau
                    if (data.niveau_id) {
                        form.niveau_id = data.niveau_id;
                    }

                    // Auto-fill annee_scolaire
                    if (data.annee_scolaire_id) {
                        form.annee_scolaire_id = data.annee_scolaire_id;
                    }
                } else {
                    console.error('❌ No data returned:', data);
                }
            } catch (error) {
                console.error('❌ Error fetching classe:', error);
            }
        }
    );
}
