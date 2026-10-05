<?php

namespace App\Http\Requests;

use App\Models\UmkmListing;
use App\Services\ScopeAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUmkmListingRequest extends FormRequest
{
    /**
     * Tentukan apakah user berwenang memperbarui listing UMKM.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        // Resolusi listing dari route parameter
        $listing = $this->route('listing') ?? $this->route('umkm');
        if ($listing instanceof UmkmListing) {
            return ScopeAuthorizer::canManageUmkm($user, $listing);
        }

        if (is_numeric($listing)) {
            $model = UmkmListing::withoutGlobalScopes()->find($listing);
            return $model ? ScopeAuthorizer::canManageUmkm($user, $model) : false;
        }

        return true;
    }

    /**
     * Aturan validasi pembaruan / revisi listing UMKM.
     */
    public function rules(): array
    {
        return [
            'kategori' => ['required', 'string', 'in:jasa,barang'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'harga' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999',
            ],
            'template_pesan_wa' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Pesan kesalahan kustom berbahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'kategori.required' => 'Kategori UMKM wajib dipilih.',
            'kategori.in' => 'Kategori harus berupa jasa atau barang.',
            'nama.required' => 'Nama produk atau jasa wajib diisi.',
            'nama.max' => 'Nama produk atau jasa tidak boleh lebih dari 255 karakter.',
            'deskripsi.required' => 'Deskripsi produk atau jasa wajib diisi.',
            'harga.numeric' => 'Harga harus berupa angka yang valid.',
            'harga.min' => 'Harga tidak boleh bernilai negatif.',
            'harga.max' => 'Harga melebihi batas maksimum yang diizinkan.',
            'template_pesan_wa.max' => 'Template pesan WhatsApp maksimal 1000 karakter.',
            'foto.image' => 'Berkas foto harus berupa gambar.',
            'foto.mimes' => 'Format foto harus berupa jpeg, jpg, png, atau webp.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
