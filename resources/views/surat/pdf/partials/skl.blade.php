{{-- resources/views/surat/pdf/partials/skl.blade.php --}}
{{-- SKL: Surat Keterangan Kelahiran --}}
{{-- Objek yang diterangkan: Bayi / Peristiwa Kelahiran (Pemohon berperan sebagai Pelapor / Orang Tua) --}}

<!-- PARAGRAF PEMBUKA -->
<div class="content">
    Yang bertanda tangan di bawah ini, Pengurus Rukun Tetangga 0{{ $rt->nomor_rt ?? 5 }} / Rukun Warga 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}, Kecamatan {{ $klien->kecamatan ?? 'Coblong' }}, Kota {{ $klien->kota ?? 'Bandung' }}, dengan ini menerangkan bahwa telah menerima laporan peristiwa kelahiran dari warga:
</div>

<!-- 1. IDENTITAS PELAPOR / ORANG TUA -->
<div style="font-weight: bold; font-size: 10.5pt; color: #111827; margin: 6px 0 2px 0;">
    I. Data Pelapor / Orang Tua:
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

<!-- 2. IDENTITAS KELAHIRAN ANAK / BAYI (SUBJEK UTAMA) -->
<div class="content" style="margin-top: 6px;">
    Menerangkan dengan sebenarnya bahwa telah lahir seorang anak dengan rincian data sebagai berikut:
</div>

<div style="font-weight: bold; font-size: 10.5pt; color: #111827; margin: 4px 0 2px 0;">
    II. Data Kelahiran Bayi / Anak:
</div>
<table class="table-data" style="margin-top: 4px; margin-bottom: 8px;">
    <tr>
        <td class="label">Nama Lengkap Anak</td>
        <td class="colon">:</td>
        <td class="value" style="font-weight: bold; text-transform: uppercase;">
            {{ strtoupper($surat->form_data['nama_anak'] ?? '—') }}
        </td>
    </tr>
    <tr>
        <td class="label">Jenis Kelamin Anak</td>
        <td class="colon">:</td>
        <td class="value">
            {{ ($surat->form_data['jenis_kelamin_anak'] ?? 'L') === 'L' ? 'Laki-Laki' : 'Perempuan' }}
        </td>
    </tr>
    <tr>
        <td class="label">Hari / Tanggal Lahir</td>
        <td class="colon">:</td>
        <td class="value">
            @if(!empty($surat->form_data['tanggal_lahir_anak']))
                {{ \Carbon\Carbon::parse($surat->form_data['tanggal_lahir_anak'])->translatedFormat('l, d F Y') }}
            @else
                —
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">Nama Ibu Kandung</td>
        <td class="colon">:</td>
        <td class="value">{{ strtoupper($surat->form_data['nama_ibu'] ?? '—') }}</td>
    </tr>
    <tr>
        <td class="label">Nama Ayah Kandung</td>
        <td class="colon">:</td>
        <td class="value">{{ strtoupper($surat->form_data['nama_ayah'] ?? '—') }}</td>
    </tr>
</table>

<!-- KETERANGAN KELAHIRAN RESMI -->
<div class="content" style="margin-top: 6px;">
    Berdasarkan bukti keterangan persalinan yang dilampirkan, anak tersebut di atas adalah benar putra/putri dari pasangan suami istri warga yang bertempat tinggal dan berdomisili sah di lingkungan RT 0{{ $rt->nomor_rt ?? 5 }} RW 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}.
</div>

<div class="content" style="margin-top: 8px;">
    Surat keterangan kelahiran ini dibuat dan diberikan kepada yang bersangkutan untuk keperluan:<br>
    <div style="margin: 6px 0 6px 20px; font-weight: bold; color: #111827; font-style: italic;">
        "{{ $surat->form_data['keperluan'] ?? 'Pencatatan akta kelahiran / administrasi kependudukan' }}"
    </div>
</div>

<!-- PARAGRAF PENUTUP -->
<div class="content" style="margin-top: 10px;">
    Demikian surat keterangan kelahiran ini kami terbitkan dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya oleh pihak yang berkepentingan.
</div>
