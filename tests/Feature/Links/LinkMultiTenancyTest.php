<?php

namespace Tests\Feature\Links;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkMultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Link $linkA;
    private Link $linkB;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем двух пользователей
        $this->userA = User::factory()->create(['name' => 'User A']);
        $this->userB = User::factory()->create(['name' => 'User B']);

        // Создаем ссылки для каждого пользователя
        $this->linkA = Link::factory()->create([
            'user_id' => $this->userA->id,
            'title' => 'Link of User A',
        ]);

        $this->linkB = Link::factory()->create([
            'user_id' => $this->userB->id,
            'title' => 'Link of User B',
        ]);

        // Создаем дополнительные ссылки для проверки списков
        Link::factory(3)->create(['user_id' => $this->userA->id]);
        Link::factory(2)->create(['user_id' => $this->userB->id]);
    }

    /**
     * Тесты просмотра списка ссылок
     */
    public function test_user_can_only_see_own_links(): void
    {
        $response = $this
            ->actingAs($this->userA)
            ->get(route('links.index'));

        $response
            ->assertStatus(200)
            ->assertViewHas('links')
            ->assertSee('Link of User A')
            ->assertDontSee('Link of User B');

        // Проверяем, что в списке точно 4 ссылки (1 + 3 дополнительные)
        $this->assertEquals(4, $response->viewData('links')->count());
    }

    public function test_links_query_scoped_to_authenticated_user(): void
    {
        $this->actingAs($this->userA);
        
        $links = Link::query()
            ->where('user_id', auth()->id())
            ->get();

        $this->assertEquals(4, $links->count());
        $this->assertTrue($links->every(fn ($link) => $link->user_id === $this->userA->id));
    }

    /**
     * Тесты редактирования
     */
    public function test_user_cannot_edit_others_links(): void
    {
        $response = $this
            ->actingAs($this->userA)
            ->get(route('links.edit', $this->linkB));

        $response->assertForbidden();
    }

    public function test_user_cannot_update_others_links(): void
    {
        $response = $this
            ->actingAs($this->userA)
            ->put(route('links.update', $this->linkB), [
                'url' => 'https://example.com',
                'title' => 'Updated Title',
                'description' => 'Updated Description',
            ]);

        $response->assertForbidden();

        // Проверяем, что данные не изменились
        $this->assertDatabaseHas('links', [
            'id' => $this->linkB->id,
            'title' => 'Link of User B',
        ]);
    }

    /**
     * Тесты удаления
     */
    public function test_user_cannot_delete_others_links(): void
    {
        $response = $this
            ->actingAs($this->userA)
            ->delete(route('links.destroy', $this->linkB));

        $response->assertForbidden();
        
        // Проверяем, что ссылка не была удалена
        $this->assertDatabaseHas('links', ['id' => $this->linkB->id]);
    }

    /**
     * Тесты создания
     */
    public function test_new_link_assigned_to_authenticated_user(): void
    {
        $response = $this
            ->actingAs($this->userA)
            ->post(route('links.store'), [
                'url' => 'https://laravel.com',
                'title' => 'New Link',
                'description' => 'Description',
            ]);

        $response->assertRedirect();

        // Проверяем, что ссылка создана для правильного пользователя
        $this->assertDatabaseHas('links', [
            'title' => 'New Link',
            'user_id' => $this->userA->id,
        ]);
    }

    /**
     * Тесты переключения между пользователями
     */
    public function test_switching_users_shows_different_links(): void
    {
        // Проверяем для первого пользователя
        $responseA = $this
            ->actingAs($this->userA)
            ->get(route('links.index'));

        $responseA
            ->assertStatus(200)
            ->assertSee('Link of User A')
            ->assertDontSee('Link of User B');

        // Проверяем для второго пользователя
        $responseB = $this
            ->actingAs($this->userB)
            ->get(route('links.index'));

        $responseB
            ->assertStatus(200)
            ->assertSee('Link of User B')
            ->assertDontSee('Link of User A');
    }

    /**
     * Тесты безопасности
     */
    public function test_user_cannot_assign_link_to_another_user(): void
    {
        $response = $this
            ->actingAs($this->userA)
            ->post(route('links.store'), [
                'url' => 'https://laravel.com',
                'title' => 'New Link',
                'description' => 'Description',
                'user_id' => $this->userB->id, // Пытаемся присвоить ссылку другому пользователю
            ]);

        // Проверяем, что ссылка всё равно создана для текущего пользователя
        $this->assertDatabaseHas('links', [
            'title' => 'New Link',
            'user_id' => $this->userA->id,
        ]);

        $this->assertDatabaseMissing('links', [
            'title' => 'New Link',
            'user_id' => $this->userB->id,
        ]);
    }

    public function test_user_cannot_move_link_to_another_user(): void
    {
        $response = $this
            ->actingAs($this->userA)
            ->put(route('links.update', $this->linkA), [
                'url' => 'https://laravel.com',
                'title' => 'Updated Link',
                'description' => 'Description',
                'user_id' => $this->userB->id, // Пытаемся передать ссылку другому пользователю
            ]);

        // Проверяем, что владелец ссылки не изменился
        $this->assertDatabaseHas('links', [
            'id' => $this->linkA->id,
            'title' => 'Updated Link',
            'user_id' => $this->userA->id,
        ]);
    }

    /**
     * Тесты пагинации
     */
    public function test_pagination_respects_user_isolation(): void
    {
        // Создаем много ссылок для первого пользователя
        Link::factory(15)->create(['user_id' => $this->userA->id]);

        $response = $this
            ->actingAs($this->userA)
            ->get(route('links.index'));

        // Проверяем, что на первой странице 10 ссылок
        $this->assertEquals(10, $response->viewData('links')->count());

        // Проверяем, что все ссылки принадлежат правильному пользователю
        $this->assertTrue(
            $response->viewData('links')
                ->every(fn ($link) => $link->user_id === $this->userA->id)
        );
    }
} 