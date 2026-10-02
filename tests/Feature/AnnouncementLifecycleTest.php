<?php

namespace Tests\Feature;

use App\Events\ChatMessageSent;
use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ForumCategory;
use App\Models\ForumThread;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnnouncementLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA; // Warga RT 05
    protected User $userB; // Warga RT 05
    protected User $userC; // Warga RT 06
    protected User $ketuaRt; // Ketua RT 05
    protected User $sekretaris; // Sekretaris RT 05
    protected User $ketuaRw; // Ketua RW 03
    protected Rt $rt05;
    protected Rt $rt06;
    protected Rw $rw03;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app-id',
            'broadcasting.connections.reverb.options.host' => 'localhost',
            'broadcasting.connections.reverb.options.port' => 8080,
            'broadcasting.connections.reverb.options.scheme' => 'http',
        ]);

        require base_path('routes/channels.php');

        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->rw03 = Rw::first();
        $this->rt05 = Rt::where('nomor_rt', 5)->first();

        $this->rt06 = Rt::create([
            'rw_id' => $this->rw03->id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);

        $this->userA = User::where('nik', '3273021005050010')->first();
        $this->userB = User::where('nik', '3273021005050011')->first();

        $this->userC = User::create([
            'kode_warga' => 'WRG-990001',
            'rt_id' => $this->rt06->id,
            'rw_id' => $this->rw03->id,
            'nik' => '3273021006060001',
            'nama' => 'Cecep Tetangga RT 06',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1992-04-10',
            'alamat' => 'Jl. Sekeloa RT 06 No. 1',
            'password' => Hash::make('password'),
            'status' => 'aktif',
        ]);
        UserRole::create([
            'user_id' => $this->userC->id,
            'role' => 'warga',
            'assigned_at' => now(),
        ]);

        $this->ketuaRt = User::where('nik', '3273021005050001')->first();
        $this->sekretaris = User::where('nik', '3273021005050003')->first();
        $this->ketuaRw = User::where('nik', '3273021005030001')->first();
    }

    /**
     * 1. Announcement tanpa expiry tetap active di feed.
     */
    public function test_announcement_without_expiry_remains_active(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Tanpa Kadaluarsa',
            'konten' => 'Berlaku selamanya sampai dinonaktifkan.',
            'tipe' => 'INFO',
            'expired_at' => null,
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $this->assertEquals('ACTIVE', $announcement->lifecycle_status);

        $response = $this->actingAs($this->userA)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $response->assertStatus(200);
        $response->assertSee('Pengumuman Tanpa Kadaluarsa');
    }

    /**
     * 2. Announcement dengan future expiry tetap active di feed.
     */
    public function test_announcement_with_future_expiry_remains_active(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Masih Berlaku',
            'konten' => 'Berlaku sampai beberapa hari ke depan.',
            'tipe' => 'PENTING',
            'expired_at' => now()->addDays(5)->toDateString(),
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $this->assertEquals('ACTIVE', $announcement->lifecycle_status);

        $response = $this->actingAs($this->userA)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $response->assertStatus(200);
        $response->assertSee('Pengumuman Masih Berlaku');
    }

    /**
     * 3. Announcement yang melewati masa berlaku (kemarin) berstatus EXPIRED dan tidak muncul di active feed.
     */
    public function test_expired_announcement_does_not_appear_in_active_feed(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Sudah Kadaluarsa Kemarin',
            'konten' => 'Masa berlakunya kemarin.',
            'tipe' => 'INFO',
            'expired_at' => now()->subDay()->toDateString(),
            'is_deactivated' => false,
            'is_replaced' => false,
        ]);

        $this->assertEquals('EXPIRED', $announcement->lifecycle_status);

        // Scope active query excludes it
        $this->assertFalse(Announcement::active()->where('id', $announcement->id)->exists());

        // UI active feed excludes it
        $response = $this->actingAs($this->userA)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $response->assertStatus(200);
        $response->assertDontSee('Pengumuman Sudah Kadaluarsa Kemarin');
    }

    /**
     * 4. Deactivate pengumuman menghilangkan dari active feed, tetapi data dan komentar tetap utuh.
     */
    public function test_deactivate_announcement_removes_from_active_feed_and_preserves_data(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Akan Dinonaktifkan',
            'konten' => 'Kegiatan dibatalkan.',
            'tipe' => 'MENDESAK',
        ]);

        AnnouncementComment::create([
            'announcement_id' => $announcement->id,
            'author_id' => $this->userA->id,
            'konten' => 'Pertanyaan sebelum dinonaktifkan.',
        ]);

        $res = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.deactivate', $announcement->id), [
            'alasan' => 'Kegiatan dibatalkan karena cuaca buruk ekstrem.',
        ]);

        $res->assertRedirect();

        $fresh = $announcement->fresh();
        $this->assertTrue($fresh->is_deactivated);
        $this->assertEquals('DEACTIVATED', $fresh->lifecycle_status);
        $this->assertEquals('Kegiatan dibatalkan karena cuaca buruk ekstrem.', $fresh->deactivation_reason);
        $this->assertEquals($this->ketuaRt->id, $fresh->deactivated_by);

        // Komentar tetap utuh
        $this->assertDatabaseHas('announcement_comments', [
            'announcement_id' => $announcement->id,
            'konten' => 'Pertanyaan sebelum dinonaktifkan.',
        ]);

        // Tidak muncul di active feed
        $response = $this->actingAs($this->userA)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $response->assertDontSee('Pengumuman Akan Dinonaktifkan');
    }

    /**
     * 5. Deactivation mewajibkan pengisian alasan minimal 5 karakter.
     */
    public function test_deactivation_requires_mandatory_reason_min_5_chars(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Uji Validasi Alasan Nonaktif',
            'konten' => 'Isi pengumuman.',
            'tipe' => 'INFO',
        ]);

        // Kosong
        $resKosong = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.deactivate', $announcement->id), [
            'alasan' => '',
        ]);
        $resKosong->assertSessionHasErrors('alasan');

        // Kurang dari 5 karakter
        $resPendek = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.deactivate', $announcement->id), [
            'alasan' => 'stop',
        ]);
        $resPendek->assertSessionHasErrors('alasan');

        $this->assertFalse($announcement->fresh()->is_deactivated);
    }

    /**
     * 6. Deactivation tercatat di AuditLogger.
     */
    public function test_deactivation_is_logged_in_audit_trail(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Audit Nonaktif',
            'konten' => 'Isi pengumuman audit.',
            'tipe' => 'INFO',
        ]);

        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.deactivate', $announcement->id), [
            'alasan' => 'Dibatalkan pengurus setelah musyawarah malam.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'ANNOUNCEMENT_DEACTIVATED',
            'target_type' => 'announcements',
            'target_id' => $announcement->id,
            'user_id' => $this->ketuaRt->id,
            'alasan' => 'Dibatalkan pengurus setelah musyawarah malam.',
        ]);
    }

    /**
     * 7, 8, 9, 10, 11: Pembaruan membuat record baru, content lama tidak berubah, content baru benar,
     * status lama = REPLACED, status baru = ACTIVE.
     */
    public function test_update_creates_new_record_and_properly_transitions_lifecycle(): void
    {
        $announcementA = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Kerja Bakti Mulai Pukul 07.00',
            'konten' => 'Kumpul di lapangan RT 05 pukul 07.00 pagi.',
            'tipe' => 'PENTING',
        ]);

        AnnouncementComment::create([
            'announcement_id' => $announcementA->id,
            'author_id' => $this->userA->id,
            'konten' => 'Siap hadir jam 7 pagi!',
        ]);

        $res = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $announcementA->id), [
            'judul' => 'Kerja Bakti Mulai Pukul 08.00 (Revisi)',
            'konten' => 'Kumpul di lapangan RT 05 diundur menjadi pukul 08.00 pagi.',
            'tipe' => 'PENTING',
        ]);

        $res->assertRedirect();

        // 1. Content A tidak berubah
        $freshA = $announcementA->fresh();
        $this->assertEquals('Kerja Bakti Mulai Pukul 07.00', $freshA->judul);
        $this->assertEquals('Kumpul di lapangan RT 05 pukul 07.00 pagi.', $freshA->konten);

        // 2. A menjadi REPLACED
        $this->assertTrue($freshA->is_replaced);
        $this->assertEquals('REPLACED', $freshA->lifecycle_status);

        // 3. Record baru B ada dan status ACTIVE
        $announcementB = Announcement::where('replaces_announcement_id', $announcementA->id)->first();
        $this->assertNotNull($announcementB);
        $this->assertEquals('Kerja Bakti Mulai Pukul 08.00 (Revisi)', $announcementB->judul);
        $this->assertEquals('Kumpul di lapangan RT 05 diundur menjadi pukul 08.00 pagi.', $announcementB->konten);
        $this->assertEquals('ACTIVE', $announcementB->lifecycle_status);

        // 4. Hubungan relasi
        $this->assertEquals($announcementA->id, $announcementB->previous->id);
        $this->assertEquals($announcementB->id, $freshA->successor->id);

        // 5. Komentar tetap berada pada versi lama A
        $this->assertCount(1, $freshA->comments);
        $this->assertCount(0, $announcementB->comments);

        // 6. Active feed hanya menampilkan B, bukan A sebagai kartu aktif mandiri
        $feed = $this->actingAs($this->userA)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $feed->assertSee('Kerja Bakti Mulai Pukul 08.00 (Revisi)');
        $feedAnnouncements = $feed->viewData('announcements');
        $this->assertTrue($feedAnnouncements->contains('id', $announcementB->id));
        $this->assertFalse($feedAnnouncements->contains('id', $announcementA->id));

        // 7. Banner visual relasi "Pembaruan dari Pengumuman Sebelumnya" tampil di feed untuk B
        $feed->assertSee('Pembaruan dari Pengumuman Sebelumnya');

        // 8. Audit log tercatat
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'ANNOUNCEMENT_UPDATED',
            'target_type' => 'announcements',
            'target_id' => $announcementB->id,
            'user_id' => $this->ketuaRt->id,
        ]);
    }

    /**
     * 12. Anti-branching: Mencoba membuat successor kedua langsung dari A yang sudah digantikan ditolak (HTTP 422).
     */
    public function test_duplicate_successor_is_rejected_anti_branching(): void
    {
        $announcementA = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman A Awal',
            'konten' => 'Isi awal.',
            'tipe' => 'INFO',
        ]);

        // Pembaruan pertama (B) sukses
        $resB = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $announcementA->id), [
            'judul' => 'Pembaruan B',
            'konten' => 'Isi B.',
            'tipe' => 'INFO',
        ]);
        $resB->assertRedirect();
        $this->assertTrue($announcementA->fresh()->is_replaced);

        // Coba buat Pembaruan kedua (C) langsung dari A -> Harus DITOLAK (HTTP 422)
        $resC = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $announcementA->id), [
            'judul' => 'Pembaruan C Ilegal (Branching)',
            'konten' => 'Isi C tidak boleh jadi cabang kedua.',
            'tipe' => 'INFO',
        ]);
        $resC->assertStatus(422);

        // Database tidak memiliki cabang kedua
        $this->assertDatabaseMissing('announcements', [
            'judul' => 'Pembaruan C Ilegal (Branching)',
        ]);
    }

    /**
     * 13 & 14. Unauthorized role tidak dapat membuat pembaruan atau menonaktifkan pengumuman (HTTP 403).
     */
    public function test_unauthorized_user_cannot_update_or_deactivate_announcement(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Hak Akses',
            'konten' => 'Hanya pengurus berwenang yang boleh memodifikasi.',
            'tipe' => 'INFO',
        ]);

        // 1. Warga biasa coba buat pembaruan -> HTTP 403
        $resWargaUpdate = $this->actingAs($this->userA)->post(route('komunitas.pengumuman.pembaruan', $announcement->id), [
            'judul' => 'Pembaruan Liar Warga',
            'konten' => 'Isi ilegal.',
            'tipe' => 'INFO',
        ]);
        $resWargaUpdate->assertStatus(403);

        // 2. Warga biasa coba deactivate -> HTTP 403
        $resWargaDeactivate = $this->actingAs($this->userA)->post(route('komunitas.pengumuman.deactivate', $announcement->id), [
            'alasan' => 'Warga sembarangan menonaktifkan pengumuman pengurus.',
        ]);
        $resWargaDeactivate->assertStatus(403);

        // 3. Warga RT 06 coba buat pembaruan -> HTTP 403
        $resRt06Update = $this->actingAs($this->userC)->post(route('komunitas.pengumuman.pembaruan', $announcement->id), [
            'judul' => 'Pembaruan Lintas RT',
            'konten' => 'Isi lintas RT.',
            'tipe' => 'INFO',
        ]);
        $resRt06Update->assertStatus(403);
    }

    /**
     * 15. Menghubungkan Pengumuman ke Forum berhasil & muncul CTA di UI.
     */
    public function test_announcement_can_be_linked_to_forum_thread(): void
    {
        $category = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();
        $thread = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $this->userA->id,
            'judul' => 'Diskusi Alokasi Tempat Sampah',
            'konten' => 'Tempat sampah sebaiknya diletakkan di mana?',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Warga'],
        ]);

        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengadaan Tempat Sampah Baru',
            'konten' => 'Akan dipasang 5 unit tempat sampah pilah.',
            'tipe' => 'INFO',
        ]);

        $res = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.link-forum', $announcement->id), [
            'forum_thread_id' => $thread->id,
        ]);

        $res->assertRedirect();

        $fresh = $announcement->fresh();
        $this->assertEquals($thread->id, $fresh->forum_thread_id);
        $this->assertEquals($thread->id, $fresh->forumThread->id);

        // UI memunculkan tautan "Diskusikan di Forum"
        $viewRes = $this->actingAs($this->userA)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $viewRes->assertSee('Diskusikan di Forum');
        $viewRes->assertSee(route('komunitas.forum.thread.show', $thread->id), false);

        // Audit log tercatat
        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'ANNOUNCEMENT_FORUM_LINKED',
            'target_type' => 'announcements',
            'target_id' => $announcement->id,
            'user_id' => $this->ketuaRt->id,
        ]);
    }

    /**
     * 16. Cross-scope Forum relationship ditolak (HTTP 422).
     */
    public function test_cross_scope_forum_relationship_is_rejected(): void
    {
        // Category di RW 03 (bukan RT 05)
        $categoryRw = ForumCategory::where('scope_type', 'rw')->where('scope_id', $this->rw03->id)->first();
        $threadRw = ForumThread::create([
            'category_id' => $categoryRw->id,
            'author_id' => $this->ketuaRw->id,
            'judul' => 'Program Sanitasi Tingkat RW',
            'konten' => 'Seluruh RT di RW 03.',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Ketua RW'],
        ]);

        // Announcement di lingkup RT 05
        $announcementRt = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Internal RT 05',
            'konten' => 'Khusus RT 05.',
            'tipe' => 'INFO',
        ]);

        // Coba hubungkan pengumuman RT 05 dengan thread RW -> Ditolak HTTP 422
        $res = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.link-forum', $announcementRt->id), [
            'forum_thread_id' => $threadRw->id,
        ]);

        $res->assertStatus(422);
        $this->assertNull($announcementRt->fresh()->forum_thread_id);
    }

    /**
     * 17 & 18. Pengumuman tanpa Forum dan tanpa Pembaruan tetap valid & normal.
     */
    public function test_announcement_without_forum_and_without_update_remains_valid(): void
    {
        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Mandiri Standalone',
            'konten' => 'Tidak punya thread forum dan belum diperbarui.',
            'tipe' => 'INFO',
        ]);

        $this->assertNull($announcement->forum_thread_id);
        $this->assertNull($announcement->replaces_announcement_id);
        $this->assertTrue($announcement->canBeUpdated());
        $this->assertTrue($announcement->canLinkForum());

        $response = $this->actingAs($this->userA)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $response->assertSee('Pengumuman Mandiri Standalone');
        // Tidak menampilkan CTA palsu "Diskusikan di Forum"
        $response->assertDontSee('Diskusikan di Forum → ' . $announcement->judul);
    }

    /**
     * 19. Announcement B sebagai Pembaruan dari A dan juga memiliki Forum Terkait.
     * Kedua relationship terpisah rapi di strip bawah tanpa bercampur.
     */
    public function test_updated_announcement_can_have_forum_relationship_cleanly_separated(): void
    {
        $category = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();
        $thread = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $this->userA->id,
            'judul' => 'Diskusi Usulan Waktu Gotong Royong',
            'konten' => 'Bagaimana kalau jam 8 pagi saja?',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Warga'],
        ]);

        $announcementA = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Versi 1 Gotong Royong 07.00',
            'konten' => 'Mulai jam 7 pagi.',
            'tipe' => 'INFO',
        ]);

        // Buat Pembaruan B dengan menyertakan Forum
        $resB = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $announcementA->id), [
            'judul' => 'Versi 2 Gotong Royong 08.00 Sesuai Forum',
            'konten' => 'Diundur jam 8 pagi sesuai hasil diskusi di forum.',
            'tipe' => 'PENTING',
            'forum_thread_id' => $thread->id,
        ]);
        $resB->assertRedirect();

        $announcementB = Announcement::where('replaces_announcement_id', $announcementA->id)->first();
        $this->assertNotNull($announcementB);
        $this->assertEquals($thread->id, $announcementB->forum_thread_id);

        $view = $this->actingAs($this->userA)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $view->assertStatus(200);
        // Tampil banner Pembaruan
        $view->assertSee('Pembaruan dari Pengumuman Sebelumnya');
        $view->assertSee('Versi 1 Gotong Royong 07.00');
        // Tampil banner Forum
        $view->assertSee('Diskusikan di Forum');
        $view->assertSee(route('komunitas.forum.thread.show', $thread->id), false);
    }

    /**
     * 20. Satu Pengumuman tidak boleh menghubungkan forum dua kali (max 1 forum).
     */
    public function test_cannot_link_forum_if_already_linked(): void
    {
        $category = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();
        $thread1 = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $this->userA->id,
            'judul' => 'Thread Satu',
            'konten' => 'Isi 1.',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Warga'],
        ]);
        $thread2 = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $this->userA->id,
            'judul' => 'Thread Dua',
            'konten' => 'Isi 2.',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Warga'],
        ]);

        $announcement = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Satu Forum',
            'konten' => 'Hanya boleh satu forum.',
            'tipe' => 'INFO',
            'forum_thread_id' => $thread1->id,
        ]);

        $this->assertFalse($announcement->canLinkForum());

        $res = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.link-forum', $announcement->id), [
            'forum_thread_id' => $thread2->id,
        ]);
        $res->assertStatus(422);

        $this->assertEquals($thread1->id, $announcement->fresh()->forum_thread_id);
    }

    /**
     * 21. Active feed hanya menampilkan active (menyaring Expired, Deactivated, dan Replaced).
     */
    public function test_active_feed_strictly_filters_only_active_announcements(): void
    {
        // 1. Active
        $active = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'FEED_ITEM_ACTIVE',
            'konten' => 'Pengumuman aktif.',
            'tipe' => 'INFO',
        ]);

        // 2. Expired
        $expired = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'FEED_ITEM_EXPIRED',
            'konten' => 'Pengumuman kadaluarsa.',
            'tipe' => 'INFO',
            'expired_at' => now()->subDay()->toDateString(),
        ]);

        // 3. Deactivated
        $deactivated = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'FEED_ITEM_DEACTIVATED',
            'konten' => 'Pengumuman dinonaktifkan.',
            'tipe' => 'INFO',
            'is_deactivated' => true,
            'deactivated_at' => now(),
            'deactivated_by' => $this->ketuaRt->id,
            'deactivation_reason' => 'Dinonaktifkan demi tes filter.',
        ]);

        // 4. Replaced
        $replaced = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'FEED_ITEM_REPLACED',
            'konten' => 'Pengumuman digantikan.',
            'tipe' => 'INFO',
            'is_replaced' => true,
        ]);

        $activeFeedList = Announcement::active()->where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->pluck('judul')->toArray();

        $this->assertContains('FEED_ITEM_ACTIVE', $activeFeedList);
        $this->assertNotContains('FEED_ITEM_EXPIRED', $activeFeedList);
        $this->assertNotContains('FEED_ITEM_DEACTIVATED', $activeFeedList);
        $this->assertNotContains('FEED_ITEM_REPLACED', $activeFeedList);
    }

    /**
     * 22. Pin ordering existing tetap berfungsi: Pengumuman disematkan tetap tampil di urutan atas.
     */
    public function test_pinned_announcements_remain_first_in_ordering(): void
    {
        $unpinned = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Baru Tidak Disematkan',
            'konten' => 'Isi.',
            'tipe' => 'INFO',
            'is_pinned' => false,
            'created_at' => now(),
        ]);

        $pinned = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Lama Tapi Disematkan',
            'konten' => 'Isi pin.',
            'tipe' => 'PENTING',
            'is_pinned' => true,
            'created_at' => now()->subDays(5),
        ]);

        $res = $this->actingAs($this->userA)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $res->assertStatus(200);

        $announcementsInView = $res->viewData('announcements');
        $pinnedIndex = $announcementsInView->search(fn ($a) => $a->id === $pinned->id);
        $unpinnedIndex = $announcementsInView->search(fn ($a) => $a->id === $unpinned->id);
        $this->assertTrue($pinnedIndex !== false && $unpinnedIndex !== false);
        $this->assertTrue($pinnedIndex < $unpinnedIndex);
    }

    /**
     * 23. XSS protection tetap terjaga pada judul, konten, dan komentar.
     */
    public function test_xss_protection_in_announcements_and_comments(): void
    {
        $xssJudul = "<script>alert('xss-judul')</script>Judul Aman";
        $xssKonten = "<script>alert('xss-konten')</script>Konten Aman";

        $res = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.store'), [
            'scope_type' => 'rt',
            'judul' => $xssJudul,
            'konten' => $xssKonten,
            'tipe' => 'INFO',
        ]);
        $res->assertRedirect();

        $view = $this->actingAs($this->userA)->get(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));
        $view->assertStatus(200);
        // Tag script tidak dieksekusi secara mentah
        $view->assertDontSee("<script>alert('xss-judul')</script>", false);
        $view->assertDontSee("<script>alert('xss-konten')</script>", false);
        $view->assertSee(e($xssJudul), false);
        $view->assertSee(e($xssKonten), false);
    }

    /**
     * 24. Chat Phase 1 tetap GREEN: Pengiriman pesan chat dan broadcast event tetap berjalan normal.
     */
    public function test_chat_phase_1_remains_fully_green(): void
    {
        Event::fake([ChatMessageSent::class]);

        $res = $this->actingAs($this->userA)->postJson('/komunitas/chat/messages', [
            'scope_type' => 'rt',
            'konten' => 'Uji stabilitas Chat Phase 1 pada Phase 2.',
        ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('chat_messages', [
            'author_id' => $this->userA->id,
            'konten' => 'Uji stabilitas Chat Phase 1 pada Phase 2.',
        ]);

        Event::assertDispatched(ChatMessageSent::class);
    }

    /**
     * Scenario A — V1 Standalone:
     * V1 tidak punya previous, tidak punya newer, comment aktif.
     */
    public function test_scenario_a_v1_standalone(): void
    {
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman V1 Standalone',
            'konten' => 'Isi V1.',
            'tipe' => 'INFO',
        ]);

        $this->assertNull($v1->previous);
        $this->assertNull($v1->successor);
        $this->assertTrue($v1->isLatestVersion());
        $this->assertTrue($v1->canReceiveComments());

        // Buka via direct show URL
        $res = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v1->id));
        $res->assertStatus(200);
        $res->assertSee('Versi terbaru');
        $res->assertSee('Kirim'); // Form komentar aktif

        // Kirim tanggapan pada V1 berhasil
        $resComment = $this->actingAs($this->userA)->post(route('komunitas.pengumuman.komentar', $v1->id), [
            'konten' => 'Tanggapan warga untuk V1.',
        ]);
        $resComment->assertRedirect();
        $this->assertDatabaseHas('announcement_comments', [
            'announcement_id' => $v1->id,
            'konten' => 'Tanggapan warga untuk V1.',
        ]);
    }

    /**
     * Scenario B — V1 -> V2:
     * V1: bisa dibuka, comment lama terlihat, comment BARU ditolak, ada navigasi ke V2.
     * V2: bisa melihat V1, comment aktif.
     */
    public function test_scenario_b_v1_to_v2_transitions_and_navigation(): void
    {
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Jadwal Ronda V1 (21.00)',
            'konten' => 'Mulai jam 21.00.',
            'tipe' => 'INFO',
        ]);

        // Beri komentar saat V1 masih aktif
        AnnouncementComment::create([
            'announcement_id' => $v1->id,
            'author_id' => $this->userA->id,
            'konten' => 'Komentar asli V1.',
        ]);

        // Buat Pembaruan V2
        $resUpdate = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), [
            'judul' => 'Jadwal Ronda V2 (22.00)',
            'konten' => 'Revisi jam 22.00.',
            'tipe' => 'INFO',
        ]);
        $resUpdate->assertRedirect();

        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->first();
        $this->assertNotNull($v2);

        // 1. Direct buka V1
        $resV1 = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v1->id));
        $resV1->assertStatus(200);
        $resV1->assertSee('Versi Sebelumnya');
        $resV1->assertSee('Komentar asli V1.');
        $resV1->assertSee(route('komunitas.pengumuman.show', $v2->id), false); // Navigasi ke V2
        $resV1->assertSee('Tanggapan dikunci'); // Form ditutup

        // 2. Coba kirim komentar baru ke V1 -> DITOLAK HTTP 422
        $resBadComment = $this->actingAs($this->userA)->post(route('komunitas.pengumuman.komentar', $v1->id), [
            'konten' => 'Komentar liar pada versi kadaluarsa.',
        ]);
        $resBadComment->assertStatus(422);
        $this->assertDatabaseMissing('announcement_comments', [
            'konten' => 'Komentar liar pada versi kadaluarsa.',
        ]);

        // 3. Direct buka V2
        $resV2 = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v2->id));
        $resV2->assertStatus(200);
        $resV2->assertSee('Versi terbaru');
        $resV2->assertSee(route('komunitas.pengumuman.show', $v1->id), false); // Navigasi ke V1

        // 4. Komentar pada V2 berhasil
        $resGoodComment = $this->actingAs($this->userA)->post(route('komunitas.pengumuman.komentar', $v2->id), [
            'konten' => 'Komentar sah pada V2.',
        ]);
        $resGoodComment->assertRedirect();
        $this->assertDatabaseHas('announcement_comments', [
            'announcement_id' => $v2->id,
            'konten' => 'Komentar sah pada V2.',
        ]);
    }

    /**
     * Scenario C — V1 -> V2 -> V3:
     * Navigasi previous (V3 -> V2 -> V1) dan newer/latest (V1 -> V2 -> V3) dapat dijelajahi.
     */
    public function test_scenario_c_v1_to_v2_to_v3_chain_traversal(): void
    {
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Kegiatan V1',
            'konten' => 'Konten V1.',
            'tipe' => 'INFO',
        ]);

        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), [
            'judul' => 'Kegiatan V2',
            'konten' => 'Konten V2.',
            'tipe' => 'INFO',
        ]);
        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->first();

        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v2->id), [
            'judul' => 'Kegiatan V3',
            'konten' => 'Konten V3.',
            'tipe' => 'INFO',
        ]);
        $v3 = Announcement::where('replaces_announcement_id', $v2->id)->first();

        // 1. Pada V3: Ada tautan ke V2 (previous), tidak ada newer
        $resV3 = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v3->id));
        $resV3->assertSee(route('komunitas.pengumuman.show', $v2->id), false);
        $resV3->assertSee('Versi terbaru');

        // 2. Pada V2: Ada tautan ke V1 (previous) dan ke V3 (newer/latest)
        $resV2 = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v2->id));
        $resV2->assertSee(route('komunitas.pengumuman.show', $v1->id), false); // Ke V1
        $resV2->assertSee(route('komunitas.pengumuman.show', $v3->id), false); // Ke V3

        // 3. Pada V1: Ada tautan ke V2/V3 (newer/latest), tidak ada previous
        $resV1 = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v1->id));
        $resV1->assertSee(route('komunitas.pengumuman.show', $v3->id), false); // Ke V3
        $resV1->assertDontSee('← Versi Sebelumnya'); // Tidak ada versi lebih lama
    }

    /**
     * Scenario D — Comment Isolation:
     * V1 comments != V2 comments != V3 comments. Tidak ada komentar yang berpindah/tercampur.
     */
    public function test_scenario_d_comment_isolation_across_versions(): void
    {
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Informasi V1',
            'konten' => 'Konten V1.',
            'tipe' => 'INFO',
        ]);
        AnnouncementComment::create(['announcement_id' => $v1->id, 'author_id' => $this->userA->id, 'konten' => 'Komentar Eksklusif V1']);

        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), [
            'judul' => 'Informasi V2',
            'konten' => 'Konten V2.',
            'tipe' => 'INFO',
        ]);
        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->first();
        AnnouncementComment::create(['announcement_id' => $v2->id, 'author_id' => $this->userA->id, 'konten' => 'Komentar Eksklusif V2']);

        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v2->id), [
            'judul' => 'Informasi V3',
            'konten' => 'Konten V3.',
            'tipe' => 'INFO',
        ]);
        $v3 = Announcement::where('replaces_announcement_id', $v2->id)->first();
        AnnouncementComment::create(['announcement_id' => $v3->id, 'author_id' => $this->userA->id, 'konten' => 'Komentar Eksklusif V3']);

        // Assert database isolation
        $this->assertEquals(['Komentar Eksklusif V1'], $v1->comments()->pluck('konten')->toArray());
        $this->assertEquals(['Komentar Eksklusif V2'], $v2->comments()->pluck('konten')->toArray());
        $this->assertEquals(['Komentar Eksklusif V3'], $v3->comments()->pluck('konten')->toArray());

        // Assert view isolation
        $viewV1 = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v1->id));
        $viewV1->assertSee('Komentar Eksklusif V1');
        $viewV1->assertDontSee('Komentar Eksklusif V2');
        $viewV1->assertDontSee('Komentar Eksklusif V3');

        $viewV3 = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v3->id));
        $viewV3->assertSee('Komentar Eksklusif V3');
        $viewV3->assertDontSee('Komentar Eksklusif V1');
        $viewV3->assertDontSee('Komentar Eksklusif V2');
    }

    /**
     * Scenario E — Old Version Comment Protection:
     * Akses endpoint comment untuk V1/V2 ditolak saat versi tersebut sudah digantikan (HTTP 422).
     */
    public function test_scenario_e_old_version_comment_protection(): void
    {
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Tes Proteksi V1',
            'konten' => 'Konten V1.',
            'tipe' => 'INFO',
        ]);

        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), [
            'judul' => 'Tes Proteksi V2',
            'konten' => 'Konten V2.',
            'tipe' => 'INFO',
        ]);
        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->first();

        // 1. Submit ke V1 ditolak
        $resV1 = $this->actingAs($this->userA)->post(route('komunitas.pengumuman.komentar', $v1->id), [
            'konten' => 'Mencoba kirim ke V1 yang sudah digantikan.',
        ]);
        $resV1->assertStatus(422);

        // 2. Perbarui V2 ke V3, lalu submit ke V2 juga ditolak
        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v2->id), [
            'judul' => 'Tes Proteksi V3',
            'konten' => 'Konten V3.',
            'tipe' => 'INFO',
        ]);
        $resV2 = $this->actingAs($this->userA)->post(route('komunitas.pengumuman.komentar', $v2->id), [
            'konten' => 'Mencoba kirim ke V2 yang sudah digantikan.',
        ]);
        $resV2->assertStatus(422);

        // Pastikan tidak ada rekaman komentar baru di tabel untuk V1 dan V2
        $this->assertDatabaseMissing('announcement_comments', [
            'konten' => 'Mencoba kirim ke V1 yang sudah digantikan.',
        ]);
        $this->assertDatabaseMissing('announcement_comments', [
            'konten' => 'Mencoba kirim ke V2 yang sudah digantikan.',
        ]);
    }

    /**
     * Scenario F — Direct Old-Version URL:
     * Buka URL V1 setelah V3 sudah ada. V1 tetap terbuka (HTTP 200, no silent redirect),
     * ditandai sebagai versi lama, dan ada akses ke V3.
     */
    public function test_scenario_f_direct_old_version_url_no_silent_redirect(): void
    {
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Link WA Jadwal Awal V1',
            'konten' => 'Konten awal disebar di WA.',
            'tipe' => 'PENTING',
        ]);

        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), [
            'judul' => 'Link WA Jadwal Revisi V2',
            'konten' => 'Konten revisi V2.',
            'tipe' => 'PENTING',
        ]);
        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->first();

        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v2->id), [
            'judul' => 'Link WA Jadwal Final V3',
            'konten' => 'Konten final V3.',
            'tipe' => 'PENTING',
        ]);
        $v3 = Announcement::where('replaces_announcement_id', $v2->id)->first();

        // Simulasi warga membuka URL langsung V1 (seperti klik dari riwayat WhatsApp)
        $res = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v1->id));

        // WAJIB: Tidak boleh 302 redirect diam-diam, harus HTTP 200
        $res->assertStatus(200);

        // Menampilkan konten V1 secara utuh
        $res->assertSee('Link WA Jadwal Awal V1');
        $res->assertSee('Konten awal disebar di WA.');

        // Ditandai sebagai Versi Sebelumnya
        $res->assertSee('Versi Sebelumnya');
        $res->assertSee('Pengumuman ini sudah digantikan oleh pembaruan terbaru');

        // Menyediakan akses langsung ke V3
        $res->assertSee(route('komunitas.pengumuman.show', $v3->id), false);
    }

    /**
     * Scenario G — Deactivated Latest:
     * V3 dinonaktifkan. Histori V1 -> V2 -> V3 tetap konsisten dan dapat dijelajahi.
     */
    public function test_scenario_g_deactivated_latest_preserves_history(): void
    {
        $v1 = Announcement::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Acara V1',
            'konten' => 'Isi V1.',
            'tipe' => 'INFO',
        ]);

        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v1->id), [
            'judul' => 'Acara V2',
            'konten' => 'Isi V2.',
            'tipe' => 'INFO',
        ]);
        $v2 = Announcement::where('replaces_announcement_id', $v1->id)->first();

        $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.pembaruan', $v2->id), [
            'judul' => 'Acara V3',
            'konten' => 'Isi V3.',
            'tipe' => 'INFO',
        ]);
        $v3 = Announcement::where('replaces_announcement_id', $v2->id)->first();

        // Nonaktifkan V3 (versi terbaru)
        $resDeactivate = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.deactivate', $v3->id), [
            'alasan' => 'Kegiatan dibatalkan total karena kondisi darurat cuaca.',
        ]);
        $resDeactivate->assertRedirect();
        $this->assertTrue($v3->fresh()->is_deactivated);

        // 1. V3 tetap dapat dibuka langsung sebagai riwayat arsip
        $resV3 = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v3->id));
        $resV3->assertStatus(200);
        $resV3->assertSee('Pengumuman Dinonaktifkan');
        $resV3->assertSee('Kegiatan dibatalkan total karena kondisi darurat cuaca.');
        // V3 tetap memiliki link previous ke V2
        $resV3->assertSee(route('komunitas.pengumuman.show', $v2->id), false);
        // Komentar pada V3 ditolak
        $resV3Comment = $this->actingAs($this->userA)->post(route('komunitas.pengumuman.komentar', $v3->id), [
            'konten' => 'Coba komentar di V3 dinonaktifkan.',
        ]);
        $resV3Comment->assertStatus(422);

        // 2. V2 tetap memiliki relasi previous ke V1 dan successor ke V3
        $resV2 = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v2->id));
        $resV2->assertStatus(200);
        $resV2->assertSee(route('komunitas.pengumuman.show', $v1->id), false);
        $resV2->assertSee(route('komunitas.pengumuman.show', $v3->id), false);

        // 3. V1 tetap dapat dijelajahi
        $resV1 = $this->actingAs($this->userA)->get(route('komunitas.pengumuman.show', $v1->id));
        $resV1->assertStatus(200);
        $resV1->assertSee(route('komunitas.pengumuman.show', $v3->id), false);
    }
}

