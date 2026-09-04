@props(['class' => 'pill-normal'])
<span {{ $attributes->merge(['class' => "pill {$class}"]) }}>{{ $slot }}</span>
