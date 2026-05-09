import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermission() {
    const page = usePage();

    const getPermissions = () => {
        // Essayer de récupérer les permissions de plusieurs sources
        const perms =
            page.props.auth?.permissions ||
            page.props.auth?.user?.permissions ||
            page.props.userPermissions ||
            [];

        console.log('[usePermission] Found permissions:', perms?.length || 0);
        return perms || [];
    };

    const can = (permission) => {
        // Si pas de user, retourner false
        if (!page.props.auth?.user) {
            console.log(`[usePermission] No user, denying: ${permission}`);
            return false;
        }

        const perms = getPermissions();

        // Vérifier la permission
        const hasPermission = Array.isArray(perms) && perms.some(p =>
            (p.name === permission) || (typeof p === 'string' && p === permission)
        );

        // FALLBACK: Si super_admin et permission non trouvée, autoriser
        const isSuperAdmin = page.props.auth?.user?.roles?.some(r => r.name === 'super_admin');

        if (!hasPermission && isSuperAdmin) {
            console.log(`[usePermission] Super admin bypass for: ${permission}`);
            return true;
        }

        console.log(`[usePermission] ${permission}: ${hasPermission}`);
        return hasPermission;
    };

    const canAny = (permissions) => {
        return Array.isArray(permissions) && permissions.some(p => can(p));
    };

    const canAll = (permissions) => {
        return Array.isArray(permissions) && permissions.every(p => can(p));
    };

    return {
        can,
        canAny,
        canAll,
        getPermissions
    };
}
