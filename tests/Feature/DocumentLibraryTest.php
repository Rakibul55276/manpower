<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\DocumentLibrary\Models\LibraryDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DocumentLibraryTest extends TestCase
{
    use RefreshDatabase;

    private $superAdmin;
    private $admin;
    private $manager;
    private $otherManager;
    protected function setUp(): void
    {
        parent::setUp();
        config(['document_library.enabled' => true]);
        $this->superAdmin = $this->user('super', 'super_admin');
        $this->admin = $this->user('admin-one', 'admin');
        $this->manager = $this->user('manager-one', 'manager');
        $this->otherManager = $this->user('manager-two', 'manager');
    }

    public function test_user_can_view_and_edit_own_private_scan_metadata()
    {
        $document = LibraryDocument::create([
            'owner_id' => $this->manager->id,
            'uploaded_by' => $this->manager->id,
            'title' => 'Passport scan',
            'category' => 'Passport',
            'document_number' => 'P123456',
            'issued_on' => now()->subYear()->format('Y-m-d'),
            'expires_on' => now()->addYear()->format('Y-m-d'),
            'original_name' => 'passport.pdf',
            'storage_path' => 'document-library/'.$this->manager->id.'/passport.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);
        $this->assertSame($this->manager->id, $document->owner_id);
        $this->login($this->manager)->get(route('document-library.index'))->assertOk()->assertSee('Passport scan');
        $this->get(route('document-library.show', $document))->assertOk()->assertSee('P123456');
        $this->put(route('document-library.update', $document), [
            'title' => 'Updated passport scan',
            'category' => 'Identity',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('document_library_files', ['id' => $document->id, 'title' => 'Updated passport scan']);
    }

    public function test_private_documents_are_owner_scoped_and_only_admin_roles_can_delete()
    {
        $document = LibraryDocument::create([
            'owner_id' => $this->manager->id,
            'uploaded_by' => $this->manager->id,
            'title' => 'Private certificate',
            'original_name' => 'certificate.pdf',
            'storage_path' => 'document-library/'.$this->manager->id.'/certificate.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ]);
        $this->login($this->otherManager)->get(route('document-library.show', $document))->assertForbidden();
        $this->get(route('document-library.index'))->assertOk()->assertDontSee('Private certificate');
        $this->delete(route('document-library.destroy', $document))->assertForbidden();

        $adminDocument = LibraryDocument::create(['owner_id'=>$this->admin->id,'uploaded_by'=>$this->admin->id,'title'=>'Admin document','original_name'=>'admin.pdf','storage_path'=>'document-library/'.$this->admin->id.'/admin.pdf','mime_type'=>'application/pdf','size_bytes'=>100]);
        $disk = Mockery::mock();
        $disk->shouldReceive('delete')->once()->with($adminDocument->storage_path)->andReturnTrue();
        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);
        $this->login($this->admin)->delete(route('document-library.destroy',$adminDocument))->assertRedirect(route('document-library.index'));
        $this->assertDatabaseMissing('document_library_files',['id'=>$adminDocument->id]);

        $this->login($this->superAdmin)->get(route('document-library.index'))->assertOk()->assertSee('Private certificate');
        $disk = Mockery::mock();
        $disk->shouldReceive('delete')->once()->with($document->storage_path)->andReturnTrue();
        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);
        $this->delete(route('document-library.destroy', $document))->assertRedirect(route('document-library.index'));
        $this->assertDatabaseMissing('document_library_files', ['id' => $document->id]);
    }

    public function test_only_supported_scan_formats_are_accepted()
    {
        $this->login($this->manager)->post(route('document-library.store'), [
            'title' => 'Unsafe file',
            'scan' => UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('scan');
        $this->assertDatabaseCount('document_library_files', 0);
    }

    private function user($username, $role)
    {
        return User::create([
            'name' => ucfirst(str_replace('-', ' ', $username)),
            'username' => $username,
            'email' => $username.'@test.local',
            'password' => Hash::make('Strong-password-123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function login(User $user)
    {
        return $this->withSession(['password_hash_web' => $user->getAuthPassword()])->actingAs($user);
    }
}
