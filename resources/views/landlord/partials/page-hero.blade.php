{{--
    Navy banner that opens each inner public page.
    $eyebrow, $title (plain text), optional $lead and $center (bool).
    Optional $extraView / $extraData: a view rendered under the lead, for a
    control that belongs in the banner (a search box, the billing toggle).
--}}
<section @class(['page-hero', 'page-hero--center' => $center ?? false])>
    <div class="container">
        <div class="page-hero__inner">
            @if (! empty($eyebrow))
                <p class="eyebrow">{{ $eyebrow }}</p>
            @endif
            <h1>{{ $title }}</h1>
            @if (! empty($lead))
                <p class="page-hero__lead">{{ $lead }}</p>
            @endif
            @if (! empty($extraView))
                <div class="page-hero__extra">@include($extraView, $extraData ?? [])</div>
            @endif
        </div>
    </div>
</section>
