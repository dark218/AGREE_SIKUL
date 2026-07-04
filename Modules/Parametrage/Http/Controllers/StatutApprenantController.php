<?php

namespace Modules\Parametrage\Http\Controllers;

class StatutApprenantController extends AbstractReferentielController
{
    protected const MODEL = \Modules\Parametrage\Entities\StatutApprenant::class;
    protected const VIEW_DIR = 'Parametrage::StatutsApprenants';
    protected const ROUTE_NAME = 'parametrage.statuts_apprenants';
    protected const TITLE = "Statut d'apprenant";
}
