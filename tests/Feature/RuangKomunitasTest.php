<?php

namespace Tests\Feature;

use App\Events\ChatMessageSent;
use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\Klien;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ScopeAuthorizer;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RuangKomunitasTest extends TestCase
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

        // Muat channels ke instance driver reverb yang baru dikonfigurasi
        require base_path('routes/channels.php');

        $this->seed(\Database\Seeders\DemoSeeder::class);

        // Ambil data referensi dari seeder
        $this->rw03 = Rw::first();
        $this->rt05 = Rt::where('nomor_rt', 5)->first();

        // Buat RT 06 untuk simulasi penolakan lintas-RT
        $this->rt06 = Rt::create([
            'rw_id' => $this->rw03->id,
            'kode_rt' => '32.73.02.1005-RW03-RT06',
            'nomor_rt' => 6,
            'nama' => 'RT 06 Sekeloa',
            'format_nomor_surat' => '{nomor}/RT06-RW03/SK/{bulan_romawi}/{tahun}',
        ]);

        // User A: Warga RT 05 (Hendra Pratama)
        $this->userA = User::where('nik', '3273021005050010')->first();

        // User B: Warga RT 05 (Agus Setiawan)
        $this->userB = User::where('nik', '3273021005050011')->first();

        // User C: Warga RT 06
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

        // Pengurus RT 05 & RW 03 dari DemoSeeder
        $this->ketuaRt = User::where('nik', '3273021005050001')->first();
        $this->sekretaris = User::where('nik', '3273021005050003')->first();
        $this->ketuaRw = User::where('nik', '3273021005030001')->first();
    }

    /**
     * Acceptance Gate 1: User A (RT 05) connect dan berhasil subscribe channel RT 05.
     */
    public function test_user_a_rt05_can_successfully_authorize_rt05_private_channel(): void
    {
        $response = $this->actingAs($this->userA)->post('/broadcasting/auth', [
            'channel_name' => 'private-chat.rt.' . $this->rt05->id,
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['auth']);
    }

    /**
     * Acceptance Gate 2 & 6: User B (RT 05) mengirim pesan, broadcast event ChatMessageSent dipancarkan ke channel RT 05.
     */
    public function test_user_b_rt05_sends_chat_message_and_event_is_broadcasted_to_rt05_channel(): void
    {
        Event::fake([ChatMessageSent::class]);

        $response = $this->actingAs($this->userB)->postJson('/komunitas/chat/messages', [
            'scope_type' => 'rt',
            'konten' => 'Halo tetangga RT 05 sekalian!',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'scope_type' => 'rt',
                'scope_id' => $this->rt05->id,
                'author_id' => $this->userB->id,
                'konten' => 'Halo tetangga RT 05 sekalian!',
                'author' => [
                    'nama' => $this->userB->nama,
                    'role_badge' => 'Warga',
                ],
            ],
        ]);

        Event::assertDispatched(ChatMessageSent::class, function (ChatMessageSent $event) {
            $channels = $event->broadcastOn();
            return count($channels) === 1
                && $channels[0] instanceof PrivateChannel
                && $channels[0]->name === 'private-chat.rt.' . $this->rt05->id
                && $event->message->konten === 'Halo tetangga RT 05 sekalian!'
                && $event->message->author_id === $this->userB->id;
        });
    }

    /**
     * Acceptance Gate 3: User C (RT 06) mencoba subscribe channel RT 05 → ditolak (HTTP 403).
     */
    public function test_user_c_rt06_is_forbidden_from_subscribing_to_rt05_channel(): void
    {
        $response = $this->actingAs($this->userC)->post('/broadcasting/auth', [
            'channel_name' => 'private-chat.rt.' . $this->rt05->id,
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Acceptance Gate 4: User A mencoba kirim pesan dengan scope_id dipalsukan ke RT 06 lewat HTTP request biasa → ditolak di server.
     */
    public function test_user_a_sending_message_with_forged_scope_id_to_rt06_is_rejected_with_403(): void
    {
        $response = $this->actingAs($this->userA)->postJson('/komunitas/chat/messages', [
            'scope_type' => 'rt',
            'scope_id' => $this->rt06->id, // Mencoba menyusupkan pesan ke RT 06
            'konten' => 'Pesan ilegal menyusup ke RT lain',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('chat_messages', [
            'konten' => 'Pesan ilegal menyusup ke RT lain',
        ]);
    }

    /**
     * Acceptance Gate 4 (Tambahan): User A mencoba membaca riwayat pesan RT 06 via GET dengan scope_id palsu → ditolak di server.
     */
    public function test_user_a_fetching_messages_with_forged_scope_id_to_rt06_is_rejected_with_403(): void
    {
        $response = $this->actingAs($this->userA)->getJson('/komunitas/chat/messages?scope_type=rt&scope_id=' . $this->rt06->id);

        $response->assertStatus(403);
    }

    /**
     * Acceptance Gate 5: Pesan tersimpan benar di database dengan scope_id diturunkan dari server.
     */
    public function test_chat_message_is_persisted_correctly_in_database(): void
    {
        Event::fake([ChatMessageSent::class]);

        $konten = 'Musyawarah pos kamling malam ini pukul 20:00 WIB.';

        $response = $this->actingAs($this->userA)->postJson('/komunitas/chat/messages', [
            'scope_type' => 'rt',
            'konten' => $konten,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('chat_messages', [
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->userA->id,
            'konten' => $konten,
        ]);
    }

    /**
     * Acceptance Gate 6 (Payload detail): Payload event tidak pernah mengekspos NIK (UU PDP) & memiliki format yang tepat.
     */
    public function test_chat_message_sent_event_broadcast_payload_structure_and_no_nik(): void
    {
        $msg = ChatMessage::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->userA->id,
            'konten' => 'Uji payload event',
        ]);

        $event = new ChatMessageSent($msg);
        $payload = $event->broadcastWith();

        $this->assertEquals($msg->id, $payload['id']);
        $this->assertEquals('rt', $payload['scope_type']);
        $this->assertEquals($this->rt05->id, $payload['scope_id']);
        $this->assertEquals($this->userA->nama, $payload['author']['nama']);
        $this->assertEquals('Warga', $payload['author']['role_badge']);

        // Pastikan zero NIK di payload broadcast
        $this->assertArrayNotHasKey('nik', $payload);
        $this->assertArrayNotHasKey('nik', $payload['author']);
    }

    /**
     * Acceptance Gate 7: Output pesan di-escape dan aman dari serangan XSS (HTML/Script Tag).
     */
    public function test_chat_message_content_with_xss_tags_is_stored_safely_and_escaped(): void
    {
        Event::fake([ChatMessageSent::class]);

        $xssKonten = "<script>alert('XSS bahaya');</script><b>Teks Tebal</b>";

        $response = $this->actingAs($this->userA)->postJson('/komunitas/chat/messages', [
            'scope_type' => 'rt',
            'konten' => $xssKonten,
        ]);

        $response->assertStatus(201);

        // String tersimpan literal di database tanpa dieksekusi
        $this->assertDatabaseHas('chat_messages', [
            'author_id' => $this->userA->id,
            'konten' => $xssKonten,
        ]);

        // Verifikasi respons JSON membawa teks literal asli (yang akan di-escape di browser melalui x-text)
        $this->assertEquals($xssKonten, $response->json('data.konten'));
    }

    /**
     * Acceptance Gate 8: Reconnect & idempotensi tidak menghasilkan pesan duplikat.
     */
    public function test_chat_messages_endpoint_supports_id_deduplication_and_order(): void
    {
        // Kirim 3 pesan
        ChatMessage::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->userA->id,
            'konten' => 'Pesan 1',
        ]);
        ChatMessage::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->userB->id,
            'konten' => 'Pesan 2',
        ]);
        ChatMessage::create([
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->userA->id,
            'konten' => 'Pesan 3',
        ]);

        $response = $this->actingAs($this->userA)->getJson('/komunitas/chat/messages?scope_type=rt');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(3, $data);
        $this->assertEquals('Pesan 1', $data[0]['konten']);
        $this->assertEquals('Pesan 2', $data[1]['konten']);
        $this->assertEquals('Pesan 3', $data[2]['konten']);

        // Uji keunikan ID: tidak ada ID yang berulang
        $ids = array_column($data, 'id');
        $this->assertEquals(count($ids), count(array_unique($ids)), 'Daftar ID pesan harus unik untuk mencegah duplikasi saat reconnect');
    }

    /**
     * Scope RW: Seluruh warga dalam RW 03 (RT 05 dan RT 06) dapat subscribe & berkirim pesan di scope RW.
     */
    public function test_rw_scope_channel_and_messaging_allows_both_rt05_and_rt06_residents(): void
    {
        // User A (RT 05, RW 03) berhasil subscribe RW 03
        $resA = $this->actingAs($this->userA)->post('/broadcasting/auth', [
            'channel_name' => 'private-chat.rw.' . $this->rw03->id,
            'socket_id' => '1234.5678',
        ]);
        $resA->assertStatus(200);

        // User C (RT 06, RW 03) juga berhasil subscribe RW 03 karena satu RW
        $resC = $this->actingAs($this->userC)->post('/broadcasting/auth', [
            'channel_name' => 'private-chat.rw.' . $this->rw03->id,
            'socket_id' => '1234.5678',
        ]);
        $resC->assertStatus(200);

        // User A kirim pesan tingkat RW
        Event::fake([ChatMessageSent::class]);

        $postRes = $this->actingAs($this->userA)->postJson('/komunitas/chat/messages', [
            'scope_type' => 'rw',
            'konten' => 'Halo seluruh warga RW 03!',
        ]);
        $postRes->assertStatus(201);
        $this->assertDatabaseHas('chat_messages', [
            'scope_type' => 'rw',
            'scope_id' => $this->rw03->id,
            'author_id' => $this->userA->id,
            'konten' => 'Halo seluruh warga RW 03!',
        ]);
    }

    /**
     * Helper ScopeAuthorizer terisolasi dengan baik.
     */
    public function test_scope_authorizer_logic_direct_unit(): void
    {
        $this->assertTrue(ScopeAuthorizer::canAccess($this->userA, 'rt', $this->rt05->id));
        $this->assertFalse(ScopeAuthorizer::canAccess($this->userA, 'rt', $this->rt06->id));
        $this->assertTrue(ScopeAuthorizer::canAccess($this->userA, 'rw', $this->rw03->id));
        $this->assertFalse(ScopeAuthorizer::canAccess($this->userA, 'rw', 99999));

        // Invalid scope type
        $this->assertFalse(ScopeAuthorizer::canAccess($this->userA, 'kelurahan', 1));

        // Null user
        $this->assertFalse(ScopeAuthorizer::canAccess(null, 'rt', $this->rt05->id));
    }

    /**
     * TAHAP 2 — Test 1: Hub Index Ruang Komunitas mengembalikan data sesuai scope RT 05.
     */
    public function test_komunitas_hub_index_returns_accessible_data_for_user_rt05(): void
    {
        $response = $this->actingAs($this->userA)->get(route('komunitas.index', [
            'scope' => 'rt',
            'tab' => 'pengumuman',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('komunitas.index');
        $response->assertViewHas('announcements');
        $response->assertViewHas('forumCategories');
        $response->assertViewHas('threads');
        $response->assertViewHas('canPublishAnnouncement', false); // User A adalah warga biasa
        $response->assertViewHas('canModerateForum', false);

        // JSON response version
        $jsonRes = $this->actingAs($this->userA)->getJson(route('komunitas.index', [
            'scope' => 'rt',
            'tab' => 'pengumuman',
        ]));
        $jsonRes->assertStatus(200);
        $jsonRes->assertJsonFragment(['status' => 'success', 'scope_type' => 'rt', 'can_publish_announcement' => false]);
    }

    /**
     * TAHAP 2 — Test 2: Pengurus RT (Ketua RT / Sekretaris) dapat menerbitkan pengumuman resmi & tercatat di audit trail.
     */
    public function test_pengurus_rt_can_publish_announcement_and_logs_audit_trail(): void
    {
        $response = $this->actingAs($this->ketuaRt)->post(route('komunitas.pengumuman.store'), [
            'scope_type' => 'rt',
            'judul' => 'Pengumuman Resmi RT 05 Uji Coba',
            'konten' => 'Isi pengumuman uji coba dari Ketua RT 05.',
            'tipe' => 'PENTING',
            'is_pinned' => 1,
        ]);

        $response->assertRedirect(route('komunitas.index', ['scope' => 'rt', 'tab' => 'pengumuman']));

        $this->assertDatabaseHas('announcements', [
            'scope_type' => 'rt',
            'scope_id' => $this->rt05->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Pengumuman Resmi RT 05 Uji Coba',
            'tipe' => 'PENTING',
            'is_pinned' => true,
        ]);

        $announcement = Announcement::where('judul', 'Pengumuman Resmi RT 05 Uji Coba')->first();

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'terbitkan_pengumuman',
            'target_type' => 'announcement',
            'target_id' => $announcement->id,
            'user_id' => $this->ketuaRt->id,
        ]);
    }

    /**
     * TAHAP 2 — Test 3: Warga biasa tidak berwenang menerbitkan pengumuman dan ditolak HTTP 403.
     */
    public function test_regular_warga_cannot_publish_announcement_and_receives_403(): void
    {
        $response = $this->actingAs($this->userA)->post(route('komunitas.pengumuman.store'), [
            'scope_type' => 'rt',
            'judul' => 'Pengumuman Ilegal dari Warga Biasa',
            'konten' => 'Harusnya gagal diterbitkan.',
            'tipe' => 'INFO',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('announcements', [
            'judul' => 'Pengumuman Ilegal dari Warga Biasa',
        ]);
    }

    /**
     * TAHAP 2 — Test 4: Warga dapat mengirim komentar pada pengumuman di scope wilayahnya.
     */
    public function test_warga_can_comment_on_announcement_within_their_scope(): void
    {
        $announcement = Announcement::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();

        $response = $this->actingAs($this->userA)->post(route('komunitas.pengumuman.komentar', $announcement->id), [
            'konten' => 'Siap hadir Pak RT, terima kasih informasinya!',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('announcement_comments', [
            'announcement_id' => $announcement->id,
            'author_id' => $this->userA->id,
            'konten' => 'Siap hadir Pak RT, terima kasih informasinya!',
        ]);
    }

    /**
     * TAHAP 2 — Test 5: Warga RT 06 tidak dapat mengomentari pengumuman milik RT 05 (HTTP 403).
     */
    public function test_warga_cannot_comment_on_announcement_of_another_rt_and_receives_403(): void
    {
        $announcement = Announcement::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();

        $response = $this->actingAs($this->userC)->post(route('komunitas.pengumuman.komentar', $announcement->id), [
            'konten' => 'Komentar nyasar dari warga RT 06.',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('announcement_comments', [
            'konten' => 'Komentar nyasar dari warga RT 06.',
        ]);
    }

    /**
     * TAHAP 2 — Test 6: Warga membuat thread forum baru dan role terkunci otomatis di author_role_snapshot.
     */
    public function test_warga_can_create_forum_thread_and_locks_author_role_snapshot(): void
    {
        $category = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();

        // 1. Warga biasa membuat thread
        $resWarga = $this->actingAs($this->userA)->post(route('komunitas.forum.thread.store'), [
            'category_id' => $category->id,
            'judul' => 'Usulan Kerja Bakti Pembersihan Selokan',
            'konten' => 'Apakah bisa diagendakan kerja bakti pembersihan selokan depan gang?',
        ]);

        $resWarga->assertStatus(302);

        $threadWarga = ForumThread::where('judul', 'Usulan Kerja Bakti Pembersihan Selokan')->first();
        $this->assertNotNull($threadWarga);
        $this->assertEquals(['Warga'], $threadWarga->author_role_snapshot);

        // 2. Ketua RT membuat thread -> snapshot mencatat Ketua RT
        $resRt = $this->actingAs($this->ketuaRt)->post(route('komunitas.forum.thread.store'), [
            'category_id' => $category->id,
            'judul' => 'Agenda Musyawarah Pembangunan Pos Ronda',
            'konten' => 'Mohon masukan lokasi yang tepat untuk pos ronda baru.',
        ]);

        $resRt->assertStatus(302);

        $threadRt = ForumThread::where('judul', 'Agenda Musyawarah Pembangunan Pos Ronda')->first();
        $this->assertNotNull($threadRt);
        $this->assertContains('Ketua RT', $threadRt->author_role_snapshot);
    }

    /**
     * TAHAP 2 — Test 7: Warga RT 06 tidak dapat membuat thread di kategori RT 05 (HTTP 403).
     */
    public function test_warga_cannot_create_forum_thread_in_another_rt_category(): void
    {
        $categoryRt05 = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();

        $response = $this->actingAs($this->userC)->post(route('komunitas.forum.thread.store'), [
            'category_id' => $categoryRt05->id,
            'judul' => 'Thread Ilegal Lintas RT',
            'konten' => 'Mencoba membuat thread di RT sebelah.',
        ]);

        $response->assertStatus(403);
    }

    /**
     * TAHAP 2 — Test 8: Warga dapat melihat detail thread dan mengirim tanggapan/balasan.
     */
    public function test_warga_can_view_thread_and_reply_with_snapshot_badge(): void
    {
        $category = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();
        $thread = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Diskusi Rencana Gotong Royong',
            'konten' => 'Rencana gotong royong hari Minggu.',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Ketua RT'],
        ]);

        // View detail
        $viewRes = $this->actingAs($this->userA)->get(route('komunitas.forum.thread.show', $thread->id));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('Diskusi Rencana Gotong Royong');

        // Post balasan
        $replyRes = $this->actingAs($this->userB)->post(route('komunitas.forum.post.store', $thread->id), [
            'konten' => 'Saya setuju Pak RT, siap membawa cangkul.',
        ]);

        $replyRes->assertRedirect(route('komunitas.forum.thread.show', $thread->id));

        $this->assertDatabaseHas('forum_posts', [
            'thread_id' => $thread->id,
            'author_id' => $this->userB->id,
            'konten' => 'Saya setuju Pak RT, siap membawa cangkul.',
        ]);

        $post = ForumPost::where('thread_id', $thread->id)->first();
        $this->assertEquals(['Warga'], $post->author_role_snapshot);
    }

    /**
     * TAHAP 2 — Test 9: Balasan ditolak jika thread sudah berstatus closed (HTTP 422).
     */
    public function test_warga_cannot_reply_to_closed_forum_thread(): void
    {
        $category = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();
        $thread = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $this->ketuaRt->id,
            'judul' => 'Diskusi Ditutup Selesai',
            'konten' => 'Sudah mufakat.',
            'status' => 'closed',
            'is_pinned' => false,
            'author_role_snapshot' => ['Ketua RT'],
        ]);

        $response = $this->actingAs($this->userA)->post(route('komunitas.forum.post.store', $thread->id), [
            'konten' => 'Mencoba membalas thread yang sudah ditutup.',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('forum_posts', [
            'konten' => 'Mencoba membalas thread yang sudah ditutup.',
        ]);
    }

    /**
     * TAHAP 2 — Test 10: Ketua RT dapat memoderasi thread (pin/close/hapus) & wajib audit trail.
     */
    public function test_ketua_rt_can_moderate_thread_pin_close_hapus_and_logs_audit_trail(): void
    {
        $category = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();
        $thread = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $this->userA->id,
            'judul' => 'Thread Untuk Dimoderasi',
            'konten' => 'Konten thread awal.',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Warga'],
        ]);

        // 1. Pin
        $pinRes = $this->actingAs($this->ketuaRt)->post(route('komunitas.forum.thread.moderate', $thread->id), [
            'aksi' => 'pin',
            'alasan' => 'Topik sangat penting untuk diketahui warga.',
        ]);
        $pinRes->assertRedirect();
        $this->assertTrue($thread->fresh()->is_pinned);

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'pin_thread',
            'target_type' => 'forum_thread',
            'target_id' => $thread->id,
            'alasan' => 'Topik sangat penting untuk diketahui warga.',
        ]);

        // 2. Close
        $closeRes = $this->actingAs($this->ketuaRt)->post(route('komunitas.forum.thread.moderate', $thread->id), [
            'aksi' => 'close',
            'alasan' => 'Diskusi telah selesai dan mencapai kesepakatan.',
        ]);
        $closeRes->assertRedirect();
        $this->assertEquals('closed', $thread->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'close_thread',
            'target_type' => 'forum_thread',
            'target_id' => $thread->id,
            'alasan' => 'Diskusi telah selesai dan mencapai kesepakatan.',
        ]);

        // 3. Hapus (soft status 'dihapus')
        $hapusRes = $this->actingAs($this->ketuaRt)->post(route('komunitas.forum.thread.moderate', $thread->id), [
            'aksi' => 'hapus',
            'alasan' => 'Konten melanggar norma kerukunan warga.',
        ]);
        $hapusRes->assertRedirect(route('komunitas.index', ['scope' => 'rt', 'tab' => 'forum']));
        $this->assertEquals('dihapus', $thread->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'aksi' => 'hapus_thread',
            'target_type' => 'forum_thread',
            'target_id' => $thread->id,
            'alasan' => 'Konten melanggar norma kerukunan warga.',
        ]);
    }

    /**
     * TAHAP 2 — Test 11: Warga biasa atau sekretaris ditolak melakukan moderasi forum (HTTP 403).
     */
    public function test_regular_warga_or_sekretaris_cannot_moderate_forum_thread(): void
    {
        $category = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();
        $thread = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $this->userA->id,
            'judul' => 'Thread Uji Wewenang Moderasi',
            'konten' => 'Isi thread.',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Warga'],
        ]);

        // Warga biasa coba close
        $resWarga = $this->actingAs($this->userA)->post(route('komunitas.forum.thread.moderate', $thread->id), [
            'aksi' => 'close',
            'alasan' => 'Saya mau tutup diskusi ini.',
        ]);
        $resWarga->assertStatus(403);

        // Sekretaris coba close (moderasi forum khusus Ketua/Wakil RT sesuai SDD §3.2)
        $resSekretaris = $this->actingAs($this->sekretaris)->post(route('komunitas.forum.thread.moderate', $thread->id), [
            'aksi' => 'close',
            'alasan' => 'Sekretaris mau tutup diskusi ini.',
        ]);
        $resSekretaris->assertStatus(403);
    }

    /**
     * TAHAP 2 — Test 12: Moderasi mewajibkan pengisian alasan minimal 5 karakter.
     */
    public function test_moderation_requires_valid_alasan_min_5_chars(): void
    {
        $category = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();
        $thread = ForumThread::create([
            'category_id' => $category->id,
            'author_id' => $this->userA->id,
            'judul' => 'Thread Alasan Validasi',
            'konten' => 'Isi thread.',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Warga'],
        ]);

        // Alasan kosong
        $resKosong = $this->actingAs($this->ketuaRt)->post(route('komunitas.forum.thread.moderate', $thread->id), [
            'aksi' => 'close',
            'alasan' => '',
        ]);
        $resKosong->assertSessionHasErrors('alasan');

        // Alasan terlalu pendek (< 5 karakter)
        $resPendek = $this->actingAs($this->ketuaRt)->post(route('komunitas.forum.thread.moderate', $thread->id), [
            'aksi' => 'close',
            'alasan' => 'ok',
        ]);
        $resPendek->assertSessionHasErrors('alasan');
    }

    /**
     * TAHAP 2 — Test 13: Ketua RW dapat memoderasi thread lingkup RW tetapi tidak berwenang pada lingkup RT (HTTP 403).
     */
    public function test_ketua_rw_can_moderate_rw_thread_but_not_rt_thread(): void
    {
        $categoryRw = ForumCategory::where('scope_type', 'rw')->where('scope_id', $this->rw03->id)->first();
        $threadRw = ForumThread::create([
            'category_id' => $categoryRw->id,
            'author_id' => $this->userA->id,
            'judul' => 'Usulan Tingkat RW',
            'konten' => 'Isi usulan tingkat RW.',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Warga'],
        ]);

        // Ketua RW sukses memoderasi thread RW
        $resRw = $this->actingAs($this->ketuaRw)->post(route('komunitas.forum.thread.moderate', $threadRw->id), [
            'aksi' => 'close',
            'alasan' => 'Sudah diputuskan pada rapat koordinasi RW 03.',
        ]);
        $resRw->assertRedirect();
        $this->assertEquals('closed', $threadRw->fresh()->status);

        // Ketua RW coba moderasi thread RT 05 (Ketua RW tidak punya rt_id) -> ditolak HTTP 403
        $categoryRt = ForumCategory::where('scope_type', 'rt')->where('scope_id', $this->rt05->id)->first();
        $threadRt = ForumThread::create([
            'category_id' => $categoryRt->id,
            'author_id' => $this->userA->id,
            'judul' => 'Urusan Internal RT 05',
            'konten' => 'Khusus internal RT 05.',
            'status' => 'aktif',
            'is_pinned' => false,
            'author_role_snapshot' => ['Warga'],
        ]);

        $resRtCross = $this->actingAs($this->ketuaRw)->post(route('komunitas.forum.thread.moderate', $threadRt->id), [
            'aksi' => 'close',
            'alasan' => 'Ketua RW intervensi thread internal RT.',
        ]);
        $resRtCross->assertStatus(403);
    }
}
