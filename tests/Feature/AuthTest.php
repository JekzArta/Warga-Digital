<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DemoSeeder::class);
    }
    public function test_nik_is_hidden_from_serialization(): void
    {
        $user = User::where('nik', '3273021005050001')->first();
        $this->assertNotNull($user);

        $array = $user->toArray();
        $this->assertArrayNotHasKey('nik', $array, 'NIK tidak boleh ada di atribut toArray/JSON.');
        $this->assertArrayNotHasKey('password', $array);
    }

    /**
     * Uji coba login pengurus RT dengan NIK dan password.
     */
    public function test_user_can_login_with_nik(): void
    {
        $response = $this->post('/login', [
            'login' => '3273021005050001', // NIK Ketua RT
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Uji coba login Super Admin dengan email.
     */
    public function test_super_admin_can_login_with_email(): void
    {
        $response = $this->post('/login', [
            'login' => 'admin@wargadigital.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Warga dengan status belum_daftar diarahkan ke form aktivasi.
     */
    public function test_unregistered_citizen_is_redirected_to_activation(): void
    {
        $response = $this->post('/login', [
            'login' => '3273021005050041', // NIK Siti Rahmawati (belum_daftar)
            'password' => 'apapun',
        ]);

        $response->assertRedirect(route('first-time.form'));
        $this->assertGuest();
    }

    /**
     * Aktivasi akun warga pertama kali dengan pencocokan NIK dan tanggal lahir KTP.
     */
    public function test_citizen_first_time_activation_succeeds_with_correct_birthday(): void
    {
        $response = $this->post('/aktivasi-akun', [
            'nik' => '3273021005050041',
            'tanggal_lahir' => '1995-08-17',
            'password' => 'passwordBaru123',
            'password_confirmation' => 'passwordBaru123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('nik', '3273021005050041')->first();
        $this->assertEquals('aktif', $user->status);
        $this->assertTrue(Hash::check('passwordBaru123', $user->password));
    }

    /**
     * Aktivasi gagal jika tanggal lahir salah.
     */
    public function test_citizen_activation_fails_with_wrong_birthday(): void
    {
        $response = $this->post('/aktivasi-akun', [
            'nik' => '3273021005050041',
            'tanggal_lahir' => '1999-01-01',
            'password' => 'passwordBaru123',
            'password_confirmation' => 'passwordBaru123',
        ]);

        $response->assertSessionHasErrors('tanggal_lahir');
        $this->assertGuest();
    }
}
