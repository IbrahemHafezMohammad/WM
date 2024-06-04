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

class OneApiSeeders extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platform_codes = GamePlatformConstants::getONEProviderLobbies();

        foreach ($platform_codes as $platform_code => $lobby) {

            try {

                $PGGames = ONEProvider::getGamesList($platform_code, ONECurrencyEnums::PHP);

                if ($PGGames) {

                    $PGGames = json_decode($PGGames, true);

                    if ($PGGames['status'] == 'SC_OK') {

                        $data = $PGGames['data'];

                        $games = $data['games'];

                        $platform = GamePlatform::where('platform_code', $platform_code)->first();

                        $lobby = GameItem::where('game_id', $lobby)->first();

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

                        continue;
                    }
                }

                $PGGames = json_encode($PGGames);
                $this->command->error("No games found for {$platform_code}, API Response: {$PGGames}");
            } catch (\Throwable $e) {
                $this->command->error($e->getMessage());
            }
        }
    }
}
