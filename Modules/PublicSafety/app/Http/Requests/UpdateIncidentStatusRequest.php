<?php

namespace Modules\PublicSafety\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncidentStatusRequest extends FormRequest
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
        $method = $this -> method();
        if($method == 'PUT'){ 
            return [
                'statuses'=>['required'],
            ];
        }
        else {
            return [
                'statuses'=>['sometimes', 'required'],
            ];
        }
    }
}
