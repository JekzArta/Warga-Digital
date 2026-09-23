<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $namaJenisSurat }} - {{ $surat->nomor_surat }}</title>
    <style>
        @page {
            margin: 2cm 2.5cm 2cm 2.5cm;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.45;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            margin-bottom: 2px;
        }
        .header-title-main {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0b1313;
        }
        .header-title-sub {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #1a2525;
            margin-top: 2px;
        }
        .header-address {
            font-size: 8.5pt;
            color: #555555;
            margin-top: 4px;
        }
        .double-line {
            border-top: 3px solid #111827;
            border-bottom: 1px solid #111827;
            height: 3px;
            margin: 8px 0 20px 0;
        }
        .title-box {
            text-align: center;
            margin-bottom: 22px;
        }
        .surat-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            letter-spacing: 0.8px;
            margin-bottom: 3px;
        }
        .surat-number {
            font-size: 10pt;
            color: #374151;
        }
        .content {
            text-align: justify;
            margin-bottom: 12px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px 20px;
        }
        .table-data td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 10.5pt;
        }
        .table-data .label {
            width: 180px;
            color: #374151;
        }
        .table-data .colon {
            width: 15px;
            text-align: center;
        }
        .table-data .value {
            font-weight: 500;
            color: #111827;
        }
        .ttd-container {
            width: 100%;
            margin-top: 35px;
        }
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ttd-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
        }
        .ttd-jabatan {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 60px;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
            font-size: 10.5pt;
        }
        .stempel-digital {
            display: inline-block;
            border: 2px solid #059669;
            color: #059669;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 8px;
            border-radius: 4px;
            margin-top: 5px;
            letter-spacing: 0.5px;
        }
        .verification-footer {
            margin-top: 40px;
            border-top: 1px dashed #9ca3af;
            padding-top: 10px;
            font-size: 8pt;
            color: #6b7280;
        }
        .verification-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 8px 12px;
        }
        .badge-verified {
            font-weight: bold;
            color: #047857;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT RESMI -->
    <table class="header-table">
        <tr>
            <td>
                <div class="header-title-main">PEMERINTAH KOTA {{ strtoupper($klien->kota ?? 'BANDUNG') }}</div>
                <div class="header-title-main">KECAMATAN {{ strtoupper($klien->kecamatan ?? 'COBLONG') }} - KELURAHAN {{ strtoupper($klien->kelurahan ?? 'SEKELOA') }}</div>
                <div class="header-title-sub">RUKUN WARGA 0{{ $rw->nomor_rw ?? 3 }} - RUKUN TETANGGA 0{{ $rt->nomor_rt ?? 5 }}</div>
                <div class="header-address">Sekretariat: {{ $rt->nama ?? 'RT 05' }}, RW 0{{ $rw->nomor_rw ?? 3 }}, Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}, Kota {{ $klien->kota ?? 'Bandung' }} - Jawa Barat</div>
            </td>
        </tr>
    </table>

    <div class="double-line"></div>

    <!-- JUDUL DAN NOMOR SURAT -->
    <div class="title-box">
        <div class="surat-title">{{ strtoupper($namaJenisSurat) }}</div>
        <div class="surat-number">Nomor: <strong>{{ $surat->nomor_surat ?? '—' }}</strong></div>
    </div>

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
            <td class="value">{{ $user->alamat ?? ('RT 0' . ($rt->nomor_rt ?? 5) . ' / RW 0' . ($rw->nomor_rw ?? 3)) }}</td>
        </tr>
        @if(!empty($surat->form_data['pekerjaan']))
        <tr>
            <td class="label">Pekerjaan</td>
            <td class="colon">:</td>
            <td class="value">{{ $surat->form_data['pekerjaan'] }}</td>
        </tr>
        @endif
        @if(!empty($surat->form_data['nama_usaha']))
        <tr>
            <td class="label">Nama Usaha</td>
            <td class="colon">:</td>
            <td class="value">{{ $surat->form_data['nama_usaha'] }}</td>
        </tr>
        @endif
        @if(!empty($surat->form_data['bidang_usaha']))
        <tr>
            <td class="label">Bidang Usaha</td>
            <td class="colon">:</td>
            <td class="value">{{ $surat->form_data['bidang_usaha'] }}</td>
        </tr>
        @endif
        @if(!empty($surat->form_data['alamat_usaha']))
        <tr>
            <td class="label">Alamat Usaha</td>
            <td class="colon">:</td>
            <td class="value">{{ $surat->form_data['alamat_usaha'] }}</td>
        </tr>
        @endif
    </table>

    <!-- KETERANGAN & KEPERLUAN -->
    <div class="content">
        Berdasarkan catatan kependudukan kami, nama tersebut di atas adalah benar warga yang bertempat tinggal dan berdomisili sah di lingkungan RT 0{{ $rt->nomor_rt ?? 5 }} RW 0{{ $rw->nomor_rw ?? 3 }} Kelurahan {{ $klien->kelurahan ?? 'Sekeloa' }}.
    </div>

    <div class="content" style="margin-top: 10px;">
        Surat keterangan pengantar ini dibuat dan diberikan kepada yang bersangkutan untuk keperluan:<br>
        <div style="margin: 6px 0 6px 20px; font-weight: bold; color: #111827; font-style: italic;">
            "{{ $surat->form_data['keperluan'] ?? 'Keperluan administrasi kependudukan / dinas terkait' }}"
        </div>
    </div>

    <!-- PARAGRAF PENUTUP -->
    <div class="content" style="margin-top: 14px;">
        Demikian surat keterangan pengantar ini kami terbitkan dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya oleh pihak yang berkepentingan.
    </div>

    <!-- TANDA TANGAN -->
    <div class="ttd-container">
        <table class="ttd-table">
            <tr>
                <td></td>
                <td>
                    <div>{{ $klien->kota ?? 'Bandung' }}, {{ $tanggalSurat }}</div>
                    <div class="ttd-jabatan">Ketua RT 0{{ $rt->nomor_rt ?? 5 }} / RW 0{{ $rw->nomor_rw ?? 3 }}</div>
                    
                    <div class="stempel-digital">✓ TERVERIFIKASI SISTEM</div>
                    <div style="height: 15px;"></div>
                    
                    <div class="ttd-nama">{{ $surat->reviewer?->nama ?? 'Bambang Hartono' }}</div>
                    <div style="font-size: 8.5pt; color: #6b7280; margin-top: 2px;">Pengurus RT 0{{ $rt->nomor_rt ?? 5 }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- KODE VERIFIKASI KEASLIAN DOKUMEN -->
    <div class="verification-footer">
        <div class="verification-box">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: top;">
                        <span class="badge-verified">DOKUMEN ELEKTRONIK SAH</span> — Diterbitkan otomatis oleh Platform <strong>Warga Digital</strong>.<br>
                        Dokumen ini telah disetujui secara digital oleh Pengurus RT setempat dan memiliki kekuatan hukum administrasi lingkungan.
                    </td>
                    <td style="text-align: right; vertical-align: top; width: 170px;">
                        Kode Validasi SHA-256:<br>
                        <strong style="font-family: monospace; font-size: 8.5pt; color: #111827;">{{ $verificationCode }}</strong>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
