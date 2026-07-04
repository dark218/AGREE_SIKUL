<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // idempotence guard
        if (!Schema::hasTable('exam_finance_monthly_reports')) Schema::hasTable('exam_finance_monthly_reports') ? null : Schema::create('exam_finance_monthly_reports', function (Blueprint $table) {
            $table->id();

            // Period
            $table->date('mois')
                ->index()
                ->comment('Premier jour du mois du rapport');

            // Scope (Optional)
            $table->foreignId('ecole_id')
                ->nullable()
                ->constrained('ecoles')
                ->nullOnDelete();

            // Statistics - Counts
            $table->unsignedInteger('total_examens_planifies')
                ->default(0)
                ->comment('Nombre total d\'examens planifiés');

            $table->unsignedInteger('total_examens_finances')
                ->default(0)
                ->comment('Nombre d\'examens avec financement actif');

            $table->unsignedInteger('total_examens_en_attente')
                ->default(0)
                ->comment('Nombre d\'examens en attente de financement');

            $table->unsignedInteger('total_examens_non_finances')
                ->default(0)
                ->comment('Nombre d\'examens sans financement');

            $table->unsignedInteger('total_examens_clotured')
                ->default(0)
                ->comment('Nombre d\'examens clôturés');

            // Percentages
            $table->decimal('taux_couverture', 5, 2)
                ->default(0)
                ->comment('Taux de couverture (examens financés / total)');

            // Amounts
            $table->decimal('montant_total_facture', 15, 2)
                ->default(0)
                ->comment('Montant total facturé');

            $table->decimal('montant_total_paye', 15, 2)
                ->default(0)
                ->comment('Montant total payé');

            $table->decimal('montant_total_impaye', 15, 2)
                ->default(0)
                ->comment('Montant restant à payer');

            // Revenues & Expenses (for balance calculation)
            $table->decimal('total_revenues', 15, 2)
                ->default(0)
                ->comment('Total des recettes du mois');

            $table->decimal('total_expenses', 15, 2)
                ->default(0)
                ->comment('Total des dépenses du mois');

            $table->decimal('solde_net', 15, 2)
                ->default(0)
                ->comment('Solde net (Recettes - Dépenses)');

            // Notes
            $table->longText('notes_mois')
                ->nullable()
                ->comment('Notes et observations du mois');

            // Generated Info
            $table->timestamp('generated_at')
                ->nullable()
                ->comment('Date de génération du rapport');

            $table->string('generated_by')
                ->nullable()
                ->comment('Utilisateur ayant généré le rapport');

            // Timestamp
            $table->timestamps();

            // Indexes
            $table->unique(['mois', 'ecole_id']);
            $table->index(['generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_finance_monthly_reports');
    }
};
