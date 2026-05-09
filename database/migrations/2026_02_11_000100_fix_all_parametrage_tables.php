<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. FIX DEVISES TABLE
        Schema::table('devises', function (Blueprint $table) {
            if (Schema::hasColumn('devises', 'symbole')) {
                $table->dropColumn('symbole');
            }
            if (Schema::hasColumn('devises', 'decimal_point')) {
                $table->dropColumn('decimal_point');
            }
            if (!Schema::hasColumn('devises', 'pays_id')) {
                $table->foreignId('pays_id')->nullable()->after('libelle')->constrained('pays')->onDelete('set null');
            }
            if (!Schema::hasColumn('devises', 'etat')) {
                $table->enum('etat', ['actif', 'inactif'])->default('actif')->after('pays_id');
            }
            if (!Schema::hasColumn('devises', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('etat')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('devises', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('devises', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->after('updated_by')->constrained('users')->onDelete('set null');
            }
        });

        // 2. FIX PAYS TABLE
        if (Schema::hasColumn('pays', 'iso') && !Schema::hasColumn('pays', 'code_2_chars')) {
            DB::statement('ALTER TABLE pays CHANGE iso code_2_chars VARCHAR(255)');
        }
        if (Schema::hasColumn('pays', 'phone_length') && !Schema::hasColumn('pays', 'nombre')) {
            DB::statement('ALTER TABLE pays CHANGE phone_length nombre VARCHAR(255)');
        }
        Schema::table('pays', function (Blueprint $table) {
            if (!Schema::hasColumn('pays', 'code_3_chars')) {
                $table->string('code_3_chars')->nullable()->after('code');
            }
            if (!Schema::hasColumn('pays', 'continent')) {
                $table->string('continent')->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('pays', 'etat')) {
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
            }
            if (!Schema::hasColumn('pays', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('pays', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('pays', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
            }
        });

        // 3. FIX ZONES TABLE
        Schema::table('zones', function (Blueprint $table) {
            if (!Schema::hasColumn('zones', 'etat')) {
                $table->enum('etat', ['actif', 'inactif'])->default('actif')->after('description');
            }
            if (!Schema::hasColumn('zones', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('etat')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('zones', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('zones', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->after('updated_by')->constrained('users')->onDelete('set null');
            }
        });

        // 4. FIX OTHER TABLES (add missing audit fields and etat)
        foreach (['paysdevises', 'fournisseurs_paiement', 'banques'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    if (!Schema::hasColumn($table, 'etat')) {
                        $t->enum('etat', ['actif', 'inactif'])->default('actif');
                    }
                    if (!Schema::hasColumn($table, 'created_by')) {
                        $t->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                    }
                    if (!Schema::hasColumn($table, 'updated_by')) {
                        $t->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                    }
                    if (!Schema::hasColumn($table, 'deleted_by')) {
                        $t->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
                    }
                });
            }
        }

        // 5. CREATE MISSING TABLES
        if (!Schema::hasTable('cycles_enseignement')) {
            Schema::create('cycles_enseignement', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->index();
                $table->string('libelle');
                $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('set null');
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('niveaux_etudes')) {
            Schema::create('niveaux_etudes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->index();
                $table->string('libelle');
                $table->foreignId('cycle_id')->nullable()->constrained('cycles_enseignement')->onDelete('set null');
                $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('set null');
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('natures_examens')) {
            Schema::create('natures_examens', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->index();
                $table->string('libelle');
                $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
                $table->foreignId('niveau_id')->nullable()->constrained('niveaux_etudes')->onDelete('set null');
                $table->foreignId('cycle_id')->nullable()->constrained('cycles_enseignement')->onDelete('set null');
                $table->integer('poids')->default(1);
                $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('set null');
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('unites_organisationnelles')) {
            Schema::create('unites_organisationnelles', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->index();
                $table->string('libelle');
                $table->foreignId('unite_mre_id')->nullable()->constrained('unites_organisationnelles')->onDelete('set null');
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('matieres_unites')) {
            Schema::create('matieres_unites', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->index();
                $table->string('libelle');
                $table->foreignId('niveau_id')->nullable()->constrained('niveaux_etudes')->onDelete('set null');
                $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
                $table->foreignId('cycle_id')->nullable()->constrained('cycles_enseignement')->onDelete('set null');
                $table->decimal('note_max', 8, 2)->default(20);
                $table->decimal('coefficient', 8, 2)->default(1);
                $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('set null');
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('groupes_matieres')) {
            Schema::create('groupes_matieres', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->index();
                $table->string('libelle');
                $table->foreignId('niveau_id')->nullable()->constrained('niveaux_etudes')->onDelete('set null');
                $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
                $table->foreignId('cycle_id')->nullable()->constrained('cycles_enseignement')->onDelete('set null');
                $table->foreignId('matiere_1_id')->nullable()->constrained('matieres_unites')->onDelete('set null');
                $table->foreignId('matiere_2_id')->nullable()->constrained('matieres_unites')->onDelete('set null');
                $table->foreignId('matiere_3_id')->nullable()->constrained('matieres_unites')->onDelete('set null');
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('titres_civilites')) {
            Schema::create('titres_civilites', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->index();
                $table->string('libelle');
                $table->string('sigle')->nullable();
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('periodes_colaires')) {
            Schema::create('periodes_colaires', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->index();
                $table->string('libelle');
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('jours_feries')) {
            Schema::create('jours_feries', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->index();
                $table->string('libelle');
                $table->tinyInteger('jour')->comment('1-31');
                $table->tinyInteger('mois')->comment('1-12');
                $table->year('annee')->nullable();
                $table->date('date')->nullable()->index();
                $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('set null');
                $table->enum('etat', ['actif', 'inactif'])->default('actif');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // This migration is mainly a fix/creation migration
        // Rollback is not fully reversible due to complex constraints
        // The down() simply drops the created tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::dropIfExists('jours_feries');
        Schema::dropIfExists('periodes_colaires');
        Schema::dropIfExists('titres_civilites');
        Schema::dropIfExists('groupes_matieres');
        Schema::dropIfExists('matieres_unites');
        Schema::dropIfExists('unites_organisationnelles');
        Schema::dropIfExists('natures_examens');
        Schema::dropIfExists('niveaux_etudes');
        Schema::dropIfExists('cycles_enseignement');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
