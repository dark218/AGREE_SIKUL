<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\Document;
use Modules\RessourcesLogistique\Entities\CategorieDocument;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:documents-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:documents-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:documents-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:documents-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Document::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('titre', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $documents = $query->with(['categorie', 'auteur'])->paginate(10)->withQueryString()
                ->through(fn ($document) => [
                    'id' => $document->id,
                    'titre' => $document->titre,
                    'description' => $document->description,
                    'statut' => $document->statut,
                    'categorie' => $document->categorie?->libelle,
                    'auteur' => $document->auteur ? trim(($document->auteur->prenoms ?? '') . ' ' . ($document->auteur->nom ?? '')) : '-',
                ]);

            return Inertia::render('RessourcesLogistique::Documents/Index', [
                'documents' => $documents,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Documents", "DocumentController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            $categories = CategorieDocument::whereNull('deleted_at')->get()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->toArray();
            $users = \App\Models\User::get()->map(fn($u) => ['id' => $u->id, 'name' => trim(($u->prenoms ?? '') . ' ' . ($u->name ?? ''))])->toArray();

            return Inertia::render('RessourcesLogistique::Documents/Create', [
                'categories' => $categories,
                'users' => $users,
                'title' => 'Créer um document',
            ]);
        } catch (\Throwable $th) {
            log_error("Documents", "DocumentController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            \Log::info('📝 Document::store - STARTING');
            \Log::info('📝 Request method:', ['method' => $request->getMethod()]);
            \Log::info('📝 Request path:', ['path' => $request->path()]);
            \Log::info('📝 Request headers:', ['content-type' => $request->header('Content-Type')]);
            \Log::info('📝 Document::store - Request data:', $request->all());

            $validated = $request->validate([
                'categorie_id' => 'required|exists:categories_documents,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'fichier_id' => 'nullable|exists:fichier,id',
                'auteur_id' => 'required|exists:users,id',
                'cibles' => 'nullable|array',
                'date_publication' => 'nullable|date',
            ]);

            \Log::info('✅ Validation passed:', $validated);

            $created = Document::create($validated);

            \Log::info('✅ Record created:', $created->toArray());

            return redirect(route('documents.index'))
                ->with('success', __('messages.created_successfully'));

        } catch (\Illuminate\Validation\ValidationException $ve) {
            \Log::error('❌ VALIDATION ERROR:', [
                'errors' => $ve->errors(),
                'messages' => $ve->messages(),
            ]);
            \Log::error('Validator errors detailed:', $ve->validator->errors()->getMessages());
            return back()->withErrors($ve->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('❌ Document::store EXCEPTION:', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'ERROR: ' . get_class($e) . ' - ' . $e->getMessage());
        } catch (\Throwable $th) {
            \Log::error('❌ Document::store THROWABLE:', [
                'class' => get_class($th),
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);
            log_error("Documents", "DocumentController::store", $th->getMessage());
            return back()->with('error', 'THROWABLE: ' . get_class($th) . ' - ' . $th->getMessage());
        }
    }

    public function show(Document $document)
    {
        try {
            $document->load('categorie', 'auteur');
            $categories = CategorieDocument::whereNull('deleted_at')->get()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->toArray();
            $users = \App\Models\User::get()->map(fn($u) => ['id' => $u->id, 'name' => trim(($u->prenoms ?? '') . ' ' . ($u->name ?? ''))])->toArray();

            return Inertia::render('RessourcesLogistique::Documents/Show', [
                'document' => $document->toArray(),
                'categories' => $categories,
                'users' => $users,
            ]);
        } catch (\Throwable $th) {
            log_error("Documents", "DocumentController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Document $document)
    {
        try {
            $document->load('categorie', 'auteur');
            $categories = CategorieDocument::whereNull('deleted_at')->get()->map(fn($c) => ['id' => $c->id, 'libelle' => $c->libelle])->toArray();
            $users = \App\Models\User::get()->map(fn($u) => ['id' => $u->id, 'name' => trim(($u->prenoms ?? '') . ' ' . ($u->name ?? ''))])->toArray();

            return Inertia::render('RessourcesLogistique::Documents/Edit', [
                'document' => $document->toArray(),
                'categories' => $categories,
                'users' => $users,
            ]);
        } catch (\Throwable $th) {
            log_error("Documents", "DocumentController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Document $document)
    {
        try {
            $validated = $request->validate([
                'categorie_id' => 'required|exists:categories_documents,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'fichier_id' => 'nullable|exists:fichier,id',
                'auteur_id' => 'required|exists:users,id',
                'cibles' => 'nullable|array',
                'date_publication' => 'nullable|date',
            ]);

            $document->update($validated);

            return redirect(route('documents.show', $document->id))
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Documents", "DocumentController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Document $document)
    {
        try {
            $document->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Documents", "DocumentController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Document $document)
    {
        try {
            if ($document->trashed()) {
                $document->restore();
            } else {
                $document->delete();
            }

            return redirect()->route('documents.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Documents", "DocumentController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
