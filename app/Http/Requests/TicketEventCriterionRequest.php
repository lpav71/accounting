<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketEventCriterionRequest extends FormRequest
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
        $ticketEventCriterion = $this->route()->parameter('ticket_event_criterion');

        return [
            'name' => ['required', 'string', (is_object($ticketEventCriterion) ?
                Rule::unique('ticket_event_criteria', 'name')->ignore($ticketEventCriterion->id)
                :
                Rule::unique(
                    'ticket_event_criteria',
                    'name'
                )
            ),],
            'message_substring' => 'nullable|string',
            'ticket_theme_id' => 'nullable|integer',
            'creator_user_id' => 'nullable|integer',
            'performer_user_id' => 'nullable|integer',
            'ticket_priority_id' => 'nullable|integer',
            'last_writer' =>    [
                'nullable',
                Rule::in(array_keys(config('enums.ticket_event_criteria.last_writer'))),
            ],
            'weekday_id' => 'nullable|array',
            'messages_count' => 'nullable|integer',
            'last_message_time' => 'nullable|integer',
            'ticket_name_substring' => 'nullable|string'
        ];
    }


    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['message_substring'] = isset($data['message_substring']) ? $data['message_substring'] : null;
        $data['ticket_theme_id'] = isset($data['ticket_theme_id']) ? $data['ticket_theme_id'] : null;
        $data['creator_user_id'] = isset($data['creator_user_id']) ? $data['creator_user_id'] : null;
        $data['performer_user_id'] = isset($data['performer_user_id']) ? $data['performer_user_id'] : null;
        $data['last_writer'] = isset($data['last_writer']) ? $data['last_writer'] : null;
        $data['weekday_id'] = isset($data['weekday_id']) ? $data['weekday_id'] : [];


        $this->getInputSource()->add($data);

    }
}
