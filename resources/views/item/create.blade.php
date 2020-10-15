<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Feed Assignment</title>

    <!-- Bootstrap core CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

</head>

<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('items.index') }}">Feed Assignment</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="{{ route('items.index') }}">Home
                        <span class="sr-only">(current)</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Contact</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Content -->
<div class="container">

    <div class="row">

        <div class="col-lg-3">
            <h1 class="my-4">Feed Assignment</h1>
            <div class="list-group">
                <a href="{{ route('items.index') }}" class="list-group-item{{ request()->route()->getName() === 'items.index' ? ' active' : '' }}">Items</a>
                <a href="{{ route('items.create') }}" class="list-group-item{{ request()->route()->getName() === 'items.create' ? ' active' : '' }}">Add new (like Dashboard or Form)</a>
                <a href="#" class="list-group-item">Category 3</a>
            </div>
        </div>
        <!-- /.col-lg-3 -->

        <div class="col-lg-9">

            <div class="card mt-4">
                <div class="card-body">
                    @if($items->isEmpty())
                        <h3 class="card-title">No articles at the moment</h3>
                        <small class="card-title">Click button bellow or try refreshing a page in a minute</small>
                    @else
                        <h3 class="card-title">Latest articles</h3>
                    @endif
                </div>
            </div>
            <!-- /.card -->

            <div class="card card-outline-secondary my-4">
                <div class="card-header">
                    {!! $items->isNotEmpty() ? 'Articles' : '<div class="spinner-border text-info get-items-spinner" role="status"></div><a href="/items/fetch" class="btn btn-outline-primary float-right get-items">Get articles</a>' !!}
                </div>
                @if($items->isNotEmpty())
                    <div class="card-body">
                        @foreach($items as $item)
                            <h3><a href="{{ $item->link }}" target="_blank">{{ $item->title }}</a></h3>
                            <p>{!! $item->description !!}</p>
                            <small class="text-muted">Posted by Anonymous on {{ $item->last_modified }}</small>
                            @if(!$loop->last)
                                <hr>
                            @endif
                        @endforeach
                    </div>
                    {{ $items->links() }}
                @endif
            </div>
            <!-- /.card -->

        </div>
        <!-- /.col-lg-9 -->

    </div>

</div>
<!-- /.container -->

<!-- Footer -->
<footer class="py-5 bg-dark">
    <div class="container">
        <p class="m-0 text-center text-white">Copyright &copy; Feed Assignment 2020</p>
    </div>
    <!-- /.container -->
</footer>
<script src="{{ asset('js/app.js') }}"></script>
<script>
    $(document).ready(function () {
        const getItemsSpinner = $('.get-items-spinner')
        getItemsSpinner.hide()
        $('.get-items').on('click', function (e) {
            e.preventDefault()
            getItemsSpinner.show()
            axios.post("{{ route('items.fetch') }}").then(function (res) {
                //
                getItemsSpinner.hide()
            }).catch(function (err) {
                console.log(err)
            }).finally(function () {
                location.href = "{{ route('items.index') }}"
            })
        })
    })
</script>
</body>

</html>
