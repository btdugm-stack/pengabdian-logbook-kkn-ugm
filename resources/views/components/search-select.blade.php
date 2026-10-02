@props([
  'id',
  // Di dalam komponen Livewire: properti nilai lama dari daftar dan nilai yang
  // diketik. Di form biasa keduanya dikosongkan dan nilai dikirim lewat `name`.
  'nameProp' => null,
  'newProp' => null,
  'name' => null,
  'required' => false,
  'placeholder' => null,
  'options' => [],
  'value' => '',
  'noun' => 'data',
  'invalid' => false,
])
{{-- Kolom "cari dulu, baru pilih atau tambah". Menggantikan
     pasangan <select> + input "baru": dengan dua kolom terpisah orang mudah
     mengetik ulang nama yang sebenarnya sudah ada di dropdown (duplikat).
     Logikanya ada di Alpine.data('searchSelect') pada resources/js/app.js. --}}
<div class="combo {{ $invalid ? 'is-invalid' : '' }}">
  <div wire:ignore
    x-data="searchSelect({ options: @js($options), value: @js($value), nameProp: @js($nameProp), newProp: @js($newProp) })"
    x-on:keydown.escape="open = false">
    {{-- value diisi juga dari server supaya form biasa tetap berfungsi tanpa JavaScript. --}}
    <input id="{{ $id }}" type="text" role="combobox" autocomplete="off" maxlength="150"
      @if ($name) name="{{ $name }}" value="{{ $value }}" @endif @required($required)
      aria-autocomplete="list" aria-controls="{{ $id }}-list"
      x-bind:aria-expanded="open && rows > 0"
      x-bind:aria-activedescendant="active >= 0 ? '{{ $id }}-opt-' + active : null"
      placeholder="{{ $placeholder ?? 'Cari atau ketik '.$noun.' baru...' }}"
      x-model="query"
      x-on:input="onInput()"
      x-on:focus="open = true"
      x-on:blur="commit()"
      x-on:keydown.arrow-down.prevent="move(1)"
      x-on:keydown.arrow-up.prevent="move(-1)"
      x-on:keydown.enter.prevent="enter()">

    <ul id="{{ $id }}-list" class="combo-list" role="listbox" x-show="open && rows > 0" x-cloak>
      <template x-for="(option, index) in matches" :key="option">
        <li role="option" x-bind:id="'{{ $id }}-opt-' + index"
          x-bind:aria-selected="index === active" x-bind:class="{ 'is-active': index === active }"
          x-on:mousedown.prevent x-on:click="choose(option)" x-text="option"></li>
      </template>
      <li role="option" class="combo-add" x-show="canAdd" x-bind:id="'{{ $id }}-opt-' + matches.length"
        x-bind:aria-selected="active === matches.length" x-bind:class="{ 'is-active': active === matches.length }"
        x-on:mousedown.prevent x-on:click="commit()">Tambah baru: "<span x-text="clean(query)"></span>"</li>
    </ul>

    <div class="hint" x-show="clean(query) !== ''" x-cloak x-text="status"></div>
  </div>
</div>
