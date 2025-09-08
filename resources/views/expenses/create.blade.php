@extends('layouts.app')

@section('title', 'Catat Pengeluaran')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1 class="h4">Catat Pengeluaran</h1>
                </div>

                <div class="card-body">
                    <form action="{{ route('expenses.store') }}" method="POST">
                        @csrf
                        @include('expenses._form')
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
