<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
use Illuminate\Database\Seeder;
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
use App\Models\Setting;
use Spatie\Permission\Models\Permission;
use App\Services\PaymentService\PaymentServiceEnum;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            GameSeeder::class,
        ]);

        $password = '1234qweR';
        //add permissions category
        $name = [
            "player" => [
                "View Player List",
                "View Player Phone Number",
                "View Player Login History",
                "Create Player",
                "View Player Banks",
                "Create Player Banks",
                "Reset Player Password"
            ]
        ];

        Setting::create([
            'key' => 'GAME_AUTH_TOKEN',
            'value' => ""
        ]);

        $permission_category = PermissionCategory::create(['name' => json_encode($name)]);

        //add admins
        $user1 = User::create([
            'user_name' => 'ali',
            'password' => $password,
            'name' => 'ali',
            'phone' => '+963992861407',
        ]);

        Admin::create([
            'user_id' => $user1->id
        ]);

        $user2 = User::create([
            'user_name' => 'sadiq',
            'password' => $password,
            'name' => 'sadiq',
            'phone' => '+963992861408',
        ]);

        Admin::create([
            'user_id' => $user2->id
        ]);

        $user3 = User::create([
            'user_name' => 'jerry',
            'password' => $password,
            'name' => 'jerry',
            'phone' => '+963992861409',
        ]);

        Admin::create([
            'user_id' => $user3->id
        ]);

        $user4 = User::create([
            'user_name' => 'shibi',
            'password' => $password,
            'name' => 'shibi',
            'phone' => '+963992861410',
        ]);

        Admin::create([
            'user_id' => $user4->id
        ]);

        $user5 = User::create([
            'user_name' => 'sarah',
            'password' => $password,
            'name' => 'sarah',
            'phone' => '+963992861411',
        ]);

        Admin::create([
            'user_id' => $user5->id
        ]);

        $user6 = User::create([
            'user_name' => 'stephen',
            'password' => $password,
            'name' => 'stephen',
            'phone' => '+963992861412',
        ]);

        Admin::create([
            'user_id' => $user6->id
        ]);

        $user7 = User::create([
            'user_name' => 'silver',
            'password' => $password,
            'name' => 'silver',
            'phone' => '+971553654159',
        ]);

        Admin::create([
            'user_id' => $user7->id
        ]);

        $user8 = User::create([
            'user_name' => 'ibrahem',
            'password' => $password,
            'name' => 'ibrahem',
            'phone' => '+971556184605',
        ]);

        Admin::create([
            'user_id' => $user8->id
        ]);

        $user9 = User::create([
            'user_name' => 'harry',
            'password' => $password,
            'name' => 'harry',
            'phone' => '+84752016876',
        ]);

        Admin::create([
            'user_id' => $user9->id
        ]);

        $user10 = User::create([
            'user_name' => 'testrisk1',
            'password' => $password,
            'name' => 'testrisk1',
            'phone' => '+84752015556',
        ]);

        Admin::create([
            'user_id' => $user10->id
        ]);

        $user11 = User::create([
            'user_name' => 'testrisk2',
            'password' => $password,
            'name' => 'testrisk2',
            'phone' => '+84752011256',
        ]);

        Admin::create([
            'user_id' => $user11->id
        ]);

        $user12 = User::create([
            'user_name' => 'testrisk3',
            'password' => $password,
            'name' => 'testrisk3',
            'phone' => '+84752489256',
        ]);

        Admin::create([
            'user_id' => $user12->id
        ]);

        $user13 = User::create([
            'user_name' => 'testrisk4',
            'password' => $password,
            'name' => 'testrisk4',
            'phone' => '+84752325626',
        ]);

        Admin::create([
            'user_id' => $user13->id
        ]);

        $user14 = User::create([
            'user_name' => 'admin_jack',
            'password' => $password,
            'name' => 'jack',
            'phone' => '+84752326969',
        ]);

        Admin::create([
            'user_id' => $user14->id
        ]);

        $user15 = User::create([
            'user_name' => 'kevin_fe',
            'password' => $password,
            'name' => 'kevin',
            'phone' => '+84752458769',
        ]);

        Admin::create([
            'user_id' => $user15->id
        ]);

        //add permissions labels
        $labels = [
            'Create Admin' => [
                "en" => "Create Admin",
            ],
            'View Player List' => [
                "en" => "View Player List",
            ],
            'View Player Phone Number' => [
                "en" => "View Player Phone Number",
            ],
            'View Player Login History' => [
                "en" => "View Player Login History",
            ],
            'Create Player' => [
                "en" => "Create Player",
            ],
            'Reset Player Password' => [
                "en" => "Reset Player Password",
            ],
            'View Promotions' => [
                "en" => "View Promotions",
            ],
            'View Game Transaction Histories' => [
                "en" => "View Game Transaction Histories",
            ],
            'Finance Approve/Reject Withdraw' => [
                "en" => "Finance Approve/Reject Withdraw",
            ],
            'View Transactions Requests' => [
                "en" => "View Transactions Requests",
            ],
            'View Agents' => [
                "en" => "View Agents",
            ],
            'Add New Agent' => [
                "en" => "Add New Agent",
            ],
            'Approve/Reject Risk' => [
                "en" => "Approve/Reject Risk",
            ],
            'View Admin Login History' => [
                "en" => "View Admin Login History",
            ],
            'Change Player Agent' => [
                "en" => "Change Player Agent",
            ],
            'View Agents Change Histories' => [
                "en" => "View Agents Change Histories",
            ],
            'Create Game Category' => [
                "en" => "Create Game Category",
            ],
            'Create Promotion Category' => [
                "en" => "Create Promotion Category",
            ],
            'View Game Categories' => [
                "en" => "View Game Categories",
            ],

            'View Promotion Categories' => [
                "en" => "View Promotion Categories",
            ],
            'Create Game Item' => [
                "en" => "Create Game Item",
            ],
            'View Game Lists' => [
                "en" => "View Game Lists",
            ],
            'Change Player Balance' => [
                "en" => "Change Player Balance",
            ],
            'View IP Whitelist' => [
                "en" => "View IP Whitelist",
            ],
            'Create IP Whitelist' => [
                "en" => "Create IP Whitelist",
            ],
            'View Permission' => [
                "en" => "View Permission",
            ],
            'Add Permission' => [
                "en" => "Add Permission",
            ],
            'Update IP Whitelist' => [
                "en" => "Update IP Whitelist",
            ],
            'Delete IP Whitelist' => [
                "en" => "Delete IP Whitelist",
            ],
            'Access from any IP' => [
                "en" => "Access from any IP",
            ],
            'FA Lock Withdraw' => [
                "en" => "FA Lock Withdraw",
            ],
            'Finance Approve/Reject Deposit' => [
                "en" => "Finance Approve/Reject Deposit",
            ],
            'Create Payment Method' => [
                "en" => "Create Payment Method",
            ],
            'View Payment Methods' => [
                "en" => "View Payment Methods",
            ],
            'Adjust Payment Method Settings' => [
                "en" => "Adjust Payment Method Settings",
            ],
            'Create Bank Code' => [
                "en" => "Create Bank Code",
            ],
            'Create Payment Category' => [
                "en" => "Create Payment Category",
            ],
            'Update Payment Category' => [
                "en" => "Update Payment Category",
            ],
            'Payment Category Status Toggle' => [
                "en" => "Payment Category Status Toggle",
            ],
            'Create User Payment Method' => [
                "en" => "Create User Payment Method"
            ],
            'Update User Payment Method' => [
                "en" => "Update User Payment Method"
            ],
            'User Payment Method Status Toggle' => [
                "en" => "User Payment Method Status Toggle"
            ],
            'View Users' => [
                "en" => "View Users"
            ],
            'View Dashboard' => [
                "en" => "View Dashboard"
            ],
            'View Player Banks' => [
                "en" => "View Player Banks",
            ],
            'Create Player Banks' => [
                "en" => "Create Player Banks"
            ],
            'Add/Remove Points' => [
                "en" => "Add/Remove Points",
            ]
            
        ];

        $permissions = [];
        foreach ($labels as $permissionName => $labelArray) {
            $labelArrayWithLangCode = [$labelArray['en']];
            $permission = Permission::create([
                'name' => $permissionName,
                'label' => json_encode($labelArrayWithLangCode),
                'permission_category_id' => $permission_category->id,
            ]);
            $permissions[] = $permission;
        }

        // Create admin role and sync permissions
        $role = Role::create(['name' => 'admin']);
        $role->syncPermissions($permissions);

        $users = [$user1, $user2, $user3, $user4, $user5, $user6, $user7, $user8, $user9, $user10, $user11, $user12, $user13, $user14];
        foreach ($users as $user) {
            $user->assignRole('admin');
        }

        //add promotion categories

        $file1 = new UploadedFile(
            public_path('images/promo-cat.png'),
            'POPULAR.png',
            'image/png'
        );

        $file_name1 = Storage::putFile(GlobalConstants::PROMOTION_CATEGORY_IMAGES_PATH, $file1);

        PromotionCategory::create([
            'name' => ["en" => "CAT 1", "hi" => "लोकप्रिय"],
            'sort_order' => PromotionCategory::max('sort_order') ? PromotionCategory::max('sort_order') + 1 : 1,
            'is_active' => true,
            'icon_image' => $file_name1,
            'icon_image_desktop' => $file_name1,
        ]);

        PromotionCategory::create([
            'name' => ["en" => "NEW"],
            'sort_order' => PromotionCategory::max('sort_order') ? PromotionCategory::max('sort_order') + 1 : 1,
            'is_active' => true,
            'icon_image' => $file_name1,
            'icon_image_desktop' => $file_name1,
        ]);

        // add promotions

        $file1 = new UploadedFile(
            public_path('images/promo.jpg'),
            'POPULAR.jpg',
            'image/jpg'
        );

        $file_name1 = Storage::putFile(GlobalConstants::PROMOTIONS_IMAGES_PATH, $file1);

        $promotion = Promotion::create([
            'title' => 'promo1',
            'status' => true,
            'country' => GlobalConstants::COUNTRY_PHIL,
            'image' => $file_name1,
            'desktop_image' => $file_name1,
            'body' => "<p>Popular config options:
            selector
            plugins
            toolbar
            menubar
            height
            content_css
            Read more about TinyMCE config options in the TinyMCE docs.<p>
            <p>
            If you’re not sure what options to configure, copy the snippet into your app so you can start using the editor. You can always change the config options later.</p>
            <img src='$file_name1' alt='image' />
            <p>3. You’re ready to use TinyMCE!
            At this point, the editor is installed and ready to use in your app.

            To retrieve content from TinyMCE, either process the content with a form handler or use the getContent() API method.

            To use TinyMCE on domains other than localhost add your domain to the Approved Domains page in the Tiny Cloud dashboard.</p>",
            'start_date' => '2024-02-14 09:54:13',
            'end_date' => '2024-02-16 09:54:13',
        ]);

        $promotion->promotionCategories()->syncWithPivotValues([1], ['promotion_sort_order' => 1]);

        $promotion = Promotion::create([
            'title' => 'promo2',
            'status' => false,
            'country' => GlobalConstants::COUNTRY_PHIL,
            'image' => $file_name1,
            'desktop_image' => $file_name1,
            'body' => "Popular config options:
            selector
            plugins
            toolbar
            menubar
            height
            content_css
            Read more about TinyMCE config options in the TinyMCE docs.

            If you’re not sure what options to configure, copy the snippet into your app so you can start using the editor. You can always change the config options later.

            3. You’re ready to use TinyMCE!
            At this point, the editor is installed and ready to use in your app.

            To retrieve content from TinyMCE, either process the content with a form handler or use the getContent() API method.

            To use TinyMCE on domains other than localhost add your domain to the Approved Domains page in the Tiny Cloud dashboard.",
            'start_date' => '2024-02-14 09:54:13',
            'end_date' => '2024-02-16 09:54:13',
        ]);

        $promotion->promotionCategories()->syncWithPivotValues([2], ['promotion_sort_order' => 2]);

        $promotion = Promotion::create([
            'title' => 'promo3',
            'status' => true,
            'country' => GlobalConstants::COUNTRY_PHIL,
            'image' => $file_name1,
            'desktop_image' => $file_name1,
            'body' => "Popular config options:
            selector
            plugins
            toolbar
            menubar
            height
            content_css
            Read more about TinyMCE config options in the TinyMCE docs.

            If you’re not sure what options to configure, copy the snippet into your app so you can start using the editor. You can always change the config options later.

            3. You’re ready to use TinyMCE!
            At this point, the editor is installed and ready to use in your app.

            To retrieve content from TinyMCE, either process the content with a form handler or use the getContent() API method.

            To use TinyMCE on domains other than localhost add your domain to the Approved Domains page in the Tiny Cloud dashboard.",
            'start_date' => '2024-02-16 09:54:13',
            'end_date' => '2024-02-18 09:54:13',
        ]);

        $promotion->promotionCategories()->syncWithPivotValues([1], ['promotion_sort_order' => 3]);

        $promotion = Promotion::create([
            'title' => 'promo4',
            'status' => true,
            'country' => GlobalConstants::COUNTRY_VTNM,
            'image' => $file_name1,
            'desktop_image' => $file_name1,
            'body' => "Popular config options:
            selector
            plugins
            toolbar
            menubar
            height
            content_css
            Read more about TinyMCE config options in the TinyMCE docs.

            If you’re not sure what options to configure, copy the snippet into your app so you can start using the editor. You can always change the config options later.

            3. You’re ready to use TinyMCE!
            At this point, the editor is installed and ready to use in your app.

            To retrieve content from TinyMCE, either process the content with a form handler or use the getContent() API method.

            To use TinyMCE on domains other than localhost add your domain to the Approved Domains page in the Tiny Cloud dashboard.",
            'start_date' => null,
            'end_date' => '2024-02-18 09:54:13',
        ]);

        $promotion->promotionCategories()->syncWithPivotValues([1], ['promotion_sort_order' => 4]);

        $promotion = Promotion::create([
            'title' => 'promo5',
            'status' => true,
            'country' => GlobalConstants::COUNTRY_PHIL,
            'image' => $file_name1,
            'desktop_image' => $file_name1,
            'body' => "Popular config options:
            selector
            plugins
            toolbar
            menubar
            height
            content_css
            Read more about TinyMCE config options in the TinyMCE docs.

            If you’re not sure what options to configure, copy the snippet into your app so you can start using the editor. You can always change the config options later.

            3. You’re ready to use TinyMCE!
            At this point, the editor is installed and ready to use in your app.

            To retrieve content from TinyMCE, either process the content with a form handler or use the getContent() API method.

            To use TinyMCE on domains other than localhost add your domain to the Approved Domains page in the Tiny Cloud dashboard.",
            'start_date' => '2024-02-14 09:54:13',
            'end_date' => null,
        ]);

        $promotion->promotionCategories()->syncWithPivotValues([2], ['promotion_sort_order' => 5]);

        $promotion = Promotion::create([
            'title' => 'promo6',
            'status' => true,
            'country' => GlobalConstants::COUNTRY_PHIL,
            'image' => $file_name1,
            'desktop_image' => $file_name1,
            'body' => "Popular config options:
            selector
            plugins
            toolbar
            menubar
            height
            content_css
            Read more about TinyMCE config options in the TinyMCE docs.

            If you’re not sure what options to configure, copy the snippet into your app so you can start using the editor. You can always change the config options later.

            3. You’re ready to use TinyMCE!
            At this point, the editor is installed and ready to use in your app.

            To retrieve content from TinyMCE, either process the content with a form handler or use the getContent() API method.

            To use TinyMCE on domains other than localhost add your domain to the Approved Domains page in the Tiny Cloud dashboard.",
            'start_date' => null,
            'end_date' => null,
        ]);

        $promotion->promotionCategories()->syncWithPivotValues([1], ['promotion_sort_order' => 6]);

        $promotion = Promotion::create([
            'title' => 'promo7',
            'status' => true,
            'country' => GlobalConstants::COUNTRY_PHIL,
            'image' => $file_name1,
            'desktop_image' => $file_name1,
            'body' => "Popular config options:
            selector
            plugins
            toolbar
            menubar
            height
            content_css
            Read more about TinyMCE config options in the TinyMCE docs.

            If you’re not sure what options to configure, copy the snippet into your app so you can start using the editor. You can always change the config options later.

            3. You’re ready to use TinyMCE!
            At this point, the editor is installed and ready to use in your app.

            To retrieve content from TinyMCE, either process the content with a form handler or use the getContent() API method.

            To use TinyMCE on domains other than localhost add your domain to the Approved Domains page in the Tiny Cloud dashboard.",
            'start_date' => '2024-02-16 09:54:13',
            'end_date' => null,
        ]);

        $promotion->promotionCategories()->syncWithPivotValues([1], ['promotion_sort_order' => 7]);

        
        //add players

        $file = new UploadedFile(
            public_path('images/profile.webp'),
            'profile.webp',
            'image/webp'
        );

        $file_name = Storage::putFile(GlobalConstants::USER_IMAGES_PATH, $file);

        $player_user = User::create([
            'user_name' => 'gameplayer0001',
            'password' => $password,
            'name' => 'Tom',
            'profile_pic' => $file_name,
            'phone' => '+9715544184605'
        ]);

        $player = Player::create([
            'user_id' => $player_user->id,
        ]);

        Wallet::create([
            'player_id' => $player->id,
            'base_balance' => 97.2,
            'currency' => GlobalConstants::CURRENCY_PHP,
        ]);

        $player_user = User::create([
            'user_name' => 'gameplayer0002',
            'password' => $password,
            'name' => 'Test',
            'profile_pic' => $file_name,
            'phone' => '+971556184606'
        ]);

        $player = Player::create([
            'user_id' => $player_user->id,
        ]);

        Wallet::create([
            'player_id' => $player->id,
            'base_balance' => 0.72,
            'currency' => GlobalConstants::CURRENCY_PHP,
        ]);

        $player_user = User::create([
            'user_name' => 'gameplayer0003',
            'password' => $password,
            'name' => 'Ibrahem',
            'profile_pic' => $file_name,
            'phone' => '+971556184607'
        ]);

        $player = Player::create([
            'user_id' => $player_user->id,
        ]);

        Wallet::create([
            'player_id' => $player->id,
            'base_balance' => 97.2,
            'currency' => GlobalConstants::CURRENCY_PHP,
        ]);

        $player_user = User::create([
            'user_name' => 'gameplayer0004',
            'password' => $password,
            'name' => 'Stephen',
            'profile_pic' => $file_name,
            'phone' => '+971556184608'
        ]);

        $player = Player::create([
            'user_id' => $player_user->id,
        ]);

        Wallet::create([
            'player_id' => $player->id,
            'base_balance' => 97.2,
            'currency' => GlobalConstants::CURRENCY_PHP,
        ]);

        $player_user = User::create([
            'user_name' => 'gameplayer0005',
            'password' => $password,
            'name' => 'Sadiq',
            'profile_pic' => $file_name,
            'phone' => '+971556184609'
        ]);

        $player = Player::create([
            'user_id' => $player_user->id,
        ]);

        Wallet::create([
            'player_id' => $player->id,
            'base_balance' => 97.2,
            'currency' => GlobalConstants::CURRENCY_PHP,
        ]);

        $player_user = User::create([
            'user_name' => 'gameplayer0006',
            'password' => $password,
            'name' => 'Ali',
            'profile_pic' => $file_name,
            'phone' => '+971556184610'
        ]);

        $player = Player::create([
            'user_id' => $player_user->id,
        ]);

        Wallet::create([
            'player_id' => $player->id,
            'base_balance' => 97.2,
            'currency' => GlobalConstants::CURRENCY_PHP,
        ]);

        $player_user = User::create([
            'user_name' => 'gameplayer0007',
            'password' => $password,
            'name' => 'Hasona',
            'profile_pic' => $file_name,
            'phone' => '+963936227921'
        ]);

        $player = Player::create([
            'user_id' => $player_user->id,
        ]);

        Wallet::create([
            'player_id' => $player->id,
            'base_balance' => 97.2,
            'currency' => GlobalConstants::CURRENCY_PHP,
        ]);

        $player_user = User::create([
            'user_name' => 'gameplayer0008',
            'password' => $password,
            'name' => 'test',
            'profile_pic' => $file_name,
            'phone' => '+963936227922'
        ]);

        $player = Player::create([
            'user_id' => $player_user->id,
        ]);

        Wallet::create([
            'player_id' => $player->id,
            'base_balance' => 97.2,
            'currency' => GlobalConstants::CURRENCY_PHP,
        ]);

        $player_user = User::create([
            'user_name' => 'gameplayer0009',
            'password' => $password,
            'name' => 'new player',
            'profile_pic' => $file_name,
            'phone' => '+963936227369'
        ]);

        $player = Player::create([
            'user_id' => $player_user->id,
        ]);

        Wallet::create([
            'player_id' => $player->id,
            'base_balance' => 97.2,
            'currency' => GlobalConstants::CURRENCY_PHP,
        ]);

        $player_user = User::create([
            'user_name' => 'gameplayer0010',
            'password' => $password,
            'name' => 'new player',
            'profile_pic' => $file_name,
            'phone' => '+963936233369'
        ]);

        $player = Player::create([
            'user_id' => $player_user->id,
        ]);

        Wallet::create([
            'player_id' => $player->id,
            'base_balance' => 97.2,
            'currency' => GlobalConstants::CURRENCY_PHP,
        ]);



        // add payment category

        $icons = [
            'en_icon' => $file_name,
            'hi_icon' => $file_name,
            'tl_icon' => $file_name,
            'vn_icon' => $file_name,
        ];

        $public_names = [
            'en_public_name' => 'E-Wallet',
            'hi_public_name' => 'E-Wallet',
            'tl_public_name' => 'E-Wallet',
            'vn_public_name' => 'E-Wallet',
        ];

        $ai_payment_cat = PaymentCategory::create([
            'name' => 'E-Wallet',
            'icon' => $icons,
            'public_name' => $public_names,
            'is_enabled' => true,
        ]);

        // $icons = [
        //     'en_icon' => $file_name,
        //     'hi_icon' => $file_name,
        //     'tl_icon' => $file_name,
        //     'vn_icon' => $file_name,
        // ];

        // $public_names = [
        //     'en_public_name' => 'Bank payment',
        //     'hi_public_name' => 'Bank payment',
        //     'tl_public_name' => 'Bank payment',
        //     'vn_public_name' => 'Bank payment',
        // ];

        // $bank_payment_cat = PaymentCategory::create([
        //     'name' => 'Bank payment',
        //     'icon' => $icons,
        //     'public_name' => $public_names,
        //     'is_enabled' => true,
        // ]);


        //add bank code
        //     $file = new UploadedFile(
        //         public_path('images/bank_code.jpg'),
        //         'bank_code.jpg',
        //         'image/jpg'
        //     );

        //     $file_name = Storage::putFile(GlobalConstants::PAYMENT_METHOD_IMAGES_PATH, $file);

        //     $public_names = [
        //         'en_public_name' => 'VietcomBank',
        //         'hi_public_name' => 'VietcomBank',
        //         'tl_public_name' => 'VietcomBank',
        //         'vn_public_name' => 'VietcomBank',
        //     ];

        //     $techcombank_bank = BankCode::create([
        //         'code' => BankCodeConstants::CODE_TECHCOMBANK,
        //         'image' => $file_name,
        //         'public_name' => $public_names,
        //         'display_for_players' => true,
        //         'status' => true,
        //         'payment_category_id' => $bank_payment_cat->id
        //     ]);

        //     $public_names = [
        //         'en_public_name' => 'DongABank',
        //         'hi_public_name' => 'DongABank',
        //         'tl_public_name' => 'DongABank',
        //         'vn_public_name' => 'DongABank',
        //     ];

        //     $dongabank = BankCode::create([
        //         'code' => BankCodeConstants::CODE_DONGABANK,
        //         'image' => $file_name,
        //         'public_name' => $public_names,
        //         'display_for_players' => true,
        //         'status' => true,
        //         'payment_category_id' => $bank_payment_cat->id
        //    ]);

        //     $public_names = [
        //         'en_public_name' => 'ACB',
        //         'hi_public_name' => 'ACB',
        //         'tl_public_name' => 'ACB',
        //         'vn_public_name' => 'ACB',
        //     ];

        //     $acbbank = BankCode::create([
        //         'code' => BankCodeConstants::CODE_ACBBANK,
        //         'image' => $file_name,
        //         'public_name' => $public_names,
        //         'display_for_players' => true,
        //         'status' => true,
        //         'payment_category_id' => $bank_payment_cat->id
        //     ]);

        //     $public_names = [
        //         'en_public_name' => 'TechomBank',
        //         'hi_public_name' => 'TechomBank',
        //         'tl_public_name' => 'TechomBank',
        //         'vn_public_name' => 'TechomBank',
        //     ];

        //     $tpbank = BankCode::create([
        //         'code' => BankCodeConstants::CODE_TPBANK,
        //         'image' => $file_name,
        //         'public_name' => $public_names,
        //         'display_for_players' => true,
        //         'status' => true,
        //         'payment_category_id' => $bank_payment_cat->id
        //     ]);

        $public_names = [
            'en_public_name' => 'GCASH',
            'hi_public_name' => 'GCASH',
            'tl_public_name' => 'GCASH',
            'vn_public_name' => 'GCASH',
        ];

        $gcash = BankCode::create([
            'code' => BankCodeConstants::CODE_GCASH,
            'image' => $file_name,
            'public_name' => $public_names,
            'display_for_players' => true,
            'status' => true,
            'payment_category_id' => $ai_payment_cat->id
        ]);

        //add Payment Method

        // $public_names = [
        //     'en_public_name' => 'techcombank_bank Bank payment Method 1',
        //     'vn_public_name' => 'techcombank_bank Bank payment Method 1',
        //     'tl_public_name' => 'techcombank_bank Bank payment Method 1',
        //     'hi_public_name' => 'techcombank_bank Bank payment Method 1',
        // ];

        // PaymentMethod::create([
        //     'bank_code_id' => $techcombank_bank->id,
        //     'payment_category_id' => $bank_payment_cat->id,
        //     'account_name' => 'company1',
        //     'public_name' => $public_names,
        //     'allow_deposit' => true,
        //     'allow_withdraw' => true,
        //     'balance' => 100000,
        //     'currency' => GlobalConstants::CURRENCY_PHP,
        //     'under_maintenance' => false,
        //     'payment_code' => PaymentServiceEnum::BANK->value
        // ]);

        // $public_names = [
        //     'en_public_name' => 'techcombank_bank Bank payment Method 2',
        //     'hi_public_name' => 'techcombank_bank Bank payment Method 2',
        //     'tl_public_name' => 'techcombank_bank Bank payment Method 2',
        //     'vn_public_name' => 'techcombank_bank Bank payment Method 2',
        // ];

        // PaymentMethod::create([
        //     'bank_code_id' => $techcombank_bank->id,
        //     'payment_category_id' => $bank_payment_cat->id,
        //     'account_name' => 'techcombank_bank company2',
        //     'public_name' => $public_names,
        //     'allow_deposit' => true,
        //     'allow_withdraw' => true,
        //     'balance' => 100000,
        //     'currency' => GlobalConstants::CURRENCY_VNDK,
        //     'under_maintenance' => false,
        //     'payment_code' => PaymentServiceEnum::BANK->value
        // ]);

        // $public_names = [
        //     'en_public_name' => 'dongabank Bank payment Method 1',
        //     'hi_public_name' => 'dongabank Bank payment Method 1',
        //     'tl_public_name' => 'dongabank Bank payment Method 1',
        //     'vn_public_name' => 'dongabank Bank payment Method 1',
        // ];

        // PaymentMethod::create([
        //     'bank_code_id' => $dongabank->id,
        //     'payment_category_id' => $bank_payment_cat->id,
        //     'account_name' => 'dongabank company1',
        //     'public_name' => $public_names,
        //     'allow_deposit' => true,
        //     'allow_withdraw' => true,
        //     'balance' => 100000,
        //     'currency' => GlobalConstants::CURRENCY_PHP,
        //     'under_maintenance' => false,
        //     'payment_code' => PaymentServiceEnum::BANK->value
        // ]);

        // $public_names = [
        //     'en_public_name' => 'acbbank Bank payment Method 1',
        //     'hi_public_name' => 'acbbank Bank payment Method 1',
        //     'tl_public_name' => 'acbbank Bank payment Method 1',
        //     'vn_public_name' => 'acbbank Bank payment Method 1',
        // ];

        // PaymentMethod::create([
        //     'bank_code_id' => $acbbank->id,
        //     'payment_category_id' => $bank_payment_cat->id,
        //     'account_name' => 'acbbank company1',
        //     'public_name' => $public_names,
        //     'allow_deposit' => true,
        //     'allow_withdraw' => true,
        //     'balance' => 100000,
        //     'currency' => GlobalConstants::CURRENCY_PHP,
        //     'under_maintenance' => false,
        //     'payment_code' => PaymentServiceEnum::BANK->value
        // ]);

        // $public_names = [
        //     'en_public_name' => 'acbbank Bank payment Method 2',
        //     'hi_public_name' => 'acbbank Bank payment Method 2',
        //     'tl_public_name' => 'acbbank Bank payment Method 2',
        //     'vn_public_name' => 'acbbank Bank payment Method 2',
        // ];

        // PaymentMethod::create([
        //     'bank_code_id' => $acbbank->id,
        //     'payment_category_id' => $bank_payment_cat->id,
        //     'account_name' => 'acbbank company2',
        //     'public_name' => $public_names,
        //     'allow_deposit' => true,
        //     'allow_withdraw' => true,
        //     'balance' => 100000,
        //     'currency' => GlobalConstants::CURRENCY_VNDK,
        //     'under_maintenance' => false,
        //     'payment_code' => PaymentServiceEnum::BANK->value
        // ]);

        // $public_names = [
        //     'en_public_name' => 'tpbank Bank payment Method 1',
        //     'hi_public_name' => 'tpbank Bank payment Method 1',
        //     'tl_public_name' => 'tpbank Bank payment Method 1',
        //     'vn_public_name' => 'tpbank Bank payment Method 1',
        // ];

        // PaymentMethod::create([
        //     'bank_code_id' => $tpbank->id,
        //     'payment_category_id' => $bank_payment_cat->id,
        //     'account_name' => 'tpbank company1',
        //     'public_name' => $public_names,
        //     'allow_deposit' => true,
        //     'allow_withdraw' => true,
        //     'balance' => 100000,
        //     'currency' => GlobalConstants::CURRENCY_PHP,
        //     'under_maintenance' => false,
        //     'payment_code' => PaymentServiceEnum::BANK->value
        // ]);

        $public_names = [
            'en_public_name' => 'GCASH',
            'hi_public_name' => 'GCASH',
            'tl_public_name' => 'GCASH',
            'vn_public_name' => 'GCASH',
        ];

        PaymentMethod::create([
            'bank_code_id' => $gcash->id,
            'payment_category_id' => $ai_payment_cat->id,
            'account_name' => 'GCASH third party1',
            'public_name' => $public_names,
            'allow_deposit' => true,
            'allow_withdraw' => true,
            'balance' => 100000,
            'currency' => GlobalConstants::CURRENCY_PHP,
            'under_maintenance' => false,
            'payment_code' => PaymentServiceEnum::AI->value,
            'min_deposit_amount' => 100,
            'max_deposit_amount' => 20000,
            'min_withdraw_amount' => 100,
            'max_withdraw_amount' => 50000,
        ]);

        $public_names = [
            'en_public_name' => 'TechomBank',
            'hi_public_name' => 'TechomBank',
            'tl_public_name' => 'TechomBank',
            'vn_public_name' => 'TechomBank',
        ];

        // PaymentMethod::create([
        //     'bank_code_id' => $tpbank->id,
        //     'payment_category_id' => $bank_payment_cat->id,
        //     'account_name' => 'TPBank Houng',
        //     'public_name' => $public_names,
        //     'allow_deposit' => true,
        //     'allow_withdraw' => true,
        //     'balance' => 100000,
        //     'currency' => GlobalConstants::CURRENCY_PHP,
        //     'under_maintenance' => false,
        //     'payment_code' => PaymentServiceEnum::BANK->value,
        //     'min_deposit_amount' => 1000,
        //     'max_deposit_amount' => 1000000,
        //     'min_withdraw_amount' => 1000,
        //     'max_withdraw_amount' => 80000000,
        // ]);

        // game ip whitelist

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_VIA,
            'ip_address' => '18.142.15.150'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_VIA,
            'ip_address' => '83.110.9.175'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_VIA,
            'ip_address' => '127.0.0.1'
        ]);

        //KM
        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_KM,
            'ip_address' => '18.162.160.143'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_KM,
            'ip_address' => '18.163.29.216'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_KM,
            'ip_address' => '43.198.126.108'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_KM,
            'ip_address' => '18.163.242.132'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_KM,
            'ip_address' => '83.110.9.175'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_KM,
            'ip_address' => '127.0.0.1'
        ]);

        //AWC
        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_AWC,
            'ip_address' => '83.110.9.175'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_AWC,
            'ip_address' => '127.0.0.1'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_AWC,
            'ip_address' => '1.32.212.194'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_AWC,
            'ip_address' => '52.199.178.132'
        ]);
        
        //EVO
        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_EVO,
            'ip_address' => '83.110.9.175'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_EVO,
            'ip_address' => '127.0.0.1'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_EVO,
            'ip_address' => '217.165.138.13'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_EVO,
            'ip_address' => '3.120.233.170'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_EVO,
            'ip_address' => '3.120.107.58'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_EVO,
            'ip_address' => '3.120.216.199'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_EVO,
            'ip_address' => '3.73.86.249'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_EVO,
            'ip_address' => '52.57.96.30'
        ]);

        //UG
        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_UG,
            'ip_address' => '127.0.0.1'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_UG,
            'ip_address' => '217.165.138.13'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_UG,
            'ip_address' => '1.32.212.194'
        ]);

        //CMD
        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_CMD,
            'ip_address' => '127.0.0.1'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_CMD,
            'ip_address' => '217.165.138.13'
        ]);

        ProviderIPWhitelist::create([
            'provider_code' => GamePlatformConstants::PLATFORM_CMD,
            'ip_address' => '211.23.39.182'
        ]);

        //SABA
        // ProviderIPWhitelist::create([
        //     'provider_code' => GamePlatformConstants::PLATFORM_SABA,
        //     'ip_address' => '83.110.9.175'
        // ]);

        // ProviderIPWhitelist::create([
        //     'provider_code' => GamePlatformConstants::PLATFORM_SABA,
        //     'ip_address' => '127.0.0.1'
        // ]);

        // add km config
    }
}
