{{-- resources/views/surat/pdf/partials/sku.blade.php --}}
{{-- SKU: Surat Keterangan Usaha --}}
{{-- Objek yang diterangkan: Pemohon + Kegiatan Usahanya --}}

<!-- PARAGRAF PEMBUKA -->
<div class="content">
    Yang bertanda tangan di bawah ini, Pengurus Rukun Tetangga 0{{ $rt->nomor_rt ?? 5 }} / Rukun Warga 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}, Kecamatan {{ $klien->kecamatan ?? 'Coblong' }}, Kota {{ $klien->kota ?? 'Bandung' }}, dengan ini menerangkan dengan sebenarnya bahwa:
</div>

<!-- 1. DATA IDENTITAS PEMOHON -->
<div style="font-weight: bold; font-size: 10.5pt; color: #111827; margin: 6px 0 2px 0;">
    I. Data Pemohon (Pemilik Usaha):
</div>
<table class="table-data" style="margin-top: 4px; margin-bottom: 8px;">
    <tr>
        <td class="label">Nama Lengkap</td>
        <td class="colon">:</td>
        <td class="value">{{ strtoupper($user->nama) }}</td>
    </tr>
    <tr>
        <td class="label">Nomor Induk Kependudukan (NIK)</td>
        <td class="colon">:</td>
        <td class="value">{{ $user->nik }}</td>
    </tr>
    <tr>
        <td class="label">Jenis Kelamin</td>
        <td class="colon">:</td>
        <td class="value">{{ $user->jenis_kelamin === 'L' ? 'Laki-Laki' : 'Perempuan' }}</td>
    </tr>
    <tr>
        <td class="label">Tempat / Tanggal Lahir</td>
        <td class="colon">:</td>
        <td class="value">{{ $klien->kota ?? 'Bandung' }}, {{ $user->tanggal_lahir ? $user->tanggal_lahir->translatedFormat('d F Y') : '—' }}</td>
    </tr>
    <tr>
        <td class="label">Alamat KTP / Domisili</td>
        <td class="colon">:</td>
        <td class="value">{{ $alamatCetak }}</td>
    </tr>
</table>

<!-- 2. DATA RINCIAN KEGIATAN USAHA -->
<div style="font-weight: bold; font-size: 10.5pt; color: #111827; margin: 6px 0 2px 0;">
    II. Keterangan Kegiatan Usaha:
</div>
<table class="table-data" style="margin-top: 4px; margin-bottom: 8px;">
    <tr>
        <td class="label">Nama Usaha / Toko</td>
        <td class="colon">:</td>
        <td class="value" style="font-weight: bold; text-transform: uppercase;">
            {{ strtoupper($surat->form_data['nama_usaha'] ?? '—') }}
        </td>
    </tr>
    <tr>
        <td class="label">Bidang Usaha</td>
        <td class="colon">:</td>
        <td class="value">{{ $surat->form_data['bidang_usaha'] ?? '—' }}</td>
    </tr>
    @if(!empty($surat->form_data['lama_usaha']))
    <tr>
        <td class="label">Lama Usaha Berjalan</td>
        <td class="colon">:</td>
        <td class="value">{{ $surat->form_data['lama_usaha'] }}</td>
    </tr>
    @endif
    <tr>
        <td class="label">Alamat Lokasi Usaha</td>
        <td class="colon">:</td>
        <td class="value">{{ $surat->form_data['alamat_usaha'] ?? '—' }}</td>
    </tr>
</table>

<!-- KETERANGAN USAHA RESMI -->
<div class="content" style="margin-top: 6px;">
    Berdasarkan catatan kependudukan kami dan hasil verifikasi lapangan/berkas usaha, nama tersebut di atas adalah benar warga yang berdomisili sah di lingkungan RT 0{{ $rt->nomor_rt ?? 5 }} RW 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}, dan yang bersangkutan benar memiliki serta menjalankan kegiatan usaha tersebut di alamat yang tercantum di atas.
</div>

<div class="content" style="margin-top: 8px;">
    Surat keterangan ini dibuat dan diberikan kepada yang bersangkutan untuk keperluan:<br>
    <div style="margin: 6px 0 6px 20px; font-weight: bold; color: #111827; font-style: italic;">
        "{{ $surat->form_data['keperluan'] ?? 'Kelengkapan administrasi perizinan usaha / pengajuan fasilitas perbankan' }}"
    </div>
</div>

<!-- PARAGRAF PENUTUP -->
<div class="content" style="margin-top: 10px;">
    Demikian surat keterangan ini kami terbitkan dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya oleh pihak yang berkepentingan.
</div>
