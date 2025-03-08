<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinkRequest;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Auth\Middleware\Authenticate;

#[Authenticate]
class LinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $links = Link::query()
            ->where('user_id', auth()->id())
            ->orderBy('position')
            ->paginate(10);

        return view('links.index', compact('links'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('links.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LinkRequest $request): RedirectResponse
    {
        $link = new Link($request->validated());
        $link->user_id = auth()->id();
        $link->save();

        return redirect()
            ->route('links.index')
            ->with('success', 'Ссылка успешно создана');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Link $link): View
    {
        $this->authorize('update', $link);
        
        return view('links.edit', compact('link'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LinkRequest $request, Link $link): RedirectResponse
    {
        $this->authorize('update', $link);
        
        $link->update($request->validated());

        return redirect()
            ->route('links.index')
            ->with('success', 'Ссылка успешно обновлена');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Link $link): RedirectResponse
    {
        $this->authorize('delete', $link);
        
        $link->delete();

        return redirect()
            ->route('links.index')
            ->with('success', 'Ссылка успешно удалена');
    }
}
