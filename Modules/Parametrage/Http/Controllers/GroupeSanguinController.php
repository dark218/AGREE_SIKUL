<?php

namespace Modules\Parametrage\Http\Controllers;

class GroupeSanguinController extends AbstractReferentielController
{
    protected const MODEL = \Modules\Parametrage\Entities\GroupeSanguin::class;
    protected const VIEW_DIR = 'Parametrage::GroupesSanguins';
    protected const ROUTE_NAME = 'parametrage.groupes_sanguins';
    protected const TITLE = 'Groupe sanguin';
}
