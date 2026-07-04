<?php

namespace Modules\Parametrage\Http\Controllers;

class StatutEmployeController extends AbstractReferentielController
{
    protected const MODEL = \Modules\Parametrage\Entities\StatutEmploye::class;
    protected const VIEW_DIR = 'Parametrage::StatutsEmployes';
    protected const ROUTE_NAME = 'parametrage.statuts_employes';
    protected const TITLE = "Statut d'employé";
}
