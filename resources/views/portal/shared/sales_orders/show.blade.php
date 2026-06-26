@extends('portal.layout')
@section('title', '판매주문 상세')

@section('content')
<a href="{{ route($routePrefix . '.sales_orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 판매주문 목록</a>

@include('portal.partials.sales-order-detail', ['salesOrder' => $salesOrder, 'routePrefix' => $routePrefix])
@endsection
