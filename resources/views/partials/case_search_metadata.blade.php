data-record-search-case="{{ $case->case_code }}"
data-record-search-client="{{ $case->client?->full_name ?? 'Not provided' }}"
data-record-search-deceased="{{ $case->deceased?->full_name ?? 'Not provided' }}"
