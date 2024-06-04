<?php

namespace Database\Seeders;

use App\Models\GameItem;
use App\Models\GamePlatform;
use Illuminate\Database\Seeder;
use App\Constants\GlobalConstants;
use App\Constants\GamePlatformConstants;
use App\Services\Providers\ONEProvider\ONEProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Services\Providers\ONEProvider\Enums\ONECurrencyEnums;

class CQ9GamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {

            $CQ9Games = ONEProvider::getGamesList(GamePlatformConstants::ONE_SUB_PROVIDER_CQ9, ONECurrencyEnums::PHP);

            if ($CQ9Games) {

                $CQ9Games = json_decode($CQ9Games, true);

                if ($CQ9Games['status'] == 'SC_OK') {

                    $data = $CQ9Games['data'];

                    $games = $data['games'];

                    $platform = GamePlatform::where('platform_code', GamePlatformConstants::ONE_SUB_PROVIDER_CQ9)->first();

                    $lobby = GameItem::where('game_id', GamePlatformConstants::ONE_GAME_CODE_CQ9_LOBBY)->first();
                    
                    $lobbyCategoryIds = $lobby->gameCategories->pluck('id');

                    foreach ($games as $game) {

                        $code = $game[$data['headers']['gameCode']];

                        $exists = GameItem::where('game_id', $code)->where('game_platform_id', $platform->id)->first();

                        if ($exists) {
                            $this->command->error("Game Already Exists: {$code}");
                            continue;
                        }

                        $name = $game[$data['headers']['gameName']];
                        
                        $mage = $game[$data['headers']['imageSquare']];

                        $langs = [
                            'en' => $name,
                            'hi' => $name,
                            'tl' => $name,
                            'vn' => $name,
                        ];

                        $game_item = GameItem::create([
                            'name' => json_encode($langs),
                            'icon_square' => $mage,
                            'icon_rectangle' => $mage,
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
