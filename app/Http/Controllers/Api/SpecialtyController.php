<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Specialty\SpecialtyFilterRequest;
use App\Http\Requests\Admin\Specialty\StoreSpecialtyRequest;
use App\Http\Requests\Admin\Specialty\UpdateSpecialtyRequest;
use App\Http\Resources\Specialty\SpecialtyAdminResource;
use App\Http\Resources\Specialty\SpecialtyResource;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SpecialtyController extends Controller
{
    /**
     * Display active specialties.
     */
    public function index(): AnonymousResourceCollection
    {
        $specialties = Specialty::query()->where('is_active', true)->orderBy('name')->get();
        return SpecialtyResource::collection($specialties);
    }

    /**
     * Display all specialties for admin management.
     */
    public function adminIndex(SpecialtyFilterRequest $request): AnonymousResourceCollection
    {
        $specialties = Specialty::query()
            ->when(
                $request->has('status'),
                fn ($query) => $query->where('is_active', $request->boolean('status'))
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return SpecialtyAdminResource::collection($specialties);
    }

    /**
     * Display a specific specialty.
     */
    public function show(Specialty $specialty): SpecialtyResource
    {
        abort_unless($specialty->is_active, 404);
        return new SpecialtyResource($specialty);
    }

    /**
     * Display any specialty for admin management.
     */
    public function adminShow(Specialty $specialty): SpecialtyAdminResource
    {
        return new SpecialtyAdminResource($specialty);
    }

    /**
     * Create a new specialty.
     */
    public function store(StoreSpecialtyRequest $request): JsonResponse
    {
        $data = $request->validated();

        $specialty = DB::transaction(function () use ($data) {
            $specialty = new Specialty($data);
            $specialty->slug = $this->generateUniqueSlug($data['name']);
            $specialty->save();
            return $specialty;
        });

        return (new SpecialtyAdminResource($specialty->refresh()))->response()->setStatusCode(201);
    }

    /**
     * Update an existing specialty.
     */
    public function update(UpdateSpecialtyRequest $request, Specialty $specialty): SpecialtyAdminResource
    {
        $specialty->update($request->validated());
        return new SpecialtyAdminResource($specialty->refresh());
    }

    /**
     * Deactivate a specialty.
     */
    public function deactivate(Specialty $specialty): SpecialtyAdminResource
    {
        $specialty->is_active = false;
        $specialty->save();
        return new SpecialtyAdminResource($specialty->refresh());
    }

    /**
     * Activate a specialty.
     */
    public function activate(Specialty $specialty): SpecialtyAdminResource
    {
        $specialty->is_active = true;
        $specialty->save();
        return new SpecialtyAdminResource($specialty->refresh());
    }

    /**
     * Generate a unique slug.
     */
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = trim($name);
        $slug = mb_strtolower($slug, "UTF-8");
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug); 
        $slug = trim($slug, '-');

        if ($slug === '') {
            throw ValidationException::withMessages([
                'name' => ['The name cannot be converted into a valid slug.'],
            ]);
        }

        $baseSlug = $slug;
        $counter = 1;

        while (
            Specialty::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null,fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
