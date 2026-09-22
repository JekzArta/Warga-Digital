# AGENTS.md — Warga Digital

Aturan ini berlaku untuk semua kerja agent di repo ini. Baca sebelum membuat file, menulis kode, atau mengubah struktur proyek. Kalau instruksi di chat bertentangan dengan file ini, **tanyakan dulu**, jangan asumsi salah satu yang benar.

---

## 0. Konteks Produk

**Warga Digital** — platform web administrasi, komunikasi, dan ekonomi lokal untuk RT/RW. Dibangun untuk babak final SATU CREANOVA 2026 (final onsite 5 & 7 Oktober 2026). Tim: CodeRanger (Zaky, Faris, Khaffa, Dayra). Zaky satu-satunya yang menjalankan agent ini.

**Nama produk yang benar: "Warga Digital".** Jangan pakai "Digital RT/RW" atau "Digital RT & RW" di kode, komentar, UI, atau nama file baru — itu nama lama.

Dokumen acuan ada di `/docs/spec/`:
- `PRD-v2.md` — fitur, role, aturan bisnis
- `SDD-v2.md` — arsitektur, skema database, flow

Kalau ada pertanyaan soal *apa* yang harus dibangun dan *kenapa*, jawabannya ada di dua file itu. File ini (`AGENTS.md`) mengatur *bagaimana* cara membangunnya.

---

## 1. Stack Teknis — WAJIB

- **Laravel 11** + **MySQL**
- **Blade** untuk templating, **Alpine.js** untuk interaktivitas ringan di sisi client
- **Tailwind CSS** untuk styling
- **Laravel Reverb** untuk real-time (dipakai khusus Chat Bebas)
- Autentikasi pakai **session Laravel standar** (bukan token/JWT eksplisit) — single portal, satu titik login

### DILARANG KERAS

Stack berikut pernah dipakai di draft lama proyek ini dan **sudah tidak berlaku**. Jangan pernah menyarankan, menginstal, atau menulis kode yang memakainya:

- ❌ Next.js, React sebagai framework utama, Prisma
- ❌ n8n, Baileys (WhatsApp Gateway otomatis)
- ❌ Payment gateway apa pun (Midtrans, Xendit, Stripe, dll) — transaksi UMKM murni lewat link WhatsApp manual
- ❌ Vercel sebagai target deploy (tidak cocok untuk Laravel persisten). Target: **Railway** atau **Render**

Kalau menemukan sisa referensi ke stack lama di dalam repo (misalnya di file yang ter-copy tanpa sengaja), **flag ke Zaky**, jangan diam-diam dihapus atau diikuti.

---

## 2. Ruang Lingkup Fitur — JAGA KETAT

Ini poin paling penting di seluruh file ini. Juri babak penyisihan secara eksplisit memperingatkan: **"Tantangan terbesar adalah menjaga scope agar fitur inti benar-benar dapat diimplementasikan dengan baik."** Jangan menambah fitur di luar daftar berikut atas inisiatif sendiri, sekecil apa pun kelihatannya berguna.

### 7 fitur yang dibangun (sesuai urutan prioritas — lihat bagian 3)

1. Pengajuan Surat Otomatis
2. Ruang Komunitas (Announcement / Chat Bebas / Forum — paralel scope RT & RW)
3. Transparansi Anggaran
4. Audit Trail
5. UMKM (Jasa & Barang)
6. Kalender
7. Galeri Kegiatan

### TIDAK dibangun untuk kompetisi ini — jangan diimplementasikan sama sekali

- SOS Darurat
- Peta Interaktif (basic maupun self-managed boundary)
- Jadwal Ronda
- Dashboard agregat Kecamatan/Kelurahan

Kalau ada permintaan yang terasa mengarah ke salah satu fitur di atas (langsung maupun tidak langsung, misalnya "tambahin lokasi GPS di laporan" yang sebenarnya cikal bakal SOS), **konfirmasi dulu ke Zaky sebelum mengerjakan.**

### Role yang berlaku

`warga`, `sekretaris`, `bendahara`, `wakil_rt`, `ketua_rt`, `ketua_rw`, `super_admin`. **`wakil_rt` permission-nya identik dengan `ketua_rt`** — jangan dipisah jadi tier terbatas.

Role yang **sudah dihapus dan tidak boleh muncul lagi**: `satpam`, `ronda_malam`, `dasawisma`, `humas`.

---

## 3. Urutan Kerja yang Disarankan

Kerjakan bertahap, bukan "buatkan semua fitur sekaligus". Setiap fase harus stabil dulu sebelum lanjut ke fase berikutnya.

**Fase 1 — Fondasi (blocker semua fitur lain)**
- Migration skema database (lihat SDD §5)
- Autentikasi (NIK + password, alur set password pertama kali)
- Multi-tenant: Global Scope Eloquent untuk `rt_id`/`rw_id`
- Middleware role guard (RBAC sesuai matriks permission SDD §3.2)
- `DemoSeeder` — data simulasi sesuai SDD §10

**Fase 2 — Fitur inti (harus paling mulus, ini yang dinilai juri sebagai kekuatan utama)**
- Pengajuan Surat Otomatis (termasuk generator nomor surat & PDF)
- Ruang Komunitas
- Audit Trail

**Fase 3 — Fitur pendukung**
- Transparansi Anggaran
- UMKM

**Fase 4 — Boleh disederhanakan kalau waktu mepet**
- Kalender
- Galeri Kegiatan

Kalau harus memotong scope karena waktu, potong dari Fase 4 dulu. **Jangan pernah mengorbankan kualitas Fase 1–2 demi mengejar Fase 3–4.**

---

## 4. Aturan Keamanan & Privasi — NON-NEGOTIABLE

- **NIK tidak boleh pernah dikirim ke response JSON, ditampilkan di Blade view, atau muncul di log** selain untuk proses autentikasi itu sendiri. NIK hanya identity anchor di backend.
- Validasi NIK **global** (lintas seluruh database, bukan hanya per-RT) saat import/tambah warga.
- Semua aksi sensitif (approve/tolak surat, approve/tolak UMKM, hapus/pin/close thread, ubah transaksi kas, assign/cabut role, perpindahan warga, koreksi data warga) **wajib tercatat di `audit_logs`**. Kalau menulis controller/service untuk aksi-aksi ini, jangan lupa panggil audit logger-nya.
- Setiap query terhadap model yang tenant-scoped (`SuratPengajuan`, `KasTransaksi`, dll) **wajib** lewat Global Scope `rt_id` — jangan query manual tanpa scope kecuali eksplisit untuk Super Admin atau Ketua RW yang butuh lintas-RT.
- Password di-hash (standar Laravel `Hash::make`), tidak ada penyimpanan plaintext di mana pun termasuk seed data untuk demo (pakai password sederhana tapi tetap di-hash).

---

## 5. Konvensi Kode

- Penamaan tabel dan kolom: `snake_case`, bahasa Indonesia untuk istilah domain (`surat_pengajuan`, `kas_transaksi`) — **konsisten dengan SDD v2.0 §5.2**, jangan diterjemahkan ke bahasa Inggris.
- Status enum pakai `UPPER_SNAKE_CASE` untuk konstanta status (`MENUNGGU`, `DISETUJUI`, `DITOLAK`, `PERLU_KELENGKAPAN`) — ikuti persis penamaan di SDD, jangan diganti jadi bahasa Inggris atau format lain.
- Struktur folder Laravel mengikuti SDD §9 (`app/Http/Controllers`, `app/Services`, `app/Events`, dll). Service class dipakai untuk logika yang lebih dari sekadar CRUD (contoh: `SuratNumberGenerator`, `WargaImportValidator`, `AuditLogger`).
- Komentar kode dan commit message: bahasa Indonesia informal, boleh santai — konsisten dengan gaya kerja tim.
- UI berbahasa Indonesia sederhana, tanpa jargon teknis (target pengguna termasuk lansia dan warga literasi digital rendah).

---

## 6. Git

Repo Git dipakai lokal, satu kontributor (Zaky), tanpa branch kompleks. Tujuannya jaring pengaman, bukan kolaborasi multi-orang.

- Commit setiap kali satu fitur/bagian selesai dan **teruji jalan** (bukan commit di tengah kerjaan setengah jadi)
- Pesan commit singkat dan jelas, contoh: `feat: alur pengajuan surat sampai approve`, `fix: audit log tidak tercatat saat tolak listing UMKM`
- Kalau agent membuat perubahan besar yang berisiko (migration ulang, refactor struktur), **beri tahu dulu** sebelum eksekusi, supaya Zaky bisa commit checkpoint dulu

---

## 7. Kalau Ragu

- Fitur di luar 7 daftar resmi → **tanya, jangan tambah sendiri**
- Keputusan arsitektur yang tidak ada di SDD → **tanya**, jangan improvisasi diam-diam
- Konflik antara PRD dan SDD (kalau ada) → **tanya**, jangan pilih salah satu secara sepihak
- Kalau sebuah task butuh waktu lama atau berisiko merusak yang sudah jalan → beri ringkasan rencana dulu sebelum eksekusi penuh

Prioritas selalu: **fitur inti yang benar-benar jalan end-to-end**, lebih baik daripada tujuh fitur yang setengah jadi. Ini bukan preferensi gaya kerja — ini catatan eksplisit dari juri.
