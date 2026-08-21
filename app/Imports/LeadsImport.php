<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;

/**
 * Marker import used with Excel::toArray() to read the raw spreadsheet rows,
 * including the header row, so the expected column structure (PRD §9) can
 * be validated before any row is normalized or persisted.
 */
final class LeadsImport implements Import {}
