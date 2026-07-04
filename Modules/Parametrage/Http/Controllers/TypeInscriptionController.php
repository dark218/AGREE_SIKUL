<?php

namespace Modules\Parametrage\Http\Controllers;

class TypeInscriptionController extends AbstractReferentielController
{
    protected const MODEL = \Modules\Parametrage\Entities\TypeInscription::class;
    protected const VIEW_DIR = 'Parametrage::TypesInscriptions';
    protected const ROUTE_NAME = 'parametrage.types_inscriptions';
    protected const TITLE = "Type d'inscription";
}
