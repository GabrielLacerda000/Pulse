<?php

namespace App\Http\Controllers;

use App\Actions\ImportLeadsAction;
use App\Actions\ParseLeadImportAction;
use App\Http\Requests\StoreLeadImportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class LeadImportController extends Controller
{
    private const SESSION_KEY = 'leads_import_rows';

    /**
     * Show the spreadsheet upload form.
     */
    public function create(): Response
    {
        return Inertia::render('leads/Import');
    }

    /**
     * Parse the uploaded spreadsheet and show a preview of the import.
     */
    public function preview(StoreLeadImportRequest $request, ParseLeadImportAction $action): Response
    {
        $result = $action->execute($request->file('file'));

        session([self::SESSION_KEY => $result['rows']]);

        return Inertia::render('leads/Import', [
            'preview' => Arr::except($result, 'rows'),
        ]);
    }

    /**
     * Confirm the previewed import, persisting its rows.
     */
    public function store(ImportLeadsAction $action): RedirectResponse
    {
        $rows = session(self::SESSION_KEY);

        if ($rows === null) {
            return to_route('leads.import.create')
                ->with('error', 'A pré-visualização expirou. Envie o arquivo novamente.');
        }

        $summary = $action->execute($rows);

        session()->forget(self::SESSION_KEY);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':created leads criados, :duplicated atualizados, :invalid inválidos.', [
                'created' => $summary['created'],
                'duplicated' => $summary['duplicated'],
                'invalid' => $summary['invalid'],
            ]),
        ]);

        return to_route('leads.index');
    }
}
