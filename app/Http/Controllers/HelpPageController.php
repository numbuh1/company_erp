<?php

namespace App\Http\Controllers;

use App\Models\HelpPage;
use App\Models\HelpPageComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;

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
        $helpPages = HelpPage::withCount('components')->latest()->get();
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
            'title'                      => 'required|string|max:255',
            'route'                      => 'nullable|string|max:255|unique:help_pages,route',
            'is_active'                  => 'boolean',
            'components'                 => 'array',
            'components.*.type'          => 'required|in:text,text_with_image',
            'components.*.sort_order'    => 'required|integer|min:0',
            'components.*.image'         => 'nullable|string|max:500',
            'components.*.contents'      => 'array',
            'components.*.contents.*'    => 'nullable|string',
        ]);

        $helpPage = HelpPage::create([
            'title'     => $data['title'],
            'route'     => filled($data['route'] ?? null) ? $data['route'] : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->syncComponents($helpPage, $data['components'] ?? []);

        return redirect()->route('admin.help-pages.index')
            ->with('success', __('Help page created.'));
    }

    public function edit(HelpPage $helpPage)
    {
        $this->authorize();
        $helpPage->load('components.contents');
        $routes = $this->availableRoutes();
        return view('admin.help-pages.form', compact('helpPage', 'routes'));
    }

    public function update(Request $request, HelpPage $helpPage)
    {
        $this->authorize();

        $data = $request->validate([
            'title'                      => 'required|string|max:255',
            'route'                      => 'nullable|string|max:255|unique:help_pages,route,' . $helpPage->id,
            'is_active'                  => 'boolean',
            'components'                 => 'array',
            'components.*.id'            => 'nullable|integer',
            'components.*.type'          => 'required|in:text,text_with_image',
            'components.*.sort_order'    => 'required|integer|min:0',
            'components.*.image'         => 'nullable|string|max:500',
            'components.*.contents'      => 'array',
            'components.*.contents.*'    => 'nullable|string',
        ]);

        $helpPage->update([
            'title'     => $data['title'],
            'route'     => filled($data['route'] ?? null) ? $data['route'] : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->syncComponents($helpPage, $data['components'] ?? []);

        return redirect()->route('admin.help-pages.index')
            ->with('success', __('Help page updated.'));
    }

    private function syncComponents(HelpPage $helpPage, array $componentsData): void
    {
        $existingIds = $helpPage->components()->pluck('id')->all();
        $keepIds = [];

        foreach ($componentsData as $compData) {
            $compId = $compData['id'] ?? null;
            $attrs = [
                'type'       => $compData['type'],
                'sort_order' => $compData['sort_order'],
                'image'      => $compData['type'] === 'text_with_image' ? ($compData['image'] ?? null) : null,
            ];

            if ($compId && in_array($compId, $existingIds)) {
                $component = HelpPageComponent::findOrFail($compId);
                $component->update($attrs);
            } else {
                $component = $helpPage->components()->create($attrs);
            }

            $keepIds[] = $component->id;

            foreach ($compData['contents'] ?? [] as $locale => $content) {
                $component->contents()->updateOrCreate(
                    ['locale' => $locale],
                    ['help_page_id' => $helpPage->id, 'content' => $content ?? '']
                );
            }
        }

        $helpPage->components()->whereNotIn('id', $keepIds)->delete();
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
        return response()->json(['url' => asset('storage/' . $path)]);
    }

    // ── User-facing ────────────────────────────────────────────

    public function userIndex()
    {
        $helpPages = HelpPage::where('is_active', true)->orderBy('title')->get();
        return view('help-pages.index', compact('helpPages'));
    }

    public function show(HelpPage $helpPage)
    {
        if (! $helpPage->is_active) abort(404);
        $helpPage->load('components.contents');
        $locale = app()->getLocale();
        return view('help-pages.show', compact('helpPage', 'locale'));
    }
}
