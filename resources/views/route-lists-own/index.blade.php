@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>{{ __('My Route Lists') }}</h4>
            </div>
        </div>
    </div>
    <table class="table table-light table-bordered table-striped">
        <thead class="thead-light">
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('State') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($routeLists as $routeList)
            <tr>
                <td>
                    <a href="{{ route('route-own-lists.view', $routeList) }}" class="d-block text-dark text-center">{{ $routeList->id }}</a>
                </td>
                <td>
                    <a href="{{ route('route-own-lists.view', $routeList) }}" class="d-block text-dark text-center">{{ $routeList->date_list }}</a>
                </td>
                <td>
                    <a href="{{ route('route-own-lists.view', $routeList) }}" class="d-block text-dark text-center">{{ $routeList->currentState()->name }}</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @php
        {{
        /**
          * @var $routeLists \Illuminate\Pagination\Paginator
          **/
        }}
    @endphp
    {!! $routeLists->render() !!}
@endsection