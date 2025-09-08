@extends('layouts.app')

@section('title', 'Edit Pengeluaran')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1 class="h4">Edit Pengeluaran</h1>
                </div>

                <div class="card-body">
                    <form action="{{ route('expenses.update', $expense) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('expenses._form')
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
