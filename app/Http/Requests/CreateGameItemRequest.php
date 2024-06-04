<?php

namespace App\Http\Requests;

use App\Constants\GameCategoryConstants;
use App\Constants\GameItemConstants;
use App\Constants\GamePlatformConstants;
use App\Constants\GlobalConstants;
use App\Models\GameItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateGameItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('Create Game Item');
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
            'game_id' => ['required', Rule::unique(GameItemConstants::TABLE_NAME, 'game_id')],
            'status' => ['nullable', 'integer', Rule::in(array_keys(GameItemConstants::getStatuses()))],
            'properties' => ['sometimes', 'array'],
            'properties.*' => ['integer', Rule::in(GameItemConstants::getProperties())],
            'currencies' => ['required', 'array'],
            'currencies.*' => ['required', 'integer', Rule::in(array_keys(GlobalConstants::getCurrencies()))],
            'game_platform_id' => ['required', Rule::exists(GamePlatformConstants::TABLE_NAME, 'id')],
            'game_category_ids' => ['required', 'array'],
            'game_category_ids.*id' => ['required', Rule::exists(GameCategoryConstants::TABLE_NAME, 'id')],
            'icon_square' => ['required', 'string'],
            'icon_rectangle' => ['required', 'string'],
            'icon_square_desktop' => ['nullable', 'string'],
            'icon_rectangle_desktop' => ['nullable', 'string'],
           
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

    public function getGameItemData($validated)
    {
        $icon_square = null;

        // if ($this->hasFile('icon_square')) {

        //     $icon_square = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $this->icon_square);

        //     $icon_square ?: $icon_square = null;
        // }

        // $icon_rectangle = null;

        // if ($this->hasFile('icon_rectangle')) {

        //     $icon_rectangle = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $this->icon_rectangle);

        //     $icon_rectangle ?: $icon_rectangle = null;
        // }

        // //

        // $icon_square_desktop = null;

        // if ($this->hasFile('icon_square_desktop')) {

        //     $icon_square_desktop = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $this->icon_square_desktop);

        //     $icon_square_desktop ?: $icon_square_desktop = null;
        // }

        // $icon_rectangle_desktop = null;

        // if ($this->hasFile('icon_rectangle_desktop')) {

        //     $icon_rectangle_desktop = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $this->icon_rectangle_desktop);

        //     $icon_rectangle_desktop ?: $icon_rectangle_desktop = null;
        // }

        isset($validated['properties']) ? $properties = GameItem::calcProperties($validated['properties']) : $properties = 0;

        $currencies = GameItem::calcCurrencies($validated['currencies']);

        return [
            'name' => json_encode(['en' => $validated['en'], 'hi' => $validated['hi'] ?? null, 'tl' => $validated['tl'] ?? null, 'vn' => $validated['vn'] ?? null]),
            'status' => $validated['status'] ?? GameItemConstants::STATUS_ACTIVE,
            'properties' => $properties,
            'supported_currencies' => $currencies,
            'icon_square' => $validated['icon_square'],
            'icon_rectangle' => $validated['icon_rectangle'],
            'icon_square_desktop' => $validated['icon_square_desktop'] ?? null,
            'icon_rectangle_desktop' => $validated['icon_rectangle_desktop'] ?? null,
            'game_id' => $validated['game_id'],
            'game_platform_id' => $validated['game_platform_id'],
        ];
    }
}