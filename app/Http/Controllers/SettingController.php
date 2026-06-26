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
            'company_name'       => AppSetting::get('company_name', ''),
            'company_address'    => AppSetting::get('company_address', ''),
            'company_phone'      => AppSetting::get('company_phone', ''),
            'office_name'        => AppSetting::get('office_name', ''),
            'office_ips'         => AppSetting::get('office_ips', ''),
            'office_latitude'    => AppSetting::get('office_latitude', ''),
            'office_longitude'   => AppSetting::get('office_longitude', ''),
            'office_radius_km'   => AppSetting::get('office_radius_km', '0.2'),
            'lunch_break_start'  => AppSetting::get('lunch_break_start', '12:00'),
            'lunch_break_end'    => AppSetting::get('lunch_break_end', '13:00'),
            'leave_balance_monthly_increase' => AppSetting::get('leave_balance_monthly_increase', '0'),
            'leave_balance_reset_month'       => AppSetting::get('leave_balance_reset_month', ''),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        if (!auth()->user()->can('manage settings')) abort(403);

        $data = $request->validate([
            'company_name'      => 'nullable|string|max:255',
            'company_address'   => 'nullable|string|max:500',
            'company_phone'     => 'nullable|string|max:50',
            'office_name'       => 'nullable|string|max:255',
            'office_ips'        => 'nullable|string|max:1000',
            'office_latitude'   => 'nullable|numeric|between:-90,90',
            'office_longitude'  => 'nullable|numeric|between:-180,180',
            'office_radius_km'  => 'nullable|numeric|min:0.05|max:50',
            'lunch_break_start' => 'nullable|date_format:H:i',
            'lunch_break_end'   => 'nullable|date_format:H:i',
            'leave_balance_monthly_increase' => 'nullable|numeric|min:0',
            'leave_balance_reset_month'       => 'nullable|integer|between:1,12',
        ]);

        foreach ($data as $key => $value) {
            AppSetting::set($key, $value);
        }

        return back()->with('success', 'Settings saved.');
    }
}
