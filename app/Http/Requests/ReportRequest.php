<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class ReportRequest extends FormRequest
{
    /**
     * Имеет ли пользователь право сделать такой запрос
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Правила проверки, которые применяются к запросу
     *
     * @return array
     */
    public function rules()
    {
        return [
            'from' => 'regex:/[0-9]{2}-[0-9]{2}-[0-9]{4}/|nullable',
            'to' => 'regex:/[0-9]{2}-[0-9]{2}-[0-9]{4}/|nullable',
            'submit' => 'string',
            'save' => 'string',
            'sub_report_selected' => 'string|nullable',
        ];
    }

    public function validateResolved()
    {
        parent::validateResolved();

        $data = $this->input();

        // Добавление отсутствующих данных к запросу, чтобы не заниматься проверкой в контроллере

        $data['from'] = empty($data['from'] ?? null) ?
            null :
            Carbon::createFromFormat('d-m-Y', $data['from'])
                ->setTime(0, 0, 0, 0);

        $data['to'] = empty($data['to'] ?? null) ?
            null :
            Carbon::createFromFormat('d-m-Y', $data['to'])
                ->setTime(23, 59, 59, 999999);;

        $data['submit'] = isset($data['submit']);
        $data['save'] = isset($data['save']);

        $data['sub_report_selected'] = $data['sub_report_selected'] ?? null;

        $this->getInputSource()->add($data);

    }
}
