<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\CategorieDocument;

class CategorieDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:categorie-document-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:categorie-document-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:categorie-document-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:categorie-document-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = CategorieDocument::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('libelle', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $categories = $query->paginate(10)->withQueryString()
                ->through(fn ($categorie) => [
                    'id' => $categorie->id,
                    'code' => $categorie->code,
                    'libelle' => $categorie->libelle,
                    'statut' => $categorie->statut,
                ]);

            return Inertia::render('RessourcesLogistique::CategoriesDocuments/Index', [
                'categoriesDocuments' => $categories,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Documents", "CategorieDocumentController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('RessourcesLogistique::CategoriesDocuments/Create');
        } catch (\Throwable $th) {
            log_error("Documents", "CategorieDocumentController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:categories_documents',
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'icone' => 'nullable|string|max:100',
                'couleur' => 'nullable|string|max:50',
                'statut' => 'required|in:actif,inactif',
            ]);

            CategorieDocument::create($validated);

            return redirect()->route('categories-documents.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Documents", "CategorieDocumentController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(CategorieDocument $categorie)
    {
        try {
            $categorie->load('documents');

            return Inertia::render('RessourcesLogistique::CategoriesDocuments/Show', [
                'categorie' => $categorie->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Documents", "CategorieDocumentController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(CategorieDocument $categorie)
    {
        try {
            return Inertia::render('RessourcesLogistique::CategoriesDocuments/Edit', [
                'categorie' => $categorie->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Documents", "CategorieDocumentController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, CategorieDocument $categorie)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:100|unique:categories_documents,code,' . $categorie->id,
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'icone' => 'nullable|string|max:100',
                'couleur' => 'nullable|string|max:50',
                'statut' => 'required|in:actif,inactif',
            ]);

            $categorie->update($validated);

            return redirect()->route('categories-documents.show', $categorie)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Documents", "CategorieDocumentController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(CategorieDocument $categorie)
    {
        try {
            $categorie->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Documents", "CategorieDocumentController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(CategorieDocument $categorie)
    {
        try {
            $categorie->statut = $categorie->statut === 'actif' ? 'inactif' : 'actif';
            $categorie->save();

            return redirect()->route('categories-documents.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Documents", "CategorieDocumentController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
