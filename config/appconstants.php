<?php
return [
    'devisedefault'=>'XOF',
    'role' => [
        'superadmin' => 'Super Admin',
        'admin' => 'Admin',
        'marchand' => 'Marchand',
        'caissier' => 'Caissier',
        'manager' => 'Manager',
        'agent' => 'Agent',
        'client' => 'Client',
        'service_client' => 'Service Client',
        'service_validateur' => 'Service Validateur',
    ],

    'role_label' => [
        'Super Admin' => 'role_superadmin',
        'Admin' => 'role_admin',
        'Marchand' => 'role_marchand',
        'Caissier' => 'role_caissier',
        'Manager' => 'role_manager',
        'Agent' => 'role_agent',
        'Client' => 'role_client',
        'Service Client' => 'role_service_client',
        'Service Validateur' => 'role_service_validateur',

    ],
    'guard' => [
        'web' => 'web',
        'api' => 'api',
    ],

    'user_kyc_status' => [
        'non_verifie' => 'non_verifie',
        'en_attente' => 'en_attente',
        'verifie' => 'verifie',
        'rejete' => 'rejete'
    ],
    'user_kyc_status_label' => [
        'non_verifie' => 'kyc_non_verifie',
        'en_attente' => 'kyc_en_attente',
        'verifie' => 'kyc_verifie',
        'rejete' => 'kyc_rejete'
    ],
    'user_statut' => [
        'non_actif' => 'non_actif',
        'actif' => 'actif',
        'suspendu' => 'suspendu',
        'bloque' => 'bloque'
    ],
    'pointvente_statut' => [
        'non_actif' => 'non_actif',
        'actif' => 'actif',
        'suspendu' => 'suspendu',
        'bloque' => 'bloque'
    ],
    'caisse_statut' => [
        'ouverte' => 'ouverte',
        'fermee' => 'fermee',
        'bloquee' => 'bloquee'
    ],
    'caisse_statut_label' => [
        'ouverte' => 'caisse_ouverte',
        'fermee' => 'caisse_fermee',
        'bloquee' => 'caisse_bloquee'
    ],
    'session_caisse_statut' => [
        'ouverte' => 'ouverte',
        'fermee' => 'fermee',
        'annulee' => 'annulee',
        'attente' => 'attente',
        'attente_validation' => 'attente_validation'
    ],
    'type_employe' => [
        'caissier' => 'caissier',
        'manager' => 'manager',
    ],
    'type_employe_label' => [
        'caissier' => 'caissier',
        'manager' => 'manager',
    ],
    'caisse_type' => [
        'physique' => 'physique',
        'mobile' => 'mobile',
        'virtuelle' => 'virtuelle'
    ],
    'caisse_type_label' => [
        'physique' => 'caisse_physique',
        'mobile' => 'caisse_mobile',
        'virtuelle' => 'caisse_virtuelle'
    ],
    'user_statut_label' => [
        'non_actif' => 'statut_nonactif',
        'actif' => 'statut_actif',
        'suspendu' => 'statut_suspendu',
        'bloque' => 'statut_bloque'
    ],
    'zone_type_zone' => [
        'commerciale' => 'commerciale',
        'region' => 'region',
        'secteur' => 'secteur',
    ],
    'zone_type_zone_label' => [
        'commerciale' => 'zone_commerciale',
        'region' => 'zone_region',
        'secteur' => 'zone_secteur',
    ],
    'type_piece' => [
        'passport' => 'passport',
        'cni' => 'cni',
        'pc' => 'pc',
        'ai' => 'ai',
    ],
    'type_piece_label' => [
        'passport' => 'piece_passport',
        'cni' => 'piece_cni',
        'pc' => 'piece_pc',
        'ai' => 'piece_ai',
    ],
    'kyc_statut' => [
        'none' => 'none',
        'pending' => 'pending',
        'verified' => 'verified',
        'rejected' => 'rejected',
        'suspended' => 'suspended',
    ],
    'kyc_statut_label' => [
        'none' => 'kyc_none',
        'pending' => 'kyc_pending',
        'verified' => 'kyc_verified',
        'rejected' => 'kyc_rejected',
        'suspended' => 'kyc_suspended',
    ],
    'terminaux_statut' => [
       'deploye' => 'deploye',
       'actif' => 'actif',
       'suspendu' => 'suspendu',
       'retire' => 'retire',
    ],
    'statut_vente_pos'=>[
        'en_attente' => 'en_attente',
        'validee' => 'validee',
        'annulee' => 'annulee',
        'remboursee' => 'remboursee',
        'partielle' => 'partielle',
    ],
    'mode_paiement'=>[
        'espece' => 'espece',
        'electronique' => 'electronique',
        'mixte' => 'mixte',
    ],

    // === CONSTANTES SCOLAIRES ===
    'roles_scolaires' => [
        'directeur_general' => 'Directeur Général',
        'directeur_campus' => 'Directeur Campus',
        'directeur_ecole' => 'Directeur École',
        'enseignant' => 'Enseignant',
        'personnel_administratif' => 'Personnel Administratif',
        'parent' => 'Parent/Tuteur',
        'eleve' => 'Élève',
        'bibliothecaire' => 'Bibliothécaire',
        'infirmier' => 'Infirmier',
        'agent_securite' => 'Agent de Sécurité',
    ],

    'apprenant_statut' => [
        'actif' => 'actif',
        'suspendu' => 'suspendu',
        'exclu' => 'exclu',
        'diplome' => 'diplome',
        'abandonne' => 'abandonne',
    ],

    'inscription_type' => [
        'nouveau' => 'nouveau',
        'redoublement' => 'redoublement',
        'transfert' => 'transfert',
        'reprise' => 'reprise',
    ],

    'inscription_statut' => [
        'en_attente' => 'en_attente',
        'validee' => 'validee',
        'rejetee' => 'rejetee',
        'suspendue' => 'suspendue',
    ],

    'presence_statut' => [
        'present' => 'present',
        'absent' => 'absent',
        'retard' => 'retard',
        'absent_justifie' => 'absent_justifie',
    ],

    'evaluation_type' => [
        'interrogation' => 'interrogation',
        'devoir' => 'devoir',
        'composition' => 'composition',
        'examen' => 'examen',
        'controle_continu' => 'controle_continu',
    ],

    'mention' => [
        'passable' => 'passable',
        'assez_bien' => 'assez_bien',
        'bien' => 'bien',
        'tres_bien' => 'tres_bien',
        'excellent' => 'excellent',
    ],

    'decision_conseil' => [
        'admis' => 'admis',
        'redoublement' => 'redoublement',
        'exclusion' => 'exclusion',
    ],

    'type_contrat' => [
        'cdi' => 'CDI',
        'cdd' => 'CDD',
        'vacataire' => 'Vacataire',
        'autre' => 'Autre',
    ],

    'absences_enseignant_statut' => [
        'en_attente' => 'en_attente',
        'validee' => 'validee',
        'rejetee' => 'rejetee',
    ],

    'type_frais' => [
        'scolarite' => 'Scolarité',
        'materiel' => 'Matériel',
        'transport' => 'Transport',
        'cantine' => 'Cantine',
        'assurance' => 'Assurance',
        'activites' => 'Activités',
    ],

    'paiement_mode' => [
        'espece' => 'Espèce',
        'cheque' => 'Chèque',
        'virement' => 'Virement',
        'mobile_money' => 'Mobile Money',
        'en_ligne' => 'En Ligne',
    ],

    'etat_equipement' => [
        'neuf' => 'Neuf',
        'bon' => 'Bon',
        'endomage' => 'Endommagé',
        'perdu' => 'Perdu',
        'hors_service' => 'Hors Service',
    ],

    'type_maintenance' => [
        'preventive' => 'Préventive',
        'corrective' => 'Corrective',
        'reparation' => 'Réparation',
    ],

];
