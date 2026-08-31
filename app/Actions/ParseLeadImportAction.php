<?php

namespace App\Actions;

use App\Enums\LeadImportRowStatus;
use App\Imports\LeadsImport;
use App\Models\Lead;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

final class ParseLeadImportAction
{
    /**
     * The column headers expected from the external capture system's export
     * (PRD §9), in order.
     *
     * @var list<string>
     */
    private const array EXPECTED_COLUMNS = [
        'Nome', 'Categoria', 'Telefone', 'E-mail', 'Site', 'Tem site',
        'Instagram', 'Tem Instagram', 'Cidade', 'Estado', 'Status', 'Tipo',
        'Score', 'Avaliação', 'Nº avaliações', 'Fonte', 'Google Maps', 'Criado em',
    ];

    public function __construct(
        private readonly NormalizePhoneAction $normalizePhone,
    ) {}

    /**
     * Parse the uploaded spreadsheet and classify every row as new,
     * duplicate (matching an existing lead by normalized phone), or invalid.
     *
     * @return array{total: int, new: int, duplicated: int, invalid: int, rows: list<array<string, mixed>>}
     *
     * @throws ValidationException when the spreadsheet columns don't match the expected structure
     */
    public function execute(UploadedFile $file): array
    {
        $sheet = Excel::toArray(new LeadsImport, $file)[0] ?? [];

        $header = array_shift($sheet) ?? [];

        $columns = $this->assertValidStructure(array_values($header));

        $existingLeadsByPhone = $this->existingLeadsByNormalizedPhone();

        // Maps a normalized phone to the index of the earlier "new" row that
        // introduced it, so two rows sharing a phone within the same
        // spreadsheet are matched to each other (not just against leads
        // already in the database).
        $pendingRowsByPhone = [];

        $rows = [];

        foreach ($sheet as $rawRow) {
            if ($this->isBlankRow(array_values($rawRow))) {
                continue;
            }

            $row = $this->parseRow(
                array_combine($columns, $rawRow),
                $existingLeadsByPhone,
                $pendingRowsByPhone,
            );

            if ($row['status'] === LeadImportRowStatus::NEW && $row['normalized_phone'] !== null) {
                $pendingRowsByPhone[$row['normalized_phone']] = count($rows);
            }

            $rows[] = $row;
        }

        return [
            'total' => count($rows),
            'new' => $this->countByStatus($rows, LeadImportRowStatus::NEW),
            'duplicated' => $this->countByStatus($rows, LeadImportRowStatus::DUPLICATE),
            'invalid' => $this->countByStatus($rows, LeadImportRowStatus::INVALID),
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<mixed>  $header
     * @return list<string> the header cells normalized to slugs, used as row keys
     */
    private function assertValidStructure(array $header): array
    {
        $columns = array_map(fn (mixed $label): string => Str::slug((string) $label, '_'), $header);

        $expected = array_map(fn (string $label): string => Str::slug($label, '_'), self::EXPECTED_COLUMNS);

        if ($columns !== $expected) {
            throw ValidationException::withMessages([
                'file' => 'A planilha não segue o formato esperado. Verifique se as colunas correspondem ao layout do sistema de captação.',
            ]);
        }

        return $columns;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn (mixed $value): bool => blank($value));
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<string, Lead>  $existingLeadsByPhone
     * @param  array<string, int>  $pendingRowsByPhone  normalized phone => index of the earlier new row in this same import
     * @return array<string, mixed>
     */
    private function parseRow(array $row, Collection $existingLeadsByPhone, array $pendingRowsByPhone): array
    {
        $companyName = trim((string) ($row['nome'] ?? ''));

        if ($companyName === '') {
            return [
                'status' => LeadImportRowStatus::INVALID,
                'error' => 'Nome da empresa é obrigatório.',
            ];
        }

        $phone = $this->nullableString($row['telefone'] ?? null);
        $normalizedPhone = $phone ? $this->normalizePhone->execute($phone) : null;
        $existingLead = $normalizedPhone ? $existingLeadsByPhone->get($normalizedPhone) : null;
        $pendingRowIndex = (! $existingLead && $normalizedPhone) ? ($pendingRowsByPhone[$normalizedPhone] ?? null) : null;

        $attributes = [
            'company_name' => $companyName,
            'category' => $this->nullableString($row['categoria'] ?? null),
            'phone' => $phone,
            'email' => $this->nullableString($row['e_mail'] ?? null),
            'website' => $this->nullableString($row['site'] ?? null),
            'has_website' => $this->toBoolean($row['tem_site'] ?? null),
            'instagram' => $this->nullableString($row['instagram'] ?? null),
            'has_instagram' => $this->toBoolean($row['tem_instagram'] ?? null),
            'city' => $this->nullableString($row['cidade'] ?? null),
            'state' => $this->nullableString($row['estado'] ?? null),
            'type' => $this->nullableString($row['tipo'] ?? null),
            'score' => $this->toInteger($row['score'] ?? null),
            'rating' => $this->toDecimal($row['avaliacao'] ?? null),
            'reviews_count' => $this->toInteger($row['no_avaliacoes'] ?? null),
            'source' => $this->nullableString($row['fonte'] ?? null),
            'google_maps_url' => $this->nullableString($row['google_maps'] ?? null),
            'source_created_at' => $this->toDate($row['criado_em'] ?? null),
        ];

        if ($existingLead) {
            return [
                'status' => LeadImportRowStatus::DUPLICATE,
                'attributes' => $attributes,
                'lead_id' => $existingLead->id,
            ];
        }

        if ($pendingRowIndex !== null) {
            return [
                'status' => LeadImportRowStatus::DUPLICATE,
                'attributes' => $attributes,
                'duplicate_of_row' => $pendingRowIndex,
            ];
        }

        return [
            'status' => LeadImportRowStatus::NEW,
            'attributes' => $attributes,
            'normalized_phone' => $normalizedPhone,
        ];
    }

    /**
     * @return Collection<string, Lead>
     */
    private function existingLeadsByNormalizedPhone(): Collection
    {
        return Lead::query()
            ->whereNotNull('phone')
            ->get(['id', 'phone'])
            ->mapWithKeys(fn (Lead $lead): array => [
                (string) $this->normalizePhone->execute($lead->phone) => $lead,
            ])
            ->filter(fn (Lead $lead, string $phone): bool => $phone !== '');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function countByStatus(array $rows, LeadImportRowStatus $status): int
    {
        return count(array_filter($rows, fn (array $row): bool => $row['status'] === $status));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function toBoolean(mixed $value): bool
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['sim', 'yes', 'true', '1'], true);
    }

    private function toInteger(mixed $value): ?int
    {
        $value = trim((string) $value);

        return is_numeric($value) ? (int) $value : null;
    }

    private function toDecimal(mixed $value): ?float
    {
        $value = trim(str_replace(',', '.', (string) $value));

        return is_numeric($value) ? (float) $value : null;
    }

    private function toDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }
}
