<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Maintenance;
use App\Models\Event;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class InstallTest extends TestCase
{
    use RefreshDatabase;

    private function legacyTables(): void
    {
        Schema::create('agenda', function (Blueprint $t) {
            $t->integer('id');
            $t->date('date');
            $t->string('lieu');
            $t->text('contenu');
            $t->text('titre');
            $t->text('compositeurs');
            $t->string('special');
            $t->string('heure', 5);
            $t->string('jour', 20);
        });
        DB::table('agenda')->insert([
            ['id' => 194, 'date' => '2027-01-10', 'lieu' => 'Boverie', 'contenu' => 'Ensemble Emeraude<br /><br />Ales Ulrich, violon', 'titre' => '« Florilège »', 'compositeurs' => 'Janacek', 'special' => '0', 'heure' => '11h', 'jour' => 'dimanche'],
        ]);
        Schema::create('newsletter', function (Blueprint $t) {
            $t->integer('id');
            $t->string('email');
        });
        DB::table('newsletter')->insert([['id' => 1, 'email' => 'Ami@Exemple.be']]);
    }

    public function test_installer_is_closed_without_token(): void
    {
        config(['acdm.install_token' => '']);
        $this->get('/installation?cle=')->assertNotFound();

        config(['acdm.install_token' => 'secret-123']);
        $this->get('/installation')->assertNotFound();
        $this->get('/installation?cle=mauvais')->assertNotFound();
        $this->get('/installation?cle=secret-123')->assertOk()->assertSee('Installation du site ACDM');
    }

    public function test_installer_sets_everything_up_and_imports_from_database(): void
    {
        config(['acdm.install_token' => 'secret-123']);
        $this->legacyTables();

        $this->post('/installation', [
            'cle' => 'secret-123',
            'import' => '1',
            'name' => 'Arnaud',
            'email' => 'arnaud@acdm.eu',
            'password' => 'un-mot-de-passe-solide',
        ])->assertOk()->assertSee('Installation terminée');

        $this->assertTrue(User::where('email', 'arnaud@acdm.eu')->where('role', UserRole::Admin->value)->exists());
        $this->assertSame('Florilège', Event::where('legacy_id', 194)->value('title'));
        $this->assertSame(1, Subscriber::count());
        $this->assertDatabaseHas('pages', ['slug' => 'mentions-legales']);
    }

    public function test_installer_validates_admin_fields(): void
    {
        config(['acdm.install_token' => 'secret-123']);

        $this->post('/installation', ['cle' => 'secret-123', 'name' => '', 'email' => 'pas-un-mail', 'password' => 'court'])
            ->assertOk()
            ->assertSee('Adresse e-mail invalide')
            ->assertSee('au moins 12 caractères');
        $this->assertSame(0, User::count());
    }

    public function test_import_command_reads_legacy_tables(): void
    {
        $this->seed();
        $this->legacyTables();

        $this->artisan('acdm:import-legacy', ['--from-db' => true])->assertSuccessful();
        $this->assertSame(1, Event::count());
        $this->assertSame('ami@exemple.be', Subscriber::value('email'));
    }

    public function test_maintenance_page_is_forbidden_to_editors(): void
    {
        $editor = User::create(['name' => 'Cécile', 'email' => 'c@x.be', 'password' => 'un-long-mot-de-passe', 'role' => UserRole::Editor]);
        $this->actingAs($editor)->get('/admin/maintenance')->assertForbidden();
    }

    public function test_maintenance_page_works_for_admins(): void
    {
        $admin = User::create(['name' => 'Arnaud', 'email' => 'a@x.be', 'password' => 'un-long-mot-de-passe', 'role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/admin/maintenance')->assertOk()->assertSee('Mettre à jour la base');

        Livewire::actingAs($admin)->test(Maintenance::class)->callAction('clear')->assertSet('output', fn ($o) => $o !== '');
    }
}
