<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterDataRequest;
use App\Models\AuditLog;
use App\Models\Breed;
use App\Models\Category;
use App\Models\ContestCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->string('type')->toString() ?: 'livestock';

        return view('admin.data-induk.index', [
            'type' => $type,
            'categories' => Category::with('parent')->where('type', $type)->orderBy('name')->get(),
            'breeds' => Breed::with('category')->when($type === 'livestock', fn ($query) => $query->whereHas('category', fn ($category) => $category->where('type', 'livestock')))->orderBy('name')->get(),
            'contestCategories' => ContestCategory::orderBy('name')->get(),
        ]);
    }

    public function store(MasterDataRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $kind = $request->input('kind', 'category');

        if ($kind === 'breed') {
            Breed::create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'is_active' => $request->boolean('is_active', true),
                'sale_modes' => $data['sale_modes'],
                'purposes' => $data['purposes'] ?? [],
                'dynamic_fields' => $data['dynamic_fields'] ?? [],
            ]);
        } elseif ($kind === 'contest') {
            ContestCategory::create([
                'name' => $data['name'],
                'livestock_group' => $data['livestock_group'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);
        } else {
            Category::create([
                'type' => $data['type'],
                'name' => $data['name'],
                'slug' => $data['slug'] ?: Str::slug($data['name']),
                'parent_id' => $data['parent_id'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);
        }

        AuditLog::create(['admin_id' => $request->user()->id, 'action' => 'master_data_created', 'description' => 'Data induk dibuat: '.$data['name']]);

        return back()->with('status', 'Data induk berhasil ditambahkan.');
    }

    public function update(MasterDataRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $category->update([
            'type' => $data['type'],
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'parent_id' => $data['parent_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        AuditLog::create(['admin_id' => $request->user()->id, 'action' => 'master_data_updated', 'subject_type' => Category::class, 'subject_id' => $category->id, 'description' => 'Data induk diperbarui.']);

        return back()->with('status', 'Data induk berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->update(['is_active' => false]);
        AuditLog::create(['admin_id' => request()->user()->id, 'action' => 'master_data_disabled', 'subject_type' => Category::class, 'subject_id' => $category->id, 'description' => 'Kategori dinonaktifkan.']);

        return back()->with('status', 'Kategori dinonaktifkan.');
    }

    public function updateBreed(MasterDataRequest $request, Breed $breed): RedirectResponse
    {
        $data = $request->validated();
        $breed->update([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active'),
            'sale_modes' => $data['sale_modes'],
            'purposes' => $data['purposes'] ?? [],
            'dynamic_fields' => $data['dynamic_fields'] ?? [],
        ]);
        AuditLog::create(['admin_id' => $request->user()->id, 'action' => 'master_data_updated', 'subject_type' => Breed::class, 'subject_id' => $breed->id, 'description' => 'Jenis ternak diperbarui.']);

        return back()->with('status', 'Jenis ternak berhasil diperbarui.');
    }

    public function disableBreed(Breed $breed): RedirectResponse
    {
        $breed->update(['is_active' => false]);
        AuditLog::create(['admin_id' => request()->user()->id, 'action' => 'master_data_disabled', 'subject_type' => Breed::class, 'subject_id' => $breed->id, 'description' => 'Jenis ternak dinonaktifkan.']);

        return back()->with('status', 'Jenis ternak dinonaktifkan dan tetap tersimpan pada riwayat transaksi.');
    }
}
