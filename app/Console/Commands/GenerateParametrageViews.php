<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateParametrageViews extends Command
{
    protected $signature = 'generate:parametrage-views';
    protected $description = 'Generate Vue pages for all missing Parametrage features';

    public function handle()
    {
        $basePath = base_path('Modules/Parametrage/Resources/js/Pages');

        // Get all features from database
        $features = \DB::table('feature')
            ->where('module_id', 23)
            ->orderBy('ordre')
            ->get();

        // Features that already have views
        $existingViews = ['Devises', 'Pays', 'Zones'];

        $this->info("Generating Vue pages for Parametrage features...\n");

        foreach ($features as $feature) {
            $folderName = $this->getFolderName($feature->libelle);

            // Skip if already exists
            if (in_array($folderName, $existingViews)) {
                $this->line("⏭️  Skipping {$folderName} (already exists)");
                continue;
            }

            $folderPath = "{$basePath}/{$folderName}";

            // Create folder
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Create Index.vue
            $this->createIndexPage($folderPath, $feature);

            // Create Create.vue
            $this->createCreatePage($folderPath, $feature);

            // Create Edit.vue
            $this->createEditPage($folderPath, $feature);

            // Create Show.vue
            $this->createShowPage($folderPath, $feature);

            // Create Form component
            $this->createFormComponent($folderPath, $feature, $folderName);

            $this->line("✅ Created pages for {$feature->libelle}");
        }

        $this->info("\n✓ Done! All Vue pages generated successfully!");
    }

    private function getFolderName($libelle)
    {
        // Convert "Régions" to "Regions", "Départements" to "Departements", etc.
        $name = str_replace(['é', 'è', 'ê', 'à', 'ç'], ['e', 'e', 'e', 'a', 'c'], $libelle);
        // Handle compound names like "Types Enseignement" -> "TypesEnseignement"
        return str_replace([' ', '-'], '', Str::title($name));
    }

    private function createIndexPage($folderPath, $feature)
    {
        $folderName = $this->getFolderName($feature->libelle);
        $routePrefix = $feature->menu_url;
        $entityVar = lcfirst(str_replace('s', '', Str::camel($folderName)));
        $pluralVar = lcfirst(Str::camel($folderName));

        $content = <<<'PHP'
<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    title: String,
    {PLURAL_VAR}: Object,
    filters: Object,
});

const page = usePage();

// Filtres de recherche
const searchFilters = ref({
    code: props.filters?.code || '',
    libelle: props.filters?.libelle || '',
});

// Modal de suppression
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const search = () => {
    router.get(route('{ROUTE_PREFIX}.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.put(route('{ROUTE_PREFIX}.statut', itemToDelete.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};

const can = (permission) => {
    return true;
};
</script>

<template>
    <Head :title="page.props.title" />

    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('{ROUTE_PREFIX}-create')">
                        <Link :href="route('{ROUTE_PREFIX}.create')" class="btn btn-primary">
                            {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="search-wrapper">
                <input
                    v-model="searchFilters.code"
                    type="text"
                    :placeholder="t('fields.code')"
                    class="form-control"
                />
                <input
                    v-model="searchFilters.libelle"
                    type="text"
                    :placeholder="t('fields.label')"
                    class="form-control"
                />
                <button @click="search" class="btn btn-primary">{{ t('actions.search') }}</button>
            </div>

            <!-- Table -->
            <div class="table-responsive mt-4">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ t('fields.code') }}</th>
                            <th>{{ t('fields.label') }}</th>
                            <th>{{ t('fields.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in {PLURAL_VAR}.data" :key="item.id">
                            <td>{{ item.code }}</td>
                            <td>{{ item.libelle }}</td>
                            <td>
                                <Link :href="route('{ROUTE_PREFIX}.show', item.id)" class="btn btn-sm btn-info">
                                    {{ t('actions.view') }}
                                </Link>
                                <Link :href="route('{ROUTE_PREFIX}.edit', item.id)" class="btn btn-sm btn-warning">
                                    {{ t('actions.edit') }}
                                </Link>
                                <button @click="confirmDelete(item)" class="btn btn-sm btn-danger">
                                    {{ t('actions.delete') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :links="{PLURAL_VAR}.links" />
        </div>
    </div>

    <!-- Delete Modal -->
    <ConfirmModal
        v-model="showDeleteModal"
        title="Confirmer la suppression"
        message="Êtes-vous sûr de vouloir supprimer cet élément?"
        @confirm="deleteItem"
    />

    <FullPageLoader
        v-if="isLoading"
        :message="loaderMessage"
        :sub-message="loaderSubMessage"
        :variant="loaderVariant"
    />
</template>
PHP;

        $content = str_replace(['{PLURAL_VAR}', '{ROUTE_PREFIX}'], [$pluralVar, $routePrefix], $content);
        file_put_contents("{$folderPath}/Index.vue", $content);
    }

    private function createCreatePage($folderPath, $feature)
    {
        $routePrefix = $feature->menu_url;
        $folderName = $this->getFolderName($feature->libelle);
        $formComponentName = $folderName . 'Form';

        $content = <<<'PHP'
<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import {FORM_COMPONENT} from './{FORM_COMPONENT}.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const form = useForm({
    code: '',
    libelle: '',
    libelle_en: '',
});

const submit = () => {
    form.post(route('{ROUTE_PREFIX}.store'));
};
</script>

<template>
    <Head :title="t('actions.create')" />

    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('actions.create') }}</h4>
            </div>

            <form @submit.prevent="submit" class="custom-form">
                <{FORM_COMPONENT} :form="form" mode="create" />

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ t('actions.save') }}</button>
                    <Link :href="route('{ROUTE_PREFIX}.index')" class="btn btn-secondary ms-2">
                        {{ t('actions.cancel') }}
                    </Link>
                </div>
            </form>

            <AlertMessage v-if="form.errors" type="danger" :messages="form.errors" />
        </div>
    </div>
</template>
PHP;

        $content = str_replace(['{FORM_COMPONENT}', '{ROUTE_PREFIX}'], [$formComponentName, $routePrefix], $content);
        file_put_contents("{$folderPath}/Create.vue", $content);
    }

    private function createEditPage($folderPath, $feature)
    {
        $routePrefix = $feature->menu_url;
        $folderName = $this->getFolderName($feature->libelle);
        $formComponentName = $folderName . 'Form';

        $content = <<<'PHP'
<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import {FORM_COMPONENT} from './{FORM_COMPONENT}.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

const form = useForm({
    code: page.props.item?.code || '',
    libelle: page.props.item?.libelle || '',
    libelle_en: page.props.item?.libelle_en || '',
});

const submit = () => {
    form.put(route('{ROUTE_PREFIX}.update', page.props.item.id));
};
</script>

<template>
    <Head :title="t('actions.edit')" />

    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('actions.edit') }}</h4>
            </div>

            <form @submit.prevent="submit" class="custom-form">
                <{FORM_COMPONENT} :form="form" mode="edit" />

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ t('actions.save') }}</button>
                    <Link :href="route('{ROUTE_PREFIX}.index')" class="btn btn-secondary ms-2">
                        {{ t('actions.cancel') }}
                    </Link>
                </div>
            </form>

            <AlertMessage v-if="form.errors" type="danger" :messages="form.errors" />
        </div>
    </div>
</template>
PHP;

        $content = str_replace(['{FORM_COMPONENT}', '{ROUTE_PREFIX}'], [$formComponentName, $routePrefix], $content);
        file_put_contents("{$folderPath}/Edit.vue", $content);
    }

    private function createShowPage($folderPath, $feature)
    {
        $routePrefix = $feature->menu_url;
        $folderName = $this->getFolderName($feature->libelle);
        $formComponentName = $folderName . 'Form';

        $content = <<<'PHP'
<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import {FORM_COMPONENT} from './{FORM_COMPONENT}.vue';
import { useForm } from '@inertiajs/vue3';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

const form = useForm({
    code: page.props.item?.code || '',
    libelle: page.props.item?.libelle || '',
    libelle_en: page.props.item?.libelle_en || '',
});
</script>

<template>
    <Head :title="t('actions.view')" />

    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('actions.view') }}</h4>
            </div>

            <div class="custom-form">
                <{FORM_COMPONENT} :form="form" mode="show" />

                <div class="mt-4">
                    <Link :href="route('{ROUTE_PREFIX}.edit', page.props.item.id)" class="btn btn-primary">
                        {{ t('actions.edit') }}
                    </Link>
                    <Link :href="route('{ROUTE_PREFIX}.index')" class="btn btn-secondary ms-2">
                        {{ t('actions.back') }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
PHP;

        $content = str_replace(['{FORM_COMPONENT}', '{ROUTE_PREFIX}'], [$formComponentName, $routePrefix], $content);
        file_put_contents("{$folderPath}/Show.vue", $content);
    }

    private function createFormComponent($folderPath, $feature, $folderName)
    {
        $componentName = $folderName . 'Form';

        $content = <<<'PHP'
<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.code') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.code"
                    class="form-control"
                    :placeholder="t('fields.code')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>

        <!-- Label (French) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.label') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.libelle"
                    class="form-control"
                    :placeholder="t('fields.label')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>

        <!-- Label (English) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.label_en') }}
                </label>
                <input
                    type="text"
                    v-model="form.libelle_en"
                    class="form-control"
                    :placeholder="t('fields.label_en')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle_en" class="text-danger">
                    <strong>{{ form.errors.libelle_en }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
PHP;

        file_put_contents("{$folderPath}/{$componentName}.vue", $content);
    }
}
