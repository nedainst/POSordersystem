<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'nullable|string|max:255',
            'site_tagline' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:1000',
            'site_phone' => 'nullable|string|max:50',
            'site_address' => 'nullable|string|max:500',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'welcome_message' => 'nullable|string|max:500',
            'footer_text' => 'nullable|string|max:500',
            'opening_hours' => 'nullable|string|max:255',
            'wifi_password' => 'nullable|string|max:100',
            'currency_symbol' => 'nullable|string|max:10',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'favicon' => 'nullable|image|mimes:ico,png|max:512',
        ]);

        $textFields = [
            'site_name', 'site_tagline', 'site_description', 'site_phone',
            'site_address', 'tax_rate', 'primary_color', 'secondary_color',
            'accent_color', 'welcome_message', 'footer_text', 'opening_hours',
            'wifi_password', 'currency_symbol',
        ];

        foreach ($textFields as $field) {
            if ($request->has($field)) {
                SiteSetting::set($field, $request->input($field), 'text', 'general');
            }
        }

        // Handle file uploads
        $fileFields = ['site_logo', 'hero_image', 'favicon'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                // Delete old file
                $oldValue = SiteSetting::get($field);
                if ($oldValue) {
                    Storage::disk('public')->delete($oldValue);
                }
                $path = $request->file($field)->store('settings', 'public');
                SiteSetting::set($field, $path, 'image', 'general');
            }
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Pengaturan berhasil disimpan!');
    }
}
