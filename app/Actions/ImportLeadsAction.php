<?php

namespace App\Actions;

use App\Enums\LeadImportRowStatus;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class ImportLeadsAction
{
    /**
     * Source-derived fields updated on a lead that already exists (matched
     * by phone). The existing commercial status and notes are never
     * overwritten by an import (PRD §18).
     *
     * @var list<string>
     */
    private const array UPDATABLE_FIELDS = [
        'type', 'score', 'rating', 'reviews_count', 'source',
        'google_maps_url', 'has_website', 'has_instagram', 'source_created_at',
    ];

    /**
     * Persist a set of rows previously classified by ParseLeadImportAction.
     *
     * A row may be a duplicate of a lead already in the database
     * (`lead_id`) or of an earlier "new" row within this same spreadsheet
     * (`duplicate_of_row`, resolved once that row has been created).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{total: int, created: int, duplicated: int, invalid: int}
     */
    public function execute(array $rows): array
    {
        $created = 0;
        $duplicated = 0;
        $invalid = 0;

        DB::transaction(function () use ($rows, &$created, &$duplicated, &$invalid): void {
            $createdLeadIdsByRow = [];

            foreach ($rows as $index => $row) {
                // $rows normally arrives fresh from LeadImportController's session
                // (config('session.serialization') is "json" by default), which
                // collapses the backed enum to its scalar value on round-trip.
                $status = $row['status'] instanceof LeadImportRowStatus
                    ? $row['status']
                    : LeadImportRowStatus::from($row['status']);

                if ($status === LeadImportRowStatus::INVALID) {
                    $invalid++;

                    continue;
                }

                if ($status === LeadImportRowStatus::NEW) {
                    $createdLeadIdsByRow[$index] = $this->createLead($row['attributes']);
                    $created++;

                    continue;
                }

                $leadId = $row['lead_id'] ?? $createdLeadIdsByRow[$row['duplicate_of_row']];
                $this->updateLead($leadId, $row['attributes']);
                $duplicated++;
            }
        });

        return [
            'total' => count($rows),
            'created' => $created,
            'duplicated' => $duplicated,
            'invalid' => $invalid,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createLead(array $attributes): int
    {
        return Lead::create([
            ...$attributes,
            'status' => LeadStatus::NEW,
        ])->id;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function updateLead(int $leadId, array $attributes): void
    {
        Lead::whereKey($leadId)->update(Arr::only($attributes, self::UPDATABLE_FIELDS));
    }
}
