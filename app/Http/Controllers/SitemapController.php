<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = $this->getUrls();

        $content = view('sitemap', ['urls' => $urls])->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    protected function getUrls(): array
    {
        $baseUrl = config('app.url');

        return [
            [
                'loc' => $baseUrl,
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => $baseUrl.'/login',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
            [
                'loc' => $baseUrl.'/register',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
        ];
    }
}
