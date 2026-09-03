@extends('layouts.app')

@section('title', "Modifier l'UE — " . $ue->name)

@section('content')
    @include('esbtp.lmd.ue.partials._form')
@endsection
