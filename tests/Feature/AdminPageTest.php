<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman statis admin: nama, judul, isi (editor HTML), tampil di beranda.
 */
class AdminPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::firstOrCreate(['email' => 'page-admin@example.org'], [
            'name' => 'Page Admin',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->firstOrCreate(['role' => 'admin']);

        return $u;
    }

    private function editor(): User
    {
        $u = User::firstOrCreate(['email' => 'page-editor@example.org'], [
            'name' => 'Page Editor',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->firstOrCreate(['role' => 'eic']);

        return $u;
    }

    public function test_admin_sees_pages_menu_and_index(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get('/admin/pages');
        $res->assertOk();
        $res->assertSee('Buat Page');
        $res->assertSee('create-page', false); // modal form
        $res->assertSee('page-html-editor', false); // editor TinyMCE (textarea)
        $res->assertSee('Belum ada page');
    }

    public function test_pages_appear_in_admin_sidebar(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get(route('admin.users.index'));
        $res->assertOk();
        $res->assertSee(route('admin.pages.index'));
        $res->assertSee('Pages');
    }

    public function test_create_page_via_ajax(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->postJson('/admin/pages', [
            'name' => 'Pedoman Penulis',
            'title' => 'Pedoman Penulis',
            'content' => '<h2>Selamat datang</h2><p>Tulisan <b>pertama</b>.</p>',
        ]);
        $res->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('pages', [
            'name' => 'pedoman-penulis', // nama dislug-kan otomatis
            'title' => 'Pedoman Penulis',
        ]);
        $page = Page::where('name', 'pedoman-penulis')->first();
        $this->assertStringContainsString('<b>pertama</b>', $page->content);
    }

    public function test_create_page_duplicate_name_gets_suffix(): void
    {
        $admin = $this->admin();
        Page::create(['name' => 'tentang', 'title' => 'Tentang', 'content' => '<p>1</p>']);

        $res = $this->actingAs($admin)->postJson('/admin/pages', [
            'name' => 'tentang',
            'title' => 'Tentang Kami',
            'content' => '<p>2</p>',
        ]);
        $res->assertOk();

        $this->assertDatabaseHas('pages', ['name' => 'tentang-2', 'title' => 'Tentang Kami']);
    }

    public function test_create_page_validation(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->postJson('/admin/pages', [
            'name' => '',
            'title' => '',
            'content' => '',
        ]);
        $res->assertStatus(422);
        $this->assertDatabaseCount('pages', 0);
    }

    public function test_update_page_via_ajax(): void
    {
        $admin = $this->admin();
        $page = Page::create(['name' => 'faq', 'title' => 'FAQ', 'content' => '<p>lama</p>']);

        $res = $this->actingAs($admin)->putJson("/admin/pages/{$page->id}", [
            'name' => 'faq-jurnal',
            'title' => 'FAQ Jurnal',
            'content' => '<p>baru</p>',
        ]);
        $res->assertOk()->assertJson(['ok' => true]);

        $page->refresh();
        $this->assertSame('faq-jurnal', $page->name);
        $this->assertSame('FAQ Jurnal', $page->title);
        $this->assertSame('<p>baru</p>', $page->content);
    }

    public function test_delete_page_via_ajax(): void
    {
        $admin = $this->admin();
        $page = Page::create(['name' => 'sementara', 'title' => 'Sementara', 'content' => '<p>x</p>']);

        // modal konfirmasi ada di daftar
        $this->actingAs($admin)->get('/admin/pages')->assertSee('delete-page-'.$page->id, false);

        $res = $this->actingAs($admin)->deleteJson("/admin/pages/{$page->id}");
        $res->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_public_page_renders_content(): void
    {
        Page::create([
            'name' => 'pedoman-penulis',
            'title' => 'Pedoman Penulis',
            'content' => '<h2>Cakupan</h2><p>Isi halaman.</p>',
        ]);

        $res = $this->get('/page/pedoman-penulis');
        $res->assertOk();
        $res->assertSee('Pedoman Penulis');
        $res->assertSee('<h2>Cakupan</h2>', false);
        $res->assertSee('Isi halaman.');
    }

    public function test_public_page_unknown_name_404(): void
    {
        $this->get('/page/tidak-ada')->assertNotFound();
    }

    public function test_home_lists_pages(): void
    {
        Page::create(['name' => 'pedoman-penulis', 'title' => 'Pedoman Penulis', 'content' => '<p>x</p>']);

        $res = $this->get('/');
        $res->assertOk();
        $res->assertSee('Pedoman Penulis');
        $res->assertSee('/page/pedoman-penulis');
    }

    public function test_home_without_pages_has_no_section(): void
    {
        $res = $this->get('/');
        $res->assertOk();
        $res->assertDontSee('/page/');
    }

    public function test_create_page_with_image(): void
    {
        $admin = $this->admin();
        Storage::fake('public');

        $res = $this->actingAs($admin)->post('/admin/pages', [
            'name' => 'Berita',
            'title' => 'Berita',
            'content' => '<p>isi</p>',
            'image' => UploadedFile::fake()->image('banner.png', 400, 200),
        ], ['Accept' => 'application/json']);

        $res->assertOk()->assertJson(['ok' => true]);
        $page = Page::where('name', 'berita')->first();
        $this->assertNotNull($page);
        $this->assertNotNull($page->image_path);
        Storage::disk('public')->assertExists($page->image_path);
        $this->assertStringContainsString('storage/', $page->image_url);
    }

    public function test_update_page_replaces_image_and_deletes_old_file(): void
    {
        $admin = $this->admin();
        Storage::fake('public');
        $page = Page::create(['name' => 'faq', 'title' => 'FAQ', 'content' => '<p>x</p>', 'image_path' => 'pages/lama.png']);

        $this->actingAs($admin)->put("/admin/pages/{$page->id}", [
            'name' => 'faq',
            'title' => 'FAQ',
            'content' => '<p>baru</p>',
            'image' => UploadedFile::fake()->image('baru.jpg', 300, 150),
        ], ['Accept' => 'application/json'])->assertOk();

        $fresh = $page->fresh();
        $this->assertNotSame('pages/lama.png', $fresh->image_path);
        Storage::disk('public')->assertExists($fresh->image_path);
        Storage::disk('public')->assertMissing('pages/lama.png');
    }

    public function test_destroy_deletes_image_file(): void
    {
        $admin = $this->admin();
        Storage::fake('public');
        $page = Page::create(['name' => 'faq', 'title' => 'FAQ', 'content' => '<p>x</p>', 'image_path' => 'pages/x.png']);

        $this->actingAs($admin)->deleteJson("/admin/pages/{$page->id}")->assertOk();
        Storage::disk('public')->assertMissing('pages/x.png');
    }

    public function test_form_shows_image_preview_markup(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get('/admin/pages');
        $res->assertOk();
        $res->assertSee('name="image"', false);
        $res->assertSee('pageImageInput', false);
        $res->assertSee('Belum ada gambar');
        $res->assertSee('Preview muncul langsung');
    }

    public function test_home_and_public_show_page_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pages/halaman.jpg', 'imgdata');
        Page::create(['name' => 'berita', 'title' => 'Berita', 'content' => '<p>isi</p>', 'is_active' => true, 'image_path' => 'pages/halaman.jpg']);

        // Beranda: hanya judul — tanpa gambar dan tanpa slug
        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('Berita');
        $home->assertDontSee('storage/pages/halaman.jpg');
        $home->assertDontSee('font-mono', false); // slug /nama tidak lagi ditampilkan

        // Halaman publik: gambar tetap tampil
        $pub = $this->get('/page/berita');
        $pub->assertOk()->assertSee('storage/pages/halaman.jpg');
    }

    public function test_form_name_field_is_disabled_and_synced_from_title(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get('/admin/pages');
        $res->assertOk();
        // input tampilan: disabled (tidak bisa diketik, dilewati autofokus modal)
        $res->assertSee('x-ref="namePreview" readonly disabled', false);
        // input hidden: inilah yang terkirim ke server
        $res->assertSee('<input type="hidden" name="name" x-ref="nameHidden" value="">', false);
        $res->assertSee('Terisi otomatis dari Judul');

        // saat edit: slug lama tetap terisi (tampilan + hidden)
        Page::create(['name' => 'faq', 'title' => 'FAQ', 'content' => '<p>x</p>', 'is_active' => true]);
        $res = $this->actingAs($admin)->get('/admin/pages');
        $res->assertOk();
        $res->assertSee('x-ref="namePreview" readonly disabled', false);
        $res->assertSee('<input type="hidden" name="name" x-ref="nameHidden" value="faq">', false);
    }

    public function test_toggle_active_via_ajax(): void
    {
        $admin = $this->admin();
        $page = Page::create(['name' => 'faq', 'title' => 'FAQ', 'content' => '<p>x</p>', 'is_active' => true]);

        $res = $this->actingAs($admin)->postJson('/admin/pages/'.$page->id.'/toggle-active');
        $res->assertOk()->assertJson(['ok' => true]);
        $this->assertStringContainsString('disembunyikan', $res->json('message'));
        $this->assertFalse($page->fresh()->is_active);

        // toggle lagi → aktif
        $this->actingAs($admin)->postJson('/admin/pages/'.$page->id.'/toggle-active')->assertOk();
        $this->assertTrue($page->fresh()->is_active);
    }

    public function test_home_only_lists_active_pages(): void
    {
        Page::create(['name' => 'aktif', 'title' => 'Page Aktif', 'content' => '<p>x</p>', 'is_active' => true]);
        Page::create(['name' => 'nonaktif', 'title' => 'Page Nonaktif', 'content' => '<p>x</p>', 'is_active' => false]);

        $res = $this->get('/');
        $res->assertOk();
        $res->assertSee('Page Aktif');
        $res->assertDontSee('Page Nonaktif');
    }

    public function test_public_inactive_page_404(): void
    {
        Page::create(['name' => 'nonaktif', 'title' => 'Page Nonaktif', 'content' => '<p>x</p>', 'is_active' => false]);

        $this->get('/page/nonaktif')->assertNotFound();
    }

    public function test_index_shows_status_toggle_icons(): void
    {
        $admin = $this->admin();
        $on = Page::create(['name' => 'on', 'title' => 'ON', 'content' => '<p>x</p>', 'is_active' => true]);
        $off = Page::create(['name' => 'off', 'title' => 'OFF', 'content' => '<p>x</p>', 'is_active' => false]);

        $res = $this->actingAs($admin)->get('/admin/pages');
        $res->assertOk();
        $res->assertSee(route('admin.pages.toggle', $on), false);
        $res->assertSee(route('admin.pages.toggle', $off), false);
        $res->assertSee('Sembunyikan dari beranda');
        $res->assertSee('Tampilkan di beranda');
        $res->assertSee('Dinonaktifkan');
    }

    public function test_non_admin_cannot_manage_pages(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->get('/admin/pages')->assertForbidden();
        $this->actingAs($editor)->postJson('/admin/pages', [
            'name' => 'x', 'title' => 'X', 'content' => '<p>x</p>',
        ])->assertForbidden();
    }

    public function test_guest_cannot_manage_pages(): void
    {
        $this->get('/admin/pages')->assertRedirect(route('login'));
    }
}
