<?php

namespace App\Services;

use Illuminate\Support\Str;
use Modules\Ecole\Entities\AnneeScolaire;
use Modules\Ecole\Entities\Classe;
use Modules\GestionApprenants\Entities\Apprenant;
use Modules\GestionApprenants\Entities\Inscription;

class InscriptionService
{
    /**
     * Créer une nouvelle inscription
     */
    public function inscrireApprenant(
        Apprenant $apprenant,
        Classe $classe,
        AnneeScolaire $anneeScolaire,
        array $data
    ): Inscription {
        // Valider les conditions
        $this->validerPrerequis($apprenant, $classe, $anneeScolaire);

        // Vérifier capacité classe
        if (!$this->verifierCapaciteClasse($classe)) {
            throw new \Exception("La classe a atteint sa capacité maximale");
        }

        // Vérifier une seule inscription active par apprenant/année
        $inscriptionExistante = Inscription::where('apprenant_id', $apprenant->id)
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->where('statut', 'validee')
            ->exists();

        if ($inscriptionExistante) {
            throw new \Exception("L'apprenant est déjà inscrit pour cette année scolaire");
        }

        // Générer numéro inscription
        $numeroInscription = $this->genererNumeroInscription($classe, $anneeScolaire);

        // Créer l'inscription
        $inscription = Inscription::create([
            'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id,
            'annee_scolaire_id' => $anneeScolaire->id,
            'numero_inscription' => $numeroInscription,
            'date_inscription' => now(),
            'type_inscription' => $data['type_inscription'] ?? 'nouveau',
            'statut' => 'en_attente',
        ]);

        return $inscription;
    }

    /**
     * Valider une inscription
     */
    public function validerInscription(Inscription $inscription): void
    {
        // Vérifier que les documents sont complets
        if (!$this->verifierDocuments($inscription->apprenant)) {
            throw new \Exception("Le dossier n'est pas complet. Documents requis manquants.");
        }

        $inscription->update(['statut' => 'validee']);
    }

    /**
     * Annuler une inscription
     */
    public function annulerInscription(Inscription $inscription, string $motif): void
    {
        $inscription->update([
            'statut' => 'rejetee',
        ]);
    }

    /**
     * Vérifier les prérequis
     */
    private function validerPrerequis(
        Apprenant $apprenant,
        Classe $classe,
        AnneeScolaire $anneeScolaire
    ): void {
        // L'apprenant doit être actif
        if (!$apprenant->isActif()) {
            throw new \Exception("L'apprenant n'est pas actif");
        }

        // La classe doit être active
        if (!$classe->isActif()) {
            throw new \Exception("La classe n'est pas active");
        }

        // L'année scolaire doit être courante
        if (!$anneeScolaire->estCourante()) {
            throw new \Exception("Cette année scolaire n'est pas courante");
        }
    }

    /**
     * Vérifier la capacité d'une classe
     */
    public function verifierCapaciteClasse(Classe $classe): bool
    {
        if ($classe->capacite_max === null) {
            return true; // Pas de limite
        }

        $nombreInscrits = Inscription::where('classe_id', $classe->id)
            ->where('statut', 'validee')
            ->count();

        return $nombreInscrits < $classe->capacite_max;
    }

    /**
     * Obtenir la capacité disponible
     */
    public function getCapaciteDisponible(Classe $classe): int
    {
        if ($classe->capacite_max === null) {
            return PHP_INT_MAX;
        }

        $nombreInscrits = Inscription::where('classe_id', $classe->id)
            ->where('statut', 'validee')
            ->count();

        return max(0, $classe->capacite_max - $nombreInscrits);
    }

    /**
     * Vérifier les documents
     */
    private function verifierDocuments(Apprenant $apprenant): bool
    {
        $dossier = $apprenant->dossier;

        if (!$dossier) {
            return false;
        }

        return $dossier->isDossierComplet();
    }

    /**
     * Générer numéro inscription unique
     */
    private function genererNumeroInscription(Classe $classe, AnneeScolaire $anneeScolaire): string
    {
        $code = strtoupper($classe->ecole->code ?? 'ECO');
        $annee = $anneeScolaire->date_debut->format('y');
        $numero = str_pad(
            Inscription::where('classe_id', $classe->id)
                ->where('annee_scolaire_id', $anneeScolaire->id)
                ->count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

        return "{$code}-{$annee}-{$numero}";
    }

    /**
     * Calculer les frais d'inscription
     */
    public function calculerFraisInscription(Classe $classe, string $typeInscription): float
    {
        $frais = 0;

        // Logique métier pour calculer les frais selon la classe et le type
        // À adapter selon vos tarifs
        switch ($typeInscription) {
            case 'nouveau':
                $frais = 50000; // Frais de base
                break;
            case 'redoublement':
                $frais = 40000; // Frais réduits
                break;
            case 'transfert':
                $frais = 30000;
                break;
        }

        return $frais;
    }

    /**
     * Obtenir les inscriptions courantes pour un apprenant
     */
    public function getInscriptionCourante(Apprenant $apprenant): ?Inscription
    {
        $anneeCourante = AnneeScolaire::where('est_courante', true)->first();

        if (!$anneeCourante) {
            return null;
        }

        return Inscription::where('apprenant_id', $apprenant->id)
            ->where('annee_scolaire_id', $anneeCourante->id)
            ->where('statut', 'validee')
            ->first();
    }

    /**
     * Lister les apprenants d'une classe
     */
    public function getApprenantsClasse(Classe $classe, AnneeScolaire $anneeScolaire = null)
    {
        $query = Inscription::where('classe_id', $classe->id)
            ->where('statut', 'validee')
            ->with('apprenant.user');

        if ($anneeScolaire) {
            $query->where('annee_scolaire_id', $anneeScolaire->id);
        } else {
            // Par défaut, année courante
            $anneeCourante = AnneeScolaire::where('est_courante', true)->first();
            if ($anneeCourante) {
                $query->where('annee_scolaire_id', $anneeCourante->id);
            }
        }

        return $query->get();
    }
}
