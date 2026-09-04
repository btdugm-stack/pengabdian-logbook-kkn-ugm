@props(['label', 'value', 'note' => '', 'tone' => null])
<div class="card kpi">
  <div class="label">{{ $label }}</div>
  <div class="value {{ $tone ? "tone-{$tone}" : '' }}">{{ $value }}</div>
  @if ($note)
    <div class="note">{{ $note }}</div>
  @endif
</div>
