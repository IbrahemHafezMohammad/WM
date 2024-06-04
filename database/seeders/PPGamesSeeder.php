<?php

namespace Database\Seeders;

use App\Models\GameItem;
use App\Models\GameCategory;
use App\Models\GamePlatform;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use App\Constants\GamePlatformConstants;
use App\Services\Providers\ONEProvider\ONEProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Services\Providers\ONEProvider\Enums\ONECurrencyEnums;

class PPGamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {

            $FCGames = ONEProvider::getGamesList(GamePlatformConstants::ONE_SUB_PROVIDER_PRAGMATIC_PLAY, ONECurrencyEnums::PHP);

            if ($FCGames) {

                $FCGames = json_decode($FCGames, true);

                if ($FCGames['status'] == 'SC_OK') {

                    $data = $FCGames['data'];

                    $games = $data['games'];

                    $platform = GamePlatform::where('platform_code', GamePlatformConstants::ONE_SUB_PROVIDER_PRAGMATIC_PLAY)->first();

                    $slot_category = GameCategory::where('name', 'like', '%SLOTS%')->first();

                    foreach ($games as $game) {

                        if ($game[$data['headers']['categoryCode']] != 'SLOTS') {
                            continue;
                        }

                        $code = $game[$data['headers']['gameCode']];

                        $exists = GameItem::where('game_id', $code)->where('game_platform_id', $platform->id)->exists();

                        if ($exists) {
                            $this->command->error("Game Already Exists: {$code}");
                            continue;
                        }

                        $name = $game[$data['headers']['gameName']];

                        // $file1 = new UploadedFile(
                        //     public_path('images/FC.jpg'),
                        //     'FC.jpg',
                        //     'image/jpg'
                        // );

                        // $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

                        $file_name1 = $game[$data['headers']['imageSquare']];

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
                            'supported_currencies' => GameItem::calcCurrencies([GlobalConstants::CURRENCY_PHP, GlobalConstants::CURRENCY_VNDK]),
                            'status' => Config::get('app.env') === 'production' ? 0 : 1,
                        ]);

                        $game_item->gameCategories()->syncWithPivotValues($slot_category->id, ['game_item_sort_order' => 0]);
                    }

                    return;
                }
            }

            $this->command->error("No games found");
        } catch (\Throwable $e) {
            $this->command->error($e->getMessage());
        }
    }
}
