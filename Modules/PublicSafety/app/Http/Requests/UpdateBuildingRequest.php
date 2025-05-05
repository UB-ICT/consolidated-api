<?php

namespace Modules\PublicSafety\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $method = $this->method();
        if($method == 'PUT') {
        return [
            //
            'name'=>['required'],
            'location'=>['required'],
            'campusId'=>['required']
        ];
    } else {
        return [
            'name'=>['sometimes', 'required'],
            'location'=>['sometimes', 'required'],
            'campusId'=>['sometimes', 'required']
        ];
    }
}


    protected function prepareForValidation() {
        $this->merge([
            'campus_id'=>$this->campusId 
        ]);
    }
}
