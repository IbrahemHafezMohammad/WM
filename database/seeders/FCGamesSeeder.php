<?php

namespace Database\Seeders;

use App\Models\GameItem;
use App\Models\GamePlatform;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use App\Constants\GlobalConstants;
use Illuminate\Support\Facades\Storage;
use App\Constants\GamePlatformConstants;
use App\Services\Providers\ONEProvider\ONEProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Services\Providers\ONEProvider\Enums\ONECurrencyEnums;

class FCGamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {

            $FCGames = ONEProvider::getGamesList(GamePlatformConstants::ONE_SUB_PROVIDER_FA_CHAI, ONECurrencyEnums::PHP);

            if ($FCGames) {

                $FCGames = json_decode($FCGames, true);

                if ($FCGames['status'] == 'SC_OK') {

                    $data = $FCGames['data'];

                    $games = $data['games'];

                    $platform = GamePlatform::where('platform_code', GamePlatformConstants::ONE_SUB_PROVIDER_FA_CHAI)->first();

                    $lobby = GameItem::where('game_id', GamePlatformConstants::ONE_GAME_CODE_FA_CHAI_LOBBY)->first();
                    
                    $lobbyCategoryIds = $lobby->gameCategories->pluck('id');

                    foreach ($games as $game) {

                        $code = $game[$data['headers']['gameCode']];

                        $exists = GameItem::where('game_id', $code)->where('game_platform_id', $platform->id)->first();

                        if ($exists) {
                            $this->command->error("Game Already Exists: {$code}");
                            continue;
                        }

                        $name = $game[$data['headers']['gameName']];
                        
                        $file1 = new UploadedFile(
                            public_path('images/FC.jpg'),
                            'FC.jpg',
                            'image/jpg'
                        );
                
                        $file_name1 = Storage::putFile(GlobalConstants::GAME_ITEM_IMAGES_PATH, $file1);

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
                
                        $game_item->gameCategories()->syncWithPivotValues($lobbyCategoryIds, ['game_item_sort_order' => 0]);
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
