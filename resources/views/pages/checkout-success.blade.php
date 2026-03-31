@extends('layouts.app')

@section('title', 'Bestellung · '.$siteName)

@section('content')
    <livewire:shop.order-success :order-number="$orderNumber" />
@endsection
