<?php

namespace Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Administration\Entities\ErrorLog;

class ErrorLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:errorlog-list', ['only' => ['index','show']]);
        $this->middleware('permission.check:errorlog-delete', ['only' => ['destroy','destroyAll']]);
    }
    public function index(Request $request): Response
    {
        $filters = [
            'module' => $request->input('module'),
            'methode' => $request->input('methode'),
        ];

        $query = ErrorLog::orderBy('created_at', 'DESC');

        if (!empty($filters['module'])) {
            $query->where('module', 'LIKE', "%{$filters['module']}%");
        }
        if (!empty($filters['methode'])) {
            $query->where('methode', 'LIKE', "%{$filters['methode']}%");
        }

        $errorlogs = $query->paginate(10)->withQueryString();

        // Transformer les sessions pour Vue
        $errorlogs->getCollection()->transform(function ($errorlog) {
            return [
                'id' => $errorlog->id,
                'methode' => $errorlog->methode,
                'module' => $errorlog->module,
                'message' => $errorlog->message,
                'created_at' => $errorlog->created_at,
            ];
        });

        return Inertia::render('Administration::ErrorLog/Index', [
            'errorlogs' => $errorlogs,
            'filters' => $filters,
        ]);
    }
    public function create(string $module, string $methode, string $message): bool
    {
        try {
            ErrorLog::create([
                'module'  => $module,
                'methode' => $methode,
                'message' => $message,
            ]);

            return true;

        } catch (\Throwable $e) {
            // Sécurité : log fallback
            Log::error('ErrorLog failed: '.$e->getMessage(), [
                'module'  => $module,
                'methode' => $methode,
                'message' => $message,
            ]);

            return false;
        }
    }
    public function show($id){
        try {
            $errorlog = ErrorLog::findOrFail($id);

            return Inertia::render('Administration::ErrorLog/Show', [
                'errorlog' => $errorlog,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('administration.errorlog.index')->with('error', __('error_loading_form'));
        }
    }
    public function destroy($id)
    {
        try {
            ErrorLog::where('id', $id)->delete();
            return redirect()->route('administration.errorlog.index')
                ->with('success', __('suppressionsucces'));
        } catch (\Throwable $e) {
            return redirect()->route('administration.errorlog.index')
                ->with('error', __('erreurmaj') . ': ' . $e->getMessage());
        }
    }
    /**
     * Supprime tous les logs d'erreur
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyAll()
    {
        try {
            ErrorLog::truncate();

            return redirect()->route('administration.errorlog.index')
                ->with('success', __('deleted'));

        } catch (\Throwable $e) {
            return redirect()->route('administration.errorlog.index')
                ->with('error', __('erreurmaj') . ': ' . $e->getMessage());
        }
    }

}
