<div class="row border-bottom m-1">
    <div class="col-12 p-0">
        <div class="form-group col-md-6 p-0">
            {!! Form::label('name', __('Name').':') !!}
            {!! Form::text('name', $data['name'] ?? null, ['placeholder' => __('Name'),'class' => 'form-control', 'required']) !!}
        </div>
    </div>
    <div class="col-12 p-0">
        <div class="form-group form-inline">
            {!! Form::label('is_active', __('Active')) !!}
            {!! Form::checkbox('is_active', 1, $data['is_active'] ?? 0, ['class' => 'm-2']) !!}
        </div>
    </div>
    <div class="col-12 p-0">
        <div class="form-group form-inline">
            {!! Form::label('is_message', __('Message')) !!}
            {!! Form::checkbox('is_message', 1, $data['is_message'] ?? 0, ['class' => 'm-2']) !!}
        </div>
    </div>
    <div class="col-12 p-0">
        <div class="form-group col-md-6 p-0">
            {!! Form::label('url', __('URL').':') !!}
            {!! Form::text('url', $data['url'] ?? null, ['placeholder' => __('URL'),'class' => 'form-control', 'required']) !!}
        </div>
    </div>
    <div class="col-12 p-0">
        <div class="form-group col-md-6 p-0">
            {!! Form::label('period', __('Period, ms').':') !!}
            {!! Form::text('period', $data['period'] ?? null, ['placeholder' => __('Period'),'class' => 'form-control', 'required']) !!}
        </div>
    </div>
</div>
<div class="row border-bottom m-1">
    <div class="col-12 p-0">
        <div class="form-group col-md-6 p-0">
            {!! Form::label('need_string_in_body', __('String in Body').':') !!}
            {!! Form::text('need_string_in_body', $data['need_string_in_body'] ?? null, ['placeholder' => __('String in Body'),'class' => 'form-control', 'required']) !!}
        </div>
    </div>
    <div class="col-12 p-0">
        <div class="form-group col-md-6 p-0">
            {!! Form::label('need_response_time', __('Maximum response time, ms').':') !!}
            {!! Form::text('need_response_time', $data['need_response_time'] ?? null, ['placeholder' => __('Maximum response time, ms'),'class' => 'form-control', 'required']) !!}
        </div>
    </div>
</div>
<div class="row m-1 mt-3">
    <div class="col-12 p-0 text-left">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('Save Test') }}</button>
    </div>
</div>
