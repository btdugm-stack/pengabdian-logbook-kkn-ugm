@extends('layouts.app')

@section('title', $logbook ? 'Koreksi Logbook' : 'Input Logbook Atas Nama')
@section('description', $owner->name.' · '.$owner->roleLabel().' · '.($owner->region?->fullPath() ?? 'wilayah belum diisi'))

@section('content')
<a href="{{ route('overview.student', $owner) }}" class="back-link"><x-icon name="arrow-left" :size="16" /> Kembali ke {{ $owner->name }}</a>

@livewire('logbook-form', ['logbook' => $logbook, 'owner' => $owner])
@endsection
