<script setup>
import { ref, computed, watch, onMounted } from 'vue';
const props = defineProps({
    role: {
        type: Object,
        default: () => ({ name: '', permissions: [] })
    },
    modules: {
        type: Array,
        required: true
    },
    rolePermissions: {
        type: Array,
        default: () => []
    },
    isReadOnly: {
        type: Boolean,
        default: false
    },
    errors: {
        type: Object,
        default: () => ({})
    }
});
const emit = defineEmits(['update:role', 'submit']);
// État local
const localName = ref(props.role?.name || '');
const selectedPermissions = ref([...(props.rolePermissions || [])]);
// Mettre à jour les permissions sélectionnées au montage
onMounted(() => {
    if (props.rolePermissions && props.rolePermissions.length > 0) {
        selectedPermissions.value = [...props.rolePermissions];
    }
});
// Watch pour les changements externes
watch(() => props.role, (newRole) => {
    if (newRole) {
        localName.value = newRole.name || '';
    }
}, { immediate: true });
watch(() => props.rolePermissions, (newPermissions) => {
    if (newPermissions) {
        selectedPermissions.value = [...newPermissions];
    }
}, { immediate: true });
// Vérifier si une permission est sélectionnée
function isPermissionSelected(permissionId) {
    return selectedPermissions.value.includes(permissionId);
}
// Vérifier si toutes les permissions d'une feature sont sélectionnées
function areAllPermissionsSelected(feature) {
    if (!feature.permissions || feature.permissions.length === 0) return false;
    return feature.permissions.every(p => selectedPermissions.value.includes(p.id));
}
// Basculer une permission
function togglePermission(permissionId) {
    if (props.isReadOnly) return;
    
    const index = selectedPermissions.value.indexOf(permissionId);
    if (index > -1) {
        selectedPermissions.value.splice(index, 1);
    } else {
        selectedPermissions.value.push(permissionId);
    }
}
// Basculer toutes les permissions d'une feature
function toggleAllPermissions(feature) {
    if (props.isReadOnly) return;
    
    const allSelected = areAllPermissionsSelected(feature);
    
    if (allSelected) {
        // Désélectionner toutes
        feature.permissions.forEach(p => {
            const index = selectedPermissions.value.indexOf(p.id);
            if (index > -1) {
                selectedPermissions.value.splice(index, 1);
            }
        });
    } else {
        // Sélectionner toutes
        feature.permissions.forEach(p => {
            if (!selectedPermissions.value.includes(p.id)) {
                selectedPermissions.value.push(p.id);
            }
        });
    }
}
// Soumettre le formulaire
function submitForm() {
    emit('submit', {
        name: localName.value,
        permissions: selectedPermissions.value
    });
}
// Exposer les données pour le parent
defineExpose({
    getFormData: () => ({
        name: localName.value,
        permissions: selectedPermissions.value
    })
});
</script>
<template>
    <div class="form theme-form p-3">
        <!-- Nom du rôle -->
        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('name') : 'Nom' }} 
                        <span class="text-danger">*</span>
                    </label>
                    <input 
                        v-model="localName"
                        type="text" 
                        class="form-control"
                        :class="{ 'is-invalid': errors.name }"
                        :placeholder="$t ? $t('name') : 'Nom'"
                        :disabled="isReadOnly"
                    >
                    <div v-if="errors.name" class="invalid-feedback">
                        {{ errors.name }}
                    </div>
                </div>
            </div>
        </div>
        <!-- Permissions -->
        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <label class="form-label">
                        {{ $t ? $t('permission_id') : 'Permissions' }} 
                        <span class="text-danger">*</span>
                    </label>
                    
                    <div class="permissions-container">
                        <!-- Boucle sur les modules -->
                        <div 
                            v-for="module in modules" 
                            :key="module.id"
                            class="module-wrapper"
                        >
                            <h6 class="module-title">{{ module.libelle }}:</h6>
                            
                            <!-- Boucle sur les features du module -->
                            <div 
                                v-for="feature in module.features" 
                                :key="feature.id"
                                class="feature-wrapper"
                            >
                                <div class="feature-content">
                                    <strong class="feature-title">{{ feature.libelle }}</strong>
                                    
                                    <!-- Grille des permissions -->
                                    <div class="permissions-grid">
                                        <!-- Case "Tous" -->
                                        <div class="permission-item">
                                            <label class="checkbox-label">
                                                <input 
                                                    type="checkbox"
                                                    class="checkbox-animated"
                                                    :checked="areAllPermissionsSelected(feature)"
                                                    :disabled="isReadOnly"
                                                    @change="toggleAllPermissions(feature)"
                                                >
                                                <span class="checkbox-text">
                                                    {{ $t ? $t('all') : 'Tous' }}
                                                </span>
                                            </label>
                                        </div>
                                        
                                        <!-- Permissions individuelles -->
                                        <div 
                                            v-for="permission in feature.permissions" 
                                            :key="permission.id"
                                            class="permission-item"
                                        >
                                            <label class="checkbox-label">
                                                <input 
                                                    type="checkbox"
                                                    class="checkbox-animated"
                                                    :value="permission.id"
                                                    :checked="isPermissionSelected(permission.id)"
                                                    :disabled="isReadOnly"
                                                    @change="togglePermission(permission.id)"
                                                >
                                                <span class="checkbox-text">
                                                    {{ permission.libelle }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="errors.permissions" class="text-danger mt-2">
                        {{ errors.permissions }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
