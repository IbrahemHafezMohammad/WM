<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Admin;
use App\Models\Player;
use App\Models\Wallet;
use App\Models\BankCode;
use App\Models\GameItem;
use App\Models\Promotion;
use App\Models\GameCategory;
use App\Models\GamePlatform;
use App\Models\PaymentMethod;
use App\Models\PaymentCategory;
use App\Models\KMProviderConfig;
use App\Models\PromotionCategory;
use Illuminate\Http\UploadedFile;
use App\Constants\GlobalConstants;
use App\Models\PermissionCategory;
use Spatie\Permission\Models\Role;
use App\Models\ProviderIPWhitelist;
use App\Constants\BankCodeConstants;
use Illuminate\Support\Facades\Storage;
use App\Constants\GameCategoryConstants;
use App\Constants\GamePlatformConstants;
use Spatie\Permission\Models\Permission;
use App\Services\PaymentService\PaymentServiceEnum;

class GameSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //add game categories
        //popular
        $file1 = new UploadedFile(
            public_path('images/Categories/Inactive Popular.png'),
            'Inactive Popular.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/Categories/Active Popular.png'),
            'Active Popular.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file2);

        $popular = GameCategory::create([
            'name' => json_encode(["en" => "POPULAR", "hi" => "लोकप्रिय"]),
            'sort_order' => GameCategory::max('sort_order') ? GameCategory::max('sort_order') + 1 : 1,
            'status' => GameCategoryConstants::IS_ACTIVE,
            'icon_image' => $file_name1,
            'icon_active' => $file_name2,
        ]);




        //sports
        $file1 = new UploadedFile(
            public_path('images/Categories/Inactive Sports.png'),
            'SPORT.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/Categories/Active Sports.png'),
            'SPORT_ACTIVE.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file2);

        $sports = GameCategory::create([
            'name' => json_encode(["en" => "SPORT", "hi" => "खेल"]),
            'sort_order' => GameCategory::max('sort_order') ? GameCategory::max('sort_order') + 1 : 1,
            'status' => GameCategoryConstants::IS_ACTIVE,
            'is_lobby' => true,
            'icon_image' => $file_name1,
            'icon_active' => $file_name2,
        ]);

        $file1 = new UploadedFile(
            public_path('images/Categories/CASINO_INACTIVE2.png'),
            'CASINO_INACTIVE2.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/Categories/CASINO_ACTIVE2.png'),
            'CASINO_ACTIVE2.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file2);

        $casino = GameCategory::create([
            'name' => json_encode(["en" => "CASINO", "hi" => "कैसीनो"]),
            'sort_order' => GameCategory::max('sort_order') ? GameCategory::max('sort_order') + 1 : 1,
            'status' => GameCategoryConstants::IS_ACTIVE,
            'is_lobby' => true,
            'icon_image' => $file_name1,
            'icon_active' => $file_name2,
        ]);

        $file1 = new UploadedFile(
            public_path('images/Categories/SLOT_INACTIVE.png'),
            'SLOT_INACTIVE.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/Categories/SLOT_ACTIVE.png'),
            'SLOT_ACTIVE.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file2);

        $slots = GameCategory::create([
            'name' => json_encode(["en" => "SLOTS"]),
            'sort_order' => GameCategory::max('sort_order') ? GameCategory::max('sort_order') + 1 : 1,
            'status' => GameCategoryConstants::IS_ACTIVE,
            'icon_image' => $file_name1,
            'icon_active' => $file_name2,
        ]);

        $file1 = new UploadedFile(
            public_path('images/Categories/SELLFISH_INACTIVE2.png'),
            'SELLFISH_INACTIVE2.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/Categories/SELLFISH_ACTIVE2.png'),
            'SELLFISH_ACTIVE2.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file2);

        $fish = GameCategory::create([
            'name' => json_encode(["en" => "FISHING", "hi" => "मछली बेचें"]),
            'sort_order' => GameCategory::max('sort_order') ? GameCategory::max('sort_order') + 1 : 1,
            'status' => GameCategoryConstants::IS_ACTIVE,
            'icon_image' => $file_name1,
            'icon_active' => $file_name2,
        ]);


        //new games
        $file1 = new UploadedFile(
            public_path('images/Categories/New Release.png'),
            'New Release.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file1);

        $new_games = GameCategory::create([
            'name' => json_encode(["en" => "NEW GAMES", "hi" => "लोकप्रिय"]),
            'sort_order' => GameCategory::max('sort_order') ? GameCategory::max('sort_order') + 1 : 1,
            'status' => GameCategoryConstants::IS_ACTIVE,
            'icon_image' => $file_name1,
            'icon_active' => $file_name1,
            'parent_category_id' => 1
        ]);

        //hot games
        $file1 = new UploadedFile(
            public_path('images/Categories/Hot Games.png'),
            'Hot Games.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file1);

        $hot_games = GameCategory::create([
            'name' => json_encode(["en" => "HOT GAMES", "hi" => "लोकप्रिय"]),
            'sort_order' => GameCategory::max('sort_order') ? GameCategory::max('sort_order') + 1 : 1,
            'status' => GameCategoryConstants::IS_ACTIVE,
            'icon_image' => $file_name1,
            'icon_active' => $file_name1,
            'parent_category_id' => 1
        ]);

        //big jackpot
        $file1 = new UploadedFile(
            public_path('images/Categories/Big Jackpot.png'),
            'Big Jackpot.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_CATEGORY_IMAGES_PATH, $file1);

        $big_jackpot = GameCategory::create([
            'name' => json_encode(["en" => "BIG JACKPOT", "hi" => "लोकप्रिय"]),
            'sort_order' => GameCategory::max('sort_order') ? GameCategory::max('sort_order') + 1 : 1,
            'status' => GameCategoryConstants::IS_ACTIVE,
            'icon_image' => $file_name1,
            'icon_active' => $file_name1,
            'parent_category_id' => 1
        ]);



        //add game platform
        $file = new UploadedFile(
            public_path('images/platform.jpg'),
            'blank.jpg',
            'image/jpg'
        );

        $file_name = Storage::putFile(GlobalConstants::GAME_PLATFORM_IMAGES_PATH, $file);


        $via_platform = GamePlatform::create([
            'name' => 'ViA',
            'icon_image' => $file_name,
            'platform_code' => GamePlatformConstants::PLATFORM_VIA
        ]);

        $km_platform = GamePlatform::create([
            'name' => 'Kingmaker',
            'icon_image' => $file_name,
            'platform_code' => GamePlatformConstants::PLATFORM_KM
        ]);

        $evo_platform = GamePlatform::create([
            'name' => 'Evolution',
            'icon_image' => $file_name,
            'platform_code' => GamePlatformConstants::PLATFORM_EVO
        ]);

        $ug_platform = GamePlatform::create([
            'name' => 'UG Sports',
            'icon_image' => $file_name,
            'platform_code' => GamePlatformConstants::PLATFORM_UG
        ]);

        $cmd_platform = GamePlatform::create([
            'name' => 'CMD Sports',
            'icon_image' => $file_name,
            'platform_code' => GamePlatformConstants::PLATFORM_CMD
        ]);

        $awc_sub_sexybcrt_platform = GamePlatform::create([
            'name' => 'Ae Sexy',
            'icon_image' => $file_name,
            'platform_code' => GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT
        ]);

        $awc_sub_jili_platform = GamePlatform::create([
            'name' => 'JILI',
            'icon_image' => $file_name,
            'platform_code' => GamePlatformConstants::AWC_SUB_PROVIDER_JILI
        ]);

        //GAMES

        //live
        $file1 = new UploadedFile(
            public_path('images/Live games/via-square.jpg'),
            'via_lobby.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/Live games/via-min.png'),
            'via_lobby.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Lobby',
            'hi' => 'Lobby',
            'tl' => 'Lobby',
            'vn' => 'Lobby',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name1,
            'icon_rectangle' => $file_name2,
            'game_id' => GamePlatformConstants::VIA_GAME_CODE_LOBBY,
            'game_platform_id' => $via_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK])
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$casino->id, $new_games->id], ['game_item_sort_order' => 1]);

        //
        $file1 = new UploadedFile(
            public_path('images/Live games/ae sexy-min-sqaure.jpg'),
            'awc_aesexy.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/Live games/aesexy-min.png'),
            'awc_aesexy.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'aesexy',
            'hi' => 'aesexy',
            'tl' => 'aesexy',
            'vn' => 'aesexy',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name1,
            'icon_rectangle' => $file_name2,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_AESEXY_LOBBY,
            'game_platform_id' => $awc_sub_sexybcrt_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$casino->id, $hot_games->id], ['game_item_sort_order' => 23]);

        //evolution

        $file1 = new UploadedFile(
            public_path('images/Live games/evolution-square.jpg'),
            'platform.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/Live games/evolution-min.png'),
            'platform.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'EVO LOBBY',
            'hi' => 'EVO LOBBY',
            'tl' => 'EVO LOBBY',
            'vn' => 'EVO LOBBY',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name1,
            'icon_rectangle' => $file_name2,
            'game_id' => GamePlatformConstants::EVO_GAME_CODE_LOBBY,
            'game_platform_id' => $evo_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$casino->id, $new_games->id], ['game_item_sort_order' => 0]);

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_royal_fishing.jpg'),
            'awc_royal_fishing.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_royal_fishing.jpg'),
            'awc_royal_fishing.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Royal Fishing',
            'hi' => 'Royal Fishing',
            'tl' => 'Royal Fishing',
            'vn' => 'Royal Fishing',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_ROYAL_FISHING,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$new_games->id, $fish->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_bombing_fishing.jpg'),
            'awc_bombing_fishing.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_bombing_fishing.jpg'),
            'awc_bombing_fishing.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Bombing Fishing',
            'hi' => 'Bombing Fishing',
            'tl' => 'Bombing Fishing',
            'vn' => 'Bombing Fishing',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_BOMBING_FISHING,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$hot_games->id, $fish->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_jackpot_fishing.jpg'),
            'awc_jackpot_fishing.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_jackpot_fishing.jpg'),
            'awc_jackpot_fishing.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Jackpot Fishing',
            'hi' => 'Jackpot Fishing',
            'tl' => 'Jackpot Fishing',
            'vn' => 'Jackpot Fishing',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_JACKPOT_FISHING,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$new_games->id, $fish->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_charge_buffalo.jpg'),
            'awc_charge_buffalo.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_charge_buffalo.jpg'),
            'awc_charge_buffalo.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Charge Buffalo',
            'hi' => 'Charge Buffalo',
            'tl' => 'Charge Buffalo',
            'vn' => 'Charge Buffalo',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_CHARGE_BUFFALO,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$hot_games->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_color_game.jpg'),
            'awc_color_game.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_color_game.jpg'),
            'awc_color_game.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Color Game',
            'hi' => 'Color Game',
            'tl' => 'Color Game',
            'vn' => 'Color Game',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_COLOR_GAME,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$hot_games->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_boxing_king.jpg'),
            'awc_boxing_king.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_boxing_king.jpg'),
            'awc_boxing_king.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Boxing King',
            'hi' => 'Boxing King',
            'tl' => 'Boxing King',
            'vn' => 'Boxing King',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_BOXING_KING,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$new_games->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_fortune_gems_2.jpg'),
            'awc_fortune_gems_2.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_fortune_gems_2.jpg'),
            'awc_fortune_gems_2.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Fortune Gems 2',
            'hi' => 'Fortune Gems 2',
            'tl' => 'Fortune Gems 2',
            'vn' => 'Fortune Gems 2',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_FORTUNE_GEMS_2,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$big_jackpot->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_fortune_gems.jpg'),
            'awc_fortune_gems.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_fortune_gems.jpg'),
            'awc_fortune_gems.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Fortune Gems',
            'hi' => 'Fortune Gems',
            'tl' => 'Fortune Gems',
            'vn' => 'Fortune Gems',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_FORTUNE_GEMS,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$big_jackpot->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_irich_bingo.jpg'),
            'awc_irich_bingo.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_irich_bingo.jpg'),
            'awc_irich_bingo.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'iRich Bingo',
            'hi' => 'iRich Bingo',
            'tl' => 'iRich Bingo',
            'vn' => 'iRich Bingo',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_IRICH_BINGO,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$big_jackpot->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_golden_empire.jpg'),
            'awc_golden_empire.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_golden_empire.jpg'),
            'awc_golden_empire.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Golden Empire',
            'hi' => 'Golden Empire',
            'tl' => 'Golden Empire',
            'vn' => 'Golden Empire',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_GOLDEN_EMPIRE,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$hot_games->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_mega_ace.jpg'),
            'awc_mega_ace.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_mega_ace.jpg'),
            'awc_mega_ace.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Mega Ace',
            'hi' => 'Mega Ace',
            'tl' => 'Mega Ace',
            'vn' => 'Mega Ace',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_MEGA_ACE,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$big_jackpot->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_money_coming.jpg'),
            'awc_money_coming.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_money_coming.jpg'),
            'awc_money_coming.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Money Coming',
            'hi' => 'Money Coming',
            'tl' => 'Money Coming',
            'vn' => 'Money Coming',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_MONEY_COMING,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$new_games->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_super_ace.jpg'),
            'awc_super_ace.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_super_ace.jpg'),
            'awc_super_ace.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Super Ace',
            'hi' => 'Super Ace',
            'tl' => 'Super Ace',
            'vn' => 'Super Ace',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_SUPER_ACE,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$new_games->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc_wild_ace.jpg'),
            'awc_wild_ace.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc_wild_ace.jpg'),
            'awc_wild_ace.jpg',
            'image/jpg'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Wild Ace',
            'hi' => 'Wild Ace',
            'tl' => 'Wild Ace',
            'vn' => 'Wild Ace',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_WILD_ACE,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$new_games->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'HAPPY FISHING',
            'hi' => 'HAPPY FISHING',
            'tl' => 'HAPPY FISHING',
            'vn' => 'HAPPY FISHING',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_HAPPY_FISHING,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$hot_games->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'DRAGON FORTUNE',
            'hi' => 'DRAGON FORTUNE',
            'tl' => 'DRAGON FORTUNE',
            'vn' => 'DRAGON FORTUNE',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_DRAGON_FORTUNE,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$hot_games->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'BOOM LEGEND',
            'hi' => 'BOOM LEGEND',
            'tl' => 'BOOM LEGEND',
            'vn' => 'BOOM LEGEND',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_BOOM_LEGEND,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$big_jackpot->id, $slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'DINOSAUR TYCOON',
            'hi' => 'DINOSAUR TYCOON',
            'tl' => 'DINOSAUR TYCOON',
            'vn' => 'DINOSAUR TYCOON',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_DINOSAUR_TYCOON,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id, $hot_games->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'DINOSAUR TYCOON 2',
            'hi' => 'DINOSAUR TYCOON 2',
            'tl' => 'DINOSAUR TYCOON 2',
            'vn' => 'DINOSAUR TYCOON 2',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_DINOSAUR_TYCOON_2,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'MEGA FISHING',
            'hi' => 'MEGA FISHING',
            'tl' => 'MEGA FISHING',
            'vn' => 'MEGA FISHING',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_MEGA_FISHING,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'ALL STAR FISHING',
            'hi' => 'ALL STAR FISHING',
            'tl' => 'ALL STAR FISHING',
            'vn' => 'ALL STAR FISHING',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_ALL_STAR_FISHING,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'OCEAN KING JACKPOT',
            'hi' => 'OCEAN KING JACKPOT',
            'tl' => 'OCEAN KING JACKPOT',
            'vn' => 'OCEAN KING JACKPOT',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_OCEAN_KING_JACKPOT,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'HOT CHILLI',
            'hi' => 'HOT CHILLI',
            'tl' => 'HOT CHILLI',
            'vn' => 'HOT CHILLI',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_HOT_CHILLI,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'CHIN SHI HUANG',
            'hi' => 'CHIN SHI HUANG',
            'tl' => 'CHIN SHI HUANG',
            'vn' => 'CHIN SHI HUANG',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_CHIN_SHI_HUANG,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'WAR OF DRAGONS',
            'hi' => 'WAR OF DRAGONS',
            'tl' => 'WAR OF DRAGONS',
            'vn' => 'WAR OF DRAGONS',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_WAR_OF_DRAGONS,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'LUCKY BALL',
            'hi' => 'LUCKY BALL',
            'tl' => 'LUCKY BALL',
            'vn' => 'LUCKY BALL',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_LUCKY_BALL,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 1]);

        //

        //

        $file1 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/awc_images/awc-holder.png'),
            'awc-holder.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'HAWAII BEAUTY',
            'hi' => 'HAWAII BEAUTY',
            'tl' => 'HAWAII BEAUTY',
            'vn' => 'HAWAII BEAUTY',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::AWC_GAME_CODE_HAWAII_BEAUTY,
            'game_platform_id' => $awc_sub_jili_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 1]);

        //

        $file1 = new UploadedFile(
            public_path('images/km_images/Fish_Prawn_Crab_2.png'),
            'Fish_Prawn_Crab_2.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Fish_Prawn_Crab_2.png'),
            'Fish_Prawn_Crab_2.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Fish Prawn Crab 2',
            'hi' => 'Fish Prawn Crab 2',
            'tl' => 'Fish Prawn Crab 2',
            'vn' => 'Fish Prawn Crab 2',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_FISH_PRAWN_CRAB_2,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id, $new_games->id], ['game_item_sort_order' => 2]);

        $file1 = new UploadedFile(
            public_path('images/km_images/Thai_Fish_Prawn_Crab.png'),
            'Thai_Fish_Prawn_Crab.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Thai_Fish_Prawn_Crab.png'),
            'Thai_Fish_Prawn_Crab.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Thai Fish Prawn Crab',
            'hi' => 'Thai Fish Prawn Crab',
            'tl' => 'Thai Fish Prawn Crab',
            'vn' => 'Thai Fish Prawn Crab',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_THAI_FISH_PRAWN_CRAB,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$fish->id], ['game_item_sort_order' => 3]);

        $file1 = new UploadedFile(
            public_path('images/km_images/Belangkai_2.png'),
            'Belangkai_2.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Belangkai_2.png'),
            'Belangkai_2.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Belangkai 2',
            'hi' => 'Belangkai 2',
            'tl' => 'Belangkai 2',
            'vn' => 'Belangkai 2',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_BELANGKAI_2,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 4]);

        $file1 = new UploadedFile(
            public_path('images/km_images/Viet_Fish_Prawn_Crab.png'),
            'Viet_Fish_Prawn_Crab.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Viet_Fish_Prawn_Crab.png'),
            'Viet_Fish_Prawn_Crab.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Vietnam Fish Prawn Crab',
            'hi' => 'Vietnam Fish Prawn Crab',
            'tl' => 'Vietnam Fish Prawn Crab',
            'vn' => 'Vietnam Fish Prawn Crab',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_VIET_FISH_PRAWN_CRAB,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$fish->id, $hot_games->id], ['game_item_sort_order' => 5]);

        $file1 = new UploadedFile(
            public_path('images/km_images/Dragon_Tiger_2.png'),
            'Dragon_Tiger_2.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Dragon_Tiger_2.png'),
            'Dragon_Tiger_2.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Dragon Tiger 2',
            'hi' => 'Dragon Tiger 2',
            'tl' => 'Dragon Tiger 2',
            'vn' => 'Dragon Tiger 2',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_DRAGON_TIGER_2,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id, $big_jackpot->id], ['game_item_sort_order' => 6]);

        $file1 = new UploadedFile(
            public_path('images/km_images/Sicbo.png'),
            'Sicbo.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Sicbo.png'),
            'Sicbo.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Sicbo',
            'hi' => 'Sicbo',
            'tl' => 'Sicbo',
            'vn' => 'Sicbo',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_SICBO,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id, $hot_games->id], ['game_item_sort_order' => 7]);

        $file1 = new UploadedFile(
            public_path('images/km_images/Poker_Roulette.png'),
            'Poker_Roulette.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Poker_Roulette.png'),
            'Poker_Roulette.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Poker Roulette',
            'hi' => 'Poker Roulette',
            'tl' => 'Poker Roulette',
            'vn' => 'Poker Roulette',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_POKER_ROULETTE,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 8]);

        $file1 = new UploadedFile(
            public_path('images/km_images/7_Up_7_Down.png'),
            '7_Up_7_Down.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/7_Up_7_Down.png'),
            '7_Up_7_Down.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => '7 Up 7 Down',
            'hi' => '7 Up 7 Down',
            'tl' => '7 Up 7 Down',
            'vn' => '7 Up 7 Down',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_7_UP_7_DOWN,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 9]);

        $file1 = new UploadedFile(
            public_path('images/km_images/Fruit_Roulette.png'),
            'Fruit_Roulette.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Fruit_Roulette.png'),
            'Fruit_Roulette.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Fruit Roulette',
            'hi' => 'Fruit Roulette',
            'tl' => 'Fruit Roulette',
            'vn' => 'Fruit Roulette',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_FRUIT_ROULETTE,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 10]);

        $file1 = new UploadedFile(
            public_path('images/km_images/Baccarat.png'),
            'Baccarat.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Baccarat.png'),
            'Baccarat.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Baccarat',
            'hi' => 'Baccarat',
            'tl' => 'Baccarat',
            'vn' => 'Baccarat',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_BACCARAT,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 11]);

        $file1 = new UploadedFile(
            public_path('images/km_images/Blackjack.png'),
            'Blackjack.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Blackjack.png'),
            'Blackjack.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Blackjack',
            'hi' => 'Blackjack',
            'tl' => 'Blackjack',
            'vn' => 'Blackjack',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_BLACKJACK,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 12]);

        $file1 = new UploadedFile(
            public_path('images/km_images/Sugar_Blast.png'),
            'Sugar_Blast.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Sugar_Blast.png'),
            'Sugar_Blast.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Sugar Blast',
            'hi' => 'Sugar Blast',
            'tl' => 'Sugar Blast',
            'vn' => 'Sugar Blast',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_SUGAR_BLAST,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$fish->id, $new_games->id], ['game_item_sort_order' => 13]);

        //
        $file1 = new UploadedFile(
            public_path('images/km_images/5_card_poker.png'),
            '5_card_poker.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/5_card_poker.png'),
            '5_card_poker.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => '5 Card Poker',
            'hi' => '5 Card Poker',
            'tl' => '5 Card Poker',
            'vn' => '5 Card Poker',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_5_CARD_POKER,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 14]);

        //
        $file1 = new UploadedFile(
            public_path('images/km_images/Kingmaker_Pok_Deng.png'),
            'Kingmaker_Pok_Deng.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Kingmaker_Pok_Deng.png'),
            'Kingmaker_Pok_Deng.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Kingmaker Pok Deng',
            'hi' => 'Kingmaker Pok Deng',
            'tl' => 'Kingmaker Pok Deng',
            'vn' => 'Kingmaker Pok Deng',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_KINGMAKER_POK_DENG,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 16]);

        //
        $file1 = new UploadedFile(
            public_path('images/km_images/Pai_Kang.png'),
            'Pai_Kang.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Pai_Kang.png'),
            'Pai_Kang.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Pai Kang',
            'hi' => 'Pai Kang',
            'tl' => 'Pai Kang',
            'vn' => 'Pai Kang',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_PAI_KANG,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 17]);

        //
        $file1 = new UploadedFile(
            public_path('images/km_images/Teen_Patti.png'),
            'Teen_Patti.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Teen_Patti.png'),
            'Teen_Patti.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Teen Patti',
            'hi' => 'Teen Patti',
            'tl' => 'Teen Patti',
            'vn' => 'Teen Patti',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_TEEN_PATTI,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 18]);

        //
        $file1 = new UploadedFile(
            public_path('images/km_images/Bola_Tangkas.png'),
            'Bola_Tangkas.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Bola_Tangkas.png'),
            'Bola_Tangkas.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Bola Tangkas',
            'hi' => 'Bola Tangkas',
            'tl' => 'Bola Tangkas',
            'vn' => 'Bola Tangkas',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_BOLA_TANGKAS,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 19]);


        //
        $file1 = new UploadedFile(
            public_path('images/km_images/Ludo.png'),
            'Ludo.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Ludo.png'),
            'Ludo.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'LUDO',
            'hi' => 'LUDO',
            'tl' => 'LUDO',
            'vn' => 'LUDO',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_LUDO,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 20]);

        //
        $file1 = new UploadedFile(
            public_path('images/km_images/Tongits.png'),
            'Tongits.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/km_images/Tongits.png'),
            'Tongits.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'Tongits',
            'hi' => 'Tongits',
            'tl' => 'Tongits',
            'vn' => 'Tongits',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::KM_GAME_TYPE_TONGITS,
            'game_platform_id' => $km_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$slots->id], ['game_item_sort_order' => 21]);

        //


        //
        $file1 = new UploadedFile(
            public_path('images/sports/united gaming-min.png'),
            'UG-Logo.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/sports/united gaming-min.png'),
            'UG-Logo.png',
            'image/png'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'UG LOBBY',
            'hi' => 'UG LOBBY',
            'tl' => 'UG LOBBY',
            'vn' => 'UG LOBBY',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::UG_GAME_CODE_LOBBY,
            'game_platform_id' => $ug_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$sports->id], ['game_item_sort_order' => 0]);

        //
        $file1 = new UploadedFile(
            public_path('images/CMD.webp'),
            'CMD.webp',
            'image/webp'
        );

        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

        $file2 = new UploadedFile(
            public_path('images/CMD.webp'),
            'CMD.webp',
            'image/webp'
        );

        $file_name2 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file2);

        $langs = [
            'en' => 'CMD LOBBY',
            'hi' => 'CMD LOBBY',
            'tl' => 'CMD LOBBY',
            'vn' => 'CMD LOBBY',
        ];

        $game_item = GameItem::create([
            'name' => json_encode($langs),
            'icon_square' => $file_name2,
            'icon_rectangle' => $file_name1,
            'game_id' => GamePlatformConstants::CMD_GAME_CODE_LOBBY,
            'game_platform_id' => $cmd_platform->id,
            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
        ]);

        $game_item->gameCategories()->syncWithPivotValues([$sports->id], ['game_item_sort_order' => 0]);
    }
}
