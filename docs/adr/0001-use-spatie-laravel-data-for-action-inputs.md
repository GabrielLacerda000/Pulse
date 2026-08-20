# 0001. Use spatie/laravel-data DTOs for Action inputs

## Status

Accepted

## Context

`CreateLeadAction::execute()` originally took an untyped `array $data` built
from `StoreLeadRequest::validated()`. This is the shape BACK_GUIDILINES.md
recommends for simple cases, but it has a real cost as soon as an Action's
input has known, fixed fields:

- Nothing documents or enforces which keys `$data` must contain — that
  contract only lives in the Form Request's `rules()`, which the Action
  doesn't see.
- IDEs and Larastan can't tell you `$data['compnay_name']` is a typo.
- Every caller of the Action has to already know the array shape by
  convention, not by type.

`spatie/laravel-data` was added as a project dependency to address this:
typed, readonly Data objects that can be constructed `::from()` an array
(such as a Form Request's `validated()` output) and converted back with
`->toArray()`.

## Decision

Actions that take a fixed set of known fields receive a `spatie/laravel-data`
`Data` object instead of a raw array.

- Data objects live in `app/Data/`, one per Action input (or shared where
  genuinely the same shape), e.g. `App\Data\LeadData`.
- Controllers stay responsible for validation via Form Requests, as already
  established. The controller builds the Data object from the validated
  array — `LeadData::from($request->validated())` — and passes it to the
  Action. The Form Request and the Data object's constructor properties are
  expected to describe the same fields; the Data object does not replace
  validation, it gives the already-validated data a typed shape for the
  next layer.
- Actions type-hint the Data object directly (`execute(LeadData $data)`)
  instead of `execute(array $data)`, and use `$data->toArray()` where an
  array is still needed (e.g. for `Model::create()`).

This does **not** apply retroactively to every array in the codebase — only
to Action inputs, and only where the input has a known, fixed shape worth
naming. `BACK_GUIDILINES.md` §5's guidance on not creating an Action for a
plain `$lead->update($data)` still holds independently of this decision.

## Consequences

- New Action inputs with a fixed shape should get a `Data` object in
  `app/Data/` rather than a bare `array $data` parameter.
- Slightly more ceremony per Action (one small class per input shape), in
  exchange for typed, IDE-navigable, statically-analyzable Action
  signatures.
- `LeadData` and `StoreLeadRequest`/`UpdateLeadRequest` (via
  `LeadValidationRules`) currently describe the same nine manual lead
  fields in two places. This is accepted for now — the Form Request layer
  owns validation, the Data object layer owns the typed transfer shape —
  but if this pair drifts out of sync it's a signal to revisit.

## Example

```php
// app/Data/LeadData.php
final class LeadData extends Data
{
    public function __construct(
        public readonly string $company_name,
        public readonly ?string $category = null,
        // ...
    ) {}
}

// app/Http/Controllers/LeadController.php
public function store(StoreLeadRequest $request, CreateLeadAction $action): RedirectResponse
{
    $lead = $action->execute(LeadData::from($request->validated()));

    return to_route('leads.show', $lead);
}

// app/Actions/CreateLeadAction.php
final class CreateLeadAction
{
    public function execute(LeadData $data): Lead
    {
        return Lead::create([
            ...$data->toArray(),
            'status' => LeadStatus::NEW,
        ]);
    }
}
```
