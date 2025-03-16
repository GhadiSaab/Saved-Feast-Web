@extends('layouts.app')

@section('content')
<section class="search-results">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1>Search Results</h1>
                @if($meals->count() > 0)
                    <div class="row">
                        @foreach($meals as $meal)
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="{{ $meal->image }}" class="card-img-top" alt="{{ $meal->name }}">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ $meal->name }}</h5>
                                        <p class="card-text">{{ $meal->description }}</p>
                                        <a href="{{ route('meals.show', $meal->id) }}" class="btn btn-primary">View Meal</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p>No meals found.</p>
                @endif

                <!-- Pagination -->
                <nav class="mt-5">
                    {{ $meals->links('components.pagination') }}
                </nav>
            </div>
        </div>
    </div>
</section>
@endsection