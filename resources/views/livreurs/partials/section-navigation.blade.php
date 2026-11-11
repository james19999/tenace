@php
    $courierSections = [
        ['route' => 'authlivrable', 'url' => route('authlivrable', auth()->id()), 'label' => 'Mes livraisons', 'icon' => 'assignment_ind'],
        ['route' => 'livrable', 'url' => route('livrable'), 'label' => 'Livraisons', 'icon' => 'delivery_dining'],
        ['route' => 'show-deliveries', 'url' => route('show-deliveries'), 'label' => 'Classement', 'icon' => 'leaderboard'],
    ];
@endphp

<nav class="courier-section-nav" aria-label="Navigation des livraisons">
    @foreach ($courierSections as $section)
        @php($isCurrent = request()->routeIs($section['route']))
        <a class="courier-section-link{{ $isCurrent ? ' is-current' : '' }}" href="{{ $section['url'] }}" @if($isCurrent) aria-current="page" @endif>
            <span class="courier-section-icon material-icons" aria-hidden="true">{{ $section['icon'] }}</span>
            <span class="courier-section-label">{{ $section['label'] }}</span>
            @if($isCurrent)<span class="courier-section-current">Page actuelle</span>@endif
        </a>
    @endforeach
</nav>
