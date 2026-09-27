<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $namaJenisSurat }} - {{ $surat->nomor_surat }}</title>
    <style>
        @page {
            margin: 1.5cm 2cm;
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
            margin: 6px 0 14px 0;
        }
        .title-box {
            text-align: center;
            margin-bottom: 14px;
        }
        .surat-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            letter-spacing: 0.8px;
            margin-bottom: 2px;
        }
        .surat-number {
            font-size: 10pt;
            color: #374151;
        }
        .content {
            text-align: justify;
            margin-bottom: 10px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px 15px;
        }
        .table-data td {
            padding: 3px 6px;
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
        tr {
            page-break-inside: avoid;
        }
        .ttd-container {
            width: 100%;
            margin-top: 20px;
            page-break-inside: avoid;
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
            margin-bottom: 10px;
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
            letter-spacing: 0.5px;
        }
        .stempel-note {
            font-size: 7pt;
            color: #4b5563;
            font-style: italic;
            margin-top: 4px;
            line-height: 1.2;
        }
        .verification-footer {
            margin-top: 20px;
            border-top: 1px dashed #9ca3af;
            padding-top: 8px;
            font-size: 8pt;
            color: #6b7280;
            page-break-inside: avoid;
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

    @php
        $alamatCetak = $alamatCetak ?? (!empty($surat->form_data['alamat_domisili']) ? $surat->form_data['alamat_domisili'] : ($user->alamat ?? ('RT 0' . ($rt->nomor_rt ?? 5) . ' / RW 0' . ($rw->nomor_rw ?? 3))));
    @endphp

    <!-- KONTEN SPESIFIK JENIS SURAT (PARTIALS) -->
    @include('surat.pdf.partials.' . strtolower($surat->jenis_surat), [
        'surat' => $surat,
        'user' => $user,
        'rt' => $rt,
        'rw' => $rw,
        'klien' => $klien,
        'alamatCetak' => $alamatCetak,
    ])

    <!-- TANDA TANGAN PENGURUS -->
    <div class="ttd-container">
        <table class="ttd-table">
            <tr>
                <td></td>
                <td>
                    <div>{{ $klien->kota ?? 'Bandung' }}, {{ $tanggalSurat }}</div>
                    <div class="ttd-jabatan">{{ $jabatanPenandatangan }}</div>
                    
                    <div class="stempel-digital">TERVERIFIKASI SECARA ELEKTRONIK</div>
                    <div class="stempel-note">
                        Dokumen ini telah disetujui secara elektronik dan sah tanpa tanda tangan basah.
                    </div>
                    <div style="height: 10px;"></div>
                    
                    <div class="ttd-nama">{{ $surat->reviewer->nama }}</div>
                    <div style="font-size: 8.5pt; color: #6b7280; margin-top: 2px;">{{ $subJabatanPenandatangan }}</div>
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
                        <span class="badge-verified">DOKUMEN ELEKTRONIK RESMI</span> — Diterbitkan otomatis melalui Platform <strong>Warga Digital</strong>.<br>
                        Dokumen ini telah disetujui oleh Pengurus RT setempat sesuai data administrasi kependudukan yang tercatat.<br>
                        <span style="font-size: 7.5pt; color: #4b5563;">Verifikasi keabsahan data surat dapat dicek mandiri melalui portal resmi: <strong>{{ url('/verifikasi') }}</strong></span>
                    </td>
                    <td style="text-align: right; vertical-align: top; width: 175px;">
                        Kode Validasi Dokumen:<br>
                        <strong style="font-family: monospace; font-size: 8.5pt; color: #111827; letter-spacing: 0.5px;">{{ $verificationCode }}</strong>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
