{{-- resources/views/surat/pdf/partials/sktm.blade.php --}}
{{-- SKTM: Surat Keterangan Tidak Mampu --}}
{{-- Objek yang diterangkan: Pemohon/Keluarga + Kondisi Ekonomi --}}

<!-- PARAGRAF PEMBUKA -->
<div class="content">
    Yang bertanda tangan di bawah ini, Pengurus Rukun Tetangga 0{{ $rt->nomor_rt ?? 5 }} / Rukun Warga 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}, Kecamatan {{ $klien->kecamatan ?? 'Coblong' }}, Kota {{ $klien->kota ?? 'Bandung' }}, dengan ini menerangkan dengan sebenarnya bahwa:
</div>

<!-- DATA PEMOHON -->
<table class="table-data">
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
    <tr>
        <td class="label">Pekerjaan</td>
        <td class="colon">:</td>
        <td class="value">{{ $surat->form_data['pekerjaan'] ?? '—' }}</td>
    </tr>
</table>
{{-- CATATAN PRODUK: Angka nominal 'penghasilan_per_bulan' sengaja TIDAK DICETAK ke PDF untuk menjaga privasi warga, digantikan dengan keterangan kualitatif baku di bawah --}}

<!-- KETERANGAN KONDISI EKONOMI RESMI -->
<div class="content">
    Berdasarkan catatan kependudukan kami dan verifikasi berkas keluarga di lingkungan setempat:
</div>

<div class="content" style="margin-left: 15px; margin-top: 4px; padding: 6px 10px; background-color: #f9fafb; border-left: 3px solid #059669;">
    Bahwa nama tersebut di atas benar merupakan warga yang berdomisili sah di lingkungan RT 0{{ $rt->nomor_rt ?? 5 }} RW 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}, dan berasal dari keluarga berpenghasilan rendah / kurang mampu, dengan jumlah tanggungan keluarga sebanyak <strong>{{ $surat->form_data['jumlah_tanggungan'] ?? 0 }} orang</strong>.
</div>

<div class="content" style="margin-top: 8px;">
    Surat keterangan ini dibuat dan diberikan kepada yang bersangkutan untuk keperluan:<br>
    <div style="margin: 6px 0 6px 20px; font-weight: bold; color: #111827; font-style: italic;">
        "{{ $surat->form_data['keperluan'] ?? 'Pengajuan bantuan sosial / beasiswa / keringanan biaya' }}"
    </div>
</div>

<!-- PARAGRAF PENUTUP -->
<div class="content" style="margin-top: 10px;">
    Demikian surat keterangan ini kami terbitkan dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya oleh pihak yang berkepentingan.
</div>
