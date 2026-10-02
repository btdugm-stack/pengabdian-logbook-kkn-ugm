@extends('errors.layout')

@php
  // Pesan abort()/policy yang kita tulis sendiri sudah berbahasa Indonesia;
  // pesan bawaan paket (mis. spatie "User does not have the right roles.") tidak.
  $detail = $exception->getMessage();
  $isOwnMessage = $detail !== '' && ! $exception instanceof \Spatie\Permission\Exceptions\UnauthorizedException && $detail !== 'This action is unauthorized.';
@endphp

@section('code', '403')
@section('title', 'Akses ditolak')
@section('message', $isOwnMessage ? $detail : 'Halaman ini tidak tersedia untuk peran akun Anda. Bila menurut Anda ini keliru, hubungi admin LPPM.')
