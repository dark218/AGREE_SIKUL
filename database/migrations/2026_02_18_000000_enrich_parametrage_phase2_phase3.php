<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Phase 2 & Phase 3 Enrichments
     */
    public function up(): void
    {
        // idempotence guard
        // PHASE 2: Zones - Add missing fields
        if (Schema::hasTable('zones')) {
            Schema::table('zones', function (Blueprint $table) {
                if (!Schema::hasColumn('zones', 'type_zone')) {
                    $table->string('type_zone', 100)->nullable()->after('nom');
                }
                if (!Schema::hasColumn('zones', 'coordinates')) {
                    $table->string('coordinates', 255)->nullable()->after('type_zone');
                }
                if (!Schema::hasColumn('zones', 'description')) {
                    $table->text('description')->nullable()->after('coordinates');
                }
            });
        }

        // PHASE 3: CategoriesApprenant - Add enrichment fields
        if (Schema::hasTable('categorie_apprenants')) {
            Schema::table('categorie_apprenants', function (Blueprint $table) {
                if (!Schema::hasColumn('categorie_apprenants', 'taux_reduction')) {
                    $table->decimal('taux_reduction', 5, 2)->nullable()->after('libelle')->comment('% de réduction tarifaire');
                }
                if (!Schema::hasColumn('categorie_apprenants', 'priorite_inscription')) {
                    $table->integer('priorite_inscription')->nullable()->after('taux_reduction')->comment('Ordre de priorité');
                }
                if (!Schema::hasColumn('categorie_apprenants', 'necessite_justificatif')) {
                    $table->boolean('necessite_justificatif')->default(false)->after('priorite_inscription')->comment('Document requis');
                }
                if (!Schema::hasColumn('categorie_apprenants', 'type_categorie')) {
                    $table->string('type_categorie', 100)->nullable()->after('necessite_justificatif')->comment('social, académique, sportif, etc.');
                }
            });
        }

        // PHASE 3: CategoriesEnseignant - Add enrichment fields
        if (Schema::hasTable('categorie_enseignants')) {
            Schema::table('categorie_enseignants', function (Blueprint $table) {
                if (!Schema::hasColumn('categorie_enseignants', 'niveau_qualification')) {
                    $table->string('niveau_qualification', 100)->nullable()->after('libelle')->comment('licence, master, doctorat');
                }
                if (!Schema::hasColumn('categorie_enseignants', 'charge_horaire_min')) {
                    $table->integer('charge_horaire_min')->nullable()->after('niveau_qualification')->comment('Heures min/semaine');
                }
                if (!Schema::hasColumn('categorie_enseignants', 'charge_horaire_max')) {
                    $table->integer('charge_horaire_max')->nullable()->after('charge_horaire_min')->comment('Heures max/semaine');
                }
                if (!Schema::hasColumn('categorie_enseignants', 'taux_horaire_base')) {
                    $table->decimal('taux_horaire_base', 10, 2)->nullable()->after('charge_horaire_max')->comment('Salaire base/heure');
                }
                if (!Schema::hasColumn('categorie_enseignants', 'peut_etre_titulaire')) {
                    $table->boolean('peut_etre_titulaire')->default(false)->after('taux_horaire_base');
                }
                if (!Schema::hasColumn('categorie_enseignants', 'anciennete_requise')) {
                    $table->integer('anciennete_requise')->nullable()->after('peut_etre_titulaire')->comment('Années minimum');
                }
            });
        }

        // PHASE 3: JoursFeries - Add enrichment fields
        if (Schema::hasTable('jour_feries')) {
            Schema::table('jour_feries', function (Blueprint $table) {
                if (!Schema::hasColumn('jour_feries', 'est_recurrent')) {
                    $table->boolean('est_recurrent')->default(true)->after('libelle')->comment('Se répète chaque année');
                }
                if (!Schema::hasColumn('jour_feries', 'type_jour_ferie')) {
                    $table->string('type_jour_ferie', 100)->nullable()->after('est_recurrent')->comment('national, religieux, local');
                }
                if (!Schema::hasColumn('jour_feries', 'ecole_id')) {
                    $table->foreignId('ecole_id')->nullable()->after('type_jour_ferie')->constrained('ecoles')->onDelete('cascade');
                }
            });
        }

        // PHASE 3: ModePaiement - Add enrichment fields
        if (Schema::hasTable('mode_paiements')) {
            Schema::table('mode_paiements', function (Blueprint $table) {
                if (!Schema::hasColumn('mode_paiements', 'type_mode')) {
                    $table->string('type_mode', 100)->after('libelle')->comment('espèces, chèque, virement, mobile money, carte');
                }
                if (!Schema::hasColumn('mode_paiements', 'necessite_reference')) {
                    $table->boolean('necessite_reference')->default(false)->after('type_mode')->comment('Numéro de transaction requis');
                }
                if (!Schema::hasColumn('mode_paiements', 'delai_compensation_jours')) {
                    $table->integer('delai_compensation_jours')->nullable()->after('necessite_reference')->comment('Délai de validation');
                }
                if (!Schema::hasColumn('mode_paiements', 'frais_pourcentage')) {
                    $table->decimal('frais_pourcentage', 5, 2)->nullable()->after('delai_compensation_jours')->comment('% de frais');
                }
                if (!Schema::hasColumn('mode_paiements', 'frais_fixe')) {
                    $table->decimal('frais_fixe', 10, 2)->nullable()->after('frais_pourcentage')->comment('Montant fixe de frais');
                }
                if (!Schema::hasColumn('mode_paiements', 'montant_min')) {
                    $table->decimal('montant_min', 10, 2)->nullable()->after('frais_fixe')->comment('Montant minimum');
                }
                if (!Schema::hasColumn('mode_paiements', 'montant_max')) {
                    $table->decimal('montant_max', 10, 2)->nullable()->after('montant_min')->comment('Montant maximum');
                }
                if (!Schema::hasColumn('mode_paiements', 'fournisseur_paiement_id')) {
                    $table->foreignId('fournisseur_paiement_id')->nullable()->after('montant_max')->constrained('fournisseur_paiements')->onDelete('set null');
                }
            });
        }

        // PHASE 3: Fichier - Add enrichment fields
        if (Schema::hasTable('fichiers')) {
            Schema::table('fichiers', function (Blueprint $table) {
                if (!Schema::hasColumn('fichiers', 'type_fichier')) {
                    $table->string('type_fichier', 100)->after('libelle')->comment('document, image, video, etc.');
                }
                if (!Schema::hasColumn('fichiers', 'extensions_autorisees')) {
                    $table->string('extensions_autorisees', 255)->after('type_fichier')->comment('pdf, jpg, png (séparés virgules)');
                }
                if (!Schema::hasColumn('fichiers', 'taille_max_mo')) {
                    $table->integer('taille_max_mo')->after('extensions_autorisees')->comment('Taille max en MB');
                }
                if (!Schema::hasColumn('fichiers', 'est_obligatoire')) {
                    $table->boolean('est_obligatoire')->default(false)->after('taille_max_mo');
                }
                if (!Schema::hasColumn('fichiers', 'entite_liee')) {
                    $table->string('entite_liee', 100)->nullable()->after('est_obligatoire')->comment('apprenant, enseignant, inscription');
                }
                if (!Schema::hasColumn('fichiers', 'ordre_affichage')) {
                    $table->integer('ordre_affichage')->nullable()->after('entite_liee');
                }
                if (!Schema::hasColumn('fichiers', 'necessite_validation')) {
                    $table->boolean('necessite_validation')->default(false)->after('ordre_affichage');
                }
            });
        }

        // PHASE 3: TypeExamen - Add enrichment fields
        if (Schema::hasTable('type_examens')) {
            Schema::table('type_examens', function (Blueprint $table) {
                if (!Schema::hasColumn('type_examens', 'periodicite')) {
                    $table->string('periodicite', 100)->nullable()->after('libelle')->comment('unique, quotidien, hebdo, mensuel, trimestriel');
                }
                if (!Schema::hasColumn('type_examens', 'est_certificatif')) {
                    $table->boolean('est_certificatif')->default(false)->after('periodicite')->comment('Donne un diplôme');
                }
                if (!Schema::hasColumn('type_examens', 'nature_examen_id')) {
                    $table->foreignId('nature_examen_id')->nullable()->after('est_certificatif')->constrained('natures_examens')->onDelete('set null');
                }
            });
        }

        // PHASE 3: TypeRessource - Add enrichment fields
        if (Schema::hasTable('type_ressources')) {
            Schema::table('type_ressources', function (Blueprint $table) {
                if (!Schema::hasColumn('type_ressources', 'categorie_ressource')) {
                    $table->string('categorie_ressource', 100)->nullable()->after('libelle')->comment('matériel, humain, infrastructure, numérique');
                }
                if (!Schema::hasColumn('type_ressources', 'est_partageable')) {
                    $table->boolean('est_partageable')->default(false)->after('categorie_ressource');
                }
                if (!Schema::hasColumn('type_ressources', 'necessite_reservation')) {
                    $table->boolean('necessite_reservation')->default(false)->after('est_partageable');
                }
                if (!Schema::hasColumn('type_ressources', 'capacite_standard')) {
                    $table->integer('capacite_standard')->nullable()->after('necessite_reservation')->comment('Nombre de places/unités');
                }
                if (!Schema::hasColumn('type_ressources', 'unite_mesure')) {
                    $table->string('unite_mesure', 100)->nullable()->after('capacite_standard')->comment('unité, heure, jour');
                }
            });
        }

        // PHASE 3: TypeEvenementAgenda - Add enrichment fields
        if (Schema::hasTable('type_evenement_agendas')) {
            Schema::table('type_evenement_agendas', function (Blueprint $table) {
                if (!Schema::hasColumn('type_evenement_agendas', 'categorie_evenement')) {
                    $table->string('categorie_evenement', 100)->nullable()->after('libelle')->comment('académique, culturel, sportif, admin');
                }
                if (!Schema::hasColumn('type_evenement_agendas', 'couleur_affichage')) {
                    $table->string('couleur_affichage', 7)->nullable()->after('categorie_evenement')->comment('Code couleur hex');
                }
                if (!Schema::hasColumn('type_evenement_agendas', 'icone')) {
                    $table->string('icone', 100)->nullable()->after('couleur_affichage')->comment('Font Awesome icon');
                }
                if (!Schema::hasColumn('type_evenement_agendas', 'necessite_inscription')) {
                    $table->boolean('necessite_inscription')->default(false)->after('icone');
                }
                if (!Schema::hasColumn('type_evenement_agendas', 'capacite_max_participants')) {
                    $table->integer('capacite_max_participants')->nullable()->after('necessite_inscription');
                }
                if (!Schema::hasColumn('type_evenement_agendas', 'duree_standard_minutes')) {
                    $table->integer('duree_standard_minutes')->nullable()->after('capacite_max_participants');
                }
                if (!Schema::hasColumn('type_evenement_agendas', 'est_public')) {
                    $table->boolean('est_public')->default(true)->after('duree_standard_minutes')->comment('Visible par tous');
                }
                if (!Schema::hasColumn('type_evenement_agendas', 'ecole_id')) {
                    $table->foreignId('ecole_id')->nullable()->after('est_public')->constrained('ecoles')->onDelete('cascade');
                }
            });
        }

        // PHASE 3: UniteOrganisationnelle - Add enrichment fields
        if (Schema::hasTable('unite_organisationnelles')) {
            Schema::table('unite_organisationnelles', function (Blueprint $table) {
                if (!Schema::hasColumn('unite_organisationnelles', 'type_unite')) {
                    $table->string('type_unite', 100)->nullable()->after('libelle')->comment('direction, département, service');
                }
                if (!Schema::hasColumn('unite_organisationnelles', 'responsable_id')) {
                    $table->foreignId('responsable_id')->nullable()->after('type_unite')->constrained('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('unite_organisationnelles', 'budget_annuel')) {
                    $table->decimal('budget_annuel', 15, 2)->nullable()->after('responsable_id');
                }
                if (!Schema::hasColumn('unite_organisationnelles', 'effectif_max')) {
                    $table->integer('effectif_max')->nullable()->after('budget_annuel')->comment('Nombre de personnes');
                }
                if (!Schema::hasColumn('unite_organisationnelles', 'niveau_hierarchique')) {
                    $table->integer('niveau_hierarchique')->nullable()->after('effectif_max')->comment('Pour organigramme');
                }
                if (!Schema::hasColumn('unite_organisationnelles', 'ecole_id')) {
                    $table->foreignId('ecole_id')->nullable()->after('niveau_hierarchique')->constrained('ecoles')->onDelete('cascade');
                }
            });
        }

        // PHASE 3: NatureContrat - Add enrichment fields
        if (Schema::hasTable('nature_contrats')) {
            Schema::table('nature_contrats', function (Blueprint $table) {
                if (!Schema::hasColumn('nature_contrats', 'duree_mois')) {
                    $table->integer('duree_mois')->nullable()->after('libelle')->comment('Durée standard');
                }
                if (!Schema::hasColumn('nature_contrats', 'est_renouvelable')) {
                    $table->boolean('est_renouvelable')->default(true)->after('duree_mois');
                }
                if (!Schema::hasColumn('nature_contrats', 'type_personnel')) {
                    $table->string('type_personnel', 100)->nullable()->after('est_renouvelable')->comment('enseignant, admin, technique');
                }
                if (!Schema::hasColumn('nature_contrats', 'regime_travail')) {
                    $table->string('regime_travail', 100)->nullable()->after('type_personnel')->comment('temps plein, partiel, vacation');
                }
                if (!Schema::hasColumn('nature_contrats', 'periodicite_paiement')) {
                    $table->string('periodicite_paiement', 100)->nullable()->after('regime_travail')->comment('mensuel, hebdomadaire, etc.');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns in reverse order
        $tables = [
            'nature_contrats' => ['duree_mois', 'est_renouvelable', 'type_personnel', 'regime_travail', 'periodicite_paiement'],
            'unite_organisationnelles' => ['type_unite', 'responsable_id', 'budget_annuel', 'effectif_max', 'niveau_hierarchique', 'ecole_id'],
            'type_evenement_agendas' => ['categorie_evenement', 'couleur_affichage', 'icone', 'necessite_inscription', 'capacite_max_participants', 'duree_standard_minutes', 'est_public', 'ecole_id'],
            'type_ressources' => ['categorie_ressource', 'est_partageable', 'necessite_reservation', 'capacite_standard', 'unite_mesure'],
            'type_examens' => ['periodicite', 'est_certificatif', 'nature_examen_id'],
            'fichiers' => ['type_fichier', 'extensions_autorisees', 'taille_max_mo', 'est_obligatoire', 'entite_liee', 'ordre_affichage', 'necessite_validation'],
            'mode_paiements' => ['type_mode', 'necessite_reference', 'delai_compensation_jours', 'frais_pourcentage', 'frais_fixe', 'montant_min', 'montant_max', 'fournisseur_paiement_id'],
            'jour_feries' => ['est_recurrent', 'type_jour_ferie', 'ecole_id'],
            'categorie_enseignants' => ['niveau_qualification', 'charge_horaire_min', 'charge_horaire_max', 'taux_horaire_base', 'peut_etre_titulaire', 'anciennete_requise'],
            'categorie_apprenants' => ['taux_reduction', 'priorite_inscription', 'necessite_justificatif', 'type_categorie'],
            'zones' => ['type_zone', 'coordinates', 'description'],
        ];

        foreach ($tables as $table => $columns) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
