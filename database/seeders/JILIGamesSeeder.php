<?php

namespace Database\Seeders;

use App\Models\GameItem;
use App\Models\GameCategory;
use App\Models\GamePlatform;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Constants\GamePlatformConstants;
use App\Services\Providers\ONEProvider\ONEProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Services\Providers\ONEProvider\Enums\ONECurrencyEnums;

class JILIGamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {

            $path = public_path('awc_jili_games.json');

            if (!File::exists($path)) {
                // File not found
                $this->command->error("File not found: {$path}");
                return;
            }

            $fileContents = File::get($path);

            if ($fileContents) {

                $games = json_decode($fileContents, true);

                $platform = GamePlatform::where('platform_code', GamePlatformConstants::AWC_SUB_PROVIDER_JILI)->first();

                $fish_category1 = GameCategory::where('name', 'like', '%FISHING%')->first();

                $slot_category2 = GameCategory::where('name', 'like', '%SLOTS%')->first();

                if ($platform && $fish_category1 && $slot_category2) {

                    $file1 = new UploadedFile(
                        public_path('images/jili.png'),
                        'jili.png',
                        'jili/png'
                    );

                    $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

                    foreach ($games as $game) {

                        $code = $game['AWCGameCode'];

                        $exists = GameItem::where('game_id', $code)->where('game_platform_id', $platform->id)->first();

                        if ($exists) {
                            $this->command->error("Game Already Exists: {$code}");
                            continue;
                        }

                        $name = $game['EnglishName'];

                        $langs = [
                            'en' => $name,
                            'hi' => $name,
                            'tl' => $name,
                            'vn' => $name,
                        ];

                        $game_item = GameItem::create([
                            'name' => json_encode($langs),
                            'icon_square' => $file_name1,
                            'icon_rectangle' => $file_name1,
                            'game_id' => $code,
                            'game_platform_id' => $platform->id,
                            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK])
                        ]);

                        $cat_id = $slot_category2->id;

                        if ($game['GameType'] == GamePlatformConstants::AWC_SUB_PROVIDER_TYPE_FH) {

                            $cat_id = $fish_category1->id;
                        }

                        $game_item->gameCategories()->syncWithPivotValues($cat_id, ['game_item_sort_order' => 0]);
                    }

                    return;
                }

                $this->command->error("Cannot find platform or game category");
            }

            $this->command->error("No games found");
        } catch (\Throwable $e) {
            $this->command->error($e->getMessage());
        }
    }
}
