@extends('layouts.app')

@section('titleSection')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h4>@yield('titleSection.title', __('Manage'))</h4>
            </div>
        </div>
    </div>
@endsection

@section('alertSection')
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($message = Session::get('warning'))
        <div class="alert alert-warning">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>{{ __('Whoops!') }}</strong> {{ __('There were some problems with your input.') }}<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection

@section('settingsSection')
    <div class="col-12 p-0" id="@yield('settingsSection.id', 'manage')">
        @yield('settingsSection.form', '')
    </div>
@endsection

@section('dataSection')
    @yield('dataSection.content', '')
@endsection

@section('content')
    @yield('titleSection', '')
    @yield('alertSection', '')
    @yield('settingsSection', '')
    @yield('dataSection', '')
@endsection
