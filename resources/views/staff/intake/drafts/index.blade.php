@extends('layouts.panel')

@section('page_title', 'Intake Drafts')
@section('page_desc', 'Resume incomplete intake records before creating official case records.')

@section('content')
<div class="records-page">
    @if(session('success'))
        <div class="flash-success mb-3">{{ session('success') }}</div>
    @endif

    <div class="ops-page-header mx-[var(--panel-content-inline,20px)] mt-3">
        <div>
            <div class="ops-page-kicker">Case Intake</div>
            <h1 class="ops-page-title">Saved Intake Drafts</h1>
            <p class="ops-page-desc">Drafts stay separate from clients, deceased records, cases, reports, and payments until final submission.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('intake.main.create') }}" class="ops-btn-primary inline-flex items-center justify-center gap-2">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                New Intake
            </a>
        </div>
    </div>

    <div class="table-system-card mt-3 mx-[var(--panel-content-inline,20px)]">
        <div class="table-system-wrap">
            <table class="table-system-table w-full">
                <thead>
                    <tr>
                        <th>Draft No.</th>
                        <th>Branch</th>
                        <th>Client</th>
                        <th>Deceased</th>
                        <th>Step</th>
                        <th>Last Saved</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drafts as $draft)
                        @php
                            $fields = $draft->payload['fields'] ?? [];
                            $client = trim(implode(' ', array_filter([
                                $fields['client_first_name'] ?? null,
                                $fields['client_middle_name'] ?? null,
                                $fields['client_last_name'] ?? null,
                                $fields['client_suffix'] ?? null,
                            ])));
                            $deceased = trim(implode(' ', array_filter([
                                $fields['deceased_first_name'] ?? null,
                                $fields['deceased_middle_name'] ?? null,
                                $fields['deceased_last_name'] ?? null,
                                $fields['deceased_suffix'] ?? null,
                            ])));
                        @endphp
                        <tr>
                            <td class="font-semibold text-[var(--color-text-primary)]">{{ $draft->draft_number }}</td>
                            <td>{{ $draft->branch?->branch_code ?? '-' }}</td>
                            <td>{{ $client !== '' ? $client : '-' }}</td>
                            <td>{{ $deceased !== '' ? $deceased : '-' }}</td>
                            <td>Step {{ $draft->current_step }}</td>
                            <td>{{ optional($draft->last_saved_at ?? $draft->updated_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('intake.drafts.edit', ['draft' => $draft, 'return_to' => request()->fullUrl()]) }}" class="ops-btn-secondary inline-flex items-center justify-center gap-2">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                        Resume
                                    </a>
                                    <form method="POST" action="{{ route('intake.drafts.destroy', $draft) }}" onsubmit="return confirm('Discard this intake draft?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ops-btn-danger inline-flex items-center justify-center gap-2">
                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                            Discard
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="table-system-empty">
                                    <div class="font-semibold text-[var(--color-text-primary)]">No saved drafts</div>
                                    <div class="text-sm text-[var(--color-text-secondary)]">Start a new intake record and use Save Draft when details are incomplete.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3">
            {{ $drafts->links() }}
        </div>
    </div>
</div>
@endsection
