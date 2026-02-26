@component('mail::message')
# Metafield Alert

Hi {{ $user->name }},

Here is the metafield health summary for your connected stores.

@component('mail::table')
| Store | Quality Score | Issues |
|:------|:-------------:|-------:|
@foreach($storeReports as $report)
| {{ $report['store'] }} | {{ $report['quality_score'] !== null ? $report['quality_score'].'/100' : '—' }} | {{ $report['issues'] }} |
@endforeach
@endcomponent

@foreach($storeReports as $report)
@component('mail::button', ['url' => route('stores.scans.show', [$report['store_id'], $report['scan_id']])])
View {{ $report['store'] }} report
@endcomponent
@endforeach

You are receiving this email because you have **{{ $frequency }}** alerts enabled.
You can change this in your [account settings]({{ route('settings.profile') }}).

Thanks,
{{ config('app.name') }}
@endcomponent
