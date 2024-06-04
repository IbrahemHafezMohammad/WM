<?php

namespace App\Http\Requests;

use App\Constants\GamePlatformConstants;
use App\Constants\GlobalConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UpdateGamePlatformRequest extends FormRequest
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
            'platform_code' => ['required', 'string', Rule::unique(GamePlatformConstants::TABLE_NAME)->ignore($this->game_platform->platform_code, 'platform_code')],
            'icon_image' => ['required', 'string']
        ];
    }

    public function getGamePlatformData()
    {
        $validated = $this->validated();

        $icon_image = null;

        // if ($this->hasFile('icon_image')) {

        //     $icon_image = Storage::putFile(GlobalConstants::GAME_PLATFORM_IMAGES_PATH, $this->icon_image);

        //     !$this->game_platform->icon_image ?: Storage::delete(substr($this->game_platform->icon_image, strpos($this->game_platform->icon_image, GlobalConstants::GAME_PLATFORM_IMAGES_PATH)));

        //     $icon_image ?: $icon_image = 'Image Storing Failed';
        // }

        return [
            'name' => $validated['name'],
            'platform_code' => $validated['platform_code'],
            'icon_image' => $validated['icon_image'],
        ];
    }
}
