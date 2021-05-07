@if($isNeedWorkTime ?? false)
    <div class="modal fade" id="work-time" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header rounded-0">
                    {{ __('Please, set time, which you are working to today') }}
                </div>
                <div class="modal-body">
                    {!! Form::open(['route' => ['users.set-time', Auth::user()]]) !!}
                    <div class="form-group">
                        {!! Form::time('time_to', '20:00', ['class' => 'form-control text-center']) !!}
                    </div>
                    {!! Form::button(__('Set time'), ['type' => 'submit', 'class' => 'btn btn-success', 'name' => 'setTime', 'value' => true]) !!}
                    {!! Form::button(__('I am not working today'), ['type' => 'submit', 'class' => 'btn btn-danger pull-right']) !!}
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endif
