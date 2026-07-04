<?php

namespace Modules\Parametrage\Http\Controllers;

class LienParenteController extends AbstractReferentielController
{
    protected const MODEL = \Modules\Parametrage\Entities\LienParente::class;
    protected const VIEW_DIR = 'Parametrage::LiensParente';
    protected const ROUTE_NAME = 'parametrage.liens_parente';
    protected const TITLE = 'Lien de parenté';
}
