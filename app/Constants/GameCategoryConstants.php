<?php

namespace App\Constants;

class GameCategoryConstants
{
    const TABLE_NAME = 'game_categories';

    //image validation
    const ICON_IMAGE_MAX_SIZE = 10 * 1024;
    const ICON_IMAGE_WIDTH = 200;
    const ICON_IMAGE_HEIGHT = 200;
    const ICON_ACTIVE_MAX_SIZE = 10 * 1024;
    const ICON_ACTIVE_WIDTH = 200;
    const ICON_ACTIVE_HEIGHT = 200;

    const IS_ACTIVE = 1;

    //properties
    const PROPERTY_DESKTOP_SHOW = 1;
    const PROPERTY_MOBILE_SHOW = 2;
    const PROPERTY_TRENDING = 4;
    const PROPERTY_LOBBY_LAYOUT_1 = 8;
    const PROPERTY_LOBBY_LAYOUT_2 = 16;

    public static function getPropertiesName()
    {
        return [
            static::PROPERTY_DESKTOP_SHOW => 'Desktop Show',
            static::PROPERTY_MOBILE_SHOW => 'Mobile Show',
            static::PROPERTY_TRENDING => 'Trending',
            static::PROPERTY_LOBBY_LAYOUT_1 => 'Lobby Layout 1',
            static::PROPERTY_LOBBY_LAYOUT_2 => 'Lobby Layout 2',
        ];
    }

    public static function getPropertyName($propertyValue)
    {
        return static::getPropertiesName()[$propertyValue] ?? null;
    }

    public static function getProperties()
    {
        return [
            'desktop_show' => static::PROPERTY_DESKTOP_SHOW,
            'mobile_show' => static::PROPERTY_MOBILE_SHOW,
            'trending' => static::PROPERTY_TRENDING,
            'lobby_layout_1' => static::PROPERTY_LOBBY_LAYOUT_1,
            'lobby_layout_2' => static::PROPERTY_LOBBY_LAYOUT_2,
        ];
    }
}
