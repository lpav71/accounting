@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{$channel->name}}</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('channels.index') }}"> {{ __('Back') }}</a>
            </div>
        </div>
    </div>
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
    <div>
            {!!Form::open(array('route' => array('notificationTemplate.save', $channel->id))) !!}
            <div id='main-form'>
            @foreach ($notifications as $key => $notification)
                <div class="mb-5 unique-form">
                    <div class="form-group">
                        <div data-id={{$notification->id}} class="form-group{!! $errors->has('templates['.$key.']') ? ' has-error' : '' !!}">
                                {!! Form::label('templates['.$key.'][state]', __('State'), ['class' => 'form-label m-0']) !!}
                                {!! Form::select('templates['.$key.'][state]', \App\OrderState::pluck('name','id') ,$notification->orderState->id,['class'=>'form-control  mb-1']) !!}
                                {!! Form::label('templates['.$key.'][subject]', __('Email subject'), ['class' => 'form-label m-0']) !!}
                                {!! Form::text('templates['.$key.'][email_subject]', $notification->email_subject, ['class'=>'form-control  mb-1']) !!}
                                {!! Form::textarea('templates['.$key.'][id]', $notification->id,['class'=>'hidden']) !!}
                                {!! Form::label('templates['.$key.'][carrier_type_id]', __('Carrier type'), ['class' => 'form-label m-0']) !!}
                                {!! Form::select('templates['.$key.'][carrier_type_id]',\App\CarrierType::pluck('name','id')->prepend(__('All types'),0),isset($notification->carrier_type->id) ? $notification->carrier_type->id : null , [ 'class'=>'form-control mb-1']) !!}    
                                {!! Form::label('templates['.$key.'][status]', _("SMS")) !!}
                                {!! Form::radio('templates['.$key.'][status]', "is_sms", $notification->is_sms,['class'=>'sms-radio']) !!}
                                {!! Form::label('templates['.$key.'][status]', _("Email")) !!}
                                {!! Form::radio('templates['.$key.'][status]',"is_email", $notification->is_email) !!}
                                {!! Form::label('templates['.$key.'][status]', _("Disabled")) !!}
                                {!! Form::radio('templates['.$key.'][status]',"is_disabled", $notification->is_disabled) !!}
                                {!! Form::button(__('Delete'), ['class' => 'btn btn-danger m-1', 'data-action' => "template-delete", 'delete-url'=>route('notificationTemplate.ajaxDelete',$channel->id)]) !!}
                                @if($notification->is_sms)
                                {!! Form::textarea('templates['.$key.'][template]',$notification->template, ['class' => 'form-control']) !!}
                                @else
                                {!! Form::textarea('templates['.$key.'][template]',$notification->template, ['class' => 'form-control editor-body']) !!}
                                @endif
                                @if ($errors->has('templates['.$key.']'))
                                <span class="help-block">
                                    <strong>{!! $errors->first('templates['.$key.']') !!}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
    </div>
            <div class="col-12 text-left"> 
                {!! Form::button(__('Add one more notification'), ['class' => 'btn btn-secondary mb-3', 'data-action' => "template-add"]) !!}
            </div>
            <div class="col-12 text-left"> 
                {!! Form::button(__('Save'), ['type' => 'submit', 'class' => 'btn btn-primary']) !!}
            </div>
            {!! Form::close() !!}
            <div class="hidden" data-item="template-pattern">
            <div class="mb-5 unique-form" >
                    <div class="form-group">
                        <div class="form-group{!! $errors->has('templates[]') ? ' has-error' : '' !!}">
                                {!! Form::label('templates[][state]', __('State'), ['class' => 'form-label m-0']) !!}
                                {!! Form::select('templates[][state]', \App\OrderState::pluck('name','id'),1,['class'=>'form-control']) !!}
                                {!! Form::label('templates[][subject]', __('Email subject'), ['class' => 'form-label m-0']) !!}
                                {!! Form::text('templates[][email_subject]', '', ['class'=>'form-control  mb-1']) !!}
                                {!! Form::label('templates[][carrier_type_id]', __('Carrier type'), ['class' => 'form-label']) !!}
                                {!! Form::select('templates[][carrier_type_id]',\App\CarrierType::pluck('name','id')->prepend(__('All types'),0), null , [ 'class'=>'form-control']) !!}    
                                {!! Form::label('templates[][status]', _("SMS")) !!}
                                {!! Form::radio('templates[][status]', "is_sms",0,['class'=>'sms-radio']) !!}
                                {!! Form::label('templates[][status]', _("Email")) !!}
                                {!! Form::radio('templates[][status]',"is_email",0) !!}
                                {!! Form::label('templates[][status]', _("Disabled")) !!}
                                {!! Form::radio('templates[][status]',"is_disabled", 1) !!}
                                {!! Form::button(__('Delete'), ['class' => 'btn btn-danger', 'data-action' => "template-delete", 'delete-url'=>route('notificationTemplate.ajaxDelete',$channel->id)]) !!}
                                {!! Form::textarea('templates[][template]',' ', ['class' => 'form-control new-editor-body']) !!}
                                @if ($errors->has('templates[]'))
                                <span class="help-block">
                                    <strong>{!! $errors->first('templates[]') !!}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
@endsection
