<?php

namespace App\Http\Controllers;

use App\Models\PublicHoliday;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    public function index()
    {
        $this->_authorize();
        $holidays = PublicHoliday::orderBy('start_date')->get();
        return view('admin.public-holidays.index', compact('holidays'));
    }

    public function create()
    {
        $this->_authorize();
        return view('admin.public-holidays.form');
    }

    public function store(Request $request)
    {
        $this->_authorize();
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after_or_equal:start_date',
            'repeats_annually'  => 'nullable|boolean',
        ]);
        $data['repeats_annually'] = $request->boolean('repeats_annually');
        PublicHoliday::create($data);
        return redirect()->route('admin.public-holidays.index')->with('success', 'Holiday added.');
    }

    public function edit(PublicHoliday $publicHoliday)
    {
        $this->_authorize();
        return view('admin.public-holidays.form', compact('publicHoliday'));
    }

    public function update(Request $request, PublicHoliday $publicHoliday)
    {
        $this->_authorize();
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after_or_equal:start_date',
            'repeats_annually'  => 'nullable|boolean',
        ]);
        $data['repeats_annually'] = $request->boolean('repeats_annually');
        $publicHoliday->update($data);
        return redirect()->route('admin.public-holidays.index')->with('success', 'Holiday updated.');
    }

    public function destroy(PublicHoliday $publicHoliday)
    {
        $this->_authorize();
        $publicHoliday->delete();
        return redirect()->route('admin.public-holidays.index')->with('success', 'Holiday deleted.');
    }

    private function _authorize(): void
    {
        if (!auth()->user()->can('manage settings')) abort(403);
    }
}
