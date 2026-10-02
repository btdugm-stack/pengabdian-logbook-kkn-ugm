@extends('layouts.app')

@section('title', match (true) { $logbook->isDraft() => 'Lanjutkan Draft', $logbook->needsRevision() => 'Perbaiki Logbook', default => 'Ubah Logbook' })
@section('description', match (true) {
    $logbook->isDraft() => 'Lengkapi draft logbook lalu kirim, atau simpan lagi sebagai draft.',
    $logbook->needsRevision() => 'Perbaiki sesuai catatan pembimbing, lalu kirim ulang untuk direviu.',
    default => 'Perbaiki isi logbook yang sudah dikirim. Perubahan langsung berlaku.',
})

@section('content')
@livewire('logbook-form', ['logbook' => $logbook])
@endsection
