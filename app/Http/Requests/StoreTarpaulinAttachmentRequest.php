<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTarpaulinAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('funeral_case');

        return $case && $this->user()?->can('uploadTarpaulin', $case);
    }

    public function rules(): array
    {
        return [
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Please select a tarpaulin photo to upload.',
            'photo.image' => 'The tarpaulin attachment must be an image file.',
            'photo.mimes' => 'Only JPG, JPEG, and PNG tarpaulin photos are allowed.',
            'photo.max' => 'The tarpaulin photo must not exceed 5MB.',
        ];
    }
}
