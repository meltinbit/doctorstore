@component('mail::message')
# Scan Complete — {{ $store->shop_name ?? $store->shop_domain }}

Your latest metafield scan for **{{ $store->shop_name ?? $store->shop_domain }}** has finished.

@component('mail::table')
| Metric | Value |
|:-------|------:|
| Quality Score | {{ $scan->quality_score !== null ? $scan->quality_score.'/100' : '—' }} |
| Issues Found | {{ $scan->total_issues }} |
| Metafields Scanned | {{ $scan->total_metafields }} |
| Definitions | {{ $scan->total_definitions }} |
@endcomponent

@component('mail::button', ['url' => route('stores.scans.show', [$store->id, $scan->id])])
View Full Report
@endcomponent

You are receiving this email because email summaries are enabled for **{{ $store->shop_domain }}**.

Thanks,
{{ config('app.name') }}
@endcomponent
