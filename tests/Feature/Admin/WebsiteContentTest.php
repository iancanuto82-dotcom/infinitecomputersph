<?php

use App\Models\CarouselSlide;
use App\Models\FeaturedBrand;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function fakeImageUpload(string $name): UploadedFile
{
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO2L5VUAAAAASUVORK5CYII=',
        true
    );

    $path = tempnam(sys_get_temp_dir(), 'upl_');
    file_put_contents($path, $png ?: '');

    return new UploadedFile(
        $path,
        $name,
        'image/png',
        null,
        true
    );
}

test('owner can update website content with upload and url images', function () {
    Storage::fake('public');

    $owner = User::factory()->create([
        'role' => 'owner',
    ]);

    $response = $this->actingAs($owner)->put(route('admin.content.update'), [
        'slides' => [
            [
                'title' => 'Gaming Build Promo',
                'subtitle' => 'Top picks this week',
                'label' => 'Hot Deals',
                'sort_order' => 1,
                'is_active' => 1,
                'image_file' => fakeImageUpload('slide-banner.png'),
            ],
            [
                'title' => 'Creator Build Promo',
                'subtitle' => 'Workstation-ready parts',
                'label' => 'Creator',
                'sort_order' => 2,
                'is_active' => 1,
                'image_url' => 'https://example.com/carousel-two.jpg',
            ],
        ],
        'brands' => [
            [
                'name' => 'NVIDIA',
                'sort_order' => 1,
                'is_active' => 1,
                'logo_url' => 'https://example.com/nvidia-logo.png',
            ],
            [
                'name' => 'ASUS',
                'sort_order' => 2,
                'is_active' => 1,
                'logo_file' => fakeImageUpload('asus-logo.png'),
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.content.edit', absolute: false));

    $uploadedSlide = CarouselSlide::query()
        ->where('sort_order', 1)
        ->firstOrFail();
    expect($uploadedSlide->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists((string) $uploadedSlide->image_path);

    $urlSlide = CarouselSlide::query()
        ->where('sort_order', 2)
        ->firstOrFail();
    expect($urlSlide->image_url)->toBe('https://example.com/carousel-two.jpg');

    $uploadedBrand = FeaturedBrand::query()
        ->where('name', 'ASUS')
        ->firstOrFail();
    expect($uploadedBrand->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists((string) $uploadedBrand->logo_path);

    $this->assertDatabaseHas('featured_brands', [
        'name' => 'NVIDIA',
        'logo_url' => 'https://example.com/nvidia-logo.png',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $owner->id,
        'action' => 'updated',
        'target_type' => 'website_content',
    ]);
});

test('staff without content permission cannot access website content admin', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'admin_permissions' => ['products.view'],
    ]);

    $this->actingAs($staff)
        ->get(route('admin.content.edit'))
        ->assertForbidden();

    $this->actingAs($staff)
        ->put(route('admin.content.update'), [
            'slides' => [
                ['title' => 'Denied', 'is_active' => 1],
            ],
            'brands' => [
                ['name' => 'Denied', 'is_active' => 1],
            ],
        ])
        ->assertForbidden();
});

test('staff with content edit permission can update website content', function () {
    $staff = User::factory()->create([
        'role' => 'staff',
        'admin_permissions' => ['content.edit'],
    ]);

    $this->actingAs($staff)
        ->get(route('admin.content.edit'))
        ->assertOk()
        ->assertSee('Website Content');

    $response = $this->actingAs($staff)->put(route('admin.content.update'), [
        'slides' => [
            [
                'sort_order' => 1,
                'is_active' => 1,
                'image_url' => 'https://example.com/staff-slide.jpg',
            ],
        ],
        'brands' => [
            [
                'name' => 'Staff Brand',
                'sort_order' => 1,
                'is_active' => 1,
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.content.edit', absolute: false));

    $this->assertDatabaseHas('carousel_slides', [
        'image_url' => 'https://example.com/staff-slide.jpg',
    ]);
    $this->assertDatabaseHas('featured_brands', [
        'name' => 'Staff Brand',
    ]);
});
