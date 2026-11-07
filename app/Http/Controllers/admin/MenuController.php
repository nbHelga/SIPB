<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function menu(Request $request)
    {
        // $query = Menu::query()->orderBy('kategori')->orderBy('nama');
        $query = Menu::query()->orderBy('nama', 'asc');

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('filter.kategori')) {
            $query->where('kategori', $request->input('filter.kategori'));
        }

        $menus = $query->paginate(20)->withQueryString();
        $kategoriList = Menu::query()
            ->whereNotNull('kategori')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori');

        return view('admin.menu', compact('menus', 'kategoriList'));
    }

    public function menuJson($id)
    {
        return response()->json(Menu::findOrFail($id));
    }

    private function validateMenu(Request $request): array
    {
        // Normalisasi checkbox sebelum validasi.
        $request->merge([
            'status' => $request->input('status') === 'open' ? 'open' : 'close',
            'is_bestseller' => $request->boolean('is_bestseller'),
            'is_reservable' => $request->boolean('is_reservable'),
        ]);

        return $request->validate([
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'nama' => ['required', 'string', 'min:3', 'max:100'],
            'harga' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'kategori' => ['required', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:open,close'],
            'is_bestseller' => ['required', 'boolean'],
            'is_reservable' => ['required', 'boolean'],
        ], [
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Format gambar harus JPG, PNG, atau WEBP.',
            'gambar.max' => 'Ukuran gambar maksimal 2 MB.',
            'nama.required' => 'Nama menu wajib diisi.',
            'nama.min' => 'Nama menu minimal 3 karakter.',
            'nama.max' => 'Nama menu maksimal 100 karakter.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.numeric' => 'Harga harus berupa angka.',
            'harga.min' => 'Harga tidak boleh kurang dari 0.',
            'harga.max' => 'Harga maksimal Rp10.000.000.',
            'kategori.required' => 'Kategori wajib dipilih atau diisi.',
            'kategori.max' => 'Kategori maksimal 50 karakter.',
            'deskripsi.max' => 'Deskripsi maksimal 1000 karakter.',
        ]);
    }

    public function menuStore(Request $request)
    {
        $validated = $this->validateMenu($request);

        if ($request->hasFile('gambar')) {
            $validated['gambar'] = $request->file('gambar')->store('menu', 'public');
        }

        Menu::create($validated);

        return redirect()->route('admin.menu')
            ->with('success', 'Menu berhasil ditambahkan.');
    }

    public function menuUpdate(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);
        $validated = $this->validateMenu($request);

        if ($request->hasFile('gambar')) {
            $oldImage = $menu->gambar;
            $validated['gambar'] = $request->file('gambar')->store('menu', 'public');

            if ($oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        }

        $menu->update($validated);

        return redirect()->route('admin.menu')
            ->with('success', 'Menu berhasil diperbarui.');
    }

    public function menuDelete($id)
    {
        $menu = Menu::findOrFail($id);

        if ($menu->gambar) {
            Storage::disk('public')->delete($menu->gambar);
        }

        $menu->delete();

        return redirect()->route('admin.menu')
            ->with('success', 'Menu berhasil dihapus.');
    }

    public function menuAvailability(Request $request, $id)
    {
        abort_unless(
            in_array(auth()->user()?->status, ['admin', 'kasir'], true),
            403
        );

        $validated = $request->validate([
            'status' => ['required', 'in:open,close'],
            'is_reservable' => ['required', 'boolean'],
        ]);

        $menu = Menu::findOrFail($id);
        $menu->update([
            'status' => $validated['status'],
            'is_reservable' => (bool) $validated['is_reservable'],
        ]);

        return redirect()->route('admin.menu')
            ->with('success', 'Status menu berhasil diperbarui.');
    }
}
