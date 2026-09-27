{{-- resources/views/surat/pdf/partials/skkm.blade.php --}}
{{-- SKKm: Surat Keterangan Kematian --}}
{{-- Objek yang diterangkan: Almarhum / Peristiwa Kematian (Pemohon berperan sebagai Pelapor / Ahli Waris) --}}

<!-- PARAGRAF PEMBUKA -->
<div class="content">
    Yang bertanda tangan di bawah ini, Pengurus Rukun Tetangga 0{{ $rt->nomor_rt ?? 5 }} / Rukun Warga 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}, Kecamatan {{ $klien->kecamatan ?? 'Coblong' }}, Kota {{ $klien->kota ?? 'Bandung' }}, dengan ini menerangkan bahwa telah menerima laporan kematian dari warga:
</div>

<!-- 1. IDENTITAS PELAPOR / AHLI WARIS -->
<div style="font-weight: bold; font-size: 10.5pt; color: #111827; margin: 6px 0 2px 0;">
    I. Data Pelapor / Keluarga:
</div>
<table class="table-data" style="margin-top: 4px; margin-bottom: 8px;">
    <tr>
        <td class="label">Nama Lengkap Pelapor</td>
        <td class="colon">:</td>
        <td class="value">{{ strtoupper($user->nama) }}</td>
    </tr>
    <tr>
        <td class="label">NIK Pelapor</td>
        <td class="colon">:</td>
        <td class="value">{{ $user->nik }}</td>
    </tr>
    <tr>
        <td class="label">Jenis Kelamin</td>
        <td class="colon">:</td>
        <td class="value">{{ $user->jenis_kelamin === 'L' ? 'Laki-Laki' : 'Perempuan' }}</td>
    </tr>
    <tr>
        <td class="label">Alamat Domisili</td>
        <td class="colon">:</td>
        <td class="value">{{ $alamatCetak }}</td>
    </tr>
</table>

<!-- 2. IDENTITAS ALMARHUM / ALMARHUMAH (SUBJEK UTAMA) -->
<div class="content" style="margin-top: 6px;">
    Menerangkan dengan sebenarnya bahwa telah meninggal dunia warga dengan rincian data sebagai berikut:
</div>

<div style="font-weight: bold; font-size: 10.5pt; color: #111827; margin: 4px 0 2px 0;">
    II. Data Almarhum / Almarhumah:
</div>
<table class="table-data" style="margin-top: 4px; margin-bottom: 8px;">
    <tr>
        <td class="label">Nama Lengkap Almarhum/ah</td>
        <td class="colon">:</td>
        <td class="value" style="font-weight: bold; text-transform: uppercase;">
            {{ strtoupper($surat->form_data['nama_almarhum'] ?? '—') }}
        </td>
    </tr>
    <tr>
        <td class="label">Hari / Tanggal Meninggal</td>
        <td class="colon">:</td>
        <td class="value">
            @if(!empty($surat->form_data['tanggal_meninggal']))
                {{ \Carbon\Carbon::parse($surat->form_data['tanggal_meninggal'])->translatedFormat('l, d F Y') }}
            @else
                —
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">Tempat Meninggal</td>
        <td class="colon">:</td>
        <td class="value">{{ $surat->form_data['tempat_meninggal'] ?? '—' }}</td>
    </tr>
</table>
{{-- CATATAN PRODUK: Field 'penyebab' kematian sengaja TIDAK DICETAK ke PDF karena penentuan sebab medis adalah kewenangan dokter/RS, bukan RT --}}

<!-- KETERANGAN KEMATIAN RESMI -->
<div class="content" style="margin-top: 6px;">
    Berdasarkan catatan kependudukan kami dan surat keterangan medis yang dilampirkan, almarhum/almarhumah tersebut di atas semasa hidupnya adalah benar warga yang bertempat tinggal dan berdomisili sah di lingkungan RT 0{{ $rt->nomor_rt ?? 5 }} RW 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}.
</div>

<div class="content" style="margin-top: 8px;">
    Surat keterangan kematian ini dibuat dan diberikan kepada pelapor/ahli waris untuk keperluan:<br>
    <div style="margin: 6px 0 6px 20px; font-weight: bold; color: #111827; font-style: italic;">
        "{{ $surat->form_data['keperluan'] ?? 'Pencatatan akta kematian / administrasi kependudukan' }}"
    </div>
</div>

<!-- PARAGRAF PENUTUP -->
<div class="content" style="margin-top: 10px;">
    Demikian surat keterangan kematian ini kami terbitkan dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya oleh pihak yang berkepentingan.
</div>
