<?php

namespace Tests\Feature\Links;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $validLinkData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        
        // Подготавливаем валидные данные для переиспользования в тестах
        $this->validLinkData = [
            'url' => 'https://laravel.com',
            'title' => 'Laravel',
            'description' => 'The PHP Framework',
            'position' => 1,
        ];
    }

    /**
     * Тесты создания ссылки
     */
    public function test_url_is_required_for_creating_link(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), [
                ...$this->validLinkData,
                'url' => '',
            ]);

        $response
            ->assertSessionHasErrors('url')
            ->assertSessionDoesntHaveErrors(['title', 'description', 'position']);
    }

    public function test_url_must_be_valid_url_for_creating_link(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), [
                ...$this->validLinkData,
                'url' => 'not-a-url',
            ]);

        $response->assertSessionHasErrors('url');
    }

    public function test_url_must_not_exceed_255_characters(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), [
                ...$this->validLinkData,
                'url' => 'https://'.str_repeat('a', 255).'.com',
            ]);

        $response->assertSessionHasErrors('url');
    }

    public function test_title_is_required_for_creating_link(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), [
                ...$this->validLinkData,
                'title' => '',
            ]);

        $response
            ->assertSessionHasErrors('title')
            ->assertSessionDoesntHaveErrors(['url', 'description', 'position']);
    }

    public function test_title_must_not_exceed_255_characters(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), [
                ...$this->validLinkData,
                'title' => str_repeat('a', 256),
            ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_description_is_optional(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), [
                ...$this->validLinkData,
                'description' => null,
            ]);

        $response->assertSessionDoesntHaveErrors('description');
    }

    public function test_position_must_be_numeric(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), [
                ...$this->validLinkData,
                'position' => 'not-a-number',
            ]);

        $response->assertSessionHasErrors('position');
    }

    public function test_position_must_not_be_negative(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), [
                ...$this->validLinkData,
                'position' => -1,
            ]);

        $response->assertSessionHasErrors('position');
    }

    /**
     * Тесты обновления ссылки
     */
    public function test_url_is_required_for_updating_link(): void
    {
        $link = Link::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->put(route('links.update', $link), [
                ...$this->validLinkData,
                'url' => '',
            ]);

        $response->assertSessionHasErrors('url');
    }

    public function test_cannot_update_link_of_another_user(): void
    {
        $anotherUser = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->put(route('links.update', $link), $this->validLinkData);

        $response->assertForbidden();
    }

    /**
     * Тесты удаления ссылки
     */
    public function test_cannot_delete_link_of_another_user(): void
    {
        $anotherUser = User::factory()->create();
        $link = Link::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->delete(route('links.destroy', $link));

        $response->assertForbidden();
        $this->assertDatabaseHas('links', ['id' => $link->id]);
    }

    /**
     * Тесты авторизации
     */
    public function test_guest_cannot_access_links(): void
    {
        $link = Link::factory()->create();

        // Проверяем все маршруты
        $this->get(route('links.index'))->assertRedirect(route('login'));
        $this->get(route('links.create'))->assertRedirect(route('login'));
        $this->post(route('links.store'), $this->validLinkData)->assertRedirect(route('login'));
        $this->get(route('links.edit', $link))->assertRedirect(route('login'));
        $this->put(route('links.update', $link), $this->validLinkData)->assertRedirect(route('login'));
        $this->delete(route('links.destroy', $link))->assertRedirect(route('login'));
    }

    /**
     * Тесты issue_id
     */
    public function test_issue_id_must_exist_in_issues_table(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), [
                ...$this->validLinkData,
                'issue_id' => 999, // несуществующий ID
            ]);

        $response->assertSessionHasErrors('issue_id');
    }

    public function test_issue_id_can_be_null(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('links.store'), [
                ...$this->validLinkData,
                'issue_id' => null,
            ]);

        $response->assertSessionDoesntHaveErrors('issue_id');
    }
} 