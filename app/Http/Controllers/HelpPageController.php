<?php

namespace App\Http\Controllers;

use App\Models\HelpPage;
use App\Models\HelpPageContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Storage;

class HelpPageController extends Controller
{
    private function authorize(): void
    {
        if (! auth()->user()->can('manage settings')) abort(403);
    }

    private function availableRoutes(): array
    {
        $skip = ['sanctum.', 'ignition.', 'debugbar.', 'telescope.', 'horizon.', '_', 'generated::', 'admin.help-pages.', 'help.', 'locale.'];

        return collect(RouteFacade::getRoutes()->getRoutesByName())
            ->keys()
            ->filter(function (string $name) use ($skip) {
                foreach ($skip as $prefix) {
                    if (str_starts_with($name, $prefix)) return false;
                }
                return true;
            })
            ->sort()
            ->values()
            ->toArray();
    }

    public function index()
    {
        $this->authorize();
        $helpPages = HelpPage::withCount('contents')->latest()->get();
        return view('admin.help-pages.index', compact('helpPages'));
    }

    public function create()
    {
        $this->authorize();
        $routes = $this->availableRoutes();
        return view('admin.help-pages.form', compact('routes'));
    }

    public function store(Request $request)
    {
        $this->authorize();

        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'route'      => 'required|string|max:255|unique:help_pages,route',
            'is_active'  => 'boolean',
            'contents'   => 'array',
            'contents.*' => 'nullable|string',
        ]);

        $helpPage = HelpPage::create([
            'title'     => $data['title'],
            'route'     => $data['route'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        foreach ($data['contents'] ?? [] as $locale => $content) {
            if (filled($content)) {
                $helpPage->contents()->create(['locale' => $locale, 'content' => $content]);
            }
        }

        return redirect()->route('admin.help-pages.index')
            ->with('success', __('Help page created.'));
    }

    public function edit(HelpPage $helpPage)
    {
        $this->authorize();
        $helpPage->load('contents');
        $routes = $this->availableRoutes();
        return view('admin.help-pages.form', compact('helpPage', 'routes'));
    }

    public function update(Request $request, HelpPage $helpPage)
    {
        $this->authorize();

        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'route'      => 'required|string|max:255|unique:help_pages,route,' . $helpPage->id,
            'is_active'  => 'boolean',
            'contents'   => 'array',
            'contents.*' => 'nullable|string',
        ]);

        $helpPage->update([
            'title'     => $data['title'],
            'route'     => $data['route'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        foreach ($data['contents'] ?? [] as $locale => $content) {
            $helpPage->contents()->updateOrCreate(
                ['locale' => $locale],
                ['content' => $content ?? '']
            );
        }

        return redirect()->route('admin.help-pages.index')
            ->with('success', __('Help page updated.'));
    }

    public function destroy(HelpPage $helpPage)
    {
        $this->authorize();
        $helpPage->delete();
        return back()->with('success', __('Help page deleted.'));
    }

    public function uploadImage(Request $request)
    {
        $this->authorize();
        $request->validate(['image' => 'required|image|max:5120']);
        $path = $request->file('image')->store('help_images', 'public');
        return response()->json(['url' => Storage::url($path)]);
    }
}
