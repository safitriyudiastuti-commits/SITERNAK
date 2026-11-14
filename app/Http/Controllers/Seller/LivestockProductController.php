<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\LivestockProductRequest;
use App\Http\Requests\Seller\RestockLivestockProductRequest;
use App\Models\CommodityType;
use App\Models\Livestock;
use App\Models\LivestockProduct;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class LivestockProductController extends Controller
{
    public function index(Request $request): View
    {
        $store = $this->activeStore($request);
        $products = LivestockProduct::query()
            ->where('store_id', $store->id)
            ->with(['commodityType', 'media'])
            ->latest()
            ->get();

        return view('seller.hasil-ternak.index', compact('products', 'store'));
    }

    public function create(Request $request): View
    {
        $store = $this->activeStore($request);

        return view('seller.hasil-ternak.form', [
            'store' => $store,
            'product' => null,
            ...$this->formOptions($store),
        ]);
    }

    public function store(LivestockProductRequest $request): RedirectResponse
    {
        $store = $this->activeStore($request);
        $attributes = $request->validated();
        unset($attributes['media'], $attributes['media_remove']);

        $storedPaths = [];
        try {
            DB::transaction(function () use ($store, $attributes, $request, &$storedPaths): void {
                $product = LivestockProduct::query()->create([
                    'store_id' => $store->id,
                    ...$attributes,
                ]);
                $this->saveMedia($product, $request->file('media', []), $storedPaths);
            });
        } catch (Throwable $exception) {
            $this->deleteMediaFiles($storedPaths);
            throw $exception;
        }

        return redirect()->route('seller.hasil-ternak.index')
            ->with('success', 'Produk hasil ternak berhasil ditambahkan.');
    }

    public function edit(Request $request, int $product): View
    {
        $store = $this->activeStore($request);
        $item = $this->ownedProduct($store, $product);

        return view('seller.hasil-ternak.form', [
            'store' => $store,
            'product' => $item,
            ...$this->formOptions($store),
        ]);
    }

    public function update(LivestockProductRequest $request, int $product): RedirectResponse
    {
        $store = $this->activeStore($request);
        $item = $this->ownedProduct($store, $product);
        $attributes = $request->validated();
        unset($attributes['media'], $attributes['media_remove']);

        $storedPaths = [];
        $removedPaths = [];
        try {
            DB::transaction(function () use (
                $item,
                $attributes,
                $request,
                &$storedPaths,
                &$removedPaths
            ): void {
                $item->update($attributes);

                $removeIds = $request->input('media_remove', []);
                if ($removeIds !== []) {
                    $mediaToRemove = $item->media()->whereIn('id', $removeIds)->get();
                    $removedPaths = $mediaToRemove->pluck('path')->all();
                    $item->media()->whereIn('id', $mediaToRemove->modelKeys())->delete();
                }

                $this->saveMedia($item, $request->file('media', []), $storedPaths);
            });
        } catch (Throwable $exception) {
            $this->deleteMediaFiles($storedPaths);
            throw $exception;
        }

        $this->deleteMediaFiles($removedPaths);

        return redirect()->route('seller.hasil-ternak.index')
            ->with('success', 'Produk hasil ternak berhasil diperbarui.');
    }

    public function destroy(Request $request, int $product): RedirectResponse
    {
        $store = $this->activeStore($request);
        $item = $this->ownedProduct($store, $product);
        $paths = DB::transaction(function () use ($item): array {
            $paths = $item->media()->pluck('path')->all();
            $item->delete();

            return $paths;
        });

        $this->deleteMediaFiles($paths);

        return redirect()->route('seller.hasil-ternak.index')
            ->with('success', 'Produk hasil ternak berhasil dihapus.');
    }

    public function restockForm(Request $request, int $product): View
    {
        $store = $this->activeStore($request);
        $product = $this->ownedProduct($store, $product);

        return view('seller.hasil-ternak.restock', compact('product', 'store'));
    }

    public function restock(RestockLivestockProductRequest $request, int $product): RedirectResponse
    {
        $store = $this->activeStore($request);
        $quantity = (string) $request->validated('stok');

        DB::transaction(function () use ($store, $product, $quantity): void {
            $item = LivestockProduct::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->findOrFail($product);

            if ($item->tanggal_kedaluwarsa->lt(today())) {
                throw ValidationException::withMessages([
                    'stok' => 'Produk sudah kedaluwarsa dan tidak dapat direstok.',
                ]);
            }

            if ((float) $item->stok + (float) $quantity > 999999999.999) {
                throw ValidationException::withMessages([
                    'stok' => 'Jumlah stok setelah restock terlalu besar.',
                ]);
            }

            $item->increment('stok', $quantity);
        });

        return redirect()->route('seller.hasil-ternak.index')
            ->with('success', 'Stok produk berhasil ditambahkan.');
    }

    private function activeStore(Request $request): Store
    {
        return Store::query()
            ->where('user_id', $request->user()->id)
            ->where('status', StoreStatus::Active->value)
            ->latest('id')
            ->firstOrFail();
    }

    private function ownedProduct(Store $store, int $id): LivestockProduct
    {
        return LivestockProduct::query()
            ->with(['commodityType.category', 'media'])
            ->where('store_id', $store->id)
            ->findOrFail($id);
    }

    /**
     * @return array{types: Collection<int, CommodityType>, livestockOrigins: Collection<int, Livestock>}
     */
    private function formOptions(Store $store): array
    {
        $types = CommodityType::query()
            ->active()
            ->whereHas('category', fn ($query) => $query->active()->where('group', 'hasil_ternak'))
            ->with('category')
            ->orderBy('name')
            ->get();
        $livestockOrigins = Livestock::query()
            ->where('store_id', $store->id)
            ->with('commodityType')
            ->latest()
            ->get();

        return compact('types', 'livestockOrigins');
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     * @param  array<int, string>  $storedPaths
     */
    private function saveMedia(LivestockProduct $product, array|UploadedFile|null $files, array &$storedPaths): void
    {
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        foreach ($files ?? [] as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store("livestock-products/{$product->store_id}/{$product->id}", 'public');
            if (! $path) {
                throw new RuntimeException('Foto produk gagal disimpan.');
            }

            $storedPaths[] = $path;
            $product->media()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize() ?? 0,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function deleteMediaFiles(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }
}
