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
        ];

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        if (!auth()->user()->can('manage settings')) abort(403);

        $data = $request->validate([
            'office_name' => 'nullable|string|max:255',
            'office_ips'  => 'nullable|string|max:1000',
        ]);

        foreach ($data as $key => $value) {
            AppSetting::set($key, $value);
        }

        return back()->with('success', 'Settings saved.');
    }
}
