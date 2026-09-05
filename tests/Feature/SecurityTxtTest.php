<?php

use Illuminate\Support\Carbon;

function securityTxt(): string
{
    return test()->get('/.well-known/security.txt')->getContent();
}

it('serves security.txt as plain text', function () {
    $response = $this->get('/.well-known/security.txt');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('text/plain');
});

it('advertises the configured security contact', function () {
    config(['security.contact' => 'security@task.me.uk']);

    expect(securityTxt())->toContain('Contact: mailto:security@task.me.uk');
});

// The address is per-deployment: this is a white-label app and the contact for
// one brand's instance must never be another brand's mailbox.
it('does not hard-code a contact address', function () {
    config(['security.contact' => 'disclosure@example.org']);

    expect(securityTxt())
        ->toContain('Contact: mailto:disclosure@example.org')
        ->not->toContain('fxrm.com');
});

it('states a canonical url and language', function () {
    expect(securityTxt())
        ->toContain('Canonical: '.url('/.well-known/security.txt'))
        ->toContain('Preferred-Languages: en');
});

// The reason this is a route at all: a hard-coded Expires lapses silently and
// the file is then treated as invalid, so it has to stay ahead of "now"
// whenever it is fetched.
it('always expires in the future, however far the clock is wound on', function () {
    Carbon::setTestNow('2030-05-05 12:00:00');

    preg_match('/^Expires: (.+)$/m', securityTxt(), $matches);

    expect(Carbon::parse($matches[1]))->toBeGreaterThan(Carbon::now());
});

// RFC 9116 §2.5.5 says a value more than a year out should not be used.
it('expires within a year, per RFC 9116', function () {
    preg_match('/^Expires: (.+)$/m', securityTxt(), $matches);

    expect(Carbon::parse($matches[1]))->toBeLessThanOrEqual(Carbon::now()->addYear());
});
