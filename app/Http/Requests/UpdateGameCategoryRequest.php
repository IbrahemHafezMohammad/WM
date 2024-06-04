<?php

namespace App\Http\Requests;

use App\Models\GameCategory;
use Illuminate\Validation\Rule;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use App\Constants\GameCategoryConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UpdateGameCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Create Game Category');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'en' => ['required', 'string'],
            'hi' => ['nullable', 'string'],
            'tl' => ['nullable', 'string'],
            'vn' => ['nullable', 'string'],
            'status' => 'boolean',
            'properties' => ['sometimes', 'array'],
            'properties.*' => ['integer', Rule::in(GameCategoryConstants::getProperties())],
            'icon_image' => ['required', 'string'],
            'icon_active' => ['required', 'string'],
            'icon_trend' => ['nullable', 'string'],
            'is_lobby' => ['required' , 'boolean'],
            'bg_image' => ['nullable', 'string'],
            'parent_category_id' => ['nullable', 'exists:game_categories,id'] ,
        ];
    }

    public function getGameCategoryData()
    {
        $validated = $this->validated();

        // $icon_image = null;

        // if ($this->hasFile('icon_image')) {

        //     $icon_image = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $this->icon_image);

        //     $icon_image ? $this->game_category->deleteIconImage() : $icon_image = $this->game_category->icon_image;
        // }

        // $icon_active = null;

        // if ($this->hasFile('icon_active')) {

        //     $icon_active = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $this->icon_active);

        //     $icon_active ? $this->game_category->deleteIconActive() : $icon_active = $this->game_category->icon_active;
        // }

        $name = [
            'en' => $validated['en'],
            'hi' => $validated['hi'] ?? null,
            'tl' => $validated['tl'] ?? null,
            'vn' => $validated['vn'] ?? null,
        ];

        isset($validated['properties']) ? $properties = GameCategory::calcProperties($validated['properties']) : $properties = 0;

        return [
            'name' => json_encode($name),
            'status' => $validated['status'] ?? GameCategoryConstants::IS_ACTIVE,
            'properties' => $properties,
            'updated_by' => Auth::user()->id,
            'icon_image' => $validated['icon_image'],
            'icon_active' => $validated['icon_active'],
            'icon_trend' => $validated['icon_trend'] ?? null,
            'is_lobby' => $validated['is_lobby'],
            'bg_image'=>$validated['bg_image'] ?? null,
            'parent_category_id' => $validated['parent_category_id'] ?? null,
        ];
    }
}
