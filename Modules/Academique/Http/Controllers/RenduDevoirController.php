<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\RenduDevoir;
use Modules\Academique\Entities\Devoir;
use Modules\Academique\Entities\Apprenant;
use Modules\GestionStock\Entities\Fichier;
use Illuminate\Support\Facades\Storage;

class RenduDevoirController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:rendus_devoirs-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:rendus_devoirs-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:rendus_devoirs-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:rendus_devoirs-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = RenduDevoir::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->whereHas('user', function ($user) use ($search) {
                        $user->where('nom', 'like', "%$search%")
                            ->orWhere('prenoms', 'like', "%$search%");
                    });
                });
            }

            $rendus = $query->with(['devoir', 'apprenant.user'])->paginate(10)->withQueryString()
                ->through(fn($item) => [
                    'id' => $item->id,
                    'devoir_id' => $item->devoir_id,
                    'apprenant_id' => $item->apprenant_id,
                    'date_rendu' => $item->date_rendu?->format('Y-m-d'),
                    'note_finale' => $item->note_finale,
                    'notes_enseignant' => $item->notes_enseignant,
                    'statut' => $item->statut,
                    'devoir' => $item->devoir ? [
                        'id' => $item->devoir->id,
                        'titre' => $item->devoir->titre,
                    ] : null,
                    'apprenant' => $item->apprenant ? [
                        'id' => $item->apprenant->id,
                        'matricule' => $item->apprenant->matricule,
                        'user' => $item->apprenant->user ? [
                            'id' => $item->apprenant->user->id,
                            'nom' => $item->apprenant->user->nom,
                            'prenoms' => $item->apprenant->user->prenoms,
                        ] : null,
                    ] : null,
                ]);

            return Inertia::render('Academique::RendusDevoirs/Index', [
                'title' => __('common.rendus_devoirs'),
                'rendusDevoirs' => $rendus,
                'filters' => $request->only(['search']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "RenduDevoirController::index", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            $devoirs = Devoir::select('id', 'titre')->get()->map(fn($d) => [
                'id' => $d->id,
                'libelle' => $d->titre,
            ])->toArray();

            $apprenants = Apprenant::with('user', 'classe')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($a) {
                    $apprenantName = trim(($a->user?->prenoms ?? '') . ' ' . ($a->user?->nom ?? ''));
                    $classeName = $a->classe?->nom ?? 'N/A';
                    $matricule = $a->matricule ?? 'N/A';

                    return [
                        'id' => $a->id,
                        'libelle' => "{$apprenantName} ({$matricule}) | Classe: {$classeName}",
                    ];
                })
                ->values()
                ->toArray();

            return Inertia::render('Academique::RendusDevoirs/Create', [
                'title' => __('actions.create'),
                'devoirs' => $devoirs,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "RenduDevoirController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            // DEBUG: Log incoming request data
            \Log::info('RenduDevoir store - Request data:', $request->all());

            $validated = $request->validate([
                'devoir_id' => 'required|exists:devoirs,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'date_rendu' => 'required|date',
                'note_finale' => 'nullable|numeric|min:0|max:20',
                'notes_enseignant' => 'nullable|string',
                'fichier_rendu' => 'nullable|file|mimes:pdf,doc,docx,txt,jpg,jpeg,png,zip|max:10240', // 10MB max
            ]);

            // DEBUG: Log validated data
            \Log::info('RenduDevoir store - Validated data:', $validated);

            // Handle file upload if present
            $fichier_id = null;
            if ($request->hasFile('fichier_rendu')) {
                try {
                    $file = $request->file('fichier_rendu');
                    $originalName = $file->getClientOriginalName();
                    $mimeType = $file->getMimeType();
                    $size = $file->getSize();

                    // Store file in storage/app/rendus-devoirs
                    $path = $file->store('rendus-devoirs/submissions');

                    // Create Fichier record
                    $fichier = Fichier::create([
                        'nom' => $originalName,
                        'chemin' => $path,
                        'type_mime' => $mimeType,
                        'taille' => $size,
                    ]);

                    $fichier_id = $fichier->id;
                    \Log::info('RenduDevoir store - File uploaded:', ['fichier_id' => $fichier_id, 'path' => $path]);
                } catch (\Throwable $fileError) {
                    \Log::error('RenduDevoir store - File upload error:', ['error' => $fileError->getMessage()]);
                    // Continue without file if upload fails
                }
            }

            // Add fichier_id to validated data
            $validated['fichier_id'] = $fichier_id;

            // Date is already in Y-m-d format from HTML date input, no conversion needed

            $created = RenduDevoir::create($validated);

            // DEBUG: Log created record
            \Log::info('RenduDevoir store - Created record:', $created->toArray());

            return redirect()->route('academique.rendus_devoirs.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            // DEBUG: Log validation errors
            \Log::error('RenduDevoir store - Validation errors:', $ve->errors());
            return back()->withErrors($ve->errors())->withInput();
        } catch (\Throwable $th) {
            // DEBUG: Log full error with stack trace
            \Log::error('RenduDevoir store - Exception:', [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]);
            return back()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()])->withInput();
        }
    }

    public function show(RenduDevoir $renduDevoir)
    {
        try {
            $renduDevoir->load('devoir', 'apprenant.user');

            return Inertia::render('Academique::RendusDevoirs/Show', [
                'title' => __('actions.view'),
                'rendu' => $renduDevoir,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "RenduDevoirController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(RenduDevoir $renduDevoir)
    {
        try {
            $devoirs = Devoir::select('id', 'titre')->get()->map(fn($d) => [
                'id' => $d->id,
                'libelle' => $d->titre,
            ])->toArray();
            $apprenants = Apprenant::with('user')->get()->map(function($a) {
                $apprenantName = trim(($a->user?->prenoms ?? '') . ' ' . ($a->user?->nom ?? ''));
                $matricule = $a->matricule ?? 'N/A';
                return [
                    'id' => $a->id,
                    'libelle' => "{$apprenantName} ({$matricule})"
                ];
            })->toArray();

            return Inertia::render('Academique::RendusDevoirs/Edit', [
                'title' => __('actions.edit'),
                'rendu' => $renduDevoir->load('devoir', 'apprenant.user'),
                'devoirs' => $devoirs,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "RenduDevoirController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, RenduDevoir $renduDevoir)
    {
        try {
            $validated = $request->validate([
                'devoir_id' => 'required|exists:devoirs,id',
                'apprenant_id' => 'required|exists:apprenants,id',
                'date_rendu' => 'required|date',
                'note_finale' => 'nullable|numeric|min:0|max:20',
                'notes_enseignant' => 'nullable|string',
            ]);

            $renduDevoir->update($validated);

            return redirect()->route('academique.rendus_devoirs.show', $renduDevoir)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "RenduDevoirController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(RenduDevoir $renduDevoir)
    {
        try {
            $renduDevoir->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "RenduDevoirController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(RenduDevoir $renduDevoir)
    {
        try {
            if ($renduDevoir->trashed()) {
                $renduDevoir->restore();
            } else {
                $renduDevoir->delete();
            }

            return redirect()->route('academique.rendus_devoirs.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Academique", "RenduDevoirController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
