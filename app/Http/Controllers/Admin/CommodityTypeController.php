<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommodityCategory;
use App\Models\CommodityType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommodityTypeController extends Controller
{
    public function index()
    {
        return view('admin.data-induk.index', $this->treeData());
    }

    public function show(CommodityType $type)
    {
        return view('admin.data-induk.show', $this->treeData() + ['type' => $type->load('category')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:commodity_categories,id'],
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('commodity_types', 'name')->where('category_id', $request->input('category_id')),
            ],
            'selling_modes' => ['required', 'array', 'min:1'],
            'selling_modes.*' => [Rule::in(array_keys(CommodityType::MODES))],
        ], [
            'name.required' => 'Nama jenis wajib diisi.',
            'name.unique' => 'Jenis dengan nama itu sudah ada di kategori ini.',
            'selling_modes.required' => 'Pilih minimal satu mode penjualan.',
        ]);

        $type = CommodityType::create([
            'category_id' => $data['category_id'],
            'name' => trim($data['name']),
            'selling_modes' => array_values($data['selling_modes']),
            'purposes' => [],
            'form_fields' => CommodityType::DEFAULT_FORM_FIELDS,
            'contest_categories' => [],
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.data-induk.show', $type)
            ->with('status', 'Jenis baru ditambahkan. Atur peruntukan dan kolom formulirnya di bawah.');
    }

    public function update(Request $request, CommodityType $type)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:commodity_categories,id'],
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('commodity_types', 'name')
                    ->where('category_id', $request->input('category_id'))
                    ->ignore($type->id),
            ],
            'selling_modes' => ['required', 'array', 'min:1'],
            'selling_modes.*' => [Rule::in(array_keys(CommodityType::MODES))],
            'purposes' => ['nullable', 'array'],
            'purposes.*' => [Rule::in(array_keys(CommodityType::PURPOSES))],
            'required_fields' => ['nullable', 'array'],
            'required_fields.*' => [Rule::in(array_keys(CommodityType::FORM_FIELDS))],
            'contest_categories' => ['nullable', 'string', 'max:200'],
        ], [
            'name.required' => 'Nama jenis wajib diisi.',
            'name.unique' => 'Jenis dengan nama itu sudah ada di kategori ini.',
            'selling_modes.required' => 'Pilih minimal satu mode penjualan.',
        ]);

        $required = $data['required_fields'] ?? [];

        $type->update([
            'category_id' => $data['category_id'],
            'name' => trim($data['name']),
            'selling_modes' => array_values($data['selling_modes']),
            'purposes' => array_values($data['purposes'] ?? []),
            'form_fields' => collect(array_keys(CommodityType::FORM_FIELDS))
                ->mapWithKeys(fn ($key) => [$key => in_array($key, $required, true)])
                ->all(),
            'contest_categories' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) ($data['contest_categories'] ?? ''))
            ))),
        ]);

        return redirect()
            ->route('admin.data-induk.show', $type)
            ->with('status', 'Perubahan disimpan.');
    }

    // Aktifkan / nonaktifkan. Jenis tidak pernah dihapus karena bisa sudah dipakai produk atau transaksi.
    public function toggle(CommodityType $type)
    {
        $type->update(['is_active' => ! $type->is_active]);

        return redirect()
            ->route('admin.data-induk.show', $type)
            ->with('status', $type->is_active
                ? 'Jenis diaktifkan kembali.'
                : 'Jenis dinonaktifkan. Tidak muncul di pilihan baru, data lama tetap aman.');
    }

    private function treeData(): array
    {
        $categories = CommodityCategory::with(['types' => fn ($q) => $q->orderBy('name')])
            ->orderBy('sort_order')
            ->get();

        return compact('categories');
    }
}
