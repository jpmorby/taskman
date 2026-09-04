<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * `desc` holds markdown source, so it is stored verbatim and sanitised once on
 * render. These cover both halves of that bargain: nothing dangerous escapes
 * the render path, and nothing legitimate is destroyed on the way in.
 */
function task(string $desc = '', string $title = 'A task'): Task
{
    return Task::factory()->make(['desc' => $desc, 'title' => $title]);
}

test('script tags never reach the rendered description', function () {
    expect(task('<script>alert(1)</script>')->desc_html)
        ->not->toContain('<script>')
        ->not->toContain('alert(1)');
});

test('event handler attributes never reach the rendered description', function () {
    expect(task('<img src=x onerror="alert(1)">')->desc_html)
        ->not->toContain('onerror');
});

test('javascript urls are not rendered as links', function () {
    expect(task('[click me](javascript:alert(1))')->desc_html)
        ->not->toContain('javascript:');
});

test('html smuggled inside markdown is stripped', function () {
    expect(task("**bold**\n\n<iframe src=\"//evil.example\"></iframe>")->desc_html)
        ->toContain('<strong>bold</strong>')
        ->not->toContain('<iframe');
});

test('markdown written through the api is sanitised on render too', function () {
    $user = User::factory()->create();

    // The API stores raw input by design; the render path is what protects it.
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'desc' => '<script>alert(document.cookie)</script>',
    ]);

    expect($task->fresh()->desc_html)->not->toContain('<script>');
});

test('ordinary markdown is preserved rather than mangled on write', function () {
    $user = User::factory()->create();

    $source = "Tom & Jerry, 10 < 20\n\nSee <https://example.com> for more";

    $task = Task::factory()->create(['user_id' => $user->id, 'desc' => $source]);

    // Storing markdown through Purify used to delete the autolink and
    // entity-encode the ampersand; the column must hold what was typed.
    expect($task->fresh()->desc)->toBe($source);
});

test('an autolink survives to the rendered output', function () {
    expect(task('See <https://example.com> for more')->desc_html)
        ->toContain('https://example.com');
});

test('the preview is plain text and not double encoded', function () {
    expect(task('Tom & Jerry')->desc_preview)->toBe('Tom & Jerry');
});

test('the preview strips markup rather than showing it', function () {
    expect(task('**bold** and <b>html</b>')->desc_preview)
        ->toBe('bold and html');
});

test('a preview of legacy entity encoded content is not double encoded', function () {
    // Rows written before sanitisation moved to render time hold '&amp;'.
    expect(task('Tom &amp; Jerry')->desc_preview)->toBe('Tom & Jerry');
});

test('a null description renders as an empty string', function () {
    $task = Task::factory()->make(['desc' => null]);

    expect($task->desc_html)->toBe('')
        ->and($task->desc_preview)->toBe('');
});
