<?php

namespace App\Constants;

class GamePlatformConstants
{
    const TABLE_NAME = 'game_platforms';

    //Providers

    const PLATFORM_SABA = 'SABA';
    const PLATFORM_V8 = 'V8';
    const PLATFORM_KM = 'KM';
    const PLATFORM_VIA = 'VIA';
    const PLATFORM_AWC = 'AWC';
    const PLATFORM_EVO = 'EVO';
    const PLATFORM_UG = 'UG';
    const PLATFORM_CMD = 'CMD';
    const PLATFORM_DS88 = 'DS88';
    const PLATFORM_PINNACLE = 'PINNACLE';
    const PLATFORM_SS = 'SS';
    const PLATFORM_DAGA = "DAGA";
    const PLATFORM_ONE = "ONE";
    const PLATFORM_GEMINI = "GEMINI";

    public static function getPlatforms()
    {

        return [
            self::PLATFORM_SABA => 'SABA Platform',
            self::PLATFORM_V8 => 'V8 Platform',
            self::PLATFORM_KM => 'KM Platform',
            self::PLATFORM_VIA => 'VIA Platform',
            self::PLATFORM_AWC => 'AWC Platform',
            self::PLATFORM_EVO => 'EVO Platform',
            self::PLATFORM_UG => 'UG Platform',
            self::PLATFORM_CMD => 'CMD Platform',
            self::PLATFORM_DS88 => 'DS88 Platform',
            self::PLATFORM_PINNACLE => 'PINNACLE Platform',
            self::PLATFORM_SS => 'SS Platform',
            self::PLATFORM_DAGA => 'DAGA Platform',
            self::PLATFORM_ONE => 'ONE Platform',
            self::PLATFORM_GEMINI => 'GEMINI Platform',
        ];
    }

    public static function getPlatform($Type)
    {
        return static::getPlatforms()[$Type] ?? null;
    }

    // SABA Provider Games
    const SABA_GAME_TYPE_SABA = 'saba';

    public static function getSABAGameTypes()
    {
        return [
            self::SABA_GAME_TYPE_SABA => 'SABA Game',
        ];
    }

    public static function getSABAGameType($Type)
    {
        return static::getSABAGameTypes()[$Type] ?? null;
    }

    // V8 Provider Games
    const V8_GAME_TYPE_V8 = 'v8';

    public static function getV8GameTypes()
    {
        return [
            self::V8_GAME_TYPE_V8 => 'V8 Game',
        ];
    }

    public static function getV8GameType($Type)
    {
        return static::getSABAGameTypes()[$Type] ?? null;
    }

    // KM Provider Games

    const KM_GAME_TYPE_FISH_PRAWN_CRAB_2 = 'Fish_Prawn_Crab_2';
    const KM_GAME_TYPE_THAI_FISH_PRAWN_CRAB = 'Thai_Fish_Prawn_Crab';
    const KM_GAME_TYPE_BELANGKAI_2 = 'Belangkai_2';
    const KM_GAME_TYPE_VIET_FISH_PRAWN_CRAB = 'Viet_Fish_Prawn_Crab';
    const KM_GAME_TYPE_DRAGON_TIGER_2 = 'Dragon_Tiger_2';
    const KM_GAME_TYPE_SICBO = 'Sicbo';
    const KM_GAME_TYPE_POKER_ROULETTE = 'Poker_Roulette';
    const KM_GAME_TYPE_7_UP_7_DOWN = '7_Up_7_Down';
    const KM_GAME_TYPE_FRUIT_ROULETTE = 'Fruit_Roulette';
    const KM_GAME_TYPE_BACCARAT = 'Baccarat';
    const KM_GAME_TYPE_BLACKJACK = 'Blackjack';
    const KM_GAME_TYPE_SUGAR_BLAST = 'Sugar_Blast';
    const KM_GAME_TYPE_5_CARD_POKER = '5_Card_Poker';
    const KM_GAME_TYPE_KINGMAKER_POK_DENG = 'Kingmaker_Pok_Deng';
    const KM_GAME_TYPE_PAI_KANG = 'Pai_Kang';
    const KM_GAME_TYPE_TEEN_PATTI = 'Teen_Patti';
    const KM_GAME_TYPE_BOLA_TANGKAS = 'Bola_Tangkas';
    const KM_GAME_TYPE_LUDO = 'Ludo';
    const KM_GAME_TYPE_TONGITS = 'Tongits';

    public static function getKMGameTypes()
    {
        return [
            self::KM_GAME_TYPE_FISH_PRAWN_CRAB_2 => 'Fish Prawn Crab 2',
            self::KM_GAME_TYPE_THAI_FISH_PRAWN_CRAB => 'Thai Fish Prawn Crab',
            self::KM_GAME_TYPE_BELANGKAI_2 => 'Belangkai 2',
            self::KM_GAME_TYPE_VIET_FISH_PRAWN_CRAB => 'Vietnam Fish Prawn Crab',
            self::KM_GAME_TYPE_DRAGON_TIGER_2 => 'Dragon Tiger 2',
            self::KM_GAME_TYPE_SICBO => 'Sicbo',
            self::KM_GAME_TYPE_POKER_ROULETTE => 'Poker Roulette',
            self::KM_GAME_TYPE_7_UP_7_DOWN => '7 Up 7 Down',
            self::KM_GAME_TYPE_FRUIT_ROULETTE => 'Fruit Roulette',
            self::KM_GAME_TYPE_BACCARAT => 'Baccarat',
            self::KM_GAME_TYPE_BLACKJACK => 'Blackjack',
            self::KM_GAME_TYPE_SUGAR_BLAST => 'Sugar Blast',
            self::KM_GAME_TYPE_5_CARD_POKER => '5 Card Poker',
            self::KM_GAME_TYPE_KINGMAKER_POK_DENG => 'Kingmaker Pok Deng',
            self::KM_GAME_TYPE_PAI_KANG => 'Pai Kang',
            self::KM_GAME_TYPE_TEEN_PATTI => 'Teen Patti',
            self::KM_GAME_TYPE_BOLA_TANGKAS => 'Bola Tangkas',
            self::KM_GAME_TYPE_LUDO => 'LUDO',
            self::KM_GAME_TYPE_TONGITS => 'Tongits',
        ];
    }

    public static function getKMGameType($Type)
    {
        return static::getKMGameTypes()[$Type] ?? null;
    }

    // VIA Provider Games
    const VIA_GAME_CODE_LOBBY = 'VIA_LOBBY';
    const VIA_GAME_CODE_BACCARAT60S = 'BACCARAT60S';
    const VIA_GAME_CODE_BACCARAT30S = 'BACCARAT30S';
    const VIA_GAME_CODE_TPBCRT60S = 'TPBCRT60S';
    const VIA_GAME_CODE_PAIRBCRT60S = 'PAIRBCRT60S';
    const VIA_GAME_CODE_SUPERSIX60S = 'SUPERSIX60S';
    const VIA_GAME_CODE_DT60S = 'DT60S';
    const VIA_GAME_CODE_SB60S = 'SB60S';
    const VIA_GAME_CODE_POKER60S = 'POKER60S';
    const VIA_GAME_CODE_RU60S = 'RU60S';
    const VIA_GAME_CODE_ARU60S = 'ARU60S';

    public static function getVIAGameTypes()
    {
        return [
            self::VIA_GAME_CODE_LOBBY => 'VIA LOBBY',
            self::VIA_GAME_CODE_BACCARAT60S => 'Classic Baccarat',
            self::VIA_GAME_CODE_BACCARAT30S => 'Speed ​​Baccarat',
            self::VIA_GAME_CODE_TPBCRT60S => 'Lucky Natural',
            self::VIA_GAME_CODE_PAIRBCRT60S => 'Pair Baccarat',
            self::VIA_GAME_CODE_SUPERSIX60S => 'Wealth SuperSix',
            self::VIA_GAME_CODE_DT60S => 'Dragon Tiger',
            self::VIA_GAME_CODE_SB60S => 'Classic Sic Bo',
            self::VIA_GAME_CODE_POKER60S => 'poker',
            self::VIA_GAME_CODE_RU60S => 'Classic Roulette',
            self::VIA_GAME_CODE_ARU60S => 'Auto Roulette',
        ];
    }

    public static function getVIAGameType($Type)
    {
        return static::getVIAGameTypes()[$Type] ?? null;
    }

    // EVO Provider Games
    const EVO_GAME_CODE_LOBBY = 'EVO_LOBBY';
    const EVO_GAME_CODE_FRENCH_ROULETTE_GOLD = 'oldksovn4laaaj5g';
    const EVO_GAME_CODE_BACCARAT_SW = 'rev2xgz6sjfqam4i';
    const EVO_GAME_CODE_THREE_CARD_POKER = 'n5emwq5c5dwepwam';
    const EVO_GAME_CODE_CARIBBEAN_STUD_POKER = 'CSPTable00000001';

    public static function getEVOGameTypes()
    {
        return [
            self::EVO_GAME_CODE_LOBBY => 'EVO LOBBY',
            self::EVO_GAME_CODE_FRENCH_ROULETTE_GOLD => 'French Roulette Gold',
            self::EVO_GAME_CODE_BACCARAT_SW => 'Baccarat SW',
            self::EVO_GAME_CODE_THREE_CARD_POKER => 'Three Card Poker',
            self::EVO_GAME_CODE_CARIBBEAN_STUD_POKER => 'Caribbean Stud Poker',
        ];
    }

    public static function getEVOGameType($Type)
    {
        return static::getEVOGameTypes()[$Type] ?? null;
    }

    // UG Provider Games
    const UG_GAME_CODE_LOBBY = 'UG_LOBBY';

    public static function getUGGameTypes()
    {
        return [
            self::UG_GAME_CODE_LOBBY => 'UG LOBBY',
        ];
    }

    public static function getUGGameType($Type)
    {
        return static::getUGGameTypes()[$Type] ?? null;
    }

    // SS Provider Games
    const SS_GAME_CODE_LOBBY = 'SS_LOBBY';

    public static function getSSGameTypes()
    {
        return [
            self::SS_GAME_CODE_LOBBY => 'SS LOBBY',
        ];
    }

    public static function getSSGameType($Type)
    {
        return static::getSSGameTypes()[$Type] ?? null;
    }

    // ONE Sub Provider Games

    const ONE_SUB_PROVIDER_PRAGMATIC_PLAY = 'PP';
    const ONE_SUB_PROVIDER_EVOLIVE = 'EVOLIVE';
    const ONE_SUB_PROVIDER_JILI = 'JL';
    const ONE_SUB_PROVIDER_PG_SOFT = 'PGS';
    const ONE_SUB_PROVIDER_CQ9 = 'CQ9';
    const ONE_SUB_PROVIDER_FA_CHAI = 'FC';
    const ONE_SUB_PROVIDER_SPINIX = 'SPNX';
    const ONE_SUB_PROVIDER_SPADE_GAMING = 'SPDG';
    const ONE_SUB_PROVIDER_JDB = 'JDB';
    const ONE_SUB_PROVIDER_JOKER = 'JOK';
    const ONE_SUB_PROVIDER_DC_HACKSAW = 'HSG';
    const ONE_SUB_PROVIDER_JDB_GTF = 'JDB-GTF';
    const ONE_SUB_PROVIDER_MICRO_GAMING = 'MG';
    const ONE_SUB_PROVIDER_DC_RELAX_GAMING = 'RG';
    const ONE_SUB_PROVIDER_ALIZE = 'ALG';
    const ONE_SUB_PROVIDER_BNG = 'BNG';
    const ONE_SUB_PROVIDER_B_GAMING = 'BG';
    const ONE_SUB_PROVIDER_EZUGI = 'EZUGI';
    const ONE_SUB_PROVIDER_WINFINITY = 'WIFY';
    const ONE_SUB_PROVIDER_PLAY_NGO = 'PNG';
    const ONE_SUB_PROVIDER_YELLOW_BAT = 'YBNGO';
    const ONE_SUB_PROVIDER_IFG_3OAK = 'IFG';
    const ONE_SUB_PROVIDER_SPRIBE = 'SPB';
    const ONE_SUB_PROVIDER_I_LOVE_U = 'ILU';
    const ONE_SUB_PROVIDER_AP_ENGLISH = 'AP';
    const ONE_SUB_PROVIDER_YEE_BET = 'YB';
    const ONE_SUB_PROVIDER_TADA = 'TD';
    const ONE_SUB_PROVIDER_HACKSAW = 'HSD';
    const ONE_SUB_PROVIDER_ASKMESLOT = 'AMBS';

    public static function getONESubProviders()
    {
        return [
            self::ONE_SUB_PROVIDER_PRAGMATIC_PLAY => 'PRAGMATIC PLAY',
            self::ONE_SUB_PROVIDER_EVOLIVE => 'EVOLIVE',
            self::ONE_SUB_PROVIDER_JILI => 'JILI',
            self::ONE_SUB_PROVIDER_PG_SOFT => 'PG SOFT',
            self::ONE_SUB_PROVIDER_CQ9 => 'CQ9',
            self::ONE_SUB_PROVIDER_FA_CHAI => 'FA CHAI',
            self::ONE_SUB_PROVIDER_SPINIX => 'SPINIX',
            self::ONE_SUB_PROVIDER_SPADE_GAMING => 'SPADE GAMING',
            self::ONE_SUB_PROVIDER_JDB => 'JDB',
            self::ONE_SUB_PROVIDER_JOKER => 'JOKER',
            self::ONE_SUB_PROVIDER_DC_HACKSAW => 'DC HACKSAW',
            self::ONE_SUB_PROVIDER_JDB_GTF => 'JDB GTF',
            self::ONE_SUB_PROVIDER_MICRO_GAMING => 'MICRO GAMING',
            self::ONE_SUB_PROVIDER_DC_RELAX_GAMING => 'DC RELAX GAMING',
            self::ONE_SUB_PROVIDER_ALIZE => 'ALIZE',
            self::ONE_SUB_PROVIDER_BNG => 'BNG',
            self::ONE_SUB_PROVIDER_B_GAMING => 'B GAMING',
            self::ONE_SUB_PROVIDER_EZUGI => 'EZUGI',
            self::ONE_SUB_PROVIDER_WINFINITY => 'WINFINITY',
            self::ONE_SUB_PROVIDER_PLAY_NGO => 'PLAY N GO',
            self::ONE_SUB_PROVIDER_YELLOW_BAT => 'YELLOW BAT',
            self::ONE_SUB_PROVIDER_IFG_3OAK => 'IFG 3OAK',
            self::ONE_SUB_PROVIDER_SPRIBE => 'SPRIBE',
            self::ONE_SUB_PROVIDER_I_LOVE_U => 'I LOVE U',
            self::ONE_SUB_PROVIDER_AP_ENGLISH => 'AP ENGLISH',
            self::ONE_SUB_PROVIDER_YEE_BET => 'YEE BET',
            self::ONE_SUB_PROVIDER_TADA => 'TADA',
            self::ONE_SUB_PROVIDER_HACKSAW => 'HACKSAW',
            self::ONE_SUB_PROVIDER_ASKMESLOT => 'ASKMESLOT',
        ];
    }

    // ONE Provider Games

    const ONE_GAME_CODE_PP_LOBBY = 'PP_LOBBY';
    const ONE_GAME_CODE_DRAGON_TIGER = 'PP_1001';
    const ONE_GAME_CODE_SPACEMAN = 'PP_1301';
    const ONE_GAME_CODE_AMERICAN_BLACKJACK = 'PP_bjmb';

    const ONE_GAME_CODE_EVOLIVE_LOBBY = 'EVOLIVE_LOBBY';

    const ONE_GAME_CODE_JL_LOBBY = 'JL_LOBBY';

    const ONE_GAME_CODE_PG_SOFT_LOBBY = 'PGS_LOBBY';
    const ONE_GAME_CODE_HONEY_TRAP = 'PGS_1';
    const ONE_GAME_CODE_CANDY_BONANZA = 'PGS_100';
    const ONE_GAME_CODE_RISE_OF_APOLLO = 'PGS_101';

    const ONE_GAME_CODE_CQ9_LOBBY = 'CQ9_LOBBY';
    const ONE_GAME_CODE_FRUIT_KING = 'CQ9_1';
    const ONE_GAME_CODE_LUCKY_BATS = 'CQ9_10';
    const ONE_GAME_CODE_FOOTBALL_FEVER_M = 'CQ9_GB13';

    const ONE_GAME_CODE_FA_CHAI_LOBBY = 'FC_LOBBY';
    const ONE_GAME_CODE_MONKEY_KING_FISHING = 'FC_21003';
    const ONE_GAME_CODE_STAR_HUNTER = 'FC_21008';
    const ONE_GAME_CODE_ANIMAL_RACING = 'FC_22024';

    const ONE_GAME_CODE_SPINIX_LOBBY = 'SPNX_LOBBY';
    const ONE_GAME_CODE_OUTLAW_RICH = 'SPNX_5f4f26f96f589e3bd48ec18e';
    const ONE_GAME_CODE_SPIRIT_SPREAD = 'SPNX_60586144969a692c186055c5';
    const ONE_GAME_CODE_VAULT_OF_DEVIL = 'SPNX_60c81b1d5b4be52b6cf62466';
    const ONE_GAME_CODE_SUPER_FISHING_RUSH = 'SPNX_60c81c5f39da8a0ba85ebd7b';
    const ONE_GAME_CODE_SUPER_HILO_ISLAND = 'SPNX_632a85f2220bd54d70f78328';
    const ONE_GAME_CODE_SUPER_SIC_BO = 'SPNX_632a860e220bd54d70f78329';

    const ONE_GAME_CODE_SPADE_GAMING_LOBBY = 'SPDG_LOBBY';
    const ONE_GAME_CODE_BICYCLE_RACE = 'SPDG_A-DB04';
    const ONE_GAME_CODE_DIRT_BIKE = 'SPDG_A-DB05';
    const ONE_GAME_CODE_SEXY_VEGAS = 'SPDG_S-VB01';
    const ONE_GAME_CODE_ALIEN_HUNTER = 'SPDG_F-AH01';
    const ONE_GAME_CODE_FISHING_WAR = 'SPDG_F-SF02';

    const ONE_GAME_CODE_JDB_LOBBY = 'JDB_LOBBY';
    const ONE_GAME_CODE_KINGSMAN = 'JDB_0_14016';
    const ONE_GAME_CODE_GLAMOROUS_GIRL = 'JDB_14067';
    const ONE_GAME_CODE_DRAGON_FISHING = 'JDB_7001';
    const ONE_GAME_CODE_TONGBI_NIU_NIU = 'JDB_18_18001';
    const ONE_GAME_CODE_ROBBERY_NIU_NIU = 'JDB_18_18002';

    const ONE_GAME_CODE_JOKER_LOBBY = 'JOK_LOBBY';
    const ONE_GAME_CODE_LIGHTNING_GOD = 'JOK_1ru5x5zx7us6r';
    const ONE_GAME_CODE_FLAMES_OF_FORTUNE = 'JOK_3erm9p7wssiks';
    const ONE_GAME_CODE_FISH_PRAWN_CRAB = 'JOK_p91iknyjba8oa';
    const ONE_GAME_CODE_ALADDIN = 'JOK_113qm5xnhxoqn';

    const ONE_GAME_CODE_DC_HACKSAW_LOBBY = 'HSG_LOBBY';
    const ONE_GAME_CODE_STICK_EM = 'HSG_201053';
    const ONE_GAME_CODE_XPANDER = 'HSG_201067';

    const ONE_GAME_CODE_JDB_GTF_LOBBY = 'JDB-GTF_LOBBY';
    const ONE_GAME_CODE_ROMA = 'JDB-GTF_66001';
    const ONE_GAME_CODE_GOLDEN_ACE = 'JDB-GTF_66009';

    const ONE_GAME_CODE_MICRO_GAMING_LOBBY = 'MG_LOBBY';
    const ONE_GAME_CODE_WD_FUWA_FISHING = 'MG_SFG_WDFuWaFishing';
    const ONE_GAME_CODE_777_ROYAL_WHEEL = 'MG_SMG_777RoyalWheel';
    const ONE_GAME_CODE_9_MASKS_ON_FIRE = 'MG_SMG_9masksOfFire';

    const ONE_GAME_CODE_DC_RELAX_GAMING_LOBBY = 'RG_LOBBY';
    const ONE_GAME_CODE_BLACKJACK_NEO = 'RG_150186';
    const ONE_GAME_CODE_CAVEMAN_BOB = 'RG_150187';

    const ONE_GAME_CODE_ALIZE_LOBBY = 'ALG_LOBBY';
    const ONE_GAME_CODE_SPIN_WONDERLAND = 'ALG_alzspin';
    const ONE_GAME_CODE_DICE = 'ALG_dice';

    const ONE_GAME_CODE_BNG_LOBBY = 'BNG_LOBBY';
    const ONE_GAME_CODE_BOOK_OF_SUN = 'BNG_139';
    const ONE_GAME_CODE_DRAGON_PEARLS = 'BNG_151';

    const ONE_GAME_CODE_B_GAMING_LOBBY = 'BG_LOBBY';
    const ONE_GAME_CODE_ALL_LUCKY_CLOVERS = 'BG_AllLuckyClover';
    const ONE_GAME_CODE_ALOHA_KING_ELVIS = 'BG_AlohaKingElvis';

    const ONE_GAME_CODE_EZUGI_LOBBY = 'EZUGI_LOBBY';
    const ONE_GAME_CODE_GOLD_BLACKJACK_5  = 'EZUGI_1';
    const ONE_GAME_CODE_ITALIAN_ROULETTE  = 'EZUGI_1000';
    const ONE_GAME_CODE_ITALIAN_BACCARAT  = 'EZUGI_100';

    const ONE_GAME_CODE_WINFINITY_LOBBY = 'WIFY_LOBBY';
    const ONE_GAME_CODE_ALL_AMERICAN_RD_EMULATOR_ROULETTE = 'WIFY_5fce5447e260c9bc0bcd9865';
    const ONE_GAME_CODE_ALL_VENICE_BLACK_JACK_4 = 'WIFY_636100f85b022aff5dde191a';

    const ONE_GAME_CODE_PLAY_NGO_LOBBY = 'PNG_LOBBY';
    const ONE_GAME_CODE_ALL_BELL_OF_FORTUNE = 'PNG_105';
    const ONE_GAME_CODE_ALL_CATS_AND_CASH = 'PNG_193';

    const ONE_GAME_CODE_YELLOW_BAT_LOBBY = 'YBNGO_LOBBY';
    const ONE_GAME_CODE_DRAGONOVA = 'YBNGO_1_1001';
    const ONE_GAME_CODE_OCEAN_PHOENIX = 'YBNGO_2_2001';

    const ONE_GAME_CODE_IFG_3OAK_LOBBY = 'IFG_LOBBY';
    const ONE_GAME_CODE_OCEAN_15_DRAGON_PEARLS = 'IFG_oa_15_dragon_pearls';
    const ONE_GAME_CODE_OCEAN_3_COINS = 'IFG_oa_3_coins';

    const ONE_GAME_CODE_SPRIBE_LOBBY = 'SPB_LOBBY';
    const ONE_GAME_CODE_OCEAN_AVIATOR = 'SPB_aviator';
    const ONE_GAME_CODE_OCEAN_HILO = 'SPB_hi-lo';

    const ONE_GAME_CODE_I_LOVE_U_LOBBY = 'ILU_LOBBY';
    const ONE_GAME_CODE_OCEAN_JUNGLE = 'ILU_2562';
    const ONE_GAME_CODE_OCEAN_CHRISTMAS = 'ILU_2577';

    const ONE_GAME_CODE_AP_ENGLISH_LOBBY = 'AP_LOBBY';
    const ONE_GAME_CODE_BATTLE_OF_HEROES = 'AP_10001';
    const ONE_GAME_CODE_COUNTER_TERRORISTS = 'AP_10002';

    const ONE_GAME_CODE_YEE_BET_LOBBY = 'YB_LOBBY';
    const ONE_GAME_CODE_ALL_BRIGHT_ROULETTE_ROU05 = 'YB_2781_stg';
    const ONE_GAME_CODE_ALL_WHITE_ROULETTE_ROU03 = 'YB_2692';

    const ONE_GAME_CODE_TADA_LOBBY = 'TD_LOBBY';
    const ONE_GAME_CODE_ROYAL_FISHING = 'TD_1';
    const ONE_GAME_CODE_COLOR_PREDICTION = 'TD_204';
    const ONE_GAME_CODE_BLACKJACK = 'TD_219';
    const ONE_GAME_CODE_WITCHES_NIGHT = 'TD_226';

    const ONE_GAME_CODE_HACKSAW_LOBBY = 'HSD_LOBBY';
    const ONE_GAME_CODE_OM_NOM = 'HSD_1043';

    const ONE_GAME_CODE_ASKMESLOT_LOBBY = 'AMBS_LOBBY';
    const ONE_GAME_CODE_ANGRY_WIN = 'AMBS_ANGRYBIRD';

    public static function getONEProviderLobbies()
    {
        return [
            self::ONE_SUB_PROVIDER_PRAGMATIC_PLAY => self::ONE_GAME_CODE_PP_LOBBY,
            self::ONE_SUB_PROVIDER_EVOLIVE => self::ONE_GAME_CODE_EVOLIVE_LOBBY,
            self::ONE_SUB_PROVIDER_JILI => self::ONE_GAME_CODE_JL_LOBBY,
            self::ONE_SUB_PROVIDER_PG_SOFT => self::ONE_GAME_CODE_PG_SOFT_LOBBY,
            self::ONE_SUB_PROVIDER_CQ9 => self::ONE_GAME_CODE_CQ9_LOBBY,
            self::ONE_SUB_PROVIDER_FA_CHAI => self::ONE_GAME_CODE_FA_CHAI_LOBBY,
            self::ONE_SUB_PROVIDER_SPINIX => self::ONE_GAME_CODE_SPINIX_LOBBY,
            self::ONE_SUB_PROVIDER_SPADE_GAMING => self::ONE_GAME_CODE_SPADE_GAMING_LOBBY,
            self::ONE_SUB_PROVIDER_JDB => self::ONE_GAME_CODE_JDB_LOBBY,
            self::ONE_SUB_PROVIDER_JOKER => self::ONE_GAME_CODE_JOKER_LOBBY,
            self::ONE_SUB_PROVIDER_DC_HACKSAW => self::ONE_GAME_CODE_DC_HACKSAW_LOBBY,
            self::ONE_SUB_PROVIDER_JDB_GTF => self::ONE_GAME_CODE_JDB_GTF_LOBBY,
            self::ONE_SUB_PROVIDER_MICRO_GAMING => self::ONE_GAME_CODE_MICRO_GAMING_LOBBY,
            self::ONE_SUB_PROVIDER_DC_RELAX_GAMING => self::ONE_GAME_CODE_DC_RELAX_GAMING_LOBBY,
            self::ONE_SUB_PROVIDER_ALIZE => self::ONE_GAME_CODE_ALIZE_LOBBY,
            self::ONE_SUB_PROVIDER_BNG => self::ONE_GAME_CODE_BNG_LOBBY,
            self::ONE_SUB_PROVIDER_B_GAMING => self::ONE_GAME_CODE_B_GAMING_LOBBY,
            self::ONE_SUB_PROVIDER_EZUGI => self::ONE_GAME_CODE_EZUGI_LOBBY,
            self::ONE_SUB_PROVIDER_WINFINITY => self::ONE_GAME_CODE_WINFINITY_LOBBY,
            self::ONE_SUB_PROVIDER_PLAY_NGO => self::ONE_GAME_CODE_PLAY_NGO_LOBBY,
            self::ONE_SUB_PROVIDER_YELLOW_BAT => self::ONE_GAME_CODE_YELLOW_BAT_LOBBY,
            self::ONE_SUB_PROVIDER_IFG_3OAK => self::ONE_GAME_CODE_IFG_3OAK_LOBBY,
            self::ONE_SUB_PROVIDER_SPRIBE => self::ONE_GAME_CODE_SPRIBE_LOBBY,
            self::ONE_SUB_PROVIDER_I_LOVE_U => self::ONE_GAME_CODE_I_LOVE_U_LOBBY,
            self::ONE_SUB_PROVIDER_AP_ENGLISH => self::ONE_GAME_CODE_AP_ENGLISH_LOBBY,
            self::ONE_SUB_PROVIDER_YEE_BET => self::ONE_GAME_CODE_YEE_BET_LOBBY,
            self::ONE_SUB_PROVIDER_TADA => self::ONE_GAME_CODE_TADA_LOBBY,
            self::ONE_SUB_PROVIDER_HACKSAW => self::ONE_GAME_CODE_HACKSAW_LOBBY,
            self::ONE_SUB_PROVIDER_ASKMESLOT => self::ONE_GAME_CODE_ASKMESLOT_LOBBY,
        ];
    }

    public static function getONEProviderLobby($provider)
    {
        return static::getONEProviderLobbies()[$provider] ?? null;
    }

    public static function getONEGameTypes()
    {
        return [
            self::ONE_GAME_CODE_HONEY_TRAP => 'HONEY_TRAP',
            self::ONE_GAME_CODE_CANDY_BONANZA => 'CANDY_BONANZA',
            self::ONE_GAME_CODE_RISE_OF_APOLLO => 'RISE_OF_APOLLO',
            self::ONE_GAME_CODE_FRUIT_KING => 'FRUIT_KING',
            self::ONE_GAME_CODE_LUCKY_BATS => 'LUCKY_BATS',
            self::ONE_GAME_CODE_FOOTBALL_FEVER_M => 'FOOTBALL_FEVER_M',
            self::ONE_GAME_CODE_MONKEY_KING_FISHING => 'MONKEY_KING_FISHING',
            self::ONE_GAME_CODE_STAR_HUNTER => 'STAR_HUNTER',
            self::ONE_GAME_CODE_ANIMAL_RACING => 'ANIMAL_RACING',
            self::ONE_GAME_CODE_OUTLAW_RICH => 'OUTLAW_RICH',
            self::ONE_GAME_CODE_SPIRIT_SPREAD => 'SPIRIT_SPREAD',
            self::ONE_GAME_CODE_VAULT_OF_DEVIL => 'VAULT_OF_DEVIL',
            self::ONE_GAME_CODE_SUPER_FISHING_RUSH => 'SUPER_FISHING_RUSH',
            self::ONE_GAME_CODE_SUPER_HILO_ISLAND => 'SUPER_HILO_ISLAND',
            self::ONE_GAME_CODE_SUPER_SIC_BO => 'SUPER_SIC_BO',
            self::ONE_GAME_CODE_BICYCLE_RACE => 'BICYCLE',
            self::ONE_GAME_CODE_DIRT_BIKE => 'DIRT_BIKE',
            self::ONE_GAME_CODE_SEXY_VEGAS => 'SEXY_VEGAS',
            self::ONE_GAME_CODE_ALIEN_HUNTER => 'ALIEN_HUNTER',
            self::ONE_GAME_CODE_FISHING_WAR => 'FISHING_WAR',
            self::ONE_GAME_CODE_KINGSMAN => 'KINGSMAN',
        ];
    }


    public static function getONEGameType($Type)
    {
        return static::getONEGameTypes()[$Type] ?? null;
    }

    // Pinnacle Provider Games
    const PINNACLE_GAME_CODE_LOBBY = 'PINNACLE_LOBBY';

    public static function getPinnacleGameTypes()
    {
        return [
            self::PINNACLE_GAME_CODE_LOBBY => 'PINNACLE LOBBY',
        ];
    }

    public static function getPinnacleGameType($Type)
    {
        return static::getPinnacleGameTypes()[$Type] ?? null;
    }

    // DS888 Provider Games
    const DS888_GAME_CODE_COCKFIGHT = '1';

    public static function getDS88GameTypes()
    {
        return [
            self::DS888_GAME_CODE_COCKFIGHT => 'DS88 COCKFIGHT',
        ];
    }

    public static function getDS88GameType($Type)
    {
        return static::getDS88GameTypes()[$Type] ?? null;
    }

    // CMD Provider Games
    const CMD_GAME_CODE_LOBBY = 'CMD_LOBBY';

    public static function getCMDGameTypes()
    {
        return [
            self::CMD_GAME_CODE_LOBBY => 'CMD LOBBY',
        ];
    }

    public static function getCMDGameType($Type)
    {
        return static::getCMDGameTypes()[$Type] ?? null;
    }


    //AWC Provider Sub Providers
    const AWC_SUB_PROVIDER_SEXYBCRT = 'SEXYBCRT';
    const AWC_SUB_PROVIDER_JILI = 'JILI';
    const AWC_SUB_PROVIDER_HORSEBOOK = 'HORSEBOOK';

    public static function getAWCSubProviders()
    {
        return [
            self::AWC_SUB_PROVIDER_SEXYBCRT => 'SEXYBCRT',
            self::AWC_SUB_PROVIDER_JILI => 'JILI',
            self::AWC_SUB_PROVIDER_HORSEBOOK => 'HORSEBOOK',
        ];
    }

    public static function getAWCSubProvider($Type)
    {
        return static::getAWCSubProviders()[$Type] ?? null;
    }

    // AWC Sub Provider Types

    const AWC_SUB_PROVIDER_TYPE_LIVE = 'LIVE';
    const AWC_SUB_PROVIDER_TYPE_SLOT = 'SLOT';
    const AWC_SUB_PROVIDER_TYPE_ESPORTS = 'ESPORTS';
    const AWC_SUB_PROVIDER_TYPE_EGAME = 'EGAME';
    const AWC_SUB_PROVIDER_TYPE_FH = 'FH';
    const AWC_SUB_PROVIDER_TYPE_TABLE = 'TABLE';
    const AWC_SUB_PROVIDER_TYPE_VIRTUAL = 'VIRTUAL';
    const AWC_SUB_PROVIDER_TYPE_LOTTO = 'LOTTO';
    const AWC_SUB_PROVIDER_TYPE_BINGO = 'BINGO';

    public static function getAWCSubProviderTypes()
    {
        return [
            self::AWC_SUB_PROVIDER_TYPE_LIVE => 'LIVE',
            self::AWC_SUB_PROVIDER_TYPE_SLOT => 'SLOT',
            self::AWC_SUB_PROVIDER_TYPE_ESPORTS => 'ESPORTS',
            self::AWC_SUB_PROVIDER_TYPE_EGAME => 'EGAME',
            self::AWC_SUB_PROVIDER_TYPE_FH => 'FH',
            self::AWC_SUB_PROVIDER_TYPE_TABLE => 'TABLE',
            self::AWC_SUB_PROVIDER_TYPE_VIRTUAL => 'VIRTUAL',
            self::AWC_SUB_PROVIDER_TYPE_LOTTO => 'LOTTO',
            self::AWC_SUB_PROVIDER_TYPE_BINGO => 'BINGO',
        ];
    }

    public static function getAWCSubProviderType($Type)
    {
        return static::getAWCGameTypes()[$Type] ?? null;
    }


    // AWC Provider Games
    const AWC_GAME_CODE_AESEXY_LOBBY = 'MX-LIVE-001';
    const AWC_GAME_CODE_BACCARAT_CLASSIC = 'MX-LIVE-001';
    const AWC_GAME_CODE_BACCARAT = 'MX-LIVE-002';
    const AWC_GAME_CODE_DRAGON_TIGER = 'MX-LIVE-006';
    const AWC_GAME_CODE_ROULETTE = 'MX-LIVE-009';
    const AWC_GAME_CODE_RED_BLUE_DUEL = 'MX-LIVE-010';
    const AWC_GAME_CODE_TEEN_PATTI_2020 = 'MX-LIVE-011';
    const AWC_GAME_CODE_EXTRA_ANDAR_BAHAR = 'MX-LIVE-012';
    const AWC_GAME_CODE_THAI_HI_LO = 'MX-LIVE-014';
    const AWC_GAME_CODE_THAI_FISH_PRAWN_CRAB = 'MX-LIVE-015';
    const AWC_GAME_CODE_EXTRA_SICBO = 'MX-LIVE-016';
    const AWC_GAME_CODE_SEDIE = 'MX-LIVE-017';
    const AWC_GAME_CODE_ROYAL_FISHING = 'JILI-FISH-001';
    const AWC_GAME_CODE_BOMBING_FISHING = 'JILI-FISH-002';
    const AWC_GAME_CODE_JACKPOT_FISHING = 'JILI-FISH-003';
    const AWC_GAME_CODE_HAPPY_FISHING = 'JILI-FISH-005';
    const AWC_GAME_CODE_DRAGON_FORTUNE = 'JILI-FISH-006';
    const AWC_GAME_CODE_BOOM_LEGEND = 'JILI-FISH-008';
    const AWC_GAME_CODE_DINOSAUR_TYCOON = 'JILI-FISH-004';
    const AWC_GAME_CODE_DINOSAUR_TYCOON_2 = 'JILI-FISH-011';
    const AWC_GAME_CODE_MEGA_FISHING = 'JILI-FISH-007';
    const AWC_GAME_CODE_ALL_STAR_FISHING = 'JILI-FISH-009';
    const AWC_GAME_CODE_OCEAN_KING_JACKPOT = 'JILI-FISH-012';
    const AWC_GAME_CODE_CHARGE_BUFFALO = 'JILI-SLOT-026';
    const AWC_GAME_CODE_BOXING_KING = 'JILI-SLOT-031';
    const AWC_GAME_CODE_FORTUNE_GEMS_2 = 'JILI-SLOT-076';
    const AWC_GAME_CODE_FORTUNE_GEMS = 'JILI-SLOT-043';
    const AWC_GAME_CODE_GOLDEN_EMPIRE = 'JILI-SLOT-042';
    const AWC_GAME_CODE_MEGA_ACE = 'JILI-SLOT-051';
    const AWC_GAME_CODE_MONEY_COMING = 'JILI-SLOT-029';
    const AWC_GAME_CODE_SUPER_ACE = 'JILI-SLOT-027';
    const AWC_GAME_CODE_WILD_ACE = 'JILI-SLOT-075';
    const AWC_GAME_CODE_HOT_CHILLI = 'JILI-SLOT-002';
    const AWC_GAME_CODE_CHIN_SHI_HUANG = 'JILI-SLOT-003';
    const AWC_GAME_CODE_WAR_OF_DRAGONS = 'JILI-SLOT-004';
    const AWC_GAME_CODE_LUCKY_BALL = 'JILI-SLOT-006';
    const AWC_GAME_CODE_HAWAII_BEAUTY = 'JILI-SLOT-012';
    const AWC_GAME_CODE_COLOR_GAME = 'JILI-TABLE-023';
    const AWC_GAME_CODE_IRICH_BINGO = 'JILI-TABLE-008';

    public static function getAWCGameNames()
    {
        return [
            self::AWC_GAME_CODE_AESEXY_LOBBY => 'AE Sexy',
            self::AWC_GAME_CODE_BACCARAT_CLASSIC => 'Baccarat Classic',
            self::AWC_GAME_CODE_BACCARAT => 'Baccarat',
            self::AWC_GAME_CODE_DRAGON_TIGER => 'Dragon Tiger',
            self::AWC_GAME_CODE_ROULETTE => 'Roulette',
            self::AWC_GAME_CODE_RED_BLUE_DUEL => 'Red Blue Duel',
            self::AWC_GAME_CODE_TEEN_PATTI_2020 => 'Teen Patti 2020',
            self::AWC_GAME_CODE_EXTRA_ANDAR_BAHAR => 'Extra Andar Bahar',
            self::AWC_GAME_CODE_THAI_HI_LO => 'Thai Hi Lo',
            self::AWC_GAME_CODE_THAI_FISH_PRAWN_CRAB => 'Thai Fish Prawn Crab',
            self::AWC_GAME_CODE_EXTRA_SICBO => 'Extra Sicbo',
            self::AWC_GAME_CODE_SEDIE => 'Sedie',
            self::AWC_GAME_CODE_ROYAL_FISHING => 'Royal Fishing',
            self::AWC_GAME_CODE_BOMBING_FISHING => 'Bombing Fishing',
            self::AWC_GAME_CODE_JACKPOT_FISHING => 'Jackpot Fishing',
            self::AWC_GAME_CODE_CHARGE_BUFFALO => 'Charge Buffalo',
            self::AWC_GAME_CODE_COLOR_GAME => 'Color Game',
            self::AWC_GAME_CODE_BOXING_KING => 'Boxing King',
            self::AWC_GAME_CODE_FORTUNE_GEMS_2 => 'Fortune Gems 2',
            self::AWC_GAME_CODE_FORTUNE_GEMS => 'Fortune Gems',
            self::AWC_GAME_CODE_IRICH_BINGO => 'iRich Bingo',
            self::AWC_GAME_CODE_GOLDEN_EMPIRE => 'Golden Empire',
            self::AWC_GAME_CODE_MEGA_ACE => 'Mega Ace',
            self::AWC_GAME_CODE_MONEY_COMING => 'Money Coming',
            self::AWC_GAME_CODE_SUPER_ACE => 'Super Ace',
            self::AWC_GAME_CODE_WILD_ACE => 'Wild Ace',
            self::AWC_GAME_CODE_HAPPY_FISHING => 'HAPPY FISHING',
            self::AWC_GAME_CODE_DRAGON_FORTUNE => 'DRAGON FORTUNE',
            self::AWC_GAME_CODE_BOOM_LEGEND => 'BOOM LEGEND',
            self::AWC_GAME_CODE_DINOSAUR_TYCOON => 'DINOSAUR TYCOON',
            self::AWC_GAME_CODE_DINOSAUR_TYCOON_2 => 'DINOSAUR TYCOON 2',
            self::AWC_GAME_CODE_MEGA_FISHING => 'MEGA FISHING',
            self::AWC_GAME_CODE_ALL_STAR_FISHING => 'ALL STAR FISHING',
            self::AWC_GAME_CODE_OCEAN_KING_JACKPOT => 'OCEAN KING JACKPOT',
            self::AWC_GAME_CODE_HOT_CHILLI => 'HOT CHILLI',
            self::AWC_GAME_CODE_CHIN_SHI_HUANG => 'CHIN SHI HUANG',
            self::AWC_GAME_CODE_WAR_OF_DRAGONS => 'WAR OF DRAGONS',
            self::AWC_GAME_CODE_LUCKY_BALL => 'LUCKY BALL',
            self::AWC_GAME_CODE_HAWAII_BEAUTY => 'HAWAII BEAUTY',

        ];
    }

    public static function getAWCGameName($Type)
    {
        return static::getAWCGameNames()[$Type] ?? null;
    }

    public static function getAWCGameTypes()
    {
        return [
            self::AWC_GAME_CODE_AESEXY_LOBBY => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_BACCARAT_CLASSIC => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_BACCARAT => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_DRAGON_TIGER => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_ROULETTE => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_RED_BLUE_DUEL => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_TEEN_PATTI_2020 => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_EXTRA_ANDAR_BAHAR => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_THAI_HI_LO => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_THAI_FISH_PRAWN_CRAB => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_EXTRA_SICBO => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_SEDIE => self::AWC_SUB_PROVIDER_TYPE_LIVE,
            self::AWC_GAME_CODE_ROYAL_FISHING => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_BOMBING_FISHING => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_JACKPOT_FISHING => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_CHARGE_BUFFALO => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_COLOR_GAME => self::AWC_SUB_PROVIDER_TYPE_TABLE,
            self::AWC_GAME_CODE_BOXING_KING => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_FORTUNE_GEMS_2 => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_FORTUNE_GEMS => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_IRICH_BINGO => self::AWC_SUB_PROVIDER_TYPE_TABLE,
            self::AWC_GAME_CODE_GOLDEN_EMPIRE => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_MEGA_ACE => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_MONEY_COMING => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_SUPER_ACE => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_WILD_ACE => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_HAPPY_FISHING => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_DRAGON_FORTUNE => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_BOOM_LEGEND => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_DINOSAUR_TYCOON => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_DINOSAUR_TYCOON_2 => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_MEGA_FISHING => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_ALL_STAR_FISHING => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_OCEAN_KING_JACKPOT => self::AWC_SUB_PROVIDER_TYPE_FH,
            self::AWC_GAME_CODE_HOT_CHILLI => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_CHIN_SHI_HUANG => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_WAR_OF_DRAGONS => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_LUCKY_BALL => self::AWC_SUB_PROVIDER_TYPE_SLOT,
            self::AWC_GAME_CODE_HAWAII_BEAUTY => self::AWC_SUB_PROVIDER_TYPE_SLOT,
        ];
    }

    public static function getAWCGameType($Type)
    {
        $result = static::getAWCGameTypes()[$Type] ?? null;

        if (is_null($result)) {

            if (strpos($Type, 'FISH') !== false) {
                return self::AWC_SUB_PROVIDER_TYPE_FH;
            } elseif (strpos($Type, self::AWC_SUB_PROVIDER_TYPE_SLOT) !== false) {
                return self::AWC_SUB_PROVIDER_TYPE_SLOT;
            } elseif (strpos($Type, self::AWC_SUB_PROVIDER_TYPE_LIVE) !== false) {
                return self::AWC_SUB_PROVIDER_TYPE_LIVE;
            } elseif (strpos($Type, self::AWC_SUB_PROVIDER_TYPE_TABLE) !== false) {
                return self::AWC_SUB_PROVIDER_TYPE_TABLE;
            }
        } else {
            return $result;
        } 
    }

    // Gemini Group Type
    const GEMINI_GROUP_TYPE_BINGO                   =   'Bingo';
    const GEMINI_GROUP_TYPE_HASH                    =   'Hash';

    // Gemini Provider Games
    const GEMINI_GAME_TYPE_CARIBBEANBINGO           =   'CaribbeanBingo';
    const GEMINI_GAME_TYPE_CAVEBINGO                =   'CaveBingo';
    const GEMINI_GAME_TYPE_LOSTRUINS                =   'LostRuins';
    const GEMINI_GAME_TYPE_MULTIPLAYERCRASH         =   'MultiPlayerCrash';
    const GEMINI_GAME_TYPE_ODINBINGO                =   'OdinBingo';
    const GEMINI_GAME_TYPE_STANDALONEBLACKJACK      =   'StandAloneBlackjack';
    const GEMINI_GAME_TYPE_STANDALONEDIAMONDS       =   'StandAloneDiamonds';
    const GEMINI_GAME_TYPE_STANDALONEDICE           =   'StandAloneDice';
    const GEMINI_GAME_TYPE_STANDALONEHILO           =   'StandAloneHilo';
    const GEMINI_GAME_TYPE_STANDALONEKENO           =   'StandAloneKeno';
    const GEMINI_GAME_TYPE_STANDALONELIMBO          =   'StandAloneLimbo';
    const GEMINI_GAME_TYPE_STANDALONEMINES          =   'StandAloneMines';
    const GEMINI_GAME_TYPE_STANDALONEPLINKO         =   'StandAlonePlinko';
    const GEMINI_GAME_TYPE_STANDALONEVIDEOPOKER     =   'StandAloneVideoPoker';
    const GEMINI_GAME_TYPE_STANDALONEWHEEL          =   'StandAloneWheel';
    const GEMINI_GAME_TYPE_STEAMPUNK                =   'Steampunk';

    public static function getGeminiGameTypes()
    {
        return [
            self::GEMINI_GAME_TYPE_CARIBBEANBINGO           =>   'CaribbeanBingo',
            self::GEMINI_GAME_TYPE_CAVEBINGO                =>   'CaveBingo',
            self::GEMINI_GAME_TYPE_LOSTRUINS                =>   'LostRuins',
            self::GEMINI_GAME_TYPE_MULTIPLAYERCRASH         =>   'MultiPlayerCrash',
            self::GEMINI_GAME_TYPE_ODINBINGO                =>   'OdinBingo',
            self::GEMINI_GAME_TYPE_STANDALONEBLACKJACK      =>   'StandAloneBlackjack',
            self::GEMINI_GAME_TYPE_STANDALONEDIAMONDS       =>   'StandAloneDiamonds',
            self::GEMINI_GAME_TYPE_STANDALONEDICE           =>   'StandAloneDice',
            self::GEMINI_GAME_TYPE_STANDALONEHILO           =>   'StandAloneHilo',
            self::GEMINI_GAME_TYPE_STANDALONEKENO           =>   'StandAloneKeno',
            self::GEMINI_GAME_TYPE_STANDALONELIMBO          =>   'StandAloneLimbo',
            self::GEMINI_GAME_TYPE_STANDALONEMINES          =>   'StandAloneMines',
            self::GEMINI_GAME_TYPE_STANDALONEPLINKO         =>   'StandAlonePlinko',
            self::GEMINI_GAME_TYPE_STANDALONEVIDEOPOKER     =>   'StandAloneVideoPoker',
            self::GEMINI_GAME_TYPE_STANDALONEWHEEL          =>   'StandAloneWheel',
            self::GEMINI_GAME_TYPE_STEAMPUNK                =>   'Steampunk',
        ];
    }

    public static function getGeminiGameType($Type)
    {
        return static::getGeminiGameTypes()[$Type] ?? null;
    }

    // All Games
    public static function getAllGames()
    {
        return [
            self::SABA_GAME_TYPE_SABA => 'SABA Game',
            self::V8_GAME_TYPE_V8 => 'V8 Game',
            self::KM_GAME_TYPE_FISH_PRAWN_CRAB_2 => 'Fish Prawn Crab 2',
            self::KM_GAME_TYPE_THAI_FISH_PRAWN_CRAB => 'Thai Fish Prawn Crab',
            self::KM_GAME_TYPE_BELANGKAI_2 => 'Belangkai 2',
            self::KM_GAME_TYPE_VIET_FISH_PRAWN_CRAB => 'Vietnam Fish Prawn Crab',
            self::KM_GAME_TYPE_DRAGON_TIGER_2 => 'Dragon Tiger 2',
            self::KM_GAME_TYPE_SICBO => 'Sicbo',
            self::KM_GAME_TYPE_POKER_ROULETTE => 'Poker Roulette',
            self::KM_GAME_TYPE_7_UP_7_DOWN => '7 Up 7 Down',
            self::KM_GAME_TYPE_FRUIT_ROULETTE => 'Fruit Roulette',
            self::KM_GAME_TYPE_BACCARAT => 'Baccarat',
            self::KM_GAME_TYPE_BLACKJACK => 'Baccarat',
            self::KM_GAME_TYPE_SUGAR_BLAST => 'Sugar Blast',
            self::KM_GAME_TYPE_5_CARD_POKER => '5 Card Poker',
            self::KM_GAME_TYPE_KINGMAKER_POK_DENG => 'Kingmaker Pok Deng',
            self::KM_GAME_TYPE_PAI_KANG => 'Pai Kang',
            self::KM_GAME_TYPE_TEEN_PATTI => 'Teen Patti',
            self::KM_GAME_TYPE_BOLA_TANGKAS => 'Bola Tangkas',
            self::KM_GAME_TYPE_LUDO => 'LUDO',
            self::KM_GAME_TYPE_TONGITS => 'Tongits',
            self::VIA_GAME_CODE_LOBBY => 'VIA LOBBY',
            self::VIA_GAME_CODE_BACCARAT60S => 'Classic Baccarat',
            self::VIA_GAME_CODE_BACCARAT30S => 'Speed ​​Baccarat',
            self::VIA_GAME_CODE_TPBCRT60S => 'Lucky Natural ',
            self::VIA_GAME_CODE_PAIRBCRT60S => 'Pair Baccarat',
            self::VIA_GAME_CODE_SUPERSIX60S => 'Wealth SuperSix',
            self::VIA_GAME_CODE_DT60S => 'Dragon Tiger',
            self::VIA_GAME_CODE_SB60S => 'Classic Sic Bo',
            self::VIA_GAME_CODE_POKER60S => 'poker',
            self::VIA_GAME_CODE_RU60S => 'Classic Roulette',
            self::VIA_GAME_CODE_ARU60S => 'Auto Roulette',
            self::AWC_GAME_CODE_AESEXY_LOBBY => 'AE Sexy',
            self::AWC_GAME_CODE_BACCARAT_CLASSIC => 'Baccarat Classic',
            self::AWC_GAME_CODE_BACCARAT => 'Baccarat',
            self::AWC_GAME_CODE_DRAGON_TIGER => 'Dragon Tiger',
            self::AWC_GAME_CODE_ROULETTE => 'Roulette',
            self::AWC_GAME_CODE_RED_BLUE_DUEL => 'Red Blue Duel',
            self::AWC_GAME_CODE_TEEN_PATTI_2020 => 'Teen Patti 2020',
            self::AWC_GAME_CODE_EXTRA_ANDAR_BAHAR => 'Extra Andar Bahar',
            self::AWC_GAME_CODE_THAI_HI_LO => 'Thai Hi Lo',
            self::AWC_GAME_CODE_THAI_FISH_PRAWN_CRAB => 'Thai Fish Prawn Crab',
            self::AWC_GAME_CODE_EXTRA_SICBO => 'Extra Sicbo',
            self::AWC_GAME_CODE_SEDIE => 'Sedie',
            self::AWC_GAME_CODE_ROYAL_FISHING => 'Royal Fishing',
            self::AWC_GAME_CODE_BOMBING_FISHING => 'Bombing Fishing',
            self::AWC_GAME_CODE_JACKPOT_FISHING => 'Jackpot Fishing',
            self::AWC_GAME_CODE_CHARGE_BUFFALO => 'Charge Buffalo',
            self::AWC_GAME_CODE_COLOR_GAME => 'Color Game',
            self::AWC_GAME_CODE_BOXING_KING => 'Boxing King',
            self::AWC_GAME_CODE_FORTUNE_GEMS_2 => 'Fortune Gems 2',
            self::AWC_GAME_CODE_FORTUNE_GEMS => 'Fortune Gems',
            self::AWC_GAME_CODE_IRICH_BINGO => 'iRich Bingo',
            self::AWC_GAME_CODE_GOLDEN_EMPIRE => 'Golden Empire',
            self::AWC_GAME_CODE_MEGA_ACE => 'Mega Ace',
            self::AWC_GAME_CODE_MONEY_COMING => 'Money Coming',
            self::AWC_GAME_CODE_SUPER_ACE => 'Super Ace',
            self::AWC_GAME_CODE_WILD_ACE => 'Wild Ace',
            self::AWC_GAME_CODE_HAPPY_FISHING => 'HAPPY FISHING',
            self::AWC_GAME_CODE_DRAGON_FORTUNE => 'DRAGON FORTUNE',
            self::AWC_GAME_CODE_BOOM_LEGEND => 'BOOM LEGEND',
            self::AWC_GAME_CODE_DINOSAUR_TYCOON => 'DINOSAUR TYCOON',
            self::AWC_GAME_CODE_DINOSAUR_TYCOON_2 => 'DINOSAUR TYCOON 2',
            self::AWC_GAME_CODE_MEGA_FISHING => 'MEGA FISHING',
            self::AWC_GAME_CODE_ALL_STAR_FISHING => 'ALL STAR FISHING',
            self::AWC_GAME_CODE_OCEAN_KING_JACKPOT => 'OCEAN KING JACKPOT',
            self::AWC_GAME_CODE_HOT_CHILLI => 'HOT CHILLI',
            self::AWC_GAME_CODE_CHIN_SHI_HUANG => 'CHIN SHI HUANG',
            self::AWC_GAME_CODE_WAR_OF_DRAGONS => 'WAR OF DRAGONS',
            self::AWC_GAME_CODE_LUCKY_BALL => 'LUCKY BALL',
            self::AWC_GAME_CODE_HAWAII_BEAUTY => 'HAWAII BEAUTY',
            self::EVO_GAME_CODE_LOBBY => 'EVO LOBBY',
            self::EVO_GAME_CODE_FRENCH_ROULETTE_GOLD => 'French Roulette Gold',
            self::EVO_GAME_CODE_BACCARAT_SW => 'Baccarat SW',
            self::EVO_GAME_CODE_THREE_CARD_POKER => 'Three Card Poker',
            self::EVO_GAME_CODE_CARIBBEAN_STUD_POKER => 'Caribbean Stud Poker',
            self::UG_GAME_CODE_LOBBY => 'UG LOBBY',
            self::CMD_GAME_CODE_LOBBY => 'CMD LOBBY',

            // gimini games
            self::GEMINI_GAME_TYPE_CARIBBEANBINGO           =>   'CaribbeanBingo',
            self::GEMINI_GAME_TYPE_CAVEBINGO                =>   'CaveBingo',
            self::GEMINI_GAME_TYPE_LOSTRUINS                =>   'LostRuins',
            self::GEMINI_GAME_TYPE_MULTIPLAYERCRASH         =>   'MultiPlayerCrash',
            self::GEMINI_GAME_TYPE_ODINBINGO                =>   'OdinBingo',
            self::GEMINI_GAME_TYPE_STANDALONEBLACKJACK      =>   'StandAloneBlackjack',
            self::GEMINI_GAME_TYPE_STANDALONEDIAMONDS       =>   'StandAloneDiamonds',
            self::GEMINI_GAME_TYPE_STANDALONEDICE           =>   'StandAloneDice',
            self::GEMINI_GAME_TYPE_STANDALONEHILO           =>   'StandAloneHilo',
            self::GEMINI_GAME_TYPE_STANDALONEKENO           =>   'StandAloneKeno',
            self::GEMINI_GAME_TYPE_STANDALONELIMBO          =>   'StandAloneLimbo',
            self::GEMINI_GAME_TYPE_STANDALONEMINES          =>   'StandAloneMines',
            self::GEMINI_GAME_TYPE_STANDALONEPLINKO         =>   'StandAlonePlinko',
            self::GEMINI_GAME_TYPE_STANDALONEVIDEOPOKER     =>   'StandAloneVideoPoker',
            self::GEMINI_GAME_TYPE_STANDALONEWHEEL          =>   'StandAloneWheel',
            self::GEMINI_GAME_TYPE_STEAMPUNK                =>   'Steampunk',
        ];
    }

    // get games conversion rate

    public static function getGamesConversionRate()
    {
        return [
            self::SABA_GAME_TYPE_SABA => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => 9999999999,
                'min_amount' => 1,
                'max_amount' => 9999999999,
            ],
            self::V8_GAME_TYPE_V8 => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_FISH_PRAWN_CRAB_2 => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_THAI_FISH_PRAWN_CRAB => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_BELANGKAI_2 => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_VIET_FISH_PRAWN_CRAB => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_DRAGON_TIGER_2 => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_SICBO => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_POKER_ROULETTE => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_7_UP_7_DOWN => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_FRUIT_ROULETTE => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_BACCARAT => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_BLACKJACK => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_SUGAR_BLAST => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
            self::KM_GAME_TYPE_5_CARD_POKER => [
                'multiply_rate' => 1,
                'min_points' => 1,
                'max_points' => null,
                'min_amount' => 1,
                'max_amount' => null,
            ],
        ];
    }

    public static function getGameConversionRate($Type)
    {
        return static::getGamesConversionRate()[$Type] ?? null;
    }
}
