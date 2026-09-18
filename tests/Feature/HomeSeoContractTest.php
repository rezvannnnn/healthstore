<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSeoContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_exposes_website_structured_data_with_product_search_action(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->where('structuredData.@context', 'https://schema.org')
            ->where('structuredData.@type', 'WebSite')
            ->where('structuredData.name', config('app.name', 'HealthStore'))
            ->where('structuredData.url', url('/'))
            ->where('structuredData.inLanguage', 'fa-IR')
            ->where('structuredData.potentialAction.@type', 'SearchAction')
            ->where('structuredData.potentialAction.target', route('products.index').'?search={search_term_string}')
            ->where('structuredData.potentialAction.query-input', 'required name=search_term_string')
        );
    }
}
