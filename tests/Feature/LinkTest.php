<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_links_page(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('links.index'));

        $response->assertStatus(200);
        $response->assertViewIs('links.index');
    }

    public function test_user_can_create_link(): void
    {
        $linkData = [
            'url' => 'https://laravel.com',
            'title' => 'Laravel Website',
            'description' => 'Official Laravel website',
            'position' => 1,
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), $linkData);

        $response
            ->assertRedirect(route('links.index'))
            ->assertSessionHas('success', 'Ссылка успешно создана');

        $this->assertDatabaseHas('links', [
            'url' => $linkData['url'],
            'title' => $linkData['title'],
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_update_own_link(): void
    {
        $link = Link::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $updatedData = [
            'url' => 'https://laravel.com/docs',
            'title' => 'Laravel Documentation',
            'description' => 'Updated description',
            'position' => 2,
        ];

        $response = $this
            ->actingAs($this->user)
            ->put(route('links.update', $link), $updatedData);

        $response
            ->assertRedirect(route('links.index'))
            ->assertSessionHas('success', 'Ссылка успешно обновлена');

        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'url' => $updatedData['url'],
            'title' => $updatedData['title'],
        ]);
    }

    public function test_user_can_delete_own_link(): void
    {
        $link = Link::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->delete(route('links.destroy', $link));

        $response
            ->assertRedirect(route('links.index'))
            ->assertSessionHas('success', 'Ссылка успешно удалена');

        $this->assertDatabaseMissing('links', [
            'id' => $link->id,
        ]);
    }

    public function test_links_are_ordered_by_position(): void
    {
        // Создаем ссылки в случайном порядке
        Link::factory()->create([
            'user_id' => $this->user->id,
            'position' => 3,
            'title' => 'Third Link',
        ]);

        Link::factory()->create([
            'user_id' => $this->user->id,
            'position' => 1,
            'title' => 'First Link',
        ]);

        Link::factory()->create([
            'user_id' => $this->user->id,
            'position' => 2,
            'title' => 'Second Link',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('links.index'));

        $response->assertStatus(200);
        
        // Проверяем порядок ссылок на странице
        $response->assertSeeInOrder([
            'First Link',
            'Second Link',
            'Third Link',
        ]);
    }

    public function test_user_can_see_create_link_form(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('links.create'));

        $response->assertStatus(200);
        $response->assertViewIs('links.create');
    }

    public function test_user_can_see_edit_link_form(): void
    {
        $link = Link::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('links.edit', $link));

        $response->assertStatus(200);
        $response->assertViewIs('links.edit');
        $response->assertSee($link->title);
        $response->assertSee($link->url);
    }
}
