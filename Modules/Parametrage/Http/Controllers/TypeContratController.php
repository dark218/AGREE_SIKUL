<?php

namespace Modules\Parametrage\Http\Controllers;

class TypeContratController extends AbstractReferentielController
{
    protected const MODEL = \Modules\Parametrage\Entities\TypeContrat::class;
    protected const VIEW_DIR = 'Parametrage::TypesContrats';
    protected const ROUTE_NAME = 'parametrage.types_contrats';
    protected const TITLE = 'Type de contrat';
}
