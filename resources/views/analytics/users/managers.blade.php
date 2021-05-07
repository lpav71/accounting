@extends('layouts.app')

@section('content')
<div>
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Users analytics') }}</h2>
            </div>
        </div>
    </div>
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
    <div class="col-12 p-0" id="analytics">
            {!! Form::open(['route' => 'analytics.users','method'=>'GET', 'class' => 'form-group']) !!}
            <div class="d-flex flex-row">
                <div>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            {!! Form::label('from', __('From'), ['class' => 'form-control form-control-sm m-0']) !!}
                        </div>
                        <div class="input-group-append">
                            {!!  Form::input('dateTime-local', 'from', $dateFrom, array('class' => 'form-control form-control-sm')) !!}
                        </div>
                    </div>
                </div>
                <div>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            {!! Form::label('to', __('To'), ['class' => 'form-control form-control-sm m-0']) !!}
                        </div>
                        <div class="input-group-append">
                                {!!  Form::input('dateTime-local', 'to', $dateTo, array('class' => 'form-control form-control-sm')) !!}
                        </div>
                    </div>
                </div>
                <div>
                    <div class="input-group input-group-sm ">
                        <div class="input-group-prepend" style="width: 200px">
                            {{ Form::select('role', \App\Role::orderBy('name')->pluck('name'),$role, ['class' => 'form-control form-control-sm','id'=>'role-selector']) }}
                        </div>
                    </div>
                </div>
                <div>
                    <div class="input-group input-group-sm ">
                    <div class="input-group-prepend" style="width: 200px">
                            {{ Form::select('user', \App\User::orderBy('name')->pluck('name'),$user, ['class' => 'form-control form-control-sm','id'=>'user-selector','data-users'=>$userSelector]) }}
                        </div>
                    </div>
                </div> 
                {!! Form::submit(__('Save default'), ['class' => 'btn btn-sm pull-right col-sm-auto col-xs-12 mt-2 mt-sm-0 ml-0 ml-sm-5', 'name' => 'save']) !!}
            </div>
            @foreach($selectors as $key=>$value)
            <div class="form-group mt-4">
                    {!! Form::label('successful['.$key.'][]', $value['label'].':') !!}
                    {!! Form::select('successful['.$key.'][]', $value['list'], $value['successful'], ['multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
            </div>
            @endforeach
            {!! Form::hidden('report', 1) !!}
            {!! Form::submit(__('Show report'), ['class' => 'btn btn-sm', 'name' => 'submit']) !!}
            {!! Form::close() !!}
    </div>
</div>
    <div>
            @foreach ($data as $key => $item)
            <div class="card border-secondary mb-1">
                    <div class="card-header p-1" id="heading{{$key}}">
                        <h5 class="mb-0">
                            <button class="btn btn-secondary btn-smbtn-secondary " data-toggle="collapse" data-target="#collapse{{$key}}" aria-expanded="false" aria-controls="collapse{{$key}}">
                            {{$item['name']}}
                        </button>
                        @foreach ($item['information'] as $fieldName => $information)
                            <span>{{$fieldName}}: {{$information}} </span>
                        @endforeach
                        </h5>
                    </div>
            
                    <div id="collapse{{$key}}" class="collapse" aria-labelledby="heading{{$key}}">
                        <div class="card-body p-0">
                            <table class="table table-light table-bordered table-responsive-sm">
                                <thead class="thead-light">
                                    <tr>
                                        @foreach ($item['tablecolumns'] as $tablecolumns)
                                        <th>{{ $tablecolumns }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($item['tablerows'] as $row)
                                            <tr @if(isset($row['color']) && $row['color']!='') style="background:{{$row['color']}}" @endif>
                                                    @foreach ($row['rowcolumns'] as $rowcolumnitem)
                                                        @if(isset($rowcolumnitem['link']) && $rowcolumnitem['link']!=null)
                                                        <td><a class="text-dark" href={{$rowcolumnitem['link']}}>{{$rowcolumnitem['text']}}</a></td>
                                                        @else
                                                        <td>{{$rowcolumnitem['text']}}</td>
                                                        @endif
                                                    @endforeach
                                            </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endforeach
    </div>
@endsection