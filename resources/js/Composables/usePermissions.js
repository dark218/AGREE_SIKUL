import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable pour la gestion des permissions et rôles
 */
export function usePermissions() {
    const page = usePage();

    // Utilisateur courant
    const user = computed(() => {
        const u = page.props.auth?.user || null;
        if (u) {
            console.log('[usePermissions] User found:', u.email, 'Perms:', u.permissions?.length);
        }
        return u;
    });

    // Rôle de l'utilisateur (premier rôle)
    const userRole = computed(() => user.value?.roles?.[0]?.name || user.value?.role || null);

    // Liste de tous les rôles
    const userRoles = computed(() => {
        if (!user.value?.roles) return [];
        return user.value.roles.map(r => typeof r === 'string' ? r : r.name);
    });

    // Permissions de l'utilisateur
    const userPermissions = computed(() => {
        if (!user.value?.permissions) {
            console.log('[usePermissions] ❌ No permissions found in user:', user.value);
            return [];
        }
        const perms = user.value.permissions.map(p => typeof p === 'string' ? p : p.name);
        console.log('[usePermissions] ✅ Permissions loaded:', perms);
        return perms;
    });

    /**
     * Vérifier si l'utilisateur est connecté
     */
    const isAuthenticated = computed(() => !!user.value);

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    const hasRole = (role) => {
        if (!user.value) return false;
        return userRoles.value.includes(role);
    };

    /**
     * Vérifier si l'utilisateur a au moins un des rôles
     */
    const hasAnyRole = (roles) => {
        if (!user.value || !Array.isArray(roles)) return false;
        return roles.some(role => userRoles.value.includes(role));
    };

    /**
     * Vérifier si l'utilisateur a tous les rôles
     */
    const hasAllRoles = (roles) => {
        if (!user.value || !Array.isArray(roles)) return false;
        return roles.every(role => userRoles.value.includes(role));
    };

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    const hasPermission = (permission) => {
        if (!user.value) return false;
        // Super Admin a toutes les permissions
        //if (hasRole('Super Admin')) return true;
        return userPermissions.value.includes(permission);
    };

    /**
     * Vérifier si l'utilisateur a au moins une des permissions
     */
    const hasAnyPermission = (permissions) => {
        if (!user.value || !Array.isArray(permissions)) return false;
        // Super Admin a toutes les permissions
        if (hasRole('Super Admin')) return true;
        return permissions.some(permission => userPermissions.value.includes(permission));
    };

    /**
     * Vérifier si l'utilisateur a toutes les permissions
     */
    const hasAllPermissions = (permissions) => {
        if (!user.value || !Array.isArray(permissions)) return false;
        // Super Admin a toutes les permissions
        if (hasRole('Super Admin')) return true;
        return permissions.every(permission => userPermissions.value.includes(permission));
    };

    /**
     * Vérifier si l'utilisateur peut effectuer une action
     */
    const can = (permissionOrRole) => {
        const hasPerm = hasPermission(permissionOrRole);
        const hasRl = hasRole(permissionOrRole);
        const result = hasPerm || hasRl;
        return result;
    };

    /**
     * Vérifier si l'utilisateur ne peut pas effectuer une action
     */
    const cannot = (permissionOrRole) => {
        return !can(permissionOrRole);
    };

    /**
     * Vérifier si l'utilisateur est admin
     */
    const isAdmin = computed(() => {
        return hasAnyRole(['Super Admin', 'Administrateur', 'Admin']);
    });

    /**
     * Vérifier si l'utilisateur est super admin
     */
    const isSuperAdmin = computed(() => {
        return hasRole('super_admin') || hasRole('Super Admin');
    });

    return {
        // State
        user,
        userRole,
        userRoles,
        userPermissions,
        isAuthenticated,
        isAdmin,
        isSuperAdmin,

        // Methods
        hasRole,
        hasAnyRole,
        hasAllRoles,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        can,
        cannot,
    };
}
