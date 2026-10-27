<?php

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Support\PublicUrls;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The cards' links on a listing, in order.
 *
 * @return list<string>
 */
function cardLinks(string $html): array
{
    preg_match_all('#<a href="([^"]+)" class="post-card">#', $html, $links);

    return $links[1];
}

it('makes an article a page type, at its own root address', function () {
    $article = Page::factory()->ofType(PageType::Article)->create(['slug' => 'ce-este-seo-on-page']);

    expect(PageType::from('article'))->toBe(PageType::Article)
        ->and(PageType::Article->label())->toBe('Articol')
        ->and(PublicUrls::for($article))->toBe('/ce-este-seo-on-page/');

    // Served by the catch-all, like any other page.
    $this->get('/ce-este-seo-on-page')->assertOk();
});

it('gives a category its articles through pages(), and nothing else', function () {
    $category = PostCategory::factory()->create();
    $article = Page::factory()->ofType(PageType::Article)->create(['post_category_id' => $category->id]);
    // A page of another type with the column set is still not an article.
    Page::factory()->create(['post_category_id' => $category->id]);
    Page::factory()->ofType(PageType::Article)->create();
    $post = Post::factory()->create(['post_category_id' => $category->id]);

    expect($category->pages()->sole()->is($article))->toBeTrue()
        ->and($article->category?->is($category))->toBeTrue()
        // posts() is left as it was.
        ->and($category->posts()->sole()->is($post))->toBeTrue();
});

it('leaves its articles without a category when one is deleted', function () {
    $category = PostCategory::factory()->create();
    $article = Page::factory()->ofType(PageType::Article)->create(['post_category_id' => $category->id]);

    $category->delete();

    expect($article->fresh()?->post_category_id)->toBeNull();
});

it('lists only published articles at /blog, newest first, each linking to its root address', function () {
    Page::factory()->ofType(PageType::Article)->create(['slug' => 'al-treilea', 'published_at' => now()->subDays(3)]);
    Page::factory()->ofType(PageType::Article)->create(['slug' => 'cel-mai-nou', 'published_at' => now()->subHour()]);
    Page::factory()->ofType(PageType::Article)->create(['slug' => 'al-doilea', 'published_at' => now()->subDays(2)]);
    Page::factory()->ofType(PageType::Article)->create(['slug' => 'al-patrulea', 'published_at' => now()->subDays(4)]);

    Page::factory()->ofType(PageType::Article)->draft()->create(['slug' => 'ciorna', 'title' => 'Articol ciornă']);
    Page::factory()->ofType(PageType::Article)->scheduled()->create(['slug' => 'programat', 'title' => 'Articol programat']);
    Page::factory()->ofType(PageType::Article)->create(['slug' => 'sters', 'title' => 'Articol șters'])->delete();
    Page::factory()->ofType(PageType::Service)->create(['slug' => 'serviciu', 'title' => 'Un serviciu']);
    Post::factory()->create(['title' => 'O postare', 'status' => ContentStatus::Published, 'published_at' => now()->subMinute()]);

    $html = $this->get('/blog')->assertOk()->getContent();

    expect(cardLinks($html))->toBe(['/cel-mai-nou/', '/al-doilea/', '/al-treilea/', '/al-patrulea/'])
        ->and($html)->not->toContain('class="blog-lead"')
        ->not->toContain('page-hero--led')
        ->and($html)->toContain('<span class="blog-categories__count">4</span>')
        ->not->toContain('Articol ciornă')
        ->not->toContain('Articol programat')
        ->not->toContain('Articol șters')
        ->not->toContain('Un serviciu')
        ->not->toContain('O postare');
});

it('pages through the articles with ?pagina=, and finds no page past the last', function () {
    // 12 cards: nine, then three.
    Page::factory()->ofType(PageType::Article)->count(12)
        ->sequence(fn (Sequence $sequence) => ['slug' => 'articol-'.($sequence->index + 1), 'published_at' => now()->subDays($sequence->index + 1)])
        ->create();

    $first = $this->get('/blog')->assertOk()->getContent();
    $second = $this->get('/blog?pagina=2')->assertOk()->getContent();

    expect(cardLinks($first))->toHaveCount(9)->toContain('/articol-1/')->not->toContain('/articol-10/')
        ->and($second)->toContain('<title>Blog, pagina 2 | Webis</title>')
        ->and(cardLinks($second))->toBe(['/articol-10/', '/articol-11/', '/articol-12/']);

    $this->get('/blog?pagina=3')->assertNotFound();
});

it('says so on /blog while there are no articles', function () {
    Page::factory()->ofType(PageType::Article)->draft()->create();

    $html = $this->get('/blog')->assertOk()->getContent();

    expect($html)->toContain('<p class="portfolio-empty">Încă nu am publicat articole.</p>')
        ->not->toContain('class="blog-lead"')
        ->not->toContain('class="post-card"')
        ->not->toContain('class="blog-categories"')
        ->not->toContain('page-hero--led');
});

it('lists one category\'s articles at /blog/categorie/{slug}, with its own title and pages', function () {
    $seo = PostCategory::factory()->create([
        'name' => 'SEO',
        'slug' => 'seo',
        'description' => 'Cum te găsesc clienții pe Google.',
    ]);
    Page::factory()->ofType(PageType::Article)->count(10)
        ->sequence(fn (Sequence $sequence) => ['slug' => 'seo-'.($sequence->index + 1), 'published_at' => now()->subDays($sequence->index + 1)])
        ->create(['post_category_id' => $seo->id]);
    Page::factory()->ofType(PageType::Article)->draft()->create(['slug' => 'seo-ciorna', 'post_category_id' => $seo->id]);
    Page::factory()->ofType(PageType::Article)->create(['slug' => 'alt-subiect', 'published_at' => now()->subMinute()]);

    $html = $this->get('/blog/categorie/seo')->assertOk()->getContent();

    expect($html)->toContain('<title>SEO · Blog | Webis</title>')
        ->toContain('<meta name="description" content="Cum te găsesc clienții pe Google.">')
        ->and(cardLinks($html))->toHaveCount(9)->toContain('/seo-1/')->not->toContain('/alt-subiect/')
        ->and($html)->toContain('href="'.route('posts.category', 'seo').'?pagina=2"');

    expect(cardLinks($this->get('/blog/categorie/seo?pagina=2')->assertOk()->getContent()))->toBe(['/seo-10/']);

    $this->get('/blog/categorie/seo?pagina=3')->assertNotFound();
});

it('says so on a category with no published articles yet', function () {
    PostCategory::factory()->create(['slug' => 'gazduire']);

    $this->get('/blog/categorie/gazduire')->assertOk()
        ->assertSee('Încă nu am publicat articole în această categorie.');
});

it('finds no category at an unknown slug', function () {
    $this->get('/blog/categorie/nu-exista')->assertNotFound();
    $this->get('/blog/categorie/Nu_Exista')->assertNotFound();
});

it('redirects a renamed category from its old address', function () {
    $category = PostCategory::factory()->create(['slug' => 'seo']);
    $category->update(['slug' => 'optimizare-seo']);

    $this->get('/blog/categorie/seo')
        ->assertStatus(301)
        ->assertRedirect(route('posts.category', 'optimizare-seo'));
});

it('sends the old site\'s project archive to the portfolio', function () {
    $this->get('/proiect')->assertStatus(301)->assertRedirect('/portofoliu/');
});
