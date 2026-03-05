<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Table;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin Warung',
            'email' => 'admin@warung.com',
            'password' => Hash::make('password'),
        ]);

        // Site Settings
        $settings = [
            ['key' => 'site_name', 'value' => 'Warung Nusantara', 'type' => 'text', 'group' => 'general'],
            ['key' => 'site_tagline', 'value' => 'Cita Rasa Autentik Indonesia', 'type' => 'text', 'group' => 'general'],
            ['key' => 'site_description', 'value' => 'Warung makan dengan menu autentik Indonesia yang lezat dan harga terjangkau', 'type' => 'text', 'group' => 'general'],
            ['key' => 'site_phone', 'value' => '0812-3456-7890', 'type' => 'text', 'group' => 'general'],
            ['key' => 'site_address', 'value' => 'Jl. Merdeka No. 17, Jakarta', 'type' => 'text', 'group' => 'general'],
            ['key' => 'primary_color', 'value' => '#DC2626', 'type' => 'text', 'group' => 'general'],
            ['key' => 'secondary_color', 'value' => '#FFFFFF', 'type' => 'text', 'group' => 'general'],
            ['key' => 'accent_color', 'value' => '#FEE2E2', 'type' => 'text', 'group' => 'general'],
            ['key' => 'tax_rate', 'value' => '10', 'type' => 'text', 'group' => 'general'],
            ['key' => 'currency_symbol', 'value' => 'Rp', 'type' => 'text', 'group' => 'general'],
            ['key' => 'opening_hours', 'value' => 'Senin - Minggu, 08:00 - 22:00', 'type' => 'text', 'group' => 'general'],
            ['key' => 'wifi_password', 'value' => 'warung123', 'type' => 'text', 'group' => 'general'],
            ['key' => 'welcome_message', 'value' => 'Selamat datang di warung kami! Silakan scan QR code di meja untuk memesan.', 'type' => 'text', 'group' => 'general'],
            ['key' => 'footer_text', 'value' => '© 2026 Warung Nusantara. Semua hak dilindungi.', 'type' => 'text', 'group' => 'general'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::create($setting);
        }

        // Categories
        $categories = [
            ['name' => 'Makanan Utama', 'slug' => 'makanan-utama', 'description' => 'Hidangan utama yang mengenyangkan', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'Minuman', 'slug' => 'minuman', 'description' => 'Minuman segar dan hangat', 'sort_order' => 2, 'is_active' => true],
            ['name' => 'Cemilan', 'slug' => 'cemilan', 'description' => 'Camilan ringan dan gorengan', 'sort_order' => 3, 'is_active' => true],
            ['name' => 'Dessert', 'slug' => 'dessert', 'description' => 'Makanan penutup yang manis', 'sort_order' => 4, 'is_active' => true],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        // Menu Items
        $menuItems = [
            // Makanan Utama
            ['category_id' => 1, 'name' => 'Nasi Goreng Spesial', 'slug' => 'nasi-goreng-spesial', 'description' => 'Nasi goreng dengan telur, ayam, dan sayuran segar', 'price' => 25000, 'is_available' => true, 'is_featured' => true, 'sort_order' => 1],
            ['category_id' => 1, 'name' => 'Mie Goreng Jawa', 'slug' => 'mie-goreng-jawa', 'description' => 'Mie goreng bumbu Jawa dengan irisan ayam', 'price' => 22000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 2],
            ['category_id' => 1, 'name' => 'Ayam Bakar Madu', 'slug' => 'ayam-bakar-madu', 'description' => 'Ayam bakar dengan saus madu spesial + nasi', 'price' => 35000, 'is_available' => true, 'is_featured' => true, 'sort_order' => 3],
            ['category_id' => 1, 'name' => 'Soto Ayam', 'slug' => 'soto-ayam', 'description' => 'Soto ayam kuah kuning dengan nasi dan pelengkap', 'price' => 20000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 4],
            ['category_id' => 1, 'name' => 'Nasi Rendang', 'slug' => 'nasi-rendang', 'description' => 'Rendang sapi empuk dengan nasi putih hangat', 'price' => 32000, 'is_available' => true, 'is_featured' => true, 'sort_order' => 5],
            ['category_id' => 1, 'name' => 'Gado-Gado', 'slug' => 'gado-gado', 'description' => 'Sayuran segar dengan bumbu kacang spesial', 'price' => 18000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 6],
            ['category_id' => 1, 'name' => 'Nasi Campur Bali', 'slug' => 'nasi-campur-bali', 'description' => 'Nasi dengan beragam lauk khas Bali', 'price' => 30000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 7],
            ['category_id' => 1, 'name' => 'Bakso Malang', 'slug' => 'bakso-malang', 'description' => 'Bakso daging sapi dengan kuah kaldu spesial', 'price' => 20000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 8],

            // Minuman
            ['category_id' => 2, 'name' => 'Es Teh Manis', 'slug' => 'es-teh-manis', 'description' => 'Teh manis dingin yang menyegarkan', 'price' => 5000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 1],
            ['category_id' => 2, 'name' => 'Es Jeruk Segar', 'slug' => 'es-jeruk-segar', 'description' => 'Jeruk peras segar dengan es batu', 'price' => 8000, 'is_available' => true, 'is_featured' => true, 'sort_order' => 2],
            ['category_id' => 2, 'name' => 'Kopi Susu', 'slug' => 'kopi-susu', 'description' => 'Kopi susu gula aren yang creamy', 'price' => 15000, 'is_available' => true, 'is_featured' => true, 'sort_order' => 3],
            ['category_id' => 2, 'name' => 'Jus Alpukat', 'slug' => 'jus-alpukat', 'description' => 'Jus alpukat segar dengan susu coklat', 'price' => 15000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 4],
            ['category_id' => 2, 'name' => 'Air Mineral', 'slug' => 'air-mineral', 'description' => 'Air mineral botol 600ml', 'price' => 4000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 5],
            ['category_id' => 2, 'name' => 'Es Campur', 'slug' => 'es-campur', 'description' => 'Es campur dengan aneka topping buah', 'price' => 12000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 6],

            // Cemilan
            ['category_id' => 3, 'name' => 'Tahu Crispy', 'slug' => 'tahu-crispy', 'description' => 'Tahu goreng renyah dengan sambal kecap', 'price' => 10000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 1],
            ['category_id' => 3, 'name' => 'Pisang Goreng', 'slug' => 'pisang-goreng', 'description' => 'Pisang goreng crispy dengan topping keju/coklat', 'price' => 12000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 2],
            ['category_id' => 3, 'name' => 'Tempe Mendoan', 'slug' => 'tempe-mendoan', 'description' => 'Tempe mendoan khas Purwokerto', 'price' => 8000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 3],
            ['category_id' => 3, 'name' => 'Kentang Goreng', 'slug' => 'kentang-goreng', 'description' => 'Kentang goreng renyah dengan saus sambal', 'price' => 15000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 4],

            // Dessert
            ['category_id' => 4, 'name' => 'Es Krim Kelapa', 'slug' => 'es-krim-kelapa', 'description' => 'Es krim kelapa homemade', 'price' => 12000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 1],
            ['category_id' => 4, 'name' => 'Pudding Coklat', 'slug' => 'pudding-coklat', 'description' => 'Pudding coklat lembut dengan vla vanilla', 'price' => 10000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 2],
            ['category_id' => 4, 'name' => 'Klepon', 'slug' => 'klepon', 'description' => 'Klepon tradisional isi gula merah', 'price' => 8000, 'is_available' => true, 'is_featured' => false, 'sort_order' => 3],
        ];

        foreach ($menuItems as $item) {
            MenuItem::create($item);
        }

        // Tables
        for ($i = 1; $i <= 10; $i++) {
            Table::create([
                'name' => 'Meja ' . $i,
                'capacity' => $i <= 6 ? 4 : 6,
                'status' => 'available',
                'is_active' => true,
            ]);
        }
    }
}
