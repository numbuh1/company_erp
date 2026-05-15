<?php
namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        if (!auth()->user()->can('manage settings')) abort(403);

        $settings = [
            'office_name'       => AppSetting::get('office_name', ''),
            'office_ips'        => AppSetting::get('office_ips', ''),
            'office_latitude'   => AppSetting::get('office_latitude', ''),
            'office_longitude'  => AppSetting::get('office_longitude', ''),
            'office_radius_km'  => AppSetting::get('office_radius_km', '0.2'),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        if (!auth()->user()->can('manage settings')) abort(403);

        $data = $request->validate([
            'office_name'      => 'nullable|string|max:255',
            'office_ips'       => 'nullable|string|max:1000',
            'office_latitude'  => 'nullable|numeric|between:-90,90',
            'office_longitude' => 'nullable|numeric|between:-180,180',
            'office_radius_km' => 'nullable|numeric|min:0.05|max:50',
        ]);

        foreach ($data as $key => $value) {
            AppSetting::set($key, $value);
        }

        return back()->with('success', 'Settings saved.');
    }
}
