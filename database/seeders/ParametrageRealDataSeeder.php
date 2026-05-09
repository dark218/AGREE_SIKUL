<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParametrageRealDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Seeding données réelles du module Paramétrage...');

        // Désactiver les FK pour permettre truncate sans cascade
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $paysId    = DB::table('pays')->first()?->id ?? 1;
        $cycleId   = DB::table('cycles_enseignement')->first()?->id ?? 1;
        $sectionId = DB::table('sections')->first()?->id ?? 1;
        $niveauId  = DB::table('niveaux')->first()?->id ?? 1;

        // =============================================
        // 1. PAYS SUPPLÉMENTAIRES (Afrique de l'Ouest)
        // =============================================
        $paysData = [
            ['code' => 'CI', 'code_3_chars' => 'CIV', 'code_2_chars' => 'CI', 'libelle' => "Côte d'Ivoire", 'continent' => 'Afrique', 'nombre' => 225],
            ['code' => 'GN', 'code_3_chars' => 'GIN', 'code_2_chars' => 'GN', 'libelle' => 'Guinée', 'continent' => 'Afrique', 'nombre' => 224],
            ['code' => 'ML', 'code_3_chars' => 'MLI', 'code_2_chars' => 'ML', 'libelle' => 'Mali', 'continent' => 'Afrique', 'nombre' => 223],
            ['code' => 'BF', 'code_3_chars' => 'BFA', 'code_2_chars' => 'BF', 'libelle' => 'Burkina Faso', 'continent' => 'Afrique', 'nombre' => 226],
            ['code' => 'TG', 'code_3_chars' => 'TGO', 'code_2_chars' => 'TG', 'libelle' => 'Togo', 'continent' => 'Afrique', 'nombre' => 228],
            ['code' => 'BJ', 'code_3_chars' => 'BEN', 'code_2_chars' => 'BJ', 'libelle' => 'Bénin', 'continent' => 'Afrique', 'nombre' => 229],
            ['code' => 'MR', 'code_3_chars' => 'MRT', 'code_2_chars' => 'MR', 'libelle' => 'Mauritanie', 'continent' => 'Afrique', 'nombre' => 222],
            ['code' => 'GH', 'code_3_chars' => 'GHA', 'code_2_chars' => 'GH', 'libelle' => 'Ghana', 'continent' => 'Afrique', 'nombre' => 233],
            ['code' => 'FR', 'code_3_chars' => 'FRA', 'code_2_chars' => 'FR', 'libelle' => 'France', 'continent' => 'Europe', 'nombre' => 33],
        ];
        foreach ($paysData as $pays) {
            if (!DB::table('pays')->where('code', $pays['code'])->exists()) {
                DB::table('pays')->insert(array_merge($pays, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
            }
        }
        $this->command->info('✅ Pays supplémentaires ajoutés');

        // =============================================
        // 2. BANQUES (Sénégal / Afrique de l'Ouest)
        // =============================================
        if (DB::table('banques')->count() < 5) {
            $banques = [
                ['code' => 'SGBS', 'libelle' => 'Société Générale de Banques au Sénégal', 'pays_id' => $paysId],
                ['code' => 'CBAO', 'libelle' => 'Compagnie Bancaire de l\'Afrique Occidentale', 'pays_id' => $paysId],
                ['code' => 'BHS', 'libelle' => 'Banque de l\'Habitat du Sénégal', 'pays_id' => $paysId],
                ['code' => 'UBA', 'libelle' => 'United Bank for Africa', 'pays_id' => $paysId],
                ['code' => 'ECOBANK', 'libelle' => 'Ecobank Sénégal', 'pays_id' => $paysId],
                ['code' => 'BOA', 'libelle' => 'Bank of Africa', 'pays_id' => $paysId],
                ['code' => 'BICIS', 'libelle' => 'Banque Internationale pour le Commerce et l\'Industrie du Sénégal', 'pays_id' => $paysId],
                ['code' => 'ATB', 'libelle' => 'Atlantic Business International', 'pays_id' => $paysId],
            ];
            foreach ($banques as $banque) {
                if (!DB::table('banques')->where('code', $banque['code'])->exists()) {
                    DB::table('banques')->insert(array_merge($banque, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
                }
            }
            $this->command->info('✅ Banques ajoutées');
        }

        // =============================================
        // 3. MOYENS DE PAIEMENT
        // =============================================
        $moyensPaiement = [
            ['code' => 'WAVE', 'libelle' => 'Wave Mobile Money'],
            ['code' => 'OM', 'libelle' => 'Orange Money'],
            ['code' => 'FREE_MONEY', 'libelle' => 'Free Money'],
            ['code' => 'WARI', 'libelle' => 'Wari'],
            ['code' => 'WIZALL', 'libelle' => 'Wizall Money'],
            ['code' => 'CHEQUE', 'libelle' => 'Chèque bancaire'],
            ['code' => 'VIREMENT', 'libelle' => 'Virement bancaire'],
            ['code' => 'ESPECES', 'libelle' => 'Espèces'],
            ['code' => 'CARTE', 'libelle' => 'Carte bancaire'],
        ];
        foreach ($moyensPaiement as $mp) {
            if (!DB::table('moyens_paiement')->where('code', $mp['code'])->exists()) {
                DB::table('moyens_paiement')->insert(array_merge($mp, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
            }
        }
        $this->command->info('✅ Moyens de paiement ajoutés');

        // =============================================
        // 4. DEVISES
        // =============================================
        $devises = [
            ['code' => 'XOF', 'code_iso' => 'XOF', 'libelle' => 'Franc CFA BCEAO', 'symbol' => 'FCFA', 'decimal_places' => 0, 'is_default' => 1, 'exchange_rate' => 1.0, 'pays_id' => $paysId],
            ['code' => 'EUR', 'code_iso' => 'EUR', 'libelle' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'is_default' => 0, 'exchange_rate' => 655.957, 'pays_id' => null],
            ['code' => 'USD', 'code_iso' => 'USD', 'libelle' => 'Dollar Américain', 'symbol' => '$', 'decimal_places' => 2, 'is_default' => 0, 'exchange_rate' => 620.0, 'pays_id' => null],
            ['code' => 'GBP', 'code_iso' => 'GBP', 'libelle' => 'Livre Sterling', 'symbol' => '£', 'decimal_places' => 2, 'is_default' => 0, 'exchange_rate' => 780.0, 'pays_id' => null],
        ];
        foreach ($devises as $devise) {
            if (!DB::table('devises')->where('code', $devise['code'])->exists()) {
                DB::table('devises')->insert(array_merge($devise, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
            }
        }
        $this->command->info('✅ Devises ajoutées');

        // =============================================
        // 5. TITRES DE CIVILITÉ
        // =============================================
        $titres = [
            ['code' => 'M', 'libelle' => 'Monsieur', 'sigle' => 'M.'],
            ['code' => 'MME', 'libelle' => 'Madame', 'sigle' => 'Mme'],
            ['code' => 'MLLE', 'libelle' => 'Mademoiselle', 'sigle' => 'Mlle'],
            ['code' => 'DR', 'libelle' => 'Docteur', 'sigle' => 'Dr'],
            ['code' => 'PR', 'libelle' => 'Professeur', 'sigle' => 'Pr'],
            ['code' => 'ING', 'libelle' => 'Ingénieur', 'sigle' => 'Ing.'],
        ];
        foreach ($titres as $titre) {
            if (!DB::table('titres_civilites')->where('code', $titre['code'])->exists()) {
                DB::table('titres_civilites')->insert(array_merge($titre, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
            }
        }
        $this->command->info('✅ Titres de civilité ajoutés');

        // =============================================
        // 6. FONCTIONS (RH)
        // =============================================
        // Récupérer les IDs des unités organisationnelles existantes
        $uoAdmin  = DB::table('unites_organisationnelles')->where('code', 'ADMIN')->value('id')   ?? DB::table('unites_organisationnelles')->value('id');
        $uoPedago = DB::table('unites_organisationnelles')->where('code', 'PEDAGO')->value('id')  ?? $uoAdmin;
        $uoFinance= DB::table('unites_organisationnelles')->where('code', 'FINANCE')->value('id') ?? $uoAdmin;
        $uoRH     = DB::table('unites_organisationnelles')->where('code', 'HR')->value('id')      ?? $uoAdmin;

        $fonctions = [
            ['code' => 'DIR_GEN',   'libelle' => 'Directeur Général',                  'unite_organisationnelle_id' => $uoAdmin],
            ['code' => 'DIR_ACAD',  'libelle' => 'Directeur Académique',               'unite_organisationnelle_id' => $uoPedago],
            ['code' => 'DIR_ADM',   'libelle' => 'Directeur Administratif',            'unite_organisationnelle_id' => $uoAdmin],
            ['code' => 'PROF_TITU', 'libelle' => 'Professeur Titulaire',              'unite_organisationnelle_id' => $uoPedago],
            ['code' => 'PROF_CERT', 'libelle' => 'Professeur Certifié',               'unite_organisationnelle_id' => $uoPedago],
            ['code' => 'PROF_CONT', 'libelle' => 'Professeur Contractuel',            'unite_organisationnelle_id' => $uoPedago],
            ['code' => 'CPE',       'libelle' => 'Conseiller Principal d\'Éducation', 'unite_organisationnelle_id' => $uoPedago],
            ['code' => 'SURVEILL',  'libelle' => 'Surveillant Général',               'unite_organisationnelle_id' => $uoPedago],
            ['code' => 'SECR_DIR',  'libelle' => 'Secrétaire de Direction',           'unite_organisationnelle_id' => $uoAdmin],
            ['code' => 'COMPT',     'libelle' => 'Comptable',                          'unite_organisationnelle_id' => $uoFinance],
            ['code' => 'INFIRM',    'libelle' => 'Infirmier(e)',                       'unite_organisationnelle_id' => $uoAdmin],
            ['code' => 'BIBLIO',    'libelle' => 'Bibliothécaire',                     'unite_organisationnelle_id' => $uoPedago],
            ['code' => 'AGENT_SEC', 'libelle' => 'Agent de Sécurité',                 'unite_organisationnelle_id' => $uoAdmin],
            ['code' => 'AGENT_ENT', 'libelle' => 'Agent d\'Entretien',                'unite_organisationnelle_id' => $uoAdmin],
            ['code' => 'CHAUFF',    'libelle' => 'Chauffeur',                          'unite_organisationnelle_id' => $uoAdmin],
        ];
        foreach ($fonctions as $fn) {
            if (!DB::table('fonctions')->where('code', $fn['code'])->exists()) {
                DB::table('fonctions')->insert(array_merge($fn, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
            }
        }
        $this->command->info('✅ Fonctions ajoutées');

        // =============================================
        // 7. NATURES DE CONTRATS
        // =============================================
        $naturesContrats = [
            ['code' => 'CDI', 'libelle' => 'Contrat à Durée Indéterminée'],
            ['code' => 'CDD', 'libelle' => 'Contrat à Durée Déterminée'],
            ['code' => 'VACATAIRE', 'libelle' => 'Vacataire'],
            ['code' => 'STAGE', 'libelle' => 'Stage'],
            ['code' => 'BENEVOLAT', 'libelle' => 'Bénévolat'],
            ['code' => 'PRESTATION', 'libelle' => 'Prestation de services'],
            ['code' => 'FONCT', 'libelle' => 'Fonctionnaire détaché'],
        ];
        foreach ($naturesContrats as $nc) {
            if (!DB::table('natures_contrats')->where('code', $nc['code'])->exists()) {
                DB::table('natures_contrats')->insert(array_merge($nc, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
            }
        }
        $this->command->info('✅ Natures de contrats ajoutées');

        // =============================================
        // 8. TYPES DE RESSOURCES
        // =============================================
        if (DB::table('type_ressources')->count() == 0) {
            $typeRessources = [
                ['code' => 'SALLE_COURS', 'libelle' => 'Salle de cours'],
                ['code' => 'LABO', 'libelle' => 'Laboratoire'],
                ['code' => 'BIBLIO', 'libelle' => 'Bibliothèque'],
                ['code' => 'SALLE_INFO', 'libelle' => 'Salle informatique'],
                ['code' => 'TERRAIN', 'libelle' => 'Terrain de sport'],
                ['code' => 'GYMNASE', 'libelle' => 'Gymnase'],
                ['code' => 'AMPHI', 'libelle' => 'Amphithéâtre'],
                ['code' => 'REFECT', 'libelle' => 'Réfectoire / Cantine'],
                ['code' => 'INFIRM', 'libelle' => 'Infirmerie'],
                ['code' => 'SALLE_CONF', 'libelle' => 'Salle de conférence'],
            ];
            DB::table('type_ressources')->insert(array_map(fn($r) => array_merge($r, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]), $typeRessources));
            $this->command->info('✅ Types de ressources ajoutés');
        }

        // =============================================
        // 9. CATÉGORIES D'APPRENANTS
        // =============================================
        $catApprenants = [
            ['code' => 'BOURSIER', 'libelle' => 'Boursier de l\'État'],
            ['code' => 'DEMI_BOURS', 'libelle' => 'Demi-boursier'],
            ['code' => 'PAYANT', 'libelle' => 'Payant'],
            ['code' => 'EXONERE', 'libelle' => 'Exonéré'],
            ['code' => 'REDOUBLANT', 'libelle' => 'Redoublant'],
            ['code' => 'NOUVEAU', 'libelle' => 'Nouvel inscrit'],
            ['code' => 'TRANSFERE', 'libelle' => 'Transféré'],
        ];
        foreach ($catApprenants as $ca) {
            if (!DB::table('categorie_apprenants')->where('code', $ca['code'])->exists()) {
                DB::table('categorie_apprenants')->insert(array_merge($ca, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
            }
        }
        $this->command->info('✅ Catégories d\'apprenants ajoutées');

        // =============================================
        // 10. CATÉGORIES D'ENSEIGNANTS
        // =============================================
        $catEnseignants = [
            ['code' => 'CERTIFIE', 'libelle' => 'Certifié (CAPES/CAFOP)'],
            ['code' => 'AGREGE', 'libelle' => 'Agrégé'],
            ['code' => 'CONTRACTUEL', 'libelle' => 'Contractuel'],
            ['code' => 'VACATAIRE', 'libelle' => 'Vacataire'],
            ['code' => 'DETACHE', 'libelle' => 'Détaché de la fonction publique'],
            ['code' => 'ASSISTANT', 'libelle' => 'Assistant'],
            ['code' => 'MAITRE_CONF', 'libelle' => 'Maître de Conférences'],
        ];
        foreach ($catEnseignants as $ce) {
            if (!DB::table('categorie_enseignants')->where('code', $ce['code'])->exists()) {
                DB::table('categorie_enseignants')->insert(array_merge($ce, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
            }
        }
        $this->command->info('✅ Catégories d\'enseignants ajoutées');

        // =============================================
        // 11. NATURES D'EXAMENS
        // =============================================
        if (DB::table('natures_examens')->count() == 0) {
            $naturesExamens = [
                ['code' => 'DEVOIR',     'libelle' => 'Devoir Surveillé',       'poids' => 1, 'cycle_id' => $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'INTERRO',    'libelle' => 'Interrogation',          'poids' => 1, 'cycle_id' => $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'COMPO',      'libelle' => 'Composition',            'poids' => 2, 'cycle_id' => $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'EXAMEN',     'libelle' => 'Examen Final',           'poids' => 3, 'cycle_id' => $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'TP',         'libelle' => 'Travaux Pratiques',      'poids' => 1, 'cycle_id' => $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'ORAL',       'libelle' => 'Examen Oral',            'poids' => 1, 'cycle_id' => $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'SOUTENANCE', 'libelle' => 'Soutenance de mémoire', 'poids' => 5, 'cycle_id' => $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'RATTRAPAGE', 'libelle' => 'Épreuve de rattrapage', 'poids' => 2, 'cycle_id' => $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
            ];
            DB::table('natures_examens')->insert(array_map(fn($n) => array_merge($n, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]), $naturesExamens));
            $this->command->info('✅ Natures d\'examens ajoutées');
        }

        // =============================================
        // 12. TYPES D'EXAMENS
        // =============================================
        if (DB::table('type_examens')->count() == 0) {
            $baseExamen = ['cycle_id' => $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId];
            $typeExamens = [
                ['code' => 'BAC',      'libelle' => 'Baccalauréat'],
                ['code' => 'BEPC',     'libelle' => 'Brevet d\'Études du Premier Cycle'],
                ['code' => 'CFEE',     'libelle' => 'Certificat de Fin d\'Études Élémentaires'],
                ['code' => 'BTS',      'libelle' => 'Brevet de Technicien Supérieur'],
                ['code' => 'LICENCE',  'libelle' => 'Licence'],
                ['code' => 'MASTER',   'libelle' => 'Master'],
                ['code' => 'DOCTORAT', 'libelle' => 'Doctorat'],
                ['code' => 'CAP',      'libelle' => 'Certificat d\'Aptitude Professionnelle'],
                ['code' => 'INTERNE',  'libelle' => 'Examen Interne'],
            ];
            DB::table('type_examens')->insert(array_map(fn($t) => array_merge($t, $baseExamen, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]), $typeExamens));
            $this->command->info('✅ Types d\'examens ajoutés');
        }

        // =============================================
        // 13. PÉRIODES SCOLAIRES
        // =============================================
        if (DB::table('periodes_colaires')->count() == 0) {
            $periodes = [
                ['code' => 'TRIM1', 'libelle' => '1er Trimestre'],
                ['code' => 'TRIM2', 'libelle' => '2ème Trimestre'],
                ['code' => 'TRIM3', 'libelle' => '3ème Trimestre'],
                ['code' => 'SEM1', 'libelle' => '1er Semestre'],
                ['code' => 'SEM2', 'libelle' => '2ème Semestre'],
                ['code' => 'AN', 'libelle' => 'Annuel'],
            ];
            DB::table('periodes_colaires')->insert(array_map(fn($p) => array_merge($p, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]), $periodes));
            $this->command->info('✅ Périodes scolaires ajoutées');
        }

        // =============================================
        // 14. JOURS FÉRIÉS (Sénégal)
        // =============================================
        if (DB::table('jours_feries')->count() == 0) {
            $jours = [
                ['code' => 'JR01', 'libelle' => 'Jour de l\'An', 'jour' => 1, 'mois' => 1, 'pays_id' => $paysId],
                ['code' => 'JR02', 'libelle' => 'Fête du Travail', 'jour' => 1, 'mois' => 5, 'pays_id' => $paysId],
                ['code' => 'JR03', 'libelle' => 'Fête de l\'Indépendance', 'jour' => 4, 'mois' => 4, 'pays_id' => $paysId],
                ['code' => 'JR04', 'libelle' => 'Assomption', 'jour' => 15, 'mois' => 8, 'pays_id' => $paysId],
                ['code' => 'JR05', 'libelle' => 'Toussaint', 'jour' => 1, 'mois' => 11, 'pays_id' => $paysId],
                ['code' => 'JR06', 'libelle' => 'Noël', 'jour' => 25, 'mois' => 12, 'pays_id' => $paysId],
                ['code' => 'JR07', 'libelle' => 'Korité (Aïd El-Fitr)', 'jour' => 1, 'mois' => 1, 'pays_id' => $paysId],
                ['code' => 'JR08', 'libelle' => 'Tabaski (Aïd El-Adha)', 'jour' => 1, 'mois' => 1, 'pays_id' => $paysId],
                ['code' => 'JR09', 'libelle' => 'Tamkharit', 'jour' => 1, 'mois' => 1, 'pays_id' => $paysId],
                ['code' => 'JR10', 'libelle' => 'Gamou (Mawlid)', 'jour' => 1, 'mois' => 1, 'pays_id' => $paysId],
            ];
            DB::table('jours_feries')->insert(array_map(fn($j) => array_merge($j, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]), $jours));
            $this->command->info('✅ Jours fériés ajoutés');
        }

        // =============================================
        // 15. TYPES D'ÉTABLISSEMENTS
        // =============================================
        try {
            if (DB::table('type_etablissements')->count() == 0) {
                $typeEtab = [
                    ['code' => 'PRIVE', 'libelle' => 'Établissement privé', 'pays_id' => $paysId],
                    ['code' => 'PUBLIC', 'libelle' => 'Établissement public', 'pays_id' => $paysId],
                    ['code' => 'PRIVE_CATHO', 'libelle' => 'Établissement privé catholique', 'pays_id' => $paysId],
                    ['code' => 'PRIVE_ISLA', 'libelle' => 'Établissement privé islamique', 'pays_id' => $paysId],
                    ['code' => 'INTERNAT', 'libelle' => 'Internat', 'pays_id' => $paysId],
                    ['code' => 'FORMATION', 'libelle' => 'Centre de formation professionnelle', 'pays_id' => $paysId],
                ];
                DB::table('type_etablissements')->insert(array_map(fn($t) => array_merge($t, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]), $typeEtab));
                $this->command->info('✅ Types d\'établissements ajoutés');
            }
        } catch (\Exception $e) {
            $this->command->warn('⚠️  Table type_etablissements introuvable, ignoré.');
        }

        // =============================================
        // 16. TYPES D'ENSEIGNEMENT
        // =============================================
        try {
            if (DB::table('type_enseignements')->count() == 0) {
                $typeEns = [
                    ['code' => 'GENERAL', 'libelle' => 'Enseignement Général', 'pays_id' => $paysId],
                    ['code' => 'TECH', 'libelle' => 'Enseignement Technique', 'pays_id' => $paysId],
                    ['code' => 'PROF', 'libelle' => 'Enseignement Professionnel', 'pays_id' => $paysId],
                    ['code' => 'FRANCO_ARAB', 'libelle' => 'Franco-Arabe', 'pays_id' => $paysId],
                    ['code' => 'BILINGUE', 'libelle' => 'Bilingue', 'pays_id' => $paysId],
                    ['code' => 'SUPERIEUR', 'libelle' => 'Enseignement Supérieur', 'pays_id' => $paysId],
                ];
                DB::table('type_enseignements')->insert(array_map(fn($t) => array_merge($t, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]), $typeEns));
                $this->command->info('✅ Types d\'enseignement ajoutés');
            }
        } catch (\Exception $e) {
            $this->command->warn('⚠️  Table type_enseignements introuvable, ignoré.');
        }

        // =============================================
        // 17. FOURNISSEURS DE PAIEMENT
        // =============================================
        $fichierDefaultId = DB::table('fichier')->first()?->id ?? null;
        $fournisseurs = [
            ['nom' => 'Orange Money', 'code' => 'ORANGE_MONEY', 'type' => 'mm', 'statut' => 'actif', 'logo_id' => $fichierDefaultId],
            ['nom' => 'Wave', 'code' => 'WAVE', 'type' => 'mm', 'statut' => 'actif', 'logo_id' => $fichierDefaultId],
            ['nom' => 'Free Money', 'code' => 'FREE_MONEY', 'type' => 'mm', 'statut' => 'actif', 'logo_id' => $fichierDefaultId],
            ['nom' => 'Visa Card', 'code' => 'VISA', 'type' => 'card', 'statut' => 'actif', 'logo_id' => $fichierDefaultId],
            ['nom' => 'Mastercard', 'code' => 'MASTERCARD', 'type' => 'card', 'statut' => 'actif', 'logo_id' => $fichierDefaultId],
            ['nom' => 'Société Générale', 'code' => 'SG_BANK', 'type' => 'bank', 'statut' => 'actif', 'logo_id' => $fichierDefaultId],
        ];
        foreach ($fournisseurs as $f) {
            if (!DB::table('fournisseurs_paiement')->where('code', $f['code'])->exists()) {
                DB::table('fournisseurs_paiement')->insert(array_merge($f, ['created_at' => now(), 'updated_at' => now()]));
            }
        }
        $this->command->info('✅ Fournisseurs de paiement ajoutés');

        // =============================================
        // 18. INSTITUTION RÉELLE
        // =============================================
        $institutionRow = DB::table('institutions')->first();
        $institutionNom = $institutionRow?->nom;
        if (!$institutionNom || $institutionNom === 'Institution Par Défaut') {
            $updateData = [
                'code'               => 'AGREE_SIKUL',
                'nom'                => 'AGREE SIKUL',
                'sigle'              => 'AS',
                'type'               => 'secondaire',
                'statut_juridique'   => 'Société privée',
                'email_principal'    => 'contact@agreesikul.com',
                'telephone_principal'=> '+221 33 000 00 00',
                'site_web'           => 'www.agreesikul.com',
                'ville'              => 'Dakar',
                'pays_id'            => $paysId,
                'description'        => 'Plateforme de gestion scolaire AGREE SIKUL — Excellence académique et innovation pédagogique.',
                'vision'             => 'Devenir la référence des établissements scolaires en Afrique de l\'Ouest.',
                'mission'            => 'Former les leaders de demain avec des outils pédagogiques modernes.',
                'statut'             => 'actif',
                'updated_at'         => now(),
            ];
            if ($institutionRow) {
                DB::table('institutions')->where('id', $institutionRow->id)->update($updateData);
                $institutionId = $institutionRow->id;
            } else {
                $institutionId = DB::table('institutions')->insertGetId(array_merge($updateData, ['created_at' => now()]));
            }
            $this->command->info('✅ Institution AGREE SIKUL mise à jour');
        } else {
            $institutionId = $institutionRow->id;
        }

        // =============================================
        // 19. CAMPUS RÉELS
        // =============================================
        // Campus — upsert par code
        $campusDakarData = [
            'code' => 'CAMPUS_DAKAR', 'nom' => 'Campus Principal de Dakar',
            'institution_id' => $institutionId, 'ville' => 'Dakar',
            'adresse' => 'Rue 10, Médina, Dakar', 'telephone' => '+221 33 800 00 00',
            'email' => 'dakar@agreesikul.com', 'statut' => 'actif',
        ];
        $campusThiesData = [
            'code' => 'CAMPUS_THIES', 'nom' => 'Campus de Thiès',
            'institution_id' => $institutionId, 'ville' => 'Thiès',
            'adresse' => 'Avenue Léopold Sédar Senghor, Thiès', 'telephone' => '+221 33 900 00 00',
            'email' => 'thies@agreesikul.com', 'statut' => 'actif',
        ];
        $existingDakar = DB::table('campuses')->where('code', 'CAMPUS_DAKAR')->first();
        if ($existingDakar) {
            DB::table('campuses')->where('id', $existingDakar->id)->update(array_merge($campusDakarData, ['updated_at' => now()]));
            $campusPrincipal = $existingDakar->id;
        } elseif (DB::table('campuses')->where('nom', 'Campus Par Défaut')->exists()) {
            $old = DB::table('campuses')->where('nom', 'Campus Par Défaut')->first();
            DB::table('campuses')->where('id', $old->id)->update(array_merge($campusDakarData, ['updated_at' => now()]));
            $campusPrincipal = $old->id;
        } else {
            $campusPrincipal = DB::table('campuses')->insertGetId(array_merge($campusDakarData, ['created_at' => now(), 'updated_at' => now()]));
        }
        $existingThies = DB::table('campuses')->where('code', 'CAMPUS_THIES')->first();
        if ($existingThies) {
            $campusThies = $existingThies->id;
        } else {
            $campusThies = DB::table('campuses')->insertGetId(array_merge($campusThiesData, ['created_at' => now(), 'updated_at' => now()]));
        }
        $this->command->info('✅ Campus créés/mis à jour');

        // =============================================
        // 20. ÉCOLES RÉELLES
        // =============================================
        // Upsert écoles — pas de truncate, upsert par code
        $upsertEcole = function(string $code, string $nom, int $campusId, string $type) {
            $row = DB::table('ecoles')->where('code', $code)->first();
            $data = ['code' => $code, 'nom' => $nom, 'campus_id' => $campusId, 'type_enseignement' => $type, 'statut' => 'actif', 'updated_at' => now()];
            if ($row) {
                DB::table('ecoles')->where('id', $row->id)->update($data);
                return $row->id;
            }
            return DB::table('ecoles')->insertGetId(array_merge($data, ['created_at' => now()]));
        };
        $firstEcole = DB::table('ecoles')->first();
        if (!$firstEcole || in_array($firstEcole->nom, ['École Primaire 1', 'École Secondaire 1'])) {
            $ecolePrimaire   = $upsertEcole('EP_DAKAR', 'École Primaire AGREE SIKUL', $campusPrincipal, 'primaire');
            $ecoleSecondaire = $upsertEcole('ES_DAKAR', 'Lycée AGREE SIKUL', $campusPrincipal, 'secondaire');
            $ecoleThies      = $upsertEcole('ES_THIES', 'Collège AGREE SIKUL Thiès', $campusThies ?? $campusPrincipal, 'secondaire');
            $this->command->info('✅ Écoles créées/mises à jour');
        } else {
            $ecolePrimaire   = DB::table('ecoles')->where('code', 'EP_DAKAR')->value('id')
                            ?? DB::table('ecoles')->where('type_enseignement', 'primaire')->value('id')
                            ?? $firstEcole->id;
            $ecoleSecondaire = DB::table('ecoles')->where('code', 'ES_DAKAR')->value('id')
                            ?? DB::table('ecoles')->where('type_enseignement', 'secondaire')->value('id')
                            ?? $ecolePrimaire;
            $ecoleThies      = DB::table('ecoles')->where('code', 'ES_THIES')->value('id') ?? $ecoleSecondaire;
        }

        // =============================================
        // 21. NIVEAUX RÉELS
        // =============================================
        if (DB::table('niveaux')->count() < 5) {
            DB::table('niveaux')->truncate();
            // Primaire
            $niv1 = DB::table('niveaux')->insertGetId(['code' => 'CP', 'libelle' => 'Cours Préparatoire', 'ecole_id' => $ecolePrimaire, 'ordre' => 1, 'age_min' => 5, 'age_max' => 7, 'cycle' => 'primaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            $niv2 = DB::table('niveaux')->insertGetId(['code' => 'CE1', 'libelle' => 'CE1', 'ecole_id' => $ecolePrimaire, 'ordre' => 2, 'age_min' => 6, 'age_max' => 8, 'cycle' => 'primaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            $niv3 = DB::table('niveaux')->insertGetId(['code' => 'CE2', 'libelle' => 'CE2', 'ecole_id' => $ecolePrimaire, 'ordre' => 3, 'age_min' => 7, 'age_max' => 9, 'cycle' => 'primaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            $niv4 = DB::table('niveaux')->insertGetId(['code' => 'CM1', 'libelle' => 'CM1', 'ecole_id' => $ecolePrimaire, 'ordre' => 4, 'age_min' => 8, 'age_max' => 10, 'cycle' => 'primaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            $niv5 = DB::table('niveaux')->insertGetId(['code' => 'CM2', 'libelle' => 'CM2', 'ecole_id' => $ecolePrimaire, 'ordre' => 5, 'age_min' => 9, 'age_max' => 12, 'cycle' => 'primaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            // Secondaire 1er cycle
            $niv6 = DB::table('niveaux')->insertGetId(['code' => '6E', 'libelle' => '6ème', 'ecole_id' => $ecoleSecondaire, 'ordre' => 6, 'age_min' => 11, 'age_max' => 13, 'cycle' => 'secondaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            $niv7 = DB::table('niveaux')->insertGetId(['code' => '5E', 'libelle' => '5ème', 'ecole_id' => $ecoleSecondaire, 'ordre' => 7, 'age_min' => 12, 'age_max' => 14, 'cycle' => 'secondaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            $niv8 = DB::table('niveaux')->insertGetId(['code' => '4E', 'libelle' => '4ème', 'ecole_id' => $ecoleSecondaire, 'ordre' => 8, 'age_min' => 13, 'age_max' => 15, 'cycle' => 'secondaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            $niv9 = DB::table('niveaux')->insertGetId(['code' => '3E', 'libelle' => '3ème', 'ecole_id' => $ecoleSecondaire, 'ordre' => 9, 'age_min' => 14, 'age_max' => 16, 'cycle' => 'secondaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            // Secondaire 2nd cycle
            $niv10 = DB::table('niveaux')->insertGetId(['code' => '2NDE', 'libelle' => 'Seconde', 'ecole_id' => $ecoleSecondaire, 'ordre' => 10, 'age_min' => 15, 'age_max' => 17, 'cycle' => 'secondaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            $niv11 = DB::table('niveaux')->insertGetId(['code' => '1ERE', 'libelle' => 'Première', 'ecole_id' => $ecoleSecondaire, 'ordre' => 11, 'age_min' => 16, 'age_max' => 18, 'cycle' => 'secondaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            $niv12 = DB::table('niveaux')->insertGetId(['code' => 'TERM', 'libelle' => 'Terminale', 'ecole_id' => $ecoleSecondaire, 'ordre' => 12, 'age_min' => 17, 'age_max' => 20, 'cycle' => 'secondaire', 'statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
            $this->command->info('✅ Niveaux créés (12 niveaux)');
        } else {
            $niv1 = DB::table('niveaux')->where('code', 'CP')->first()?->id ?? DB::table('niveaux')->first()->id;
            $niv6 = DB::table('niveaux')->where('code', '6E')->first()?->id ?? $niv1;
            $niv10 = DB::table('niveaux')->where('code', '2NDE')->first()?->id ?? $niv1;
            $niv12 = DB::table('niveaux')->where('code', 'TERM')->first()?->id ?? $niv1;
            $niv2 = DB::table('niveaux')->where('code', 'CE1')->first()?->id ?? $niv1;
            $niv5 = DB::table('niveaux')->where('code', 'CM2')->first()?->id ?? $niv1;
            $niv9 = DB::table('niveaux')->where('code', '3E')->first()?->id ?? $niv6;
        }

        // =============================================
        // 22. CLASSES RÉELLES
        // =============================================
        if (DB::table('classes')->count() < 5) {
            DB::table('classes')->truncate();
            $classes = [
                // Primaire
                ['nom' => 'CP A',       'niveau_id' => $niv1 ?? 1, 'ecole_id' => $ecolePrimaire,   'capacite_max' => 35, 'salle' => 'Salle 01'],
                ['nom' => 'CP B',       'niveau_id' => $niv1 ?? 1, 'ecole_id' => $ecolePrimaire,   'capacite_max' => 35, 'salle' => 'Salle 02'],
                ['nom' => 'CE1 A',      'niveau_id' => $niv2 ?? 1, 'ecole_id' => $ecolePrimaire,   'capacite_max' => 35, 'salle' => 'Salle 03'],
                ['nom' => 'CM2 A',      'niveau_id' => $niv5 ?? 1, 'ecole_id' => $ecolePrimaire,   'capacite_max' => 40, 'salle' => 'Salle 06'],
                // Secondaire 1er cycle
                ['nom' => '6ème A',     'niveau_id' => $niv6 ?? 2, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 45, 'salle' => 'Salle B01'],
                ['nom' => '6ème B',     'niveau_id' => $niv6 ?? 2, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 45, 'salle' => 'Salle B02'],
                ['nom' => '5ème A',     'niveau_id' => $niv7 ?? 2, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 45, 'salle' => 'Salle B03'],
                ['nom' => '4ème A',     'niveau_id' => $niv8 ?? 2, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 45, 'salle' => 'Salle B04'],
                ['nom' => '3ème A',     'niveau_id' => $niv9 ?? 2, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 45, 'salle' => 'Salle B05'],
                // Secondaire 2nd cycle
                ['nom' => 'Seconde A',  'niveau_id' => $niv10 ?? 3, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 50, 'salle' => 'Salle C01'],
                ['nom' => 'Seconde B',  'niveau_id' => $niv10 ?? 3, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 50, 'salle' => 'Salle C02'],
                ['nom' => 'Première A', 'niveau_id' => $niv11 ?? 3, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 50, 'salle' => 'Salle C03'],
                ['nom' => 'Première B', 'niveau_id' => $niv11 ?? 3, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 50, 'salle' => 'Salle C04'],
                ['nom' => 'Terminale A','niveau_id' => $niv12 ?? 3, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 50, 'salle' => 'Salle C05'],
                ['nom' => 'Terminale B','niveau_id' => $niv12 ?? 3, 'ecole_id' => $ecoleSecondaire, 'capacite_max' => 50, 'salle' => 'Salle C06'],
            ];
            foreach ($classes as $classe) {
                DB::table('classes')->insert(array_merge($classe, ['statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
            }
            $this->command->info('✅ Classes créées (15 classes)');
        }

        // =============================================
        // 23. MATIÈRES RÉELLES
        // =============================================
        if (DB::table('matieres')->count() < 10) {
            DB::table('matieres')->truncate();
            $ecolePrimaireId = $ecolePrimaire ?? DB::table('ecoles')->first()?->id;
            $ecoleSecondaireId = $ecoleSecondaire ?? DB::table('ecoles')->first()?->id;
            $matieres = [
                // Primaire
                ['code' => 'MATH_P',    'libelle' => 'Mathématiques',                  'ecole_id' => $ecolePrimaireId,   'coefficient' => 3, 'note_max' => 20],
                ['code' => 'FRANC_P',   'libelle' => 'Français',                       'ecole_id' => $ecolePrimaireId,   'coefficient' => 3, 'note_max' => 20],
                ['code' => 'EVEIL_P',   'libelle' => 'Éveil',                          'ecole_id' => $ecolePrimaireId,   'coefficient' => 1, 'note_max' => 20],
                ['code' => 'SPORT_P',   'libelle' => 'Éducation Physique',             'ecole_id' => $ecolePrimaireId,   'coefficient' => 1, 'note_max' => 20],
                // Secondaire
                ['code' => 'MATH_S',    'libelle' => 'Mathématiques',                  'ecole_id' => $ecoleSecondaireId, 'coefficient' => 4, 'note_max' => 20],
                ['code' => 'FRANC_S',   'libelle' => 'Français',                       'ecole_id' => $ecoleSecondaireId, 'coefficient' => 4, 'note_max' => 20],
                ['code' => 'PHYS_CHI',  'libelle' => 'Physique-Chimie',               'ecole_id' => $ecoleSecondaireId, 'coefficient' => 3, 'note_max' => 20],
                ['code' => 'SVT',       'libelle' => 'Sciences de la Vie et de la Terre', 'ecole_id' => $ecoleSecondaireId, 'coefficient' => 2, 'note_max' => 20],
                ['code' => 'HIST_GEO',  'libelle' => 'Histoire-Géographie',            'ecole_id' => $ecoleSecondaireId, 'coefficient' => 2, 'note_max' => 20],
                ['code' => 'ANGLAIS',   'libelle' => 'Anglais',                        'ecole_id' => $ecoleSecondaireId, 'coefficient' => 2, 'note_max' => 20],
                ['code' => 'PHILO',     'libelle' => 'Philosophie',                    'ecole_id' => $ecoleSecondaireId, 'coefficient' => 2, 'note_max' => 20],
                ['code' => 'ECO_GESTION','libelle' => 'Économie-Gestion',              'ecole_id' => $ecoleSecondaireId, 'coefficient' => 2, 'note_max' => 20],
                ['code' => 'INFO',      'libelle' => 'Informatique',                   'ecole_id' => $ecoleSecondaireId, 'coefficient' => 1, 'note_max' => 20],
                ['code' => 'EPS',       'libelle' => 'Éducation Physique et Sportive', 'ecole_id' => $ecoleSecondaireId, 'coefficient' => 1, 'note_max' => 20],
                ['code' => 'ARTS',      'libelle' => 'Éducation Artistique',           'ecole_id' => $ecoleSecondaireId, 'coefficient' => 1, 'note_max' => 20],
                ['code' => 'ARABE',     'libelle' => 'Arabe',                          'ecole_id' => $ecoleSecondaireId, 'coefficient' => 1, 'note_max' => 20],
                ['code' => 'ESPAGNOL',  'libelle' => 'Espagnol',                       'ecole_id' => $ecoleSecondaireId, 'coefficient' => 1, 'note_max' => 20],
            ];
            foreach ($matieres as $mat) {
                if (!DB::table('matieres')->where('code', $mat['code'])->exists()) {
                    DB::table('matieres')->insert(array_merge($mat, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
                }
            }
            $this->command->info('✅ Matières créées (17 matières)');
        }

        // =============================================
        // 24. ZONES GÉOGRAPHIQUES
        // =============================================
        if (DB::table('zones')->count() == 0) {
            $regionId = DB::table('regions')->first()?->id ?? 1;
            $zones = [
                ['code' => 'ZONE_DAKAR', 'nom' => 'Zone Dakar', 'pays_id' => $paysId, 'region_id' => $regionId],
                ['code' => 'ZONE_NORD',  'nom' => 'Zone Nord',  'pays_id' => $paysId, 'region_id' => $regionId],
                ['code' => 'ZONE_SUD',   'nom' => 'Zone Sud',   'pays_id' => $paysId, 'region_id' => $regionId],
                ['code' => 'ZONE_EST',   'nom' => 'Zone Est',   'pays_id' => $paysId, 'region_id' => $regionId],
                ['code' => 'ZONE_OUEST', 'nom' => 'Zone Ouest', 'pays_id' => $paysId, 'region_id' => $regionId],
            ];
            DB::table('zones')->insert(array_map(fn($z) => array_merge($z, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]), $zones));
            $this->command->info('✅ Zones géographiques ajoutées');
        }

        // =============================================
        // 25. TYPES D'APPRENANTS
        // =============================================
        if (DB::table('type_apprenants')->count() == 0) {
            $cycleIds = DB::table('cycles_enseignement')->pluck('id', 'code');
            $typeApprenants = [
                ['code' => 'ELEVE_PRIM', 'libelle' => 'Élève Primaire',              'cycle_id' => $cycleIds['PRIMAIRE']     ?? $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'ELEVE_SEC1', 'libelle' => 'Élève Secondaire 1er Cycle',  'cycle_id' => $cycleIds['SECONDAIRE1']  ?? $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'ELEVE_SEC2', 'libelle' => 'Élève Secondaire 2nd Cycle',  'cycle_id' => $cycleIds['SECONDAIRE2']  ?? $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'ETUDIANT',   'libelle' => 'Étudiant',                    'cycle_id' => $cycleIds['SUPERIEUR']    ?? $cycleId, 'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
                ['code' => 'STAGIAIRE',  'libelle' => 'Stagiaire en formation',      'cycle_id' => $cycleId,                              'section_id' => $sectionId, 'niveau_id' => $niveauId, 'pays_id' => $paysId],
            ];
            DB::table('type_apprenants')->insert(array_map(fn($t) => array_merge($t, ['etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]), $typeApprenants));
            $this->command->info('✅ Types d\'apprenants ajoutés');
        }

        // =============================================
        // 26. SALLES
        // =============================================
        if (DB::table('salles')->count() < 5) {
            $ecoleId = $ecolePrimaire ?? DB::table('ecoles')->first()?->id;
            $ecoleSecId = $ecoleSecondaire ?? $ecoleId;
            $salles = [
                ['libelle' => 'Salle 01',             'code' => 'S01',    'capacite' => 35],
                ['libelle' => 'Salle 02',             'code' => 'S02',    'capacite' => 35],
                ['libelle' => 'Salle B01',            'code' => 'SB01',   'capacite' => 45],
                ['libelle' => 'Salle B02',            'code' => 'SB02',   'capacite' => 45],
                ['libelle' => 'Salle C01',            'code' => 'SC01',   'capacite' => 50],
                ['libelle' => 'Laboratoire Sciences', 'code' => 'LABO1',  'capacite' => 30],
                ['libelle' => 'Salle Informatique',   'code' => 'INFO1',  'capacite' => 25],
                ['libelle' => 'Bibliothèque',         'code' => 'BIBLIO', 'capacite' => 60],
                ['libelle' => 'Salle de Conférence',  'code' => 'CONF1',  'capacite' => 100],
            ];
            foreach ($salles as $salle) {
                if (!DB::table('salles')->where('code', $salle['code'])->exists()) {
                    DB::table('salles')->insert(array_merge($salle, ['statut' => 'actif', 'created_at' => now(), 'updated_at' => now()]));
                }
            }
            $this->command->info('✅ Salles créées (9 salles)');
        }

        $this->command->info('');
        // Réactiver les FK
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('');
        $this->command->info('🎉 ====================================');
        $this->command->info('✅ PARAMÉTRAGE COMPLET AVEC DONNÉES RÉELLES !');
        $this->command->info('   Institution : AGREE SIKUL - Dakar');
        $this->command->info('   2 Campus · 3 Écoles · 12 Niveaux · 15 Classes');
        $this->command->info('   17 Matières · 9 Salles');
        $this->command->info('   Pays, Régions, Communes, Quartiers Sénégal');
        $this->command->info('   Banques, Devises, Moyens Paiement, Fournisseurs');
        $this->command->info('   Fonctions, Contrats, Catégories, Natures Examens...');
        $this->command->info('🎉 ====================================');
    }
}
