@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> {{ __('Show Channel') }}</h2>
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
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>{{ __('Name:') }}</strong>
                {{ $channel->name }}
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <p class="m-1">
                    <button class="btn btn-light" type="button" data-toggle="collapse" data-target="#collapseReferenceCompanies" aria-expanded="false" aria-controls="collapseReferenceCompanies">
                        {{__('References company')}}
                    </button>
                </p>
                <div class="collapse" id="collapseReferenceCompanies">
                    <div class="card card-block p-2">
                        <div>
                                <a href="{{ route('channels.ref-comp',['channel' => $channel->id]) }}">{{__('All manufacturers')}}</a>
                        </div> 
                        @foreach ($manufacturers as $manufacturer)
                            <div>
                                <a href="{{ route('channels.ref-comp',['channel' => $channel->id, 'manufacturer' => $manufacturer->id]) }}">{{$manufacturer->name}}</a>
                            </div> 
                        @endforeach
                    </div>
                </div>
            </div>
            <hr>
        </div>
        <div class="col-12 mt-4">
            <h3>{{__("References companies")}}</h3>
            {!! Form::open(['url' => route('channels.ref-comp',['channel' => $channel->id]), 'files' => true]) !!}
                <div class="form-group">
                    {!! Form::label('xlsx_file', __('xlsx file').':') !!}
                    {!! Form::file('xlsx_file',['class'=>'form-control-file','id'=>'xlsx_file']) !!}
                </div>
                <div class="col-12">
                    <div class="form-group">
                        {!! Form::label('manufacturers[]', __('Manufacturers').':') !!}
                        {!! Form::select('manufacturers[]', \App\Manufacturer::orderBy('name')->pluck('name','id'), null , ['title'=>__('All manufacturers'),'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
                    </div>
                </div>
                <div class="col-12">
                        <div class="form-group">
                            {!! Form::label('template', __('Template').':') !!}
                            {!! Form::select('template', \App\ReferencesCompaniesTemplate::orderBy('name')->pluck('name','id'), null , [ 'class' => 'form-control form-control-sm selectpicker']) !!}
                        </div>
                    </div>
                <div class="d-flex">
                    <div class="form-group">
                        {!! Form::label('bid_active', __('Bid if active').':') !!}
                        {!! Form::text('bid_active', null, array('class' => 'form-control')) !!}
                    </div>
                    <div class="form-group ml-2">
                        {!! Form::label('bid_inactive', __('Bid if inactive').':') !!}
                        {!! Form::text('bid_inactive', null, array('class' => 'form-control')) !!}
                    </div>
                </div>
                <div class="form-group form-check">
                    {!! Form::submit(__('Submit'),['class'=>'btn btn-primary']); !!}
                </div>
            {!! Form::close() !!}
            <hr>
        </div>
        <div class="col-12">
            <h3>{{__("Download products from channel")}}</h3>
            {!! Form::open(array('url' => route('presta-product-download'))) !!}
                {!! Form::textarea('channel_id', $channel->id, ['hidden' =>'hidden']) !!}
                <div class="form-group">
                    {!! Form::label('references', __('Product references coma is separator').':') !!}
                    {!! Form::textarea('references', null, ['class' => 'form-control', 'style'=>'height: 4em;', 'placeholder'=>__('Enter references if not all products')]) !!}
                </div>
                <div class="custom-control custom-switch">
                    {!! Form::checkbox('is_all', 1, null, ['class'=>'custom-control-input','id'=>'is_all']) !!}
                    {!! Form::label('is_all', __('All products'), ['class' => 'custom-control-label']) !!}
                </div>
                <div class="custom-control custom-switch">
                    {!! Form::checkbox('is_update', 1, null, ['class'=>'custom-control-input','id'=>'is_update']) !!}
                    {!! Form::label('is_update', __('Update channel product'), ['class' => 'custom-control-label']) !!}
                </div>
                <div class="custom-control custom-switch">
                    {!! Form::checkbox('update_main', 1, null, ['class'=>'custom-control-input','id'=>'update_main']) !!}
                    {!! Form::label('update_main', __('Update main product and channel product'), ['class' => 'custom-control-label']) !!}
                </div>
                <div class="form-group form-check">
                    {!! Form::submit(__('Download products'),['class'=>'btn btn-primary']); !!}
                </div>
            {!! Form::close() !!}
            <hr>
        </div>
        @can('product-edit')
        <div class="col-12">
            <h3>{{__("Upload products to channel")}}</h3>
            {{ Form::open(array('url' => route('presta-product-upload'))) }}
                {!! Form::textarea('channel_id', $channel->id, ['hidden' =>'hidden']) !!}
                <div class="form-group">
                    {!! Form::label('references', __('Product references coma is separator').':') !!}
                    {!! Form::textarea('references', null, ['class' => 'form-control', 'style'=>'height: 4em;']) !!}
                </div>
                <div class="col-12">
                    <div class="form-group">
                        {!! Form::label('manufacturers[]', __('Manufacturers').':') !!}
                        {!! Form::select('manufacturers[]', \App\manufacturer::orderBy('name')->pluck('name','id'), null , ['title'=>__('All manufacturers'),'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
                    </div>
                </div>
                <div class="form-group form-check">
                    {!! Form::submit(__('Upload products'),['class'=>'btn btn-primary']); !!}
                </div>
            {{ Form::close() }}
            <hr>
        </div>
        <div class="col-12">
            <h3>{{__("Copy current channel products to other")}}</h3>
            {{ Form::open(array('url' => route('presta-product-copy'))) }}
                {!! Form::textarea('channel_id', $channel->id, ['hidden' =>'hidden']) !!}
                <div class="form-group">
                    {!! Form::label('references', __('Product references coma is separator').':') !!}
                    {!! Form::textarea('references', null, ['class' => 'form-control', 'style'=>'height: 4em;', 'placeholder'=>__('All references')]) !!}
                </div>
                <div class="col-12">
                    <div class="form-group">
                        {!! Form::label('manufacturers[]', __('Manufacturers').':') !!}
                        {!! Form::select('manufacturers[]', \App\manufacturer::orderBy('name')->pluck('name','id'), null , ['title'=>__('All manufacturers'),'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        {!! Form::label('channels[]', __('Channels').':') !!}
                        {!! Form::select('channels[]', \App\Channel::whereContentControl()->orderBy('name')->where('id','!=',$channel->id)->pluck('name','id'), null , ['title'=>__('Choose channels'), 'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
                    </div>
                </div>
                <div class="custom-control custom-switch">
                    {!! Form::checkbox('override', 1, null, ['class'=>'custom-control-input','id'=>'override']) !!}
                    {!! Form::label('override', __('Override existed products'), ['class' => 'custom-control-label']) !!}
                </div>
                <div class="custom-control custom-switch">
                    {!! Form::checkbox('update_on_channel', 1, null, ['class'=>'custom-control-input','id'=>'update_on_channel']) !!}
                    {!! Form::label('update_on_channel', __('Update on channels'), ['class' => 'custom-control-label']) !!}
                </div>
                <div class="form-group form-check">
                    {!! Form::submit(__('Copy product'),['class'=>'btn btn-primary']); !!}
                </div>
            {{ Form::close() }}
            <hr>
        </div>
        <div class="col-12">
            <h3>{{__("Apply reduction for prices")}}</h3>
            {{ Form::open(array('url' => route('presta-product-price-reduction'))) }}
                {!! Form::textarea('channel_id', $channel->id, ['hidden' =>'hidden']) !!}
                <div class="form-group">
                    {!! Form::label('references', __('Product references coma is separator').':') !!}
                    {!! Form::textarea('references', null, ['class' => 'form-control', 'style'=>'height: 4em;', 'placeholder'=>__('All references')]) !!}
                </div>
                <div class="col-12">
                    <div class="form-group">
                        {!! Form::label('manufacturers[]', __('Manufacturers').':') !!}
                        {!! Form::select('manufacturers[]', \App\manufacturer::orderBy('name')->pluck('name','id'), null , ['title'=>__('All manufacturers'),'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        {!! Form::label('categories[]', __('Categories').':') !!}
                        {!! Form::select('categories[]', \App\Category::orderBy('id')->pluck('name','id'), null , ['title'=>__('All categories'), 'multiple' => true, 'class' => 'form-control form-control-sm selectpicker']) !!}
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group form-inline mb-2">
                        {!! Form::label('reduction', __('Reduction').':') !!}
                        {!! Form::text('reduction', null, ['class' => 'form-control', 'style'=>'']) !!}
                    </div>
                    <div class="form-group form-inline mb-2">
                        {!! Form::label('rounding', __('Rounding').':') !!}
                        {!! Form::text('rounding', 0, ['class' => 'form-control', 'style'=>'']) !!}
                    </div>
                    <div class="custom-control custom-switch">
                        {!! Form::checkbox('is_percent', 1, null, ['class'=>'custom-control-input','id'=>'is_percent']) !!}
                        {!! Form::label('is_percent', __('Is percent'), ['class' => 'custom-control-label']) !!}
                    </div>
                    <div class="custom-control custom-switch">
                        {!! Form::checkbox('update_reduction_on_channel', 1, null, ['class'=>'custom-control-input','id'=>'update_reduction_on_channel']) !!}
                        {!! Form::label('update_reduction_on_channel', __('Update on channels'), ['class' => 'custom-control-label']) !!}
                    </div>
                    <div class="form-group form-check">
                        {!! Form::submit(__('Apply reduction'),['class'=>'btn btn-primary']); !!}
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <p class="m-1">
                            <button class="btn btn-light" type="button" data-toggle="collapse" data-target="#collapseReductionHistory" aria-expanded="false" aria-controls="collapseReductionHistory">
                                {{__('Price reduction history')}}
                            </button>
                        </p>
                        <div class="collapse" id="collapseReductionHistory">
                            <div class="card card-block p-2">
                                <table class="table table-striped">
                                    <tbody>
                                        @foreach ($reductionHistory as $reductionHistoryItem)
                                        <tr>
                                            <th scope="row">{{$reductionHistoryItem->created_at}}</th>
                                            <td>{!!$reductionHistoryItem->text!!}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                  </table>
                            </div>
                        </div>
                    </div>
                    <hr>
                </div>
            {{ Form::close() }}
            <hr>
            <div class="col-12 mt-4">
                <h3>{{__("Enable only products in csv in reference column")}}</h3>
                {!! Form::open(['url' => route('presta-product-enable-only-csv'), 'files' => true]) !!}
                {!! Form::textarea('channel_id', $channel->id, ['hidden' =>'hidden']) !!}
                <div class="form-group">
                    {!! Form::label('csv_file', __('Csv file').':') !!}
                    {!! Form::file('csv_file',['class'=>'form-control-file','id'=>'csv_file']) !!}
                </div>
                <div class="form-group form-check">
                    {!! Form::submit(__('Submit'),['class'=>'btn btn-primary']); !!}
                </div>
                {!! Form::close() !!}
                <hr>
            </div>
            <hr>
            <div class="col-12 mt-4">
                <h3>{{__("Upload a file to edit product visibility at source")}}</h3>
                {!! Form::open(['url' => route('presta-product-new-enable-only-csv'), 'files' => true]) !!}
                {!! Form::textarea('channel_id', $channel->id, ['hidden' =>'hidden']) !!}
                <div class="form-group">
                    {!! Form::label('xlsx_file', __('Xlsx file').':') !!}
                    {!! Form::file('xlsx_file',['class'=>'form-control-file','id'=>'xlsx_file', 'accept'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']) !!}
                </div>
                <div class="form-group form-check">
                    {!! Form::submit(__('Submit'),['class'=>'btn btn-primary']); !!}
                </div>
                {!! Form::close() !!}
                <hr>
            </div>
            <div class="col-12 mt-4">
                <h3>{{__("Update base prices by xlsx")}}</h3>
                {!! Form::open(['url' => route('presta-product-xlsx-prices'), 'files' => true]) !!}
                {!! Form::text('channel_id', $channel->id, ['hidden' =>'hidden']) !!}
                <div class="form-group">
                    {!! Form::label('xlsx_file', __('Xlsx file').':') !!}
                    {!! Form::file('xlsx_file',['class'=>'form-control-file','id'=>'xlsx_file']) !!}
                </div>
                <div class="form-group form-check">
                    {!! Form::submit(__('Submit'),['class'=>'btn btn-primary']); !!}
                </div>
                {!! Form::close() !!}
                <hr>
            </div>
            <div class="col-12 mt-4">
                <h3>{{__("Update sale prices by xlsx")}}</h3>
                {!! Form::open(['url' => route('presta-product-xlsx-sale-prices'), 'files' => true]) !!}
                {!! Form::text('channel_id', $channel->id, ['hidden' =>'hidden']) !!}
                <div class="form-group">
                    {!! Form::label('xlsx_file', __('Xlsx file').':') !!}
                    {!! Form::file('xlsx_file',['class'=>'form-control-file','id'=>'csv_file']) !!}
                </div>
                <div class="form-group form-check">
                    {!! Form::submit(__('Submit'),['class'=>'btn btn-primary']); !!}
                </div>
                {!! Form::close() !!}
                <hr>
            </div>
            <div class="col-12 mt-4">
                <h3>{{__("Apply price reduction xlsx")}}</h3>
                {!! Form::open(['url' => route('presta-product-xlsx-reduction-prices'), 'files' => true]) !!}
                {!! Form::text('channel_id', $channel->id, ['hidden' =>'hidden']) !!}
                <div class="form-group">
                    {!! Form::label('xlsx_file', __('Xlsx file').':') !!}
                    {!! Form::file('xlsx_file',['class'=>'form-control-file','id'=>'csv_file']) !!}
                </div>
                <div class="form-group form-check">
                    {!! Form::submit(__('Submit'),['class'=>'btn btn-primary']); !!}
                </div>
                {!! Form::close() !!}
                <hr>
            </div>
            <div class="col-12 mt-4">
                <h3>{{__("Upload availability")}}</h3>
                {!! Form::open(['url' => route('presta-product-upload-availability'), 'files' => true]) !!}
                {!! Form::text('channel_id', $channel->id, ['hidden' =>'hidden']) !!}
                <div class="form-group form-check">
                    {!! Form::submit(__('Upload availability'),['class'=>'btn btn-primary']); !!}
                </div>
                {!! Form::close() !!}
                <hr>
            </div>
        </div>
        @endcan
    </div>
@endsection