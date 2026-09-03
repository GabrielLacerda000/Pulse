<?php

use App\Actions\ImportLeadsAction;
use App\Actions\ParseLeadImportAction;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Builds a fake CSV UploadedFile matching one of the external capture
 * system's expected column layouts (PRD §9), given a list of rows (each a
 * list of cell values in column order). "legacy" is the original 18-column
 * layout; "v2" is the 2026-09 layout (renamed/added/dropped columns).
 *
 * @param  list<list<string>>  $rows
 */
function fakeLeadsSpreadsheet(array $rows, string $format = 'legacy'): UploadedFile
{
    $header = match ($format) {
        'legacy' => [
            'Nome', 'Categoria', 'Telefone', 'E-mail', 'Site', 'Tem site',
            'Instagram', 'Tem Instagram', 'Cidade', 'Estado', 'Status', 'Tipo',
            'Score', 'Avaliação', 'Nº avaliações', 'Fonte', 'Google Maps', 'Criado em',
        ],
        'v2' => [
            'Nome', 'Nicho', 'Telefone', 'WhatsApp provável', 'Site', 'Tem site',
            'Instagram', 'E-mail', 'Facebook', 'Responsável', 'Resumo', 'Cidade', 'Estado',
            'Score', 'Avaliação', 'Nº avaliações', 'Fonte', 'Google Maps', 'Encontrado em',
        ],
    };

    $lines = collect([$header, ...$rows])
        ->map(fn (array $row): string => implode(',', array_map(
            fn (string $cell): string => '"'.str_replace('"', '""', $cell).'"',
            $row,
        )))
        ->implode("\n");

    $path = tempnam(sys_get_temp_dir(), 'leads_import_').'.csv';
    file_put_contents($path, $lines);

    return new UploadedFile($path, 'leads.csv', 'text/csv', null, true);
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('leads.import.create'));

    $response->assertRedirect(route('login'));
});

describe('as an authenticated user', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
    });

    test('the import page can be viewed', function () {
        $response = $this->get(route('leads.import.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('leads/Import'));
    });

    test('previewing a valid spreadsheet reports counts of new, duplicated and invalid rows', function () {
        Lead::factory()->create(['phone' => '5581988887777']);

        $file = fakeLeadsSpreadsheet([
            ['Clínica Nova', 'Clínica odontológica', '81999990000', 'nova@exemplo.com', 'nova.com.br', 'Sim', '@nova', 'Não', 'Recife', 'PE', 'Novo', 'Com site', '80', '4.5', '20', 'google_maps', 'https://maps.example/nova', '2026-01-10'],
            ['', 'Sem nome', '81999991111', '', '', 'Não', '', 'Não', 'Olinda', 'PE', 'Novo', 'Sem presença', '10', '', '', 'google_maps', '', ''],
            ['Empresa Existente', 'Restaurante', '81988887777', '', '', 'Sim', '', 'Sim', 'Recife', 'PE', 'Novo', 'Com site', '90', '4.9', '50', 'google_maps', 'https://maps.example/existente', '2026-02-01'],
        ]);

        $response = $this->post(route('leads.import.preview'), ['file' => $file]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('leads/Import')
            ->where('preview.total', 3)
            ->where('preview.new', 1)
            ->where('preview.duplicated', 1)
            ->where('preview.invalid', 1)
        );
    });

    test('previewing a spreadsheet with the wrong columns reports a clear error', function () {
        $path = tempnam(sys_get_temp_dir(), 'bad_import_').'.csv';
        file_put_contents($path, "Nome,Telefone\nEmpresa,81999990000");
        $file = new UploadedFile($path, 'bad.csv', 'text/csv', null, true);

        $response = $this->post(route('leads.import.preview'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
    });

    test('confirming an import creates new leads with status novo', function () {
        $file = fakeLeadsSpreadsheet([
            ['Clínica Nova', 'Clínica odontológica', '81999990000', 'nova@exemplo.com', 'nova.com.br', 'Sim', '@nova', 'Não', 'Recife', 'PE', 'Novo', 'Com site', '80', '4.5', '20', 'google_maps', 'https://maps.example/nova', '2026-01-10'],
        ]);

        $this->post(route('leads.import.preview'), ['file' => $file]);
        $response = $this->post(route('leads.import.store'));

        $response->assertRedirect(route('leads.index'));

        $lead = Lead::firstWhere('company_name', 'Clínica Nova');

        expect($lead)->not->toBeNull();
        expect($lead->status)->toBe(LeadStatus::NEW);
        expect($lead->city)->toBe('Recife');
        expect($lead->score)->toBe(80);
    });

    test('re-importing a row for a lead with an advanced status preserves that status', function () {
        $existing = Lead::factory()->create([
            'phone' => '5581988887777',
            'status' => LeadStatus::PROPOSAL,
        ]);

        $file = fakeLeadsSpreadsheet([
            ['Empresa Existente', 'Restaurante', '81988887777', '', '', 'Sim', '', 'Sim', 'Recife', 'PE', 'Novo', 'Com site', '95', '4.9', '60', 'google_maps', 'https://maps.example/existente', '2026-02-01'],
        ]);

        $this->post(route('leads.import.preview'), ['file' => $file]);
        $this->post(route('leads.import.store'));

        expect($existing->fresh()->status)->toBe(LeadStatus::PROPOSAL);
        expect($existing->fresh()->score)->toBe(95);
    });

    test('re-importing a row for a lead preserves its existing notes', function () {
        $existing = Lead::factory()->create([
            'phone' => '5581988887777',
            'notes' => 'Observação importante do vendedor.',
        ]);

        $file = fakeLeadsSpreadsheet([
            ['Empresa Existente', 'Restaurante', '81988887777', '', '', 'Sim', '', 'Sim', 'Recife', 'PE', 'Novo', 'Com site', '95', '4.9', '60', 'google_maps', 'https://maps.example/existente', '2026-02-01'],
        ]);

        $this->post(route('leads.import.preview'), ['file' => $file]);
        $this->post(route('leads.import.store'));

        expect($existing->fresh()->notes)->toBe('Observação importante do vendedor.');
    });

    test('two rows sharing a phone within the same spreadsheet are matched to each other, not created twice', function () {
        $file = fakeLeadsSpreadsheet([
            ['Empresa Duplicada', 'Restaurante', '81977776666', '', '', 'Não', '', 'Não', 'Recife', 'PE', 'Novo', 'Sem presença', '50', '', '', 'google_maps', '', ''],
            ['Empresa Duplicada Ltda', 'Restaurante', '81977776666', '', '', 'Sim', '', 'Sim', 'Recife', 'PE', 'Novo', 'Com site', '70', '4.2', '10', 'google_maps', 'https://maps.example/duplicada', '2026-03-01'],
        ]);

        $previewResponse = $this->post(route('leads.import.preview'), ['file' => $file]);

        $previewResponse->assertInertia(fn ($page) => $page
            ->where('preview.total', 2)
            ->where('preview.new', 1)
            ->where('preview.duplicated', 1)
        );

        $this->post(route('leads.import.store'));

        expect(Lead::count())->toBe(1);

        $lead = Lead::sole();
        expect($lead->company_name)->toBe('Empresa Duplicada');
        expect($lead->score)->toBe(70);
        expect($lead->has_website)->toBeTrue();
    });

    test('a row with missing required data is reported as invalid and does not crash the import', function () {
        Lead::factory()->create(['phone' => '5581988887777']);

        $file = fakeLeadsSpreadsheet([
            ['', 'Sem nome', '81999991111', '', '', 'Não', '', 'Não', 'Olinda', 'PE', 'Novo', 'Sem presença', '10', '', '', 'google_maps', '', ''],
            ['Empresa Existente', 'Restaurante', '81988887777', '', '', 'Sim', '', 'Sim', 'Recife', 'PE', 'Novo', 'Com site', '90', '4.9', '50', 'google_maps', 'https://maps.example/existente', '2026-02-01'],
        ]);

        $this->post(route('leads.import.preview'), ['file' => $file]);
        $response = $this->post(route('leads.import.store'));

        $response->assertRedirect(route('leads.index'));
        expect(Lead::count())->toBe(1);
    });

    test('confirming an import creates leads owned by the authenticated user', function () {
        $file = fakeLeadsSpreadsheet([
            ['Clínica Nova', 'Clínica odontológica', '81999990000', 'nova@exemplo.com', 'nova.com.br', 'Sim', '@nova', 'Não', 'Recife', 'PE', 'Novo', 'Com site', '80', '4.5', '20', 'google_maps', 'https://maps.example/nova', '2026-01-10'],
        ]);

        $this->post(route('leads.import.preview'), ['file' => $file]);
        $this->post(route('leads.import.store'));

        $lead = Lead::firstWhere('company_name', 'Clínica Nova');

        expect($lead->user_id)->toBe(auth()->id());
    });

    test('leads imported by one user do not appear for another user', function () {
        $file = fakeLeadsSpreadsheet([
            ['Clínica Nova', 'Clínica odontológica', '81999990000', 'nova@exemplo.com', 'nova.com.br', 'Sim', '@nova', 'Não', 'Recife', 'PE', 'Novo', 'Com site', '80', '4.5', '20', 'google_maps', 'https://maps.example/nova', '2026-01-10'],
        ]);

        $this->post(route('leads.import.preview'), ['file' => $file]);
        $this->post(route('leads.import.store'));

        $this->actingAs(User::factory()->create());

        $leadsIndex = $this->get(route('leads.index'));
        $leadsIndex->assertInertia(fn ($page) => $page->has('leads.data', 0));

        $pipelineIndex = $this->get(route('pipeline.index'));
        $pipelineIndex->assertInertia(fn ($page) => $page->has('leads', 0));
    });

    test('importing rows that round-tripped through a JSON-serialized session does not crash', function () {
        // Reproduces app/Http/Controllers/LeadImportController.php's real session
        // round trip: config('session.serialization') is "json" by default, which
        // collapses each row's LeadImportRowStatus enum to its scalar value.
        $existing = Lead::factory()->create(['phone' => '5581988887777']);

        $file = fakeLeadsSpreadsheet([
            ['Clínica Nova', 'Clínica odontológica', '81999990000', '', '', 'Não', '', 'Não', 'Recife', 'PE', 'Novo', 'Sem presença', '80', '', '', 'google_maps', '', ''],
            ['', 'Sem nome', '81999991111', '', '', 'Não', '', 'Não', 'Olinda', 'PE', 'Novo', 'Sem presença', '10', '', '', 'google_maps', '', ''],
            ['Empresa Existente', 'Restaurante', '81988887777', '', '', 'Sim', '', 'Sim', 'Recife', 'PE', 'Novo', 'Com site', '90', '4.9', '50', 'google_maps', '', ''],
            ['Empresa Duplicada', 'Restaurante', '81977776666', '', '', 'Não', '', 'Não', 'Recife', 'PE', 'Novo', 'Sem presença', '50', '', '', 'google_maps', '', ''],
            ['Empresa Duplicada Ltda', 'Restaurante', '81977776666', '', '', 'Sim', '', 'Sim', 'Recife', 'PE', 'Novo', 'Com site', '70', '4.2', '10', 'google_maps', '', ''],
        ]);

        $rows = app(ParseLeadImportAction::class)->execute($file)['rows'];
        $sessionShapedRows = json_decode(json_encode($rows), true);

        $summary = app(ImportLeadsAction::class)->execute($sessionShapedRows);

        expect($summary)->toBe([
            'total' => 5,
            'created' => 2,
            'duplicated' => 2,
            'invalid' => 1,
        ]);
        expect(Lead::firstWhere('company_name', 'Clínica Nova'))->not->toBeNull();
        expect($existing->fresh()->score)->toBe(90);
        expect(Lead::firstWhere('company_name', 'Empresa Duplicada')?->score)->toBe(70);
    });

    describe('the v2 spreadsheet layout', function () {
        test('previewing a v2-format spreadsheet reports counts of new, duplicated and invalid rows', function () {
            Lead::factory()->create(['phone' => '5581988887777']);

            $file = fakeLeadsSpreadsheet([
                ['Clínica Nova', 'Odontologia', '81999990000', 'Sim', 'nova.com.br', 'Sim', '@nova', 'nova@exemplo.com', 'https://facebook.com/nova', 'Ana', 'Resumo da clínica.', 'Recife', 'PE', '80', '4.5', '20', 'google_maps', 'https://maps.example/nova', '2026-01-10'],
                ['', 'Sem nicho', '81999991111', 'Não', '', 'Não', '', '', '', '', '', 'Olinda', 'PE', '10', '', '', 'google_maps', '', ''],
                ['Empresa Existente', 'Restaurante', '81988887777', 'Sim', '', 'Sim', '', '', '', '', '', 'Recife', 'PE', '90', '4.9', '50', 'google_maps', 'https://maps.example/existente', '2026-02-01'],
            ], format: 'v2');

            $response = $this->post(route('leads.import.preview'), ['file' => $file]);

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page
                ->component('leads/Import')
                ->where('preview.total', 3)
                ->where('preview.new', 1)
                ->where('preview.duplicated', 1)
                ->where('preview.invalid', 1)
            );
        });

        test('confirming a v2-format import creates a lead with the new fields mapped correctly', function () {
            $file = fakeLeadsSpreadsheet([
                ['Clínica Nova', 'Odontologia', '81999990000', 'Sim', 'nova.com.br', 'Sim', '@nova', 'nova@exemplo.com', 'https://facebook.com/nova', 'Ana', 'Resumo da clínica.', 'Recife', 'PE', '80', '4.5', '20', 'google_maps', 'https://maps.example/nova', '2026-01-10'],
            ], format: 'v2');

            $this->post(route('leads.import.preview'), ['file' => $file]);
            $response = $this->post(route('leads.import.store'));

            $response->assertRedirect(route('leads.index'));

            $lead = Lead::firstWhere('company_name', 'Clínica Nova');

            expect($lead)->not->toBeNull();
            expect($lead->category)->toBe('Odontologia');
            expect($lead->email)->toBe('nova@exemplo.com');
            expect($lead->facebook)->toBe('https://facebook.com/nova');
            expect($lead->responsible)->toBe('Ana');
            expect($lead->summary)->toBe('Resumo da clínica.');
            expect($lead->likely_whatsapp)->toBeTrue();
            expect($lead->type)->toBeNull();
        });

        test('has_instagram is derived from the instagram field when the v2 layout has no explicit column', function () {
            $withInstagram = fakeLeadsSpreadsheet([
                ['Com Instagram', 'Restaurante', '81999992222', 'Não', '', 'Não', '@cominsta', '', '', '', '', 'Recife', 'PE', '50', '', '', 'google_maps', '', ''],
            ], format: 'v2');
            $this->post(route('leads.import.preview'), ['file' => $withInstagram]);
            $this->post(route('leads.import.store'));

            expect(Lead::firstWhere('company_name', 'Com Instagram')?->has_instagram)->toBeTrue();

            $withoutInstagram = fakeLeadsSpreadsheet([
                ['Sem Instagram', 'Restaurante', '81999993333', 'Não', '', 'Não', '', '', '', '', '', 'Recife', 'PE', '50', '', '', 'google_maps', '', ''],
            ], format: 'v2');
            $this->post(route('leads.import.preview'), ['file' => $withoutInstagram]);
            $this->post(route('leads.import.store'));

            expect(Lead::firstWhere('company_name', 'Sem Instagram')?->has_instagram)->toBeFalse();
        });

        test('a spreadsheet matching neither the legacy nor the v2 layout reports a clear error', function () {
            $path = tempnam(sys_get_temp_dir(), 'bad_import_').'.csv';
            file_put_contents($path, "Nome,Telefone\nEmpresa,81999990000");
            $file = new UploadedFile($path, 'bad.csv', 'text/csv', null, true);

            $response = $this->post(route('leads.import.preview'), ['file' => $file]);

            $response->assertSessionHasErrors('file');
        });
    });
});
