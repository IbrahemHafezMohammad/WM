<?php

namespace App\Constants;

class GameItemConstants
{
    const TABLE_NAME = 'game_items';

    //image validation

    const ICON_SQUARE_MAX_SIZE = 10 * 1024;
    const ICON_SQUARE_WIDTH = 300;
    const ICON_SQUARE_HEIGHT = 300;
    const ICON_RECTANGLE_MAX_SIZE = 10 * 1024;
    const ICON_RECTANGLE_WIDTH = 882;
    const ICON_RECTANGLE_HEIGHT = 554;

    const ICON_DESKTOP_SQUARE_MAX_SIZE = 10 * 1024;
    const ICON_DESKTOP_SQUARE_WIDTH = 300;
    const ICON_DESKTOP_SQUARE_HEIGHT = 300;
    const ICON_DESKTOP_RECTANGLE_MAX_SIZE = 10 * 1024;
    const ICON_DESKTOP_RECTANGLE_WIDTH = 882;
    const ICON_DESKTOP_RECTANGLE_HEIGHT = 554;

    //statuses
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;
    const STATUS_UNDER_MAINTENANCE = 3;

    public static function getStatuses()
    {
        return [
            static::STATUS_ACTIVE => 'Active',
            static::STATUS_INACTIVE => 'Inactive',
            static::STATUS_UNDER_MAINTENANCE => 'Under Maintenance'
        ];
    }

    public static function getStatus($statusValue)
    {
        return static::getStatuses()[$statusValue] ?? null;
    }

    //properties
    const PROPERTY_DESKTOP_NEW = 1;
    const PROPERTY_MOBILE_NEW = 2;
    const PROPERTY_DESKTOP_HOT = 4;
    const PROPERTY_MOBILE_HOT = 8;
    const PROPERTY_DESKTOP_POPULAR = 16;
    const PROPERTY_MOBILE_POPULAR = 32;
    const PROPERTY_TRANSFER_WALLET = 64;
    

    public static function getPropertiesName()
    {
        return [
            static::PROPERTY_DESKTOP_NEW => 'Desktop New',
            static::PROPERTY_MOBILE_NEW => 'Mobile New',
            static::PROPERTY_DESKTOP_HOT => 'Desktop Hot',
            static::PROPERTY_MOBILE_HOT => 'Mobile Hot',
            static::PROPERTY_DESKTOP_POPULAR => 'Desktop Popular',
            static::PROPERTY_MOBILE_POPULAR => 'Mobile Popular',
            static::PROPERTY_TRANSFER_WALLET => 'Transfer Wallet',
            
        ];
    }

    public static function getPropertyName($propertyValue)
    {
        return static::getPropertiesName()[$propertyValue] ?? null;
    }

    public static function getProperties()
    {
        return [
            'desktop_new' => static::PROPERTY_DESKTOP_NEW,
            'mobile_new' => static::PROPERTY_MOBILE_NEW,
            'desktop_hot' => static::PROPERTY_DESKTOP_HOT,
            'mobile_hot' => static::PROPERTY_MOBILE_HOT,
            'desktop_popular' => static::PROPERTY_DESKTOP_POPULAR,
            'mobile_popular' => static::PROPERTY_MOBILE_POPULAR,
            'transfer_wallet' => static::PROPERTY_TRANSFER_WALLET,
        ];
    }
}