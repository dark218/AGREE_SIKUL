<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            // État civil - add if not exists
            if (!Schema::hasColumn('enseignants', 'civility_title_id')) {
                $table->unsignedBigInteger('civility_title_id')->nullable();
                $table->index('civility_title_id');
            }
            if (!Schema::hasColumn('enseignants', 'gender')) {
                $table->enum('gender', ['M', 'F', 'Autre'])->nullable();
                $table->index('gender');
            }
            if (!Schema::hasColumn('enseignants', 'marital_status')) {
                $table->string('marital_status')->nullable();
            }
            if (!Schema::hasColumn('enseignants', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
                $table->index('date_of_birth');
            }
            if (!Schema::hasColumn('enseignants', 'place_of_birth')) {
                $table->string('place_of_birth')->nullable();
            }
            if (!Schema::hasColumn('enseignants', 'commune_id')) {
                $table->unsignedBigInteger('commune_id')->nullable();
                $table->index('commune_id');
            }
            if (!Schema::hasColumn('enseignants', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable();
                $table->index('department_id');
            }
            if (!Schema::hasColumn('enseignants', 'region_id')) {
                $table->unsignedBigInteger('region_id')->nullable();
                $table->index('region_id');
            }
            if (!Schema::hasColumn('enseignants', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable();
                $table->index('country_id');
            }
            if (!Schema::hasColumn('enseignants', 'photo')) {
                $table->string('photo')->nullable();
            }

            // Formation
            if (!Schema::hasColumn('enseignants', 'highest_diploma')) {
                $table->string('highest_diploma')->nullable();
            }
            if (!Schema::hasColumn('enseignants', 'speciality')) {
                $table->string('speciality')->nullable();
            }
            if (!Schema::hasColumn('enseignants', 'year_obtained')) {
                $table->year('year_obtained')->nullable();
            }
            if (!Schema::hasColumn('enseignants', 'languages')) {
                $table->json('languages')->nullable();
            }
            if (!Schema::hasColumn('enseignants', 'teacher_category')) {
                $table->string('teacher_category')->nullable();
            }
            if (!Schema::hasColumn('enseignants', 'teaching_speciality')) {
                $table->string('teaching_speciality')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            // Drop columns if they exist
            $columns = [
                'civility_title_id', 'gender', 'marital_status', 'date_of_birth',
                'place_of_birth', 'commune_id', 'department_id', 'region_id',
                'country_id', 'photo', 'highest_diploma', 'speciality',
                'year_obtained', 'languages', 'teacher_category', 'teaching_speciality'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('enseignants', $column)) {
                    // Drop index if exists
                    try {
                        if (in_array($column, ['civility_title_id', 'gender', 'date_of_birth', 'commune_id', 'department_id', 'region_id', 'country_id'])) {
                            $table->dropIndex(['enseignants_' . $column . '_index']);
                        }
                    } catch (\Exception $e) {
                        // Index might not exist
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
