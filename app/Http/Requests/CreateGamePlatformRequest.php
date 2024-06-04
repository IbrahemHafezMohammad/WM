<?php

namespace App\Http\Requests;

use App\Constants\GamePlatformConstants;
use App\Constants\GlobalConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateGamePlatformRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'platform_code' => ['required', 'string', Rule::unique(GamePlatformConstants::TABLE_NAME, 'platform_code')],
            'icon_image' => ['required', 'string']
        ];
    }

    public function getGamePlatformData()
    {
        $validated = $this->validated();

        // $icon_image = null;

        // if ($this->hasFile('icon_image')) {

        //     $icon_image = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $this->icon_image);

        //     $icon_image ?: $icon_image = 'Image Storing Failed';
        // }

        return [
            'name' => $validated['name'],
            'icon_image' => $validated['icon_image'],
            'platform_code' => $validated['platform_code']
        ];

    }

    protected function failedValidation(Validator $validator)
    {

        $response = response()->json([
            'status' => false,
            'message' => $validator->errors(),
        ], 422);

        throw new HttpResponseException($response);
    }
}
