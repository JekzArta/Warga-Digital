{{-- resources/views/surat/pdf/partials/skd.blade.php --}}
{{-- SKD: Surat Keterangan Domisili --}}
{{-- Objek yang diterangkan: Pemohon (Warga) --}}

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
        <td class="label">Alamat Tempat Tinggal</td>
        <td class="colon">:</td>
        <td class="value">{{ $alamatCetak }}</td>
    </tr>
    @if(!empty($surat->form_data['lama_tinggal']))
    <tr>
        <td class="label">Lama Menetap / Tinggal</td>
        <td class="colon">:</td>
        <td class="value">{{ $surat->form_data['lama_tinggal'] }}</td>
    </tr>
    @endif
    @if(!empty($surat->form_data['pekerjaan']))
    <tr>
        <td class="label">Pekerjaan</td>
        <td class="colon">:</td>
        <td class="value">{{ $surat->form_data['pekerjaan'] }}</td>
    </tr>
    @endif
</table>

<!-- KETERANGAN DOMISILI RESMI -->
<div class="content">
    Berdasarkan catatan kependudukan kami, nama tersebut di atas adalah benar warga yang bertempat tinggal dan berdomisili sah di lingkungan RT 0{{ $rt->nomor_rt ?? 5 }} RW 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}.
</div>

<div class="content" style="margin-top: 8px;">
    Surat keterangan ini dibuat dan diberikan kepada yang bersangkutan untuk keperluan:<br>
    <div style="margin: 6px 0 6px 20px; font-weight: bold; color: #111827; font-style: italic;">
        "{{ $surat->form_data['keperluan'] ?? 'Keperluan administrasi kependudukan / dinas terkait' }}"
    </div>
</div>

<!-- PARAGRAF PENUTUP -->
<div class="content" style="margin-top: 10px;">
    Demikian surat keterangan ini kami terbitkan dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya oleh pihak yang berkepentingan.
</div>
