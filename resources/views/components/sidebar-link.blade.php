@props(['href', 'active' => false, 'icon' => 'circle'])
<a href="{{ $href }}" {{ $attributes->class(['nav-link', 'active' => $active]) }} @if ($active) aria-current="page" @endif>
    <i class="bi bi-{{ $icon }}"></i>
    <span>{{ $slot }}</span>
</a>
