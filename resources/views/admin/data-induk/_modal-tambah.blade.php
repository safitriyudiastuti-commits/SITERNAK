<dialog id="dlg-add" class="modal">
    <form method="POST" action="{{ route('admin.data-induk.store') }}">
        @csrf
        <h3>Tambah jenis baru</h3>

        <label class="field-label" for="add-category">Kategori</label>
        <select id="add-category" name="category_id" class="di-input">
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                    {{ $category->group_label }} · {{ $category->name }}
                </option>
            @endforeach
        </select>

        <label class="field-label" for="add-name">Nama jenis</label>
        <input type="text" id="add-name" name="name" value="{{ old('name') }}" class="di-input" placeholder="Contoh: Sapi Bali" maxlength="80" required>

        <label class="field-label">Mode penjualan</label>
        @foreach (\App\Models\CommodityType::MODES as $key => $label)
            <label class="check">
                <input type="checkbox" name="selling_modes[]" value="{{ $key }}" @checked(in_array($key, old('selling_modes', ['individual'])))>
                <span>{{ $label }}</span>
            </label>
        @endforeach

        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" data-close>Batal</button>
            <button type="submit" class="btn btn-primary">Tambah Jenis</button>
        </div>
    </form>
</dialog>
