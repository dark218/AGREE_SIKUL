<script setup>
import { watch } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    servicesCantines: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';

const jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
const jourLabels = {
    'lundi': 'Lundi',
    'mardi': 'Mardi',
    'mercredi': 'Mercredi',
    'jeudi': 'Jeudi',
    'vendredi': 'Vendredi',
    'samedi': 'Samedi',
};

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Auto-calculate week_end_date and week_name
watch(
    () => props.form.week_start_date,
    (newDate) => {
        if (newDate) {
            const date = new Date(newDate);
            const endDate = new Date(date);
            endDate.setDate(endDate.getDate() + 5); // Lundi + 5 jours = Samedi

            const startFormatted = date.toLocaleDateString('fr-FR');
            const endFormatted = endDate.toLocaleDateString('fr-FR');

            props.form.week_end_date = endDate.toISOString().split('T')[0];
            props.form.week_name = `SEMAINE ${startFormatted} - ${endFormatted}`;
        }
    }
);
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Définition de la semaine -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.basic_information') || 'Définition de la semaine' }}</h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.service_cantine') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.service_cantine_id"
                    :options="servicesCantines"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.service_cantine_id" class="text-danger">
                    <strong>{{ form.errors.service_cantine_id }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.week_start_date') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.week_start_date"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.week_start_date }"
                    :disabled="isReadOnly"
                    required
                />
                <span v-if="form.errors?.week_start_date" class="text-danger">
                    <strong>{{ form.errors.week_start_date }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 2: Menu de la semaine -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.menus') || 'Menu de la semaine' }}</h5>
        </div>

        <!-- Weekly Menu Table -->
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered custom-menu-table">
                    <thead>
                        <tr style="background-color: #f8f9fa;">
                            <th style="width: 120px; font-weight: 600;">Type</th>
                            <th v-for="jour in jours" :key="jour" style="text-align: center; font-weight: 600; background-color: #999; color: white;">
                                {{ jourLabels[jour] }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Entrée row -->
                        <tr>
                            <td style="font-style: italic; font-weight: 500;">Entrée</td>
                            <td v-for="jour in jours" :key="`entree-${jour}`">
                                <input
                                    v-model="form.menus[jour].entree"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :disabled="isReadOnly"
                                    style="border: 1px solid #ccc; padding: 6px;"
                                />
                            </td>
                        </tr>

                        <!-- Plat row -->
                        <tr>
                            <td style="font-style: italic; font-weight: 500;">Plat</td>
                            <td v-for="jour in jours" :key="`plat-${jour}`">
                                <input
                                    v-model="form.menus[jour].plat"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :disabled="isReadOnly"
                                    style="border: 1px solid #ccc; padding: 6px;"
                                />
                            </td>
                        </tr>

                        <!-- Dessert row -->
                        <tr>
                            <td style="font-style: italic; font-weight: 500;">Dessert</td>
                            <td v-for="jour in jours" :key="`dessert-${jour}`">
                                <input
                                    v-model="form.menus[jour].dessert"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :disabled="isReadOnly"
                                    style="border: 1px solid #ccc; padding: 6px;"
                                />
                            </td>
                        </tr>

                        <!-- Remarques row -->
                        <tr>
                            <td style="font-style: italic; font-weight: 500;">Remarques</td>
                            <td v-for="jour in jours" :key="`remarques-${jour}`">
                                <input
                                    v-model="form.menus[jour].remarques"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :disabled="isReadOnly"
                                    style="border: 1px solid #ccc; padding: 6px;"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: État -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.status_section') || 'État' }}</h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.statut') }}</label>
                <SearchableSelect
                    v-model="form.statut"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-menu-table {
    background-color: white;
    border-collapse: collapse;
}

.custom-menu-table th {
    padding: 12px 8px;
    text-align: center;
    vertical-align: middle;
}

.custom-menu-table td {
    padding: 8px;
    border: 1px solid #ddd;
    vertical-align: middle;
}

.form-control-sm {
    min-height: 35px;
}

.form-control:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
}
</style>
