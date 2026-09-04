@props(['tone' => 'success', 'title', 'actionLabel' => null, 'actionHref' => null])
<div {{ $attributes->merge(['class' => "banner banner-{$tone}"]) }}>
  <div class="banner-icon">
    @if ($tone === 'success')
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.3 2.3L15.5 9.7"/></svg>
    @elseif ($tone === 'danger')
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
    @else
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
    @endif
  </div>
  <div class="banner-body">
    <p class="banner-title">{{ $title }}</p>
    @if ($slot->isNotEmpty())
      <p class="banner-desc">{{ $slot }}</p>
    @endif
  </div>
  @if ($actionLabel && $actionHref)
    <a href="{{ $actionHref }}" class="banner-action">{{ $actionLabel }}</a>
  @endif
</div>
