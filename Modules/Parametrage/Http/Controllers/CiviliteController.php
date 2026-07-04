<?php

namespace Modules\Parametrage\Http\Controllers;

class CiviliteController extends AbstractReferentielController
{
    protected const MODEL = \Modules\Parametrage\Entities\Civilite::class;
    protected const VIEW_DIR = 'Parametrage::Civilites';
    protected const ROUTE_NAME = 'parametrage.civilites';
    protected const TITLE = 'Civilité';
}
