<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketEventSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'=>'required|string',
            'event'=>'required|string',
            'ticket_event_criterion_id'=>'required|array',
            'ticket_event_action_id'=>'required|array'
        ];
    }
}
