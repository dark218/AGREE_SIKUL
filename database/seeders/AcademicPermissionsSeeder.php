<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AcademicPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Ajoute les permissions "activate" pour les 20 entités académiques
     * Format: [entity]-activate (Activer/Désactiver)
     *
     * Les 20 entités académiques:
     * 1. apprenants
     * 2. enseignants
     * 3. matieres
     * 4. tuteurs
     * 5. cours
     * 6. seances
     * 7. emplois_temps
     * 8. evaluations
     * 9. notes
     * 10. bulletins
     * 11. inscriptions
     * 12. dossiers_apprenants
     * 13. absences_apprenants
     * 14. absences_enseignants
     * 15. presences_seances
     * 16. justificatifs_absences
     * 17. devoirs
     * 18. rendus_devoirs
     * 19. moyennes_matieres
     * 20. personnels_administratifs
     */
    public function run(): void
    {
        // Reset cached permissions
        app()['cache']->forget('spatie.permission.cache');

        // Les 20 entités académiques
        $academicEntities = [
            'apprenants',
            'enseignants',
            'matieres',
            'tuteurs',
            'cours',
            'seances',
            'emplois_temps',
            'evaluations',
            'notes',
            'bulletins',
            'inscriptions',
            'dossiers_apprenants',
            'absences_apprenants',
            'absences_enseignants',
            'presences_seances',
            'justificatifs_absences',
            'devoirs',
            'rendus_devoirs',
            'moyennes_matieres',
            'personnels_administratifs'
        ];

        // Créer les permissions "activate" pour chaque entité
        foreach ($academicEntities as $entity) {
            $permissionName = $entity . '-activate';
            Permission::firstOrCreate(
                ['name' => $permissionName],
                [
                    'guard_name' => 'web',
                    'libelle' => 'Activer/Désactiver'
                ]
            );
        }

        $this->command->info('Academic permissions with activate action created successfully! (20 permissions)');
    }
}
