<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganisationRequest;
use App\Http\Requests\UpdateOrganisationRequest;
use App\Models\Organisation;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class OrganisationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Organisation::class);
        
        $organisations = Auth::user()->organisations()
            ->with('owner')
            ->withCount('etablissements')
            ->latest()
            ->paginate(10);

        return Inertia::render('organisations/Index', ['organisations' => $organisations]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Organisation::class);
        
        return Inertia::render('organisations/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganisationRequest $request)
    {
        Gate::authorize('create', Organisation::class);
        
        $organisation = Auth::user()->organisations()->create([
            'name' => $request->name,
            'description' => $request->description,
            'email' => $request->email,
            'phone' => $request->phone,
            'website' => $request->website,
            'address' => $request->address,
            'owner_id' => Auth::id(),
        ]);

        return redirect()->route('organisations.index')
            ->with('success', 'Organisation créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Organisation $organisation)
    {
        Gate::authorize('show', $organisation);

        $organisation->load([
            'owner',
            'establishments' => fn($query) => $query->latest(),
            'users' 
        ]);
        
        return Inertia::render('organisations/Show', [
            'organisation' => $organisation
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organisation $organisation)
    {
        Gate::authorize('update', $organisation);
        
        return Inertia::render('organisations/Edit', [
            'organisation' => $organisation
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganisationRequest $request, Organisation $organisation)
    {
        Gate::authorize('update', $organisation);
        
        $organisation->update($request->validated());

        return redirect()->route('organisations.show', $organisation)
            ->with('success', 'Organisation mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organisation $organisation)
    {
        $this->authorize('delete', $organisation);
        
        $organisation->delete();

        return redirect()->route('organisations.index')
            ->with('success', 'Organisation supprimée avec succès.');
    }
}
