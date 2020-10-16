<?php

namespace App\Http\Requests\Item;

use App\Rules\ValidXmlLink;
use Illuminate\Foundation\Http\FormRequest;

class ItemStoreRequest extends FormRequest
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
            'xml_link' => ['bail', 'required', new ValidXmlLink(), 'unique:App\ReaderResult,url'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'xml_link.unique' => 'Link had been saved in database already.',
        ];
    }
}
