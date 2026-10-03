<?php

namespace App\Http\Controllers;

use App\Constants\AuditAction;
use App\Models\SuratKelengkapan;
use App\Models\SuratPengajuan;
use App\Services\AuditLogger;
use App\Services\SuratNumberGenerator;
use App\Services\SuratPdfGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SuratController extends Controller
{
    /**
     * Daftar katalog jenis surat & pelacak surat aktif warga.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('status', 'Semua');
        $search = $request->query('search', '');

        // 6 Jenis Surat Kependudukan Resmi Warga Digital
        $katalogSurat = [
            [
                'kode' => 'SKD',
                'nama' => 'Surat Domisili (SKD)',
                'deskripsi' => 'Keterangan domisili kependudukan resmi warga di lingkungan RT.',
                'persyaratan' => 'KTP Asli / Kartu Keluarga',
                'estimasi' => '1x24 Jam',
            ],
            [
                'kode' => 'SKU',
                'nama' => 'Surat Keterangan Usaha (SKU)',
                'deskripsi' => 'Pengantar legalitas operasional usaha mikro/kecil warga di wilayah RT.',
                'persyaratan' => 'Foto Tempat Usaha & KTP',
                'estimasi' => '1x24 Jam',
            ],
            [
                'kode' => 'SKTM',
                'nama' => 'Surat Keterangan Tidak Mampu (SKTM)',
                'deskripsi' => 'Pengantar bantuan sosial, beasiswa pendidikan, atau keringanan medis RS.',
                'persyaratan' => 'Kartu Keluarga & Slip Gaji/Pernyataan',
                'estimasi' => '1x24 Jam',
            ],
            [
                'kode' => 'SPKK',
                'nama' => 'Pengantar Kartu Keluarga (SPKK)',
                'deskripsi' => 'Pengantar pembuatan baru, penambahan anggota, atau pemecahan KK.',
                'persyaratan' => 'KK Lama / Buku Nikah',
                'estimasi' => '1x24 Jam',
            ],
            [
                'kode' => 'SKL',
                'nama' => 'Keterangan Kelahiran (SKL)',
                'deskripsi' => 'Pengantar pencatatan kelahiran anak ke kantor kelurahan / Disdukcapil.',
                'persyaratan' => 'Surat Lahir RS/Bidan & KTP Orang Tua',
                'estimasi' => '1x24 Jam',
            ],
            [
                'kode' => 'SKKm',
                'nama' => 'Keterangan Kematian (SKKm)',
                'deskripsi' => 'Pengantar akta kematian dan pencatatan riwayat kependudukan warga.',
                'persyaratan' => 'Surat Medis/Dokter & KTP Almarhum',
                'estimasi' => '1x24 Jam',
            ],
        ];

        // Filter katalog berdasarkan pencarian nama atau kode
        if (!empty($search)) {
            $katalogSurat = array_values(array_filter($katalogSurat, function ($item) use ($search) {
                return str_contains(strtolower($item['nama']), strtolower($search))
                    || str_contains(strtolower($item['kode']), strtolower($search))
                    || str_contains(strtolower($item['deskripsi']), strtolower($search));
            }));
        }

        // Ambil riwayat pengajuan surat milik warga yang sedang login
        $pengajuanQuery = SuratPengajuan::where('user_id', $user->id)
            ->with(['reviewer', 'kelengkapan'])
            ->latest();

        if ($tab !== 'Semua') {
            $pengajuanQuery->where('status', $tab);
        }

        $riwayatSurat = $pengajuanQuery->get();

        // Hitung total pengajuan menunggu verifikasi untuk pengurus RT
        $antreanRtCount = 0;
        if ($user->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris'])) {
            $antreanRtCount = SuratPengajuan::where('rt_id', $user->rt_id)
                ->whereIn('status', ['MENUNGGU', 'DIREVIEW', 'PERLU_KELENGKAPAN'])
                ->count();
        }

        return view('surat.index', compact('katalogSurat', 'riwayatSurat', 'tab', 'search', 'antreanRtCount'));
    }

    /**
     * Tampilkan formulir pengajuan sesuai jenis surat yang dipilih.
     */
    public function create(string $jenis)
    {
        $jenisList = ['SKD', 'SKTM', 'SKU', 'SPKK', 'SKL', 'SKKm'];
        if (!in_array($jenis, $jenisList)) {
            return redirect()->route('surat.index')->with('error', 'Jenis surat tidak valid.');
        }

        $user = Auth::user();
        $namaJenis = SuratPdfGenerator::getNamaJenisSurat($jenis);

        return view('surat.create', compact('jenis', 'namaJenis', 'user'));
    }

    /**
     * Simpan permohonan surat baru ke database.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'jenis_surat' => 'required|in:SKD,SKTM,SKU,SPKK,SKL,SKKm',
            'keperluan' => 'required|string|max:500',
            'catatan_pemohon' => 'nullable|string|max:1000',
            'dokumen_pendukung_lain' => 'nullable|array',
            'dokumen_pendukung_lain.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // legacy fallback
        ];

        // Validasi field spesifik dan slot dokumen wajib per jenis surat
        switch ($request->jenis_surat) {
            case 'SKU':
                $rules['nama_usaha'] = 'required|string|max:200';
                $rules['bidang_usaha'] = 'required|string|max:150';
                $rules['alamat_usaha'] = 'required|string|max:300';
                $rules['lama_usaha'] = 'nullable|string|max:50';
                $rules['dokumen_usaha'] = 'required_without:dokumen|array|min:1';
                $rules['dokumen_usaha.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['dokumen_ktp'] = 'required_without:dokumen|array|min:1';
                $rules['dokumen_ktp.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
                break;
            case 'SKTM':
                $rules['pekerjaan'] = 'required|string|max:100';
                $rules['penghasilan_per_bulan'] = 'required|string|max:100';
                $rules['jumlah_tanggungan'] = 'required|integer|min:0|max:20';
                $rules['dokumen_kk'] = 'required_without:dokumen|array|min:1';
                $rules['dokumen_kk.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['dokumen_slip_gaji'] = 'required_without:dokumen|array|min:1';
                $rules['dokumen_slip_gaji.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
                break;
            case 'SPKK':
                $rules['alasan_permohonan'] = 'required|string|max:150';
                $rules['nama_kepala_keluarga'] = 'required|string|max:150';
                $rules['jumlah_anggota'] = 'required|integer|min:1|max:20';
                $rules['dokumen_kk_nikah'] = 'required_without:dokumen|array|min:1';
                $rules['dokumen_kk_nikah.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
                break;
            case 'SKL':
                $rules['nama_anak'] = 'required|string|max:150';
                $rules['jenis_kelamin_anak'] = 'required|in:L,P';
                $rules['tanggal_lahir_anak'] = 'required|date';
                $rules['nama_ibu'] = 'required|string|max:150';
                $rules['nama_ayah'] = 'required|string|max:150';
                $rules['dokumen_surat_lahir'] = 'required_without:dokumen|array|min:1';
                $rules['dokumen_surat_lahir.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['dokumen_ktp_ortu'] = 'required_without:dokumen|array|min:1';
                $rules['dokumen_ktp_ortu.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
                break;
            case 'SKKm':
                $rules['nama_almarhum'] = 'required|string|max:150';
                $rules['tanggal_meninggal'] = 'required|date';
                $rules['tempat_meninggal'] = 'required|string|max:150';
                $rules['penyebab'] = 'required|string|max:200';
                $rules['dokumen_surat_medis'] = 'required_without:dokumen|array|min:1';
                $rules['dokumen_surat_medis.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
                $rules['dokumen_ktp_almarhum'] = 'required_without:dokumen|array|min:1';
                $rules['dokumen_ktp_almarhum.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
                break;
            case 'SKD':
            default:
                $rules['alamat_domisili'] = 'required|string|max:300';
                $rules['lama_tinggal'] = 'nullable|string|max:50';
                $rules['dokumen_ktp_kk'] = 'required_without:dokumen|array|min:1';
                $rules['dokumen_ktp_kk.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:5120';
                break;
        }

        $messages = [
            'dokumen_ktp_kk.required_without' => 'Dokumen KTP Asli / Kartu Keluarga wajib diunggah.',
            'dokumen_usaha.required_without' => 'Foto Tempat Usaha wajib diunggah.',
            'dokumen_ktp.required_without' => 'KTP wajib diunggah.',
            'dokumen_kk.required_without' => 'Kartu Keluarga wajib diunggah.',
            'dokumen_slip_gaji.required_without' => 'Slip Gaji / Surat Pernyataan Tidak Mampu wajib diunggah.',
            'dokumen_kk_nikah.required_without' => 'KK Lama / Buku Nikah wajib diunggah.',
            'dokumen_surat_lahir.required_without' => 'Surat Lahir dari RS/Bidan wajib diunggah.',
            'dokumen_ktp_ortu.required_without' => 'KTP Orang Tua wajib diunggah.',
            'dokumen_surat_medis.required_without' => 'Surat Medis / Keterangan Dokter wajib diunggah.',
            'dokumen_ktp_almarhum.required_without' => 'KTP Almarhum wajib diunggah.',
            '*.mimes' => 'Format berkas lampiran harus berupa PDF, JPG, JPEG, atau PNG.',
            '*.max' => 'Ukuran setiap berkas maksimal 5 MB.',
        ];

        $validated = $request->validate($rules, $messages);

        // Kumpulkan data dinamis untuk disimpan sebagai JSON form_data
        $formData = $validated;

        // Daftar label slot untuk identifikasi dokumen saat review
        $slotLabels = [
            'dokumen_ktp_kk' => 'KTP Asli / Kartu Keluarga',
            'dokumen_usaha' => 'Foto Tempat Usaha',
            'dokumen_ktp' => 'KTP',
            'dokumen_kk' => 'Kartu Keluarga',
            'dokumen_slip_gaji' => 'Slip Gaji / Surat Pernyataan Tidak Mampu',
            'dokumen_kk_nikah' => 'KK Lama / Buku Nikah',
            'dokumen_surat_lahir' => 'Surat Lahir dari RS/Bidan',
            'dokumen_ktp_ortu' => 'KTP Orang Tua',
            'dokumen_surat_medis' => 'Surat Medis / Keterangan Dokter',
            'dokumen_ktp_almarhum' => 'KTP Almarhum',
            'dokumen_pendukung_lain' => 'Lampiran Pendukung Lain',
        ];

        // Hapus array file mentah dari formData dasar
        foreach (array_keys($slotLabels) as $slotKey) {
            unset($formData[$slotKey]);
        }
        unset($formData['jenis_surat'], $formData['dokumen']);

        // Upload dan simpan berkas terstruktur per slot kategori
        $lampiran = [];
        foreach ($slotLabels as $slotKey => $slotLabel) {
            if ($request->hasFile($slotKey)) {
                $files = is_array($request->file($slotKey)) ? $request->file($slotKey) : [$request->file($slotKey)];
                foreach ($files as $file) {
                    $path = $file->store('surat_dokumen', 'public');
                    $lampiran[$slotKey][] = [
                        'path' => $path,
                        'nama' => $file->getClientOriginalName(),
                        'label' => $slotLabel,
                        'size' => $file->getSize(),
                    ];
                }
            }
        }

        // Dukungan kompatibilitas berkas tunggal legacy jika ada
        if ($request->hasFile('dokumen')) {
            $path = $request->file('dokumen')->store('surat_dokumen', 'public');
            $lampiran['dokumen_legacy'][] = [
                'path' => $path,
                'nama' => $request->file('dokumen')->getClientOriginalName(),
                'label' => 'Dokumen Pendukung',
                'size' => $request->file('dokumen')->getSize(),
            ];
        }

        if (!empty($lampiran)) {
            $formData['lampiran'] = $lampiran;
            // Shortcut dokumen_url & dokumen_nama untuk backward compatibility
            $firstSlot = array_key_first($lampiran);
            if (!empty($lampiran[$firstSlot][0])) {
                $formData['dokumen_url'] = $lampiran[$firstSlot][0]['path'];
                $formData['dokumen_nama'] = $lampiran[$firstSlot][0]['nama'];
            }
        }

        if ($request->filled('catatan_pemohon')) {
            $formData['catatan_pemohon'] = $request->catatan_pemohon;
        }

        $surat = SuratPengajuan::create([
            'rt_id' => $user->rt_id,
            'user_id' => $user->id,
            'jenis_surat' => $request->jenis_surat,
            'form_data' => $formData,
            'status' => 'MENUNGGU',
        ]);

        // Audit Trail resmi: Warga berhasil mengajukan permohonan surat baru
        AuditLogger::log(
            AuditAction::SURAT_SUBMITTED,
            'surat_pengajuan',
            $surat->id,
            null,
            [
                'jenis_surat' => $surat->jenis_surat,
                'status' => 'MENUNGGU',
            ],
            'Warga mengajukan permohonan surat baru'
        );

        return redirect()->route('surat.show', $surat->id)
            ->with('success', 'Permohonan surat berhasil diajukan dan sedang menunggu verifikasi Ketua RT.');
    }

    /**
     * Tampilkan lembar status surat, stepper visual, dan interaksi kelengkapan.
     */
    public function show(int $id)
    {
        $surat = SuratPengajuan::with(['user', 'rt.rw.klien', 'reviewer', 'kelengkapan'])->findOrFail($id);
        $user = Auth::user();

        // Otorisasi: Pemohon sendiri ATAU pengurus RT yang berwenang
        $isOwner = $surat->user_id === $user->id;
        $isPengurusRt = $user->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris']) && $user->rt_id === $surat->rt_id;

        if (!$isOwner && !$isPengurusRt && !$user->is_super_admin) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat permohonan surat ini.');
        }

        // Sesuai SDD §6.3: Jika pengurus RT membuka surat berstatus MENUNGGU, otomatis ubah status ke DIREVIEW
        if ($isPengurusRt && $surat->status === 'MENUNGGU') {
            $sebelum = ['status' => $surat->status];
            $surat->update([
                'status' => 'DIREVIEW',
                'reviewed_by' => $user->id,
            ]);
            AuditLogger::log(AuditAction::SURAT_REVIEWED, 'surat_pengajuan', $surat->id, $sebelum, ['status' => 'DIREVIEW'], 'Pengurus RT membuka berkas permohonan surat');
        }

        $namaJenis = SuratPdfGenerator::getNamaJenisSurat($surat->jenis_surat);

        return view('surat.show', compact('surat', 'namaJenis', 'isOwner', 'isPengurusRt'));
    }

    /**
     * Unduh berkas PDF resmi yang telah disetujui.
     */
    public function downloadPdf(int $id)
    {
        $surat = SuratPengajuan::with(['user', 'rt.rw.klien', 'reviewer'])->findOrFail($id);
        $user = Auth::user();

        $isOwner = $surat->user_id === $user->id;
        $isPengurusRt = $user->hasRole(['ketua_rt', 'wakil_rt', 'sekretaris']) && $user->rt_id === $surat->rt_id;

        if (!$isOwner && !$isPengurusRt && !$user->is_super_admin) {
            abort(403, 'Akses ditolak.');
        }

        if ($surat->status !== 'DISETUJUI') {
            return redirect()->route('surat.show', $surat->id)
                ->with('error', 'Dokumen PDF hanya dapat diunduh setelah permohonan resmi disetujui oleh Ketua RT.');
        }

        if (!$surat->reviewer) {
            return redirect()->route('surat.show', $surat->id)
                ->with('error', 'Dokumen tidak dapat diterbitkan karena data penandatangan belum tersedia.');
        }

        $pdf = SuratPdfGenerator::generate($surat);
        $fileName = 'Surat_' . $surat->jenis_surat . '_' . str_replace(' ', '_', $surat->user->nama) . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Tanggapan / unggah berkas kelengkapan dari sisi warga.
     */
    public function uploadKelengkapan(Request $request, int $id)
    {
        $surat = SuratPengajuan::findOrFail($id);
        $user = Auth::user();

        if ($surat->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'pesan' => 'required|string|max:500',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('surat_kelengkapan', 'public');
        }

        SuratKelengkapan::create([
            'pengajuan_id' => $surat->id,
            'pesan' => $request->pesan,
            'file_url' => $filePath,
            'dari_role' => 'warga',
        ]);

        // Sesuai SDD §6.3: Setelah warga kirim kelengkapan, status kembali ke MENUNGGU
        $sebelum = ['status' => $surat->status];
        $surat->update(['status' => 'MENUNGGU']);

        AuditLogger::log(AuditAction::SURAT_COMPLETION_SUBMITTED, 'surat_pengajuan', $surat->id, $sebelum, ['status' => 'MENUNGGU'], 'Warga mengunggah perbaikan kelengkapan berkas');

        return redirect()->route('surat.show', $surat->id)
            ->with('success', 'Berkas kelengkapan berhasil dikirimkan. Permohonan Anda akan segera ditinjau ulang oleh Ketua RT.');
    }

    /**
     * =========================================================================
     * MEJA KERJA PENGURUS RT (Ketua RT & Wakil RT)
     * =========================================================================
     */

    /**
     * Menampilkan daftar antrean permohonan surat masuk warga RT setempat.
     */
    public function adminIndex(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('status', 'Semua');
        $search = $request->query('search', '');

        $query = SuratPengajuan::where('rt_id', $user->rt_id)
            ->with(['user', 'reviewer'])
            ->latest();

        if ($tab !== 'Semua') {
            $query->where('status', $tab);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('nama', 'like', "%{$search}%");
                })->orWhere('nomor_surat', 'like', "%{$search}%")
                  ->orWhere('jenis_surat', 'like', "%{$search}%");
            });
        }

        $daftarSurat = $query->paginate(15)->withQueryString();

        // Hitung ringkasan status antrean
        $counts = [
            'total' => SuratPengajuan::where('rt_id', $user->rt_id)->count(),
            'menunggu' => SuratPengajuan::where('rt_id', $user->rt_id)->where('status', 'MENUNGGU')->count(),
            'direview' => SuratPengajuan::where('rt_id', $user->rt_id)->where('status', 'DIREVIEW')->count(),
            'perlu_kelengkapan' => SuratPengajuan::where('rt_id', $user->rt_id)->where('status', 'PERLU_KELENGKAPAN')->count(),
            'disetujui' => SuratPengajuan::where('rt_id', $user->rt_id)->where('status', 'DISETUJUI')->count(),
            'ditolak' => SuratPengajuan::where('rt_id', $user->rt_id)->where('status', 'DITOLAK')->count(),
        ];

        return view('surat.admin.index', compact('daftarSurat', 'tab', 'search', 'counts'));
    }

    /**
     * Menyetujui permohonan surat, menerbitkan nomor surat resmi, dan mencatat audit log.
     */
    public function approve(Request $request, int $id)
    {
        $surat = SuratPengajuan::with('rt.rw')->findOrFail($id);
        $user = Auth::user();

        if ($surat->status === 'DISETUJUI') {
            return redirect()->back()->with('error', 'Surat ini telah disetujui sebelumnya.');
        }

        // Generate nomor surat resmi per RT
        $nomorSurat = SuratNumberGenerator::generate($surat->rt, $surat->jenis_surat);

        // Generate kode verifikasi dokumen (Lookup Identifier berbasis SHA-256)
        $kodeVerifikasi = strtoupper(substr(hash('sha256', ($nomorSurat ?? 'WD') . ($surat->id) . ($surat->created_at)), 0, 16));

        // Tangani kemungkinan collision secara eksplisit
        while (SuratPengajuan::withoutGlobalScopes()->where('kode_verifikasi', $kodeVerifikasi)->where('id', '!=', $surat->id)->exists()) {
            $kodeVerifikasi = strtoupper(substr(hash('sha256', ($nomorSurat ?? 'WD') . ($surat->id) . ($surat->created_at) . microtime(true)), 0, 16));
        }

        $sebelum = [
            'status' => $surat->status,
            'nomor_surat' => $surat->nomor_surat,
            'kode_verifikasi' => $surat->kode_verifikasi,
        ];

        $surat->update([
            'status' => 'DISETUJUI',
            'nomor_surat' => $nomorSurat,
            'kode_verifikasi' => $kodeVerifikasi,
            'reviewed_by' => $user->id,
            'alasan_tolak' => null,
        ]);

        $sesudah = [
            'status' => 'DISETUJUI',
            'nomor_surat' => $nomorSurat,
            'kode_verifikasi' => $kodeVerifikasi,
            'reviewed_by' => $user->id,
        ];

        // Audit log wajib sesuai aturan AGENTS.md
        AuditLogger::log(
            AuditAction::SURAT_APPROVED,
            'surat_pengajuan',
            $surat->id,
            $sebelum,
            $sesudah,
            'Permohonan surat telah disetujui oleh pengurus RT dan nomor resmi telah diterbitkan.'
        );

        return redirect()->route('surat.show', $surat->id)
            ->with('success', "Surat berhasil disetujui dengan nomor resmi: {$nomorSurat}. Warga kini dapat mengunduh dokumen PDF.");
    }

    /**
     * Menolak permohonan surat dengan alasan wajib, mencatat audit log.
     */
    public function reject(Request $request, int $id)
    {
        $request->validate([
            'alasan_tolak' => 'required|string|min:5|max:500',
        ], [
            'alasan_tolak.required' => 'Alasan penolakan wajib diisi agar warga memahami penyebabnya.',
            'alasan_tolak.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $surat = SuratPengajuan::findOrFail($id);
        $user = Auth::user();

        $sebelum = [
            'status' => $surat->status,
            'alasan_tolak' => $surat->alasan_tolak,
        ];

        $surat->update([
            'status' => 'DITOLAK',
            'alasan_tolak' => $request->alasan_tolak,
            'reviewed_by' => $user->id,
        ]);

        $sesudah = [
            'status' => 'DITOLAK',
            'alasan_tolak' => $request->alasan_tolak,
            'reviewed_by' => $user->id,
        ];

        // Audit log wajib sesuai aturan AGENTS.md
        AuditLogger::log(
            AuditAction::SURAT_REJECTED,
            'surat_pengajuan',
            $surat->id,
            $sebelum,
            $sesudah,
            $request->alasan_tolak
        );

        return redirect()->route('surat.show', $surat->id)
            ->with('success', 'Permohonan surat telah ditolak dan alasan telah disampaikan ke pemohon.');
    }

    /**
     * Meminta kelengkapan berkas kepada warga, mencatat audit log.
     */
    public function requestCompletion(Request $request, int $id)
    {
        $request->validate([
            'pesan' => 'required|string|min:5|max:500',
        ], [
            'pesan.required' => 'Instruksi kelengkapan berkas wajib diisi.',
        ]);

        $surat = SuratPengajuan::findOrFail($id);
        $user = Auth::user();

        $sebelum = ['status' => $surat->status];

        $surat->update([
            'status' => 'PERLU_KELENGKAPAN',
            'reviewed_by' => $user->id,
        ]);

        SuratKelengkapan::create([
            'pengajuan_id' => $surat->id,
            'pesan' => $request->pesan,
            'dari_role' => 'rt',
        ]);

        AuditLogger::log(
            AuditAction::SURAT_COMPLETION_REQUESTED,
            'surat_pengajuan',
            $surat->id,
            $sebelum,
            ['status' => 'PERLU_KELENGKAPAN'],
            $request->pesan
        );

        return redirect()->route('surat.show', $surat->id)
            ->with('success', 'Permintaan kelengkapan berkas berhasil dikirimkan kepada warga.');
    }
}
