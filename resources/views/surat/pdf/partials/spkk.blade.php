{{-- resources/views/surat/pdf/partials/spkk.blade.php --}}
{{-- SPKK: Surat Pengantar Kartu Keluarga --}}
{{-- Objek yang diterangkan: Permohonan / Perubahan Kartu Keluarga --}}

<!-- PARAGRAF PEMBUKA -->
<div class="content">
    Yang bertanda tangan di bawah ini, Pengurus Rukun Tetangga 0{{ $rt->nomor_rt ?? 5 }} / Rukun Warga 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}, Kecamatan {{ $klien->kecamatan ?? 'Coblong' }}, Kota {{ $klien->kota ?? 'Bandung' }}, dengan ini memberikan pengantar kepada warga:
</div>

<!-- 1. DATA PEMOHON -->
<div style="font-weight: bold; font-size: 10.5pt; color: #111827; margin: 6px 0 2px 0;">
    I. Data Pemohon:
</div>
<table class="table-data" style="margin-top: 4px; margin-bottom: 8px;">
    <tr>
        <td class="label">Nama Lengkap Pemohon</td>
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
        <td class="label">Alamat Domisili</td>
        <td class="colon">:</td>
        <td class="value">{{ $alamatCetak }}</td>
    </tr>
</table>

<!-- 2. RINCIAN PERMOHONAN KARTU KELUARGA -->
<div style="font-weight: bold; font-size: 10.5pt; color: #111827; margin: 6px 0 2px 0;">
    II. Rincian Permohonan Kartu Keluarga:
</div>
<table class="table-data" style="margin-top: 4px; margin-bottom: 8px;">
    <tr>
        <td class="label">Alasan Permohonan</td>
        <td class="colon">:</td>
        <td class="value" style="font-weight: bold;">
            {{ $surat->form_data['alasan_permohonan'] ?? 'Penerbitan / Perubahan Kartu Keluarga' }}
        </td>
    </tr>
    <tr>
        <td class="label">Nama Kepala Keluarga</td>
        <td class="colon">:</td>
        <td class="value" style="text-transform: uppercase;">
            {{ strtoupper($surat->form_data['nama_kepala_keluarga'] ?? $user->nama) }}
        </td>
    </tr>
    <tr>
        <td class="label">Jumlah Anggota Keluarga</td>
        <td class="colon">:</td>
        <td class="value">
            {{ $surat->form_data['jumlah_anggota'] ?? '—' }} Orang / Jiwa
        </td>
    </tr>
</table>

<!-- KETERANGAN PENGANTAR RESMI -->
<div class="content" style="margin-top: 6px;">
    Berdasarkan catatan kependudukan kami dan berkas lampiran yang telah diverifikasi, nama tersebut di atas adalah benar warga yang bertempat tinggal di lingkungan RT 0{{ $rt->nomor_rt ?? 5 }} RW 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}, dan bermaksud mengurus permohonan Kartu Keluarga sesuai rincian data di atas.
</div>

<div class="content" style="margin-top: 8px;">
    Surat pengantar ini dibuat dan diberikan kepada yang bersangkutan untuk keperluan:<br>
    <div style="margin: 6px 0 6px 20px; font-weight: bold; color: #111827; font-style: italic;">
        "{{ $surat->form_data['keperluan'] ?? 'Penerbitan / perubahan Kartu Keluarga di Kantor Kelurahan / Dinas Kependudukan dan Pencatatan Sipil' }}"
    </div>
</div>

<!-- PARAGRAF PENUTUP -->
<div class="content" style="margin-top: 10px;">
    Demikian surat pengantar ini kami terbitkan dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya oleh pihak yang berkepentingan.
</div>
