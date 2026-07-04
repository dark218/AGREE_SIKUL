<?php

namespace Modules\Parametrage\Http\Controllers;

class LangueController extends AbstractReferentielController
{
    protected const MODEL = \Modules\Parametrage\Entities\Langue::class;
    protected const VIEW_DIR = 'Parametrage::Langues';
    protected const ROUTE_NAME = 'parametrage.langues';
    protected const TITLE = 'Langue';
}
