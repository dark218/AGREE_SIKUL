<?php

namespace Modules\Parametrage\Http\Controllers;

class SituationMatrimonialeController extends AbstractReferentielController
{
    protected const MODEL = \Modules\Parametrage\Entities\SituationMatrimoniale::class;
    protected const VIEW_DIR = 'Parametrage::SituationsMatrimoniales';
    protected const ROUTE_NAME = 'parametrage.situations_matrimoniales';
    protected const TITLE = 'Situation matrimoniale';
}
