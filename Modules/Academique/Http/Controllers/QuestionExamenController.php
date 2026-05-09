<?php

namespace Modules\Academique\Http\Controllers;

use Modules\Academique\Entities\ExamenEnLigne;
use Modules\Academique\Entities\QuestionExamen;
use Modules\Academique\Entities\ReponseQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuestionExamenController extends Controller
{
    /**
     * Liste des questions d'un examen (API JSON)
     */
    public function index(ExamenEnLigne $examenEnLigne)
    {
        try {
            $questions = $examenEnLigne->questions()
                ->with('reponses')
                ->orderBy('ordre')
                ->get()
                ->map(function ($q) {
                    return [
                        'id' => $q->id,
                        'ordre' => $q->ordre,
                        'type' => $q->type,
                        'enonce' => $q->enonce,
                        'explication' => $q->explication,
                        'image' => $q->image,
                        'points' => $q->points,
                        'obligatoire' => $q->obligatoire,
                        'etat' => $q->etat,
                        'reponses' => $q->reponses->map(function ($r) {
                            return [
                                'id' => $r->id,
                                'ordre' => $r->ordre,
                                'texte' => $r->texte,
                                'est_correcte' => $r->est_correcte,
                                'explication' => $r->explication,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'questions' => $questions,
                'total_points' => $questions->sum('points'),
                'total_questions' => $questions->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Créer une question avec ses réponses
     */
    public function store(Request $request, ExamenEnLigne $examenEnLigne)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:qcm,vrai_faux,reponse_libre,texte_trous',
            'enonce' => 'required|string',
            'explication' => 'nullable|string',
            'points' => 'required|numeric|min:0.01',
            'obligatoire' => 'boolean',
            'reponses' => 'required_if:type,qcm,vrai_faux|array',
            'reponses.*.texte' => 'required_with:reponses|string',
            'reponses.*.est_correcte' => 'required_with:reponses|boolean',
            'reponses.*.explication' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Vérifier qu'au moins une réponse correcte pour QCM/Vrai-Faux
        if (in_array($validated['type'], ['qcm', 'vrai_faux'])) {
            $hasCorrect = collect($validated['reponses'] ?? [])->contains('est_correcte', true);
            if (!$hasCorrect) {
                return response()->json([
                    'errors' => ['reponses' => ['Au moins une réponse doit être marquée comme correcte.']]
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($validated, $examenEnLigne) {
                $maxOrdre = $examenEnLigne->questions()->max('ordre') ?? 0;

                $question = $examenEnLigne->questions()->create([
                    'type' => $validated['type'],
                    'enonce' => $validated['enonce'],
                    'explication' => $validated['explication'] ?? null,
                    'points' => $validated['points'],
                    'obligatoire' => $validated['obligatoire'] ?? true,
                    'ordre' => $maxOrdre + 1,
                    'etat' => 'actif',
                    'creation_username' => auth()->user()->name,
                ]);

                // Créer les réponses pour QCM et Vrai/Faux
                if (in_array($validated['type'], ['qcm', 'vrai_faux']) && !empty($validated['reponses'])) {
                    foreach ($validated['reponses'] as $index => $reponse) {
                        $question->reponses()->create([
                            'texte' => $reponse['texte'],
                            'est_correcte' => $reponse['est_correcte'],
                            'explication' => $reponse['explication'] ?? null,
                            'ordre' => $index + 1,
                        ]);
                    }
                }

                // Mettre à jour le nombre_questions de l'examen
                $examenEnLigne->update([
                    'nombre_questions' => $examenEnLigne->questions()->actif()->count(),
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Question ajoutée avec succès']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour une question et ses réponses
     */
    public function update(Request $request, ExamenEnLigne $examenEnLigne, QuestionExamen $question)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:qcm,vrai_faux,reponse_libre,texte_trous',
            'enonce' => 'required|string',
            'explication' => 'nullable|string',
            'points' => 'required|numeric|min:0.01',
            'obligatoire' => 'boolean',
            'reponses' => 'required_if:type,qcm,vrai_faux|array',
            'reponses.*.id' => 'nullable|integer',
            'reponses.*.texte' => 'required_with:reponses|string',
            'reponses.*.est_correcte' => 'required_with:reponses|boolean',
            'reponses.*.explication' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        if (in_array($validated['type'], ['qcm', 'vrai_faux'])) {
            $hasCorrect = collect($validated['reponses'] ?? [])->contains('est_correcte', true);
            if (!$hasCorrect) {
                return response()->json([
                    'errors' => ['reponses' => ['Au moins une réponse doit être correcte.']]
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($validated, $question) {
                $question->update([
                    'type' => $validated['type'],
                    'enonce' => $validated['enonce'],
                    'explication' => $validated['explication'] ?? null,
                    'points' => $validated['points'],
                    'obligatoire' => $validated['obligatoire'] ?? true,
                    'modification_username' => auth()->user()->name,
                ]);

                if (in_array($validated['type'], ['qcm', 'vrai_faux'])) {
                    $question->reponses()->forceDelete();
                    foreach ($validated['reponses'] as $index => $reponse) {
                        $question->reponses()->create([
                            'texte' => $reponse['texte'],
                            'est_correcte' => $reponse['est_correcte'],
                            'explication' => $reponse['explication'] ?? null,
                            'ordre' => $index + 1,
                        ]);
                    }
                }
            });

            return response()->json(['success' => true, 'message' => 'Question mise à jour']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer une question
     */
    public function destroy(ExamenEnLigne $examenEnLigne, QuestionExamen $question)
    {
        try {
            $question->reponses()->forceDelete();
            $question->delete();

            $examenEnLigne->questions()->orderBy('ordre')->get()->each(function ($q, $index) {
                $q->update(['ordre' => $index + 1]);
            });

            $examenEnLigne->update([
                'nombre_questions' => $examenEnLigne->questions()->actif()->count(),
            ]);

            return response()->json(['success' => true, 'message' => 'Question supprimée']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Réordonner les questions
     */
    public function reorder(Request $request, ExamenEnLigne $examenEnLigne)
    {
        $validator = Validator::make($request->all(), [
            'order' => 'required|array',
            'order.*' => 'integer|exists:questions_examen,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($validator->validated()['order'] as $index => $questionId) {
            QuestionExamen::where('id', $questionId)->update(['ordre' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
