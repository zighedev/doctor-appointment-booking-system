<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Clinic\ClinicFilterRequest;
use App\Http\Requests\Admin\Clinic\StoreClinicRequest;
use App\Http\Requests\Admin\Clinic\UpdateClinicRequest;
use App\Http\Resources\ClinicResource;
use App\Models\Clinic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClinicController extends Controller
{
    /**
     * Display active clinics.
     */
    public function index(): AnonymousResourceCollection
    {
        $clinics = Clinic::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(15);

        return ClinicResource::collection($clinics);
    }

    /**
     * Display an active clinic.
     */
    public function show(Clinic $clinic): ClinicResource
    {
        abort_unless($clinic->is_active, 404);
        return new ClinicResource($clinic);
    }

    /**
     * Display all clinics for admin.
     */
    public function adminIndex(ClinicFilterRequest $request): AnonymousResourceCollection
    {
        $clinics = Clinic::query()
        ->when(
            $request->has('status'),
            fn ($query) => $query->where('is_active', $request->boolean('status'))
        )
        ->when(
            $request->filled('city'),
            fn ($query) => $query->where('city', 'like', '%' . $request->input('city') . '%')
        )
        ->when(
            $request->filled('state'),
            fn ($query) => $query->where('state', 'like', '%' . $request->input('state') . '%')
        )
        ->when(
            $request->filled('country'),
            fn ($query) => $query->where('country', 'like', '%' . $request->input('country') . '%')
        )
        ->orderBy('name')
        ->paginate(15)
        ->withQueryString();
        
        return ClinicResource::collection($clinics);
    }



    /**
     * Display any clinic for admin.
     */
    public function adminShow(Clinic $clinic): ClinicResource
    {
        return new ClinicResource($clinic);
    }

    /**
     * Store a newly created clinic.
     */
    public function store(StoreClinicRequest $request): JsonResponse
    {
        $clinic = Clinic::create($request->validated());

        return (new ClinicResource($clinic->refresh()))->response()->setStatusCode(201);
    }

    /**
     * Update the specified clinic.
     */
    public function update(UpdateClinicRequest $request, Clinic $clinic): ClinicResource
    {
        $clinic->update($request->validated());
        return new ClinicResource($clinic->refresh());
    }

    /**
     * Deactivate the specified clinic.
     */
    public function deactivate(Clinic $clinic): ClinicResource
    {
        $clinic->is_active = false;
        $clinic->save();
        return new ClinicResource($clinic->refresh());
    }

    /**
     * Activate the specified clinic.
     */
    public function activate(Clinic $clinic): ClinicResource
    {
        $clinic->is_active = true;
        $clinic->save();
        return new ClinicResource($clinic->refresh());
    }
}
