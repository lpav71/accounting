<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketEventActionRequest extends FormRequest
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
        $ticketEventAction = $this->route()->parameter('ticket_event_action');

        return [
            'name' => ['required', 'string', (is_object($ticketEventAction) ?
                Rule::unique('ticket_event_actions', 'name')->ignore($ticketEventAction->id)
                :
                Rule::unique(
                    'ticket_event_actions',
                    'name'
                )
            ),],
            'message_replace' => ['nullable','string','regex:/.=>./'],
            'add_user_id' => 'nullable|integer',
            'auto_message' => 'nullable|string',
            'ticket_priority_id' => 'nullable|integer',
            'performer_user_id' => 'nullable|integer',
            'notify' => [
                'nullable',
                Rule::in(array_keys(config('enums.ticket_event_actions.notify'))),
            ],
            'users_to_add' => 'required|json'
        ];
    }


    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        $data['add_user_id'] = isset($data['add_user_id']) ? $data['add_user_id'] : null;

        $this->getInputSource()->add($data);
    }

}
