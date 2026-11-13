<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\LivestockRequest;
use App\Models\CommodityCategory;
use App\Models\CommodityType;
use App\Models\Livestock;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class LivestockController extends Controller
{
    public function index(Request $request): View
    {
        $store = $this->activeStore($request);
        $categories = CommodityCategory::query()
            ->where('group', 'ternak_hidup')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $livestock = Livestock::query()
            ->with(['commodityType.category', 'media'])
            ->where('store_id', $store->id)
            ->latest()
            ->get();

        $categories->each(function (CommodityCategory $category) use ($livestock): void {
            $category->setRelation(
                'livestock',
                $livestock->filter(fn (Livestock $item) => $item->commodityType?->category_id === $category->id)->values()
            );
        });

        return view('seller.ternak.index', compact('categories', 'store'));
    }

    public function create(Request $request): View
    {
        return view('seller.ternak.form', [
            'store' => $this->activeStore($request),
            'livestock' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(LivestockRequest $request): RedirectResponse
    {
        $store = $this->activeStore($request);
        $attributes = $this->validatedAttributes($request);

        if ($attributes['mode'] === 'individual') {
            $attributes['jumlah'] = 1;
        }

        $storedPaths = [];
        try {
            $livestock = DB::transaction(function () use ($store, $attributes, $request, &$storedPaths): Livestock {
                $livestock = Livestock::query()->create(['store_id' => $store->id] + $attributes);
                $this->saveMedia($livestock, $request->file('media', []), $storedPaths);

                return $livestock;
            });
        } catch (Throwable $exception) {
            $this->deleteMediaFiles($storedPaths);
            throw $exception;
        }

        return redirect()->route('seller.ternak.index')
            ->with('success', 'Data ternak berhasil ditambahkan.');
    }

    public function edit(Request $request, int $livestock): View
    {
        $store = $this->activeStore($request);
        $item = $this->ownedLivestock($store, $livestock);

        return view('seller.ternak.form', [
            'store' => $store,
            'livestock' => $item,
            ...$this->formOptions(),
        ]);
    }

    public function update(LivestockRequest $request, int $livestock): RedirectResponse
    {
        $store = $this->activeStore($request);
        $item = $this->ownedLivestock($store, $livestock);
        $attributes = $this->validatedAttributes($request);

        if ($attributes['mode'] === 'individual') {
            $attributes['jumlah'] = 1;
        }

        $storedPaths = [];
        $removedPaths = [];
        try {
            $item = DB::transaction(function () use (
                $item,
                $attributes,
                $request,
                &$storedPaths,
                &$removedPaths
            ): Livestock {
                $item->update($attributes);
                $removeIds = $request->input('media_remove', []);
                if ($removeIds !== []) {
                    $mediaToRemove = $item->media()->whereIn('id', $removeIds)->get();
                    $removedPaths = $mediaToRemove->pluck('path')->all();
                    $item->media()->whereIn('id', $mediaToRemove->modelKeys())->delete();
                }

                $this->saveMedia($item, $request->file('media', []), $storedPaths);

                return $item;
            });
        } catch (Throwable $exception) {
            $this->deleteMediaFiles($storedPaths);
            throw $exception;
        }

        $this->deleteMediaFiles($removedPaths);

        return redirect()->route('seller.ternak.index')
            ->with('success', 'Data ternak berhasil diperbarui.');
    }

    public function destroy(Request $request, int $livestock): RedirectResponse
    {
        $store = $this->activeStore($request);
        $item = $this->ownedLivestock($store, $livestock);
        $paths = DB::transaction(function () use ($item): array {
            $paths = $item->media()->pluck('path')->all();
            $item->delete();

            return $paths;
        });

        $this->deleteMediaFiles($paths);

        return redirect()->route('seller.ternak.index')
            ->with('success', 'Data ternak berhasil dihapus.');
    }

    private function activeStore(Request $request): Store
    {
        return Store::query()
            ->where('user_id', $request->user()->id)
            ->where('status', StoreStatus::Active->value)
            ->latest('id')
            ->firstOrFail();
    }

    private function ownedLivestock(Store $store, int $id): Livestock
    {
        return Livestock::query()
            ->with(['commodityType.category', 'media'])
            ->where('store_id', $store->id)
            ->findOrFail($id);
    }

    private function formOptions(): array
    {
        $categories = CommodityCategory::query()
            ->active()
            ->where('group', 'ternak_hidup')
            ->with(['types' => fn ($query) => $query->active()->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $types = $categories->flatMap(fn (CommodityCategory $category) => $category->types->map(
            fn ($type) => [
                'id' => $type->id,
                'category_id' => $category->id,
                'name' => $type->name,
                'selling_modes' => $type->selling_modes ?? [],
                'purposes' => $type->purposes ?? [],
                'form_fields' => $type->form_fields ?? [],
            ]
        ))->values();

        return [
            'categories' => $categories,
            'types' => $types,
            'modeLabels' => CommodityType::MODES,
            'purposeLabels' => CommodityType::PURPOSES,
        ];
    }

    private function validatedAttributes(LivestockRequest $request): array
    {
        $data = $request->validated();
        unset($data['category_id'], $data['media'], $data['media_remove']);

        return $data;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array<int, string>  $storedPaths
     */
    private function saveMedia(Livestock $livestock, array $files, array &$storedPaths): void
    {
        foreach ($files as $file) {
            $path = $file->store("livestock/{$livestock->store_id}/{$livestock->id}", 'public');
            if (! $path) {
                throw new RuntimeException('Berkas media ternak gagal disimpan.');
            }

            $storedPaths[] = $path;
            $mimeType = $file->getMimeType() ?? 'application/octet-stream';

            $livestock->media()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mimeType,
                'size' => $file->getSize(),
                'type' => str_starts_with($mimeType, 'video/') ? 'video' : 'image',
            ]);
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function deleteMediaFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (! Storage::disk('public')->delete($path)) {
                throw new RuntimeException("Berkas media {$path} gagal dihapus.");
            }
        }
    }
}
