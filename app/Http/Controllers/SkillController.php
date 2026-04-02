<?php
namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);

        $skills = Skill::orderBy('category')->orderBy('name')->get()->groupBy('category');
        return view('skills.index', compact('skills'));
    }

    public function create()
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);
        return view('skills.form');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|string|max:100',
        ]);

        Skill::create($data);
        return redirect()->route('skills.index')->with('success', 'Skill added.');
    }

    public function edit(Skill $skill)
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);
        return view('skills.form', compact('skill'));
    }

    public function update(Request $request, Skill $skill)
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', Skill::$categories),
        ]);

        $skill->update($data);
        return redirect()->route('skills.index')->with('success', 'Skill updated.');
    }

    public function destroy(Skill $skill)
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);
        $skill->delete();
        return back()->with('success', 'Skill deleted.');
    }
}
