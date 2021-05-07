@extends('layouts.app')

@section('content')
<div>
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>{{ __('Edit Channel') }}</h2>
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
    {!! Form::model($channel, ['method' => 'PATCH','route' => ['channels.update', $channel->id], 'files' => true]) !!}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('name', __('Name').':') !!}
                {!! Form::text('name', null, array('placeholder' => __('Name'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::checkbox('is_hidden', 1, null) !!}
                <strong>{{ __('Hidden') }}</strong>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('db_name', __('Database name')) !!}
                {!! Form::text('db_name', null, array('placeholder' => __('Database name'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('call_target_id', __('CallTargetId').':') !!}
                {!! Form::text('call_target_id', null, array('placeholder' => __('CallTargetId'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('telephony_numbers', __('Telephony numbers').':') !!}
                {!! Form::text('telephony_numbers', null, array('placeholder' => __('Telephony numbers'),'class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('go_proxy_url', __('Go proxy URL').':') !!}
                {!! Form::text('go_proxy_url', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('yandex_token', __('Yandex Token').':') !!}
                {!! Form::text('yandex_token', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('yandex_counter', __('Yandex Counter').':') !!}
                {!! Form::text('yandex_counter', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('google_counter', __('Google Counter').':') !!}
                {!! Form::text('google_counter', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('google_file', __('Google Key').':') !!}
                {!! Form::file('google_file', ['id' => 'google_file', 'class' => 'form-control-file', 'accept' => '.json']) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('phone', __('Phone').':') !!}
                {!! Form::text('phone', null, array('class' => 'form-control')) !!}
            </div>
        </div>

        <div class="col-12">
            <div class="form-group">
                {!! Form::label('check_certificate_token', __('Check certificate token').':') !!}
                {!! Form::text('check_certificate_token', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('template_name', __('Name for template').':') !!}
                {!! Form::text('template_name', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        <div class="col-12 mb-2">
            <div class="card">
                <p class="m-1">
                    <button class="btn btn-light" type="button" data-toggle="collapse" data-target="#content-management"
                        aria-expanded="false" aria-controls="content-management">
                        {{__('Content management')}}
                    </button>
                </p>
                <div class="collapse" id="content-management">
                    <div class="card card-block p-2">
                        <div class="form-group">
                            {!! Form::label('upload_address', __('Products upload address').':') !!}
                            {!! Form::text('upload_address', null, array('class' => 'form-control')) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('download_address', __('Product download address').':') !!}
                            {!! Form::text('download_address', null, array('class' => 'form-control')) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('upload_address_price', __('Product upload price address').':') !!}
                            {!! Form::text('upload_address_price', null, array('class' => 'form-control')) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('upload_address_availability', __('Product upload availability address').':') !!}
                            {!! Form::text('upload_address_availability', null, array('class' => 'form-control')) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('upload_key', __('Products sync key').':') !!}
                            {!! Form::text('upload_key', null, array('class' => 'form-control')) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 mb-2">
            <div class="card">
                <p class="m-1">
                    <button class="btn btn-light" type="button" data-toggle="collapse" data-target="#thermal-printer-fields"
                            aria-expanded="false" aria-controls="thermal-printer-fields">
                        {{__('Thermal printer')}}
                    </button>
                </p>
                <div class="collapse" id="thermal-printer-fields">
                    <div class="card card-block p-2">
                        <div class="col-12">
                            <div class="form-group">
                                {!! Form::label('check_title', __('Check`s title').':') !!}
                                {!! Form::textarea('check_title', null, array('placeholder' => __('Check`s title'),'class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                {!! Form::label('check_title_2', __('Check`s title').':') !!}
                                {!! Form::textarea('check_title_2', null, array('placeholder' => __('Check`s title'),'class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                {!! Form::label('check_user', __('User').':') !!}
                                {!! Form::text('check_user', null, array('placeholder' => __('User'),'class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                {!! Form::label('inn', __('INN').':') !!}
                                {!! Form::text('inn', null, array('placeholder' => __('INN'),'class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                {!! Form::label('ogrn', __('OGRN').':') !!}
                                {!! Form::text('ogrn', null, array('placeholder' => __('OGRN'),'class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                {!! Form::label('kpp', __('KPP').':') !!}
                                {!! Form::text('kpp', null, array('placeholder' => __('KPP'),'class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                {!! Form::label('check_place', __('Payment place').':') !!}
                                {!! Form::text('check_place', null, array('placeholder' => __('Payment place'),'class' => 'form-control')) !!}
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                {!! Form::label('check_qr_code', __('QR code').':') !!}
                                {!! Form::text('check_qr_code', null, array('placeholder' => __('QR code'),'class' => 'form-control')) !!}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="col-12  mb-2">
            <div class="card">
                <p class="m-1">
                    <button class="btn btn-light" type="button" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                        {{__('Yandex references companies settings')}}
                    </button>
                </p>
                <div class="collapse" id="collapseExample">
                    <div class="card card-block p-2">
                            <div class="form-group">
                                {!! Form::label('ya_endpoint', __('Channel Yandex references companies endpoint').':') !!}
                                {!! Form::text('ya_endpoint', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group ">
                                {!! Form::label('ya_ad_type', __('Ad company type').':') !!}
                                {!! Form::text('ya_ad_type', null, array('class' => 'form-control')) !!}
                            </div>
                            <p>{{__('Replacements')}}</p>
                            <div class="form-group ">
                                {!! Form::label('ya_phrase', __('Phrase (with minus words)').':') !!}
                                {!! Form::text('ya_phrase', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group ">
                                {!! Form::label('ya_header_1', __('Header 1 (max 35 letters with spaces)').':') !!}
                                {!! Form::text('ya_header_1', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group">
                                {!! Form::label('ya_header_2', __('Header 2 (max 30 letters with spaces)').':') !!}
                                {!! Form::text('ya_header_2', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group">
                                {!! Form::label('ya_text', __('Text (81 letters max)').':') !!}
                                {!! Form::text('ya_text', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group">
                                {!! Form::label('ya_link_text', __('Text of the link (max 20 letters and №, /, %, #)').':') !!}
                                {!! Form::text('ya_link_text', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group">
                                {!! Form::label('ya_region', __('Region').':') !!}
                                {!! Form::text('ya_region', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group">
                                {!! Form::label('ya_bet', __('Bet').':') !!}
                                {!! Form::text('ya_bet', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group">
                                {!! Form::label('ya_quick_link', __('Quick link headers (separator is ||)').':') !!}
                                {!! Form::text('ya_quick_link', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group">
                                {!! Form::label('ya_quick_link_descr', __('Sitelink descriptions (separator is ||)').':') !!}
                                {!! Form::text('ya_quick_link_descr', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group">
                                {!! Form::label('ya_quick_link_addr', __('Quick link addreses (separator is ||)').':') !!}
                                {!! Form::textarea('ya_quick_link_addr', null, array('class' => 'form-control')) !!}
                            </div>
                            <div class="form-group">
                                {!! Form::label('ya_details', __('Details').':') !!}
                                {!! Form::text('ya_details', null, array('class' => 'form-control')) !!}
                            </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
                    {{__("Email notifications")}}
                    <table class="table table-light table-bordered table-responsive-sm">
                        <thead class="thead-light">
                            <th class="text-center" >{{__("Server")}}</th>
                            <th class="text-center" >{{__("Port")}}</th>
                            <th class="text-center" >{{__("Secure Connection")}}</th>
                            <th class="text-center" >{{__("Username")}}</th>
                            <th class="text-center" >{{__("Password")}}</th>
                            <th class="text-center" >{{__("Channel notifications email")}}</th>
                            <th class="text-center" >{{__("Notifications enabled")}}</th>
                        </thead>
                        <tbody>
                            <td>{!! Form::text('smtp_host', null, array('class' => 'form-control')) !!}</td>
                            <td>{!! Form::text('smtp_port', null, array('class' => 'form-control')) !!}</td>
                            <td>{!! Form::text('smtp_encryption', null, array('class' => 'form-control')) !!}</td>
                            <td>{!! Form::text('smtp_username', null, array('class' => 'form-control')) !!}</td>
                            <td>{!! Form::text('smtp_password', null, array('class' => 'form-control')) !!}</td>
                            <td>{!! Form::text('notifications_email', null, array('class' => 'form-control')) !!}</td>
                            <td>{!! Form::checkbox('smtp_is_enabled',1, null, array('class' => 'form-control')) !!}</td>
                        </tbody>
                    </table>
        </div>
        <div class="col-12">
                {{__("SMS notifications")}}
                <table class="table table-light table-bordered table-responsive-sm">
                    <thead class="thead-light">
                        <th class="text-center col-8" >{{__('SMS query template')}}</th>
                        <th class="text-center col-4" >{{__('SMS notifications enabled')}}</th>
                    </thead>
                    <tbody>
                        <td class="text-center" >{!! Form::text('sms_template', null, array('class' => 'form-control')) !!}</td>
                        <td class="text-center" >{!! Form::checkbox('sms_is_enabled',1, null, array('class' => 'form-control')) !!}</td>
                        
                    </tbody>
                </table>
        </div>
        <div class="col-12">
            <div class="form-group">
                {!! Form::label('messenger_settings', __('Messenger settings').':') !!}
                {!! Form::textarea('messenger_settings', null, array('class' => 'form-control')) !!}
            </div>
        </div>
        </div>
        <div>
                {!! Form::checkbox('is_landscape_docs', 1, null) !!}
                <strong>{{ __('Is landscape documents') }}</strong>
        </div>
        <div class="col-12">
            <div class="form-group">
                <div class="form-group{!! $errors->has('invoice_template') ? ' has-error' : '' !!}">
                    {!! Form::label('invoice_template', __('Invoice template') . ':', ['class' => 'control-label']) !!}
                    {!! Form::textarea('invoice_template', NULL, ['class' => 'form-control editor-body']) !!}

                    @if ($errors->has('invoice_template'))
                        <span class="help-block">
                <strong>{!! $errors->first('invoice_template') !!}</strong>
            </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <div class="form-group{!! $errors->has('cheque_template') ? ' has-error' : '' !!}">
                    {!! Form::label('cheque_template', __('Cheque template') . ':', ['class' => 'control-label']) !!}
                    {!! Form::textarea('cheque_template', NULL, ['class' => 'form-control editor-body']) !!}

                    @if ($errors->has('cheque_template'))
                        <span class="help-block">
                <strong>{!! $errors->first('cheque_template') !!}</strong>
            </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <div class="form-group{!! $errors->has('guarantee_template') ? ' has-error' : '' !!}">
                    {!! Form::label('guarantee_template', __('Guarantee template') . ':', ['class' => 'control-label']) !!}
                    {!! Form::textarea('guarantee_template', NULL, ['class' => 'form-control editor-body']) !!}

                    @if ($errors->has('guarantee_template'))
                        <span class="help-block">
                <strong>{!! $errors->first('guarantee_template') !!}</strong>
            </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <div class="form-group{!! $errors->has('courier_template') ? ' has-error' : '' !!}">
                    {!! Form::label('courier_template', __('Courier template') . ':', ['class' => 'control-label']) !!}
                    {!! Form::textarea('courier_template', NULL, ['class' => 'form-control editor-body']) !!}

                    @if ($errors->has('courier_template'))
                        <span class="help-block">
                        <strong>{!! $errors->first('courier_template') !!}</strong>
                    </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 text-left">
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
        </div>
        <div class="col-12 text-left mt-2">
            <button type="submit" name="check" value="1" class="btn btn-primary">{{ __('Submit and check notifications') }}</button>
        </div>
        {!! Form::close() !!}
    </div>
    
@endsection
