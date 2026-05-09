import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 *  COMPOSABLE POUR GESTION DES VUES CONDITIONNELLES
 */
export function useConditionalViews() {
  const page = usePage();
  
  const user = computed(() => page.props.auth.user);
  const userRole = computed(() => user.value?.roles?.[0] || null);
  const userPermissions = computed(() => user.value?.permissions || []);
  
  /**
   *  VÉRIFIER SI UN CHAMP DOIT ÊTRE AFFICHÉ
   */
  const shouldShowField = (fieldConfig) => {
    if (!user.value) return false;
    
    // Vérifier les rôles autorisés
    if (fieldConfig.showForRoles && fieldConfig.showForRoles.length > 0) {
      const hasRole = userRole.value && fieldConfig.showForRoles.includes(userRole.value);
      if (!hasRole) return false;
    }
    
    // Vérifier les rôles interdits
    if (fieldConfig.hideForRoles && fieldConfig.hideForRoles.length > 0) {
      const hasRestrictedRole = userRole.value && fieldConfig.hideForRoles.includes(userRole.value);
      if (hasRestrictedRole) return false;
    }
    
    // Vérifier les permissions
    if (fieldConfig.showForPermissions && fieldConfig.showForPermissions.length > 0) {
      const hasPermission = userPermissions.value.some(permission => 
        fieldConfig.showForPermissions.includes(permission)
      );
      if (!hasPermission) return false;
    }
    
    // Condition personnalisée
    if (fieldConfig.showWhen && typeof fieldConfig.showWhen === 'function') {
      return fieldConfig.showWhen(user.value, page.props);
    }
    
    return true;
  };

  /**
   *  OBTENIR LES COLONNES VISIBLES POUR UNE TABLE
   */
  const getVisibleColumns = (columns, restrictedFields = []) => {
    return columns.filter(column => {
      // Champs restreints globalement
      if (restrictedFields.includes(column.key)) {
        return false;
      }
      
      return shouldShowField(column);
    });
  };

  /**
   *  OBTENIR LES ACTIONS AUTORISÉES
   */
  const getAllowedActions = (item = null) => {
    const actions = {
      view: true, // Tous peuvent voir (avec restrictions)
      create: hasAnyRole(['Super Admin', 'Administrateur', 'Superviseur Marché', 'Acheteur', 'Producteur']),
      edit: canEdit(item),
      delete: hasAnyRole(['Super Admin', 'Administrateur']),
      export: hasAnyRole(['Super Admin', 'Administrateur', 'Superviseur Marché', 'Agent Financier']),
      toggleStatus: hasAnyRole(['Super Admin', 'Administrateur', 'Superviseur Marché']),
      validate: hasAnyRole(['Super Admin', 'Administrateur', 'Agent Financier']),
      approve: hasAnyRole(['Super Admin', 'Administrateur', 'Superviseur Marché']),
      assign: hasAnyRole(['Super Admin', 'Administrateur', 'Superviseur Marché']),
    };
    
    return actions;
  };

  /**
   *  VÉRIFIER SI L'UTILISATEUR A UN RÔLE
   */
  const hasRole = (role) => {
    return userRole.value === role;
  };

  /**
   *  VÉRIFIER SI L'UTILISATEUR A UN DES RÔLES
   */
  const hasAnyRole = (roles) => {
    return roles.includes(userRole.value);
  };

  /**
   *  VÉRIFIER SI L'UTILISATEUR A UNE PERMISSION
   */
  const hasPermission = (permission) => {
    return userPermissions.value.includes(permission);
  };

  /**
   *  VÉRIFIER SI PEUT MODIFIER UN ÉLÉMENT
   */
  const canEdit = (item) => {
    // Admin peut tout modifier
    if (hasAnyRole(['Super Admin', 'Administrateur'])) {
      return true;
    }
    
    // Superviseur peut modifier dans sa zone
    if (hasRole('Superviseur Marché')) {
      return true; // Logique zone à implémenter
    }
    
    // Utilisateur peut modifier ses propres données
    if (item) {
      const userContext = page.props.userContext || {};
      
      if (userContext.acheteur_id && item.acheteur_id === userContext.acheteur_id) {
        return true;
      }
      
      if (userContext.producteur_id && item.producteur_id === userContext.producteur_id) {
        return true;
      }
      
      if (userContext.transporteur_id && item.transporteur_id === userContext.transporteur_id) {
        return true;
      }
    }
    
    return false;
  };

  /**
   *  OBTENIR LE CONTEXTE UTILISATEUR POUR AUTO-REMPLISSAGE
   */
  const getUserContext = () => {
    const context = {
      role: userRole.value,
      permissions: userPermissions.value,
    };
    
    // Ajouter les IDs selon le rôle
    if (hasRole('Acheteur') && user.value.acheteur_id) {
      context.acheteur_id = user.value.acheteur_id;
    }
    
    if (hasRole('Producteur') && user.value.producteur_id) {
      context.producteur_id = user.value.producteur_id;
    }
    
    if (hasRole('Transporteur') && user.value.transporteur_id) {
      context.transporteur_id = user.value.transporteur_id;
    }
    
    if (hasRole('Agent Technique') && user.value.zone_pre_collecte_id) {
      context.zone_pre_collecte_id = user.value.zone_pre_collecte_id;
    }
    
    return context;
  };

  /**
   *  AUTO-REMPLIR LES CHAMPS SELON LE RÔLE
   */
  const getAutoFilledForm = (baseForm) => {
    const context = getUserContext();
    const autoFilledForm = { ...baseForm };
    
    // Auto-remplir selon le contexte
    Object.keys(context).forEach(key => {
      if (key.endsWith('_id') && context[key] && !autoFilledForm[key]) {
        autoFilledForm[key] = context[key];
      }
    });
    
    return autoFilledForm;
  };

  /**
   *  CONFIGURATION DES COLONNES SELON LE MODULE
   */
  const getModuleColumns = (moduleName) => {
    const columnConfigs = {
      users: [
        { key: 'nom_complet', label: 'Nom', type: 'text' },
        { key: 'email', label: 'Email', type: 'text' },
        { key: 'telephone', label: 'Téléphone', type: 'text' },
        { key: 'roles', label: 'Rôles', type: 'list', showForRoles: ['Super Admin', 'Administrateur'] },
        { key: 'statut', label: 'Statut', type: 'badge' },
        { key: 'derniere_connexion', label: 'Dernière connexion', type: 'date' },
      ],
      
      besoins: [
        { 
          key: 'acheteur.nom', 
          label: 'Acheteur', 
          type: 'text',
          showForRoles: ['Super Admin', 'Administrateur', 'Superviseur Marché']
        },
        { key: 'produit.nom', label: 'Produit', type: 'text' },
        { key: 'quantite_demandee', label: 'Quantité', type: 'number' },
        { 
          key: 'prix_max', 
          label: 'Prix max', 
          type: 'currency',
          hideForRoles: ['Producteur'] // Producteur ne voit pas le prix max
        },
        { key: 'date_limite', label: 'Date limite', type: 'date' },
        { key: 'statut', label: 'Statut', type: 'badge' },
      ],
      
      commandes: [
        { 
          key: 'acheteur.nom', 
          label: 'Acheteur', 
          type: 'text',
          showForRoles: ['Super Admin', 'Administrateur', 'Superviseur Marché']
        },
        { 
          key: 'producteur.nom', 
          label: 'Producteur', 
          type: 'text',
          showForRoles: ['Super Admin', 'Administrateur', 'Superviseur Marché', 'Agent Technique']
        },
        { key: 'produit.nom', label: 'Produit', type: 'text' },
        { key: 'quantite', label: 'Quantité', type: 'number' },
        { 
          key: 'prix_unitaire', 
          label: 'Prix unitaire', 
          type: 'currency',
          hideForRoles: ['Transporteur']
        },
        { 
          key: 'montant_total', 
          label: 'Montant total', 
          type: 'currency',
          showForRoles: ['Super Admin', 'Administrateur', 'Agent Financier', 'Acheteur']
        },
        { key: 'statut', label: 'Statut', type: 'badge' },
      ],
    };
    
    return columnConfigs[moduleName] || [];
  };

  /**
   *  OBTENIR LES OPTIONS D'UN CHAMP
   */
  const getFieldOptions = (field) => {
    // Options depuis la config OCPV
    if (field.configKey) {
      const config = window.ocpvConfig || {};
      const options = config[field.configKey] || {};
      
      return Object.entries(options).map(([value, label]) => ({
        value: isNaN(value) ? value : parseInt(value),
        label
      }));
    }
    
    // Options depuis les props
    if (props.fieldOptions[field.name]) {
      return props.fieldOptions[field.name];
    }
    
    return field.options || [];
  };

  /**
   *  MISE À JOUR D'UN CHAMP
   */
  const updateField = (fieldName, value) => {
    emit('field-change', fieldName, value);
  };

  return {
    // État
    user,
    userRole,
    userPermissions,
    
    // Méthodes de vérification
    shouldShowField,
    hasRole,
    hasAnyRole,
    hasPermission,
    canEdit,
    
    // Helpers pour vues
    getVisibleColumns,
    getAllowedActions,
    getUserContext,
    getAutoFilledForm,
    getModuleColumns,
    getFieldOptions,
    updateField,
  };
}