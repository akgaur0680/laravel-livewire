<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostsCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/posts')->assertOk()->assertSee('Posts');

        Livewire::test('pages::posts')
            ->call('create')
            ->set('title', 'Hello')
            ->set('body', 'World')
            ->set('published', true)
            ->call('save')
            ->assertHasNoErrors();

        $post = Post::firstOrFail();
        $this->assertSame('Hello', $post->title);
        $this->assertTrue($post->published);

        Livewire::test('pages::posts')
            ->call('edit', $post->id)
            ->assertSet('title', 'Hello')
            ->set('title', 'Changed')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('Changed', $post->fresh()->title);

        Livewire::test('pages::posts')->set('title', '')->call('save')->assertHasErrors('title');

        Livewire::test('pages::posts')->call('delete', $post->id);
        $this->assertSame(0, Post::count());
    }

    public function test_search(): void
    {
        $this->actingAs(User::factory()->create());
        Post::create(['title' => 'Alpha', 'body' => 'x']);
        Post::create(['title' => 'Beta', 'body' => 'y']);

        Livewire::test('pages::posts')->set('search', 'Alph')->assertSee('Alpha')->assertDontSee('Beta');
    }
}
