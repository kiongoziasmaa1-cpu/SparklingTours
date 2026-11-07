<?php

namespace Tests\Feature;

use App\Jobs\SendOutboxMessage;
use App\Models\Booking;
use App\Models\CatalogItem;
use App\Models\MediaAsset;
use App\Models\OutboxMessage;
use App\Models\PreferenceOption;
use App\Models\Region;
use App\Models\SiteSetting;
use App\Models\TransportFare;
use App\Models\TransportRoute;
use App\Models\User;
use App\Models\VehicleType;
use App\Providers\AppServiceProvider;
use App\Services\BookingSubmissionService;
use App\Services\CaptchaVerifier;
use App\Services\InvoiceService;
use App\Services\TourPricingService;
use App\Services\TransportPricingService;
use Database\Seeders\DemoBookingSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TourismTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if ($expected = getenv('TOURISM_EXPECT_DRIVER')) {
            $this->assertSame($expected, DB::connection()->getDriverName(), 'Unexpected database driver in this verification run.');
        }
        config(['tourism.captcha.enabled' => false]);
        Queue::fake();
        $this->seed();
    }

    private function admin(array $extra = []): User
    {
        return User::create(array_merge(['name' => 'Test Administrator', 'email' => 'admin-'.Str::random(8).'@example.com', 'password' => 'Test-only-secure-passphrase!', 'role' => 'admin', 'active' => true], $extra));
    }

    private function tourData(): array
    {
        $tour = CatalogItem::where('slug', 'stone-town-and-coast')->firstOrFail();

        return ['region_id' => $tour->region_id, 'tour_id' => $tour->id, 'service_ids' => [], 'name' => 'Example Traveler', 'email' => 'traveler@example.com', 'phone' => '+255700000000', 'country' => 'Tanzania', 'preferred_contact' => 'email', 'start_date' => today()->addDays(20)->toDateString(), 'end_date' => today()->addDays(20)->toDateString(), 'adults' => 2, 'children' => 0, 'currency' => 'TZS', 'needs_accommodation' => 0, 'terms' => '1'];
    }

    private function transportData(): array
    {
        $fare = TransportFare::whereHas('vehicleType', fn ($q) => $q->where('name', 'Private car'))->firstOrFail();

        return ['route_id' => $fare->transport_route_id, 'fare_id' => $fare->id, 'pickup_at' => now()->addDays(20)->format('Y-m-d\TH:i'), 'adults' => 5, 'children' => 0, 'luggage' => 4, 'pickup_details' => 'Airport arrivals meeting point', 'dropoff_details' => 'Stone Town address supplied', 'name' => 'Transfer Traveler', 'email' => 'transfer@example.com', 'phone' => '+255700000001', 'country' => 'Tanzania', 'preferred_contact' => 'email', 'currency' => 'TZS', 'terms' => '1'];
    }

    private function reviewed(string $kind, array $data)
    {
        $response = $this->post(route($kind === 'tour' ? 'tour.review' : 'transport.review'), $data)->assertRedirect();

        return $this->get($response->headers->get('Location'))->assertOk()->assertViewIs('booking.review');
    }

    private function booking(?array $d = null): Booking
    {
        $d = $d ?? $this->tourData();

        return app(BookingSubmissionService::class)->submit('tour', $d, app(TourPricingService::class)->quote($d), (string) Str::uuid(), hash('sha256', 'fixture-guest'));
    }

    public function test_public_pages_render_and_navigation_has_independent_transport_without_hotel_menu(): void
    {
        foreach (['/', '/tours', '/explore/zanzibar/destinations', '/explore/mainland/tours', '/explore/zanzibar/activities', '/explore/zanzibar/food', '/explore/zanzibar/travel-guide', '/transport', '/about', '/contact', '/privacy', '/terms', '/photo-credits', '/plan-your-trip', '/transport/airport-stone-town', '/experience/stone-town-and-coast', '/sitemap.xml'] as $path) {
            $this->get($path)->assertOk();
        }
        $html = $this->get('/')->getContent();
        $this->assertStringContainsString('/transport', $html);
        $this->assertStringNotContainsString('href="/accommodation', $html);
        $this->get('/about')
            ->assertOk()
            ->assertSee('about-page-photo', false)
            ->assertSee('Our mission')
            ->assertSee('Our vision')
            ->assertSee('Tour planning')
            ->assertSee('Private transport')
            ->assertSee('Contact us');
        $this->get('/privacy')->assertOk()->assertSee('Privacy notice');
        $this->get('/accommodation')->assertNotFound();
        $this->get('/transport')->assertDontSee('needs_accommodation', false);
    }

    public function test_reseeding_preserves_site_content_and_keeps_all_homepage_gallery_images_available(): void
    {
        SiteSetting::setValue('about_text', 'Operator-authored About content.');
        $airport = TransportRoute::where('slug', 'airport-stone-town')->firstOrFail();
        $airport->update(['published' => false]);

        $this->seed();

        $this->assertSame('Operator-authored About content.', SiteSetting::valueFor('about_text'));
        $this->assertFalse($airport->fresh()->published);
        $this->assertSame(7, MediaAsset::where('gallery', true)->count());

        foreach (MediaAsset::where('gallery', true)->get() as $photo) {
            $this->assertFileExists(public_path($photo->path));
        }

        foreach (['about', 'privacy', 'terms'] as $slug) {
            $this->assertDatabaseHas('pages', ['slug' => $slug, 'published' => true]);
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Most preferable places')
            ->assertSee('zanzibar-section', false)
            ->assertSee('mainland-section', false)
            ->assertSee('Discover Zanzibar.')
            ->assertSee('Explore Tanzania Mainland.')
            ->assertSee('Operator-authored About content.');
        $this->get('/about')
            ->assertOk()
            ->assertSee('Our mission')
            ->assertSee('Our vision');
    }

    public function test_render_database_url_is_used_when_db_url_is_empty(): void
    {
        $databaseUrl = 'pgsql://render-user:render-password@db.example:5432/sparkling';
        $environmentKeys = ['DB_URL', 'DATABASE_URL'];
        $originalValues = [];

        foreach ($environmentKeys as $key) {
            $originalValues[$key] = [
                'env' => array_key_exists($key, $_ENV) ? $_ENV[$key] : null,
                'server' => array_key_exists($key, $_SERVER) ? $_SERVER[$key] : null,
            ];
        }

        try {
            $_ENV['DB_URL'] = $_SERVER['DB_URL'] = '';
            $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'] = $databaseUrl;
            $configuration = require config_path('database.php');

            $this->assertSame($databaseUrl, $configuration['connections']['pgsql']['url']);
        } finally {
            foreach ($originalValues as $key => $values) {
                if ($values['env'] === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $values['env'];
                }

                if ($values['server'] === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $values['server'];
                }
            }
        }
    }

    public function test_tour_book_now_collects_contact_details_and_creates_distinct_admin_booking_request(): void
    {
        $tour = CatalogItem::where('slug', 'stone-town-and-coast')->firstOrFail();
        $this->get(route('catalog.detail', $tour->slug))
            ->assertOk()
            ->assertSee(route('tour.contact', $tour->slug), false);

        $form = $this->get(route('tour.contact', $tour->slug))
            ->assertOk()
            ->assertSee('Fill in your contact details')
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="country"', false)
            ->assertSee('name="traveler_count"', false)
            ->assertSee('action="'.route('tour.contact.submit', $tour->slug).'"', false)
            ->assertDontSee('action="'.route('tour.review').'"', false);
        preg_match('/name="request_key" value="([^"]+)"/', $form->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);

        $response = $this->post(route('tour.contact.submit', $tour->slug), [
            'request_key' => $matches[1],
            'name' => 'Tour Guest',
            'email' => 'tour.guest@example.com',
            'phone' => '+255700000009',
            'country' => 'Tanzania',
            'traveler_count' => 3,
            'terms' => '1',
        ])->assertRedirect();
        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Your request was sent successfully.');

        $booking = Booking::where('email', 'tour.guest@example.com')->firstOrFail();
        $this->assertSame('tour', $booking->kind);
        $this->assertSame('tour_request', $booking->source);
        $this->assertSame(3, $booking->traveler_count);
        $this->assertNull($booking->start_date);
        $this->assertDatabaseHas('booking_items', [
            'booking_id' => $booking->id,
            'catalog_item_id' => $tour->id,
            'description' => $tour->displayName(),
        ]);
        $tourRequestItem = $booking->items()->firstOrFail();
        $this->assertSame($tour->price, $tourRequestItem->snapshot['package_price']);
        $this->assertSame($tour->currency, $tourRequestItem->snapshot['currency']);
        $this->assertSame($tour->pricing_basis, $tourRequestItem->snapshot['pricing_basis']);

        $admin = $this->admin();
        $this->actingAs($admin)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('Tour booking request')
            ->assertSee('Not provided');
        $this->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Tour requested')
            ->assertSee($tour->name)
            ->assertSee('tour.guest@example.com')
            ->assertSee('People travelling:')
            ->assertSee('3')
            ->assertSee(\App\Support\Money::format($tour->price, $tour->currency))
            ->assertSee('per person')
            ->assertDontSee('Prepare invoice');
    }

    public function test_admin_can_view_all_selected_plan_your_trip_details(): void
    {
        $destination = CatalogItem::where('kind', 'destination')->firstOrFail();
        $data = $this->tourData();
        $data['region_id'] = $destination->region_id;
        $data['tour_id'] = null;
        $data['service_ids'] = [$destination->id];
        $data['adults'] = 2;
        $data['children'] = 1;
        $data['name'] = 'Plan Guest';
        $data['email'] = 'plan.guest@example.com';
        $data['phone'] = '+255700000010';
        $data['country'] = 'Kenya';
        $review = $this->reviewed('tour', $data);
        $this->post(route('bookings.submit', 'tour'), ['draft_id' => $review->viewData('draftId')])->assertRedirect();
        $booking = Booking::where('email', 'plan.guest@example.com')->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('Plan your trip')
            ->assertSee('View');
        $this->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee($destination->name)
            ->assertSee('Plan Guest')
            ->assertSee('plan.guest@example.com')
            ->assertSee('+255700000010')
            ->assertSee('2 adult(s), 1 child(ren)')
            ->assertSee(today()->addDays(20)->format('d M Y'));
    }

    public function test_homepage_displays_animated_illustrative_testimonials_after_mainland_and_before_footer(): void
    {
        $response = $this->get('/')->assertOk()
            ->assertSee('testimonials-section', false)
            ->assertSee('data-testimonial-reveal', false)
            ->assertSee('Illustrative example');
        $html = $response->getContent();
        $mainlandPosition = strpos($html, 'mainland-section');
        $testimonialsPosition = strpos($html, 'testimonials-section');
        $footerPosition = strpos($html, '<footer');
        $this->assertNotFalse($mainlandPosition);
        $this->assertNotFalse($testimonialsPosition);
        $this->assertNotFalse($footerPosition);
        $this->assertLessThan($testimonialsPosition, $mainlandPosition);
        $this->assertLessThan($footerPosition, $testimonialsPosition);

        SiteSetting::setValue('demo_mode', '0');
        app()->forgetInstance('site.context');
        $this->get('/')->assertOk()->assertDontSee('testimonials-section', false);
    }

    public function test_homepage_hero_has_three_slides_and_uses_configured_hero_image_first(): void
    {
        SiteSetting::setValue('hero_path', 'images/stone-town.jpg');
        app()->forgetInstance('site.context');

        $html = $this->get('/')->assertOk()
            ->assertSee('data-hero-slideshow', false)
            ->assertSee('src="'.asset('images/stone-town.jpg').'"', false)
            ->assertSee('src="'.asset('images/zanzibar-beach.jpg').'"', false)
            ->assertSee('src="'.asset('images/serengeti-safari-vehicle.jpg').'"', false)
            ->getContent();

        $this->assertSame(3, substr_count($html, 'data-hero-slide src='));
        $this->assertSame(1, substr_count($html, 'class="hero-image is-active" data-hero-slide'));
    }

    public function test_homepage_gallery_is_a_single_image_only_row_with_scroll_controls(): void
    {
        $html = $this->get('/')->assertOk()
            ->assertSee('class="section-pad photo-carousel"', false)
            ->assertSee('data-gallery-prev', false)
            ->assertSee('data-gallery-next', false)
            ->assertSee('data-gallery-track', false)
            ->getContent();

        preg_match('/<section class="section-pad photo-carousel".*?<\\/section>/s', $html, $matches);
        $this->assertNotEmpty($matches);
        $gallery = $matches[0];
        $this->assertSame(7, substr_count($gallery, 'class="destination-gallery-card"'));
        $this->assertStringNotContainsString('>Tanzania<', $gallery);
        $this->assertStringNotContainsString('>Zanzibar<', $gallery);
        $this->assertStringNotContainsString('class="caption"', $gallery);
        $this->assertSame(7, substr_count($gallery, '<img'));
    }

    public function test_trip_experiences_are_name_only_selectable_dropdown_groups(): void
    {
        $destination = CatalogItem::where('kind', 'destination')->firstOrFail();
        $activity = CatalogItem::where('kind', 'activity')->firstOrFail();
        $food = CatalogItem::where('kind', 'food')->firstOrFail();
        $guide = CatalogItem::where('kind', 'guide')->firstOrFail();

        $this->withSession(['_old_input' => ['service_ids' => [$destination->id, $activity->id]]])
            ->get('/plan-your-trip')
            ->assertOk()
            ->assertSee('<details class="experience-dropdown">', false)
            ->assertSee('<summary>Destinations', false)
            ->assertSee('<summary>Activities', false)
            ->assertSee('<summary>Food &amp; meals', false)
            ->assertSee('<summary>Guide services', false)
            ->assertSee($destination->name)
            ->assertSee($activity->name)
            ->assertSee($food->name)
            ->assertSee($guide->name)
            ->assertSee('value="'.$destination->id.'" checked', false)
            ->assertSee('value="'.$activity->id.'" checked', false)
            ->assertDontSee('TZS 25,000.00')
            ->assertDontSee('Zanzibar · Free');
    }

    public function test_package_does_not_charge_included_guide_twice_and_ignores_fake_client_price(): void
    {
        $d = $this->tourData();
        $guide = CatalogItem::where('slug', 'zanzibar-guide')->first();
        $d['service_ids'] = [$guide->id];
        $d['price'] = '1.00';
        $q = app(TourPricingService::class)->quote($d);
        $this->assertSame(32000000, $q['total_minor']);
        $this->assertCount(1, $q['lines']);
        $d['service_ids'] = [CatalogItem::where('slug', 'serengeti')->first()->id];
        $this->expectException(ValidationException::class);
        app(TourPricingService::class)->quote($d);
    }

    public function test_custom_missing_price_is_pending_not_a_free_trip(): void
    {
        $d = $this->tourData();
        unset($d['tour_id']);
        $d['service_ids'] = [CatalogItem::where('slug', 'stone-town')->first()->id];
        $q = app(TourPricingService::class)->quote($d);
        $this->assertTrue($q['pending']);
        $this->assertNull($q['lines'][0]['amount']);
    }

    public function test_tour_review_submission_and_genuine_retry_create_one_request(): void
    {
        $d = $this->tourData();
        $review = $this->reviewed('tour', $d);
        $id = $review->viewData('draftId');
        $first = $this->post(route('bookings.submit', 'tour'), ['draft_id' => $id])->assertRedirect();
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->post(route('bookings.submit', 'tour'), ['draft_id' => $id])->assertRedirect($first->headers->get('Location'));
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->get($first->headers->get('Location'))->assertOk()->assertSee('TOUR-');
    }

    public function test_booking_notifications_use_notification_email_then_public_email_fallback(): void
    {
        SiteSetting::setValue('email', 'public@example.test');
        SiteSetting::setValue('notification_email', 'bookings@example.test');

        $this->booking();

        $this->assertDatabaseHas('outbox_messages', ['recipient' => 'bookings@example.test']);

        SiteSetting::setValue('notification_email', '');
        $this->booking();

        $this->assertDatabaseHas('outbox_messages', ['recipient' => 'public@example.test']);
    }

    public function test_website_settings_require_a_booking_or_public_email_address(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/settings', [
                'company_name' => 'Example Tours',
                'hero_heading' => 'Welcome',
                'hero_text' => 'Explore Tanzania.',
                'about_text' => 'Local journeys.',
                'why_text' => 'Thoughtfully arranged.',
            ])
            ->assertSessionHasErrors(['email', 'notification_email']);
    }

    public function test_accommodation_is_optional_conditional_and_preserves_regional_preferences(): void
    {
        $d = $this->tourData();
        $d['needs_accommodation'] = 1;
        $this->post(route('tour.review'), $d)->assertSessionHasErrors('accommodation');
        $d['accommodation'] = ['check_in' => today()->addDays(18)->toDateString(), 'check_out' => today()->addDays(23)->toDateString(), 'rooms' => 1, 'adults' => 2, 'children' => 0, 'budget_currency' => 'USD', 'location_id' => PreferenceOption::where('label', 'Nungwi')->first()->id, 'notes' => 'Near the beach'];
        $r = $this->reviewed('tour', $d)->assertSee('Accommodation: pending quotation');
        $this->post(route('bookings.submit', 'tour'), ['draft_id' => $r->viewData('draftId')])->assertRedirect();
        $b = Booking::first();
        $this->assertSame('320000.00', $b->known_subtotal);
        $this->assertTrue($b->quote_required);
        $this->assertSame('Nungwi', $b->accommodation->preferences['location']);
        $d['needs_accommodation'] = 0;
        $r = $this->reviewed('tour', $d);
        $this->post(route('bookings.submit', 'tour'), ['draft_id' => $r->viewData('draftId')])->assertRedirect();
        $this->assertDatabaseCount('accommodation_requests', 1);
    }

    public function test_price_change_requires_a_new_review_and_saved_snapshot_is_immutable(): void
    {
        $r = $this->reviewed('tour', $this->tourData());
        $id = $r->viewData('draftId');
        $tour = CatalogItem::where('slug', 'stone-town-and-coast')->first();
        $tour->update(['price' => '170000.00']);
        $changed = $this->post(route('bookings.submit', 'tour'), ['draft_id' => $id])->assertRedirect(route('bookings.review', $id));
        $this->get($changed->headers->get('Location'))->assertSee('A price or service condition changed');
        $this->assertDatabaseCount('bookings', 0);
        $this->post(route('bookings.submit', 'tour'), ['draft_id' => $id])->assertRedirect();
        $this->assertSame('340000.00', Booking::first()->known_subtotal);
        $tour->update(['price' => '190000.00']);
        $this->assertSame('340000.00', Booking::first()->items->first()->amount);
    }

    public function test_transport_booking_is_independent_and_capacity_and_return_fares_are_exact(): void
    {
        $d = $this->transportData();
        $q = app(TransportPricingService::class)->quote($d);
        $this->assertSame(2, $q['vehicles']);
        $this->assertSame(16000000, $q['total_minor']);
        $d['return_at'] = now()->addDays(21)->format('Y-m-d\TH:i');
        $q = app(TransportPricingService::class)->quote($d);
        $this->assertSame(32000000, $q['total_minor']);
        $r = $this->reviewed('transport', $d);
        $this->post(route('bookings.submit', 'transport'), ['draft_id' => $r->viewData('draftId')])->assertRedirect();
        $b = Booking::first();
        $this->assertSame('transport', $b->kind);
        $this->assertSame(2, $b->transport->vehicles);
        $this->assertNull($b->accommodation);
        $this->assertDatabaseCount('accommodation_requests', 0);
    }

    public function test_transport_rejects_incompatible_route_type_capacity_and_unconfigured_return(): void
    {
        $d = $this->transportData();
        $fare = TransportFare::find($d['fare_id']);
        $fare->update(['multiple_vehicles' => false]);
        $this->post(route('transport.review'), $d)->assertSessionHasErrors('transport');
        $fare->update(['multiple_vehicles' => true]);
        $d['route_id'] = TransportRoute::where('slug', 'stone-town-nungwi')->first()->id;
        $this->post(route('transport.review'), $d)->assertSessionHasErrors('transport');
    }

    public function test_per_person_transport_price_and_manual_currency_conversion(): void
    {
        $d = $this->transportData();
        $fare = TransportFare::where('pricing_basis', 'per_person')->first();
        $fare->route->update(['published' => true]);
        $d['route_id'] = $fare->transport_route_id;
        $d['fare_id'] = $fare->id;
        $d['adults'] = 3;
        $this->assertSame(9000000, app(TransportPricingService::class)->quote($d)['total_minor']);
        SiteSetting::setValue('usd_tzs_rate', '2500');
        $d['currency'] = 'USD';
        $this->assertSame(3600, app(TransportPricingService::class)->quote($d)['total_minor']);
    }

    public function test_trip_estimate_keeps_unconfigured_currency_prices_pending_instead_of_rejecting_request(): void
    {
        SiteSetting::where('key', 'usd_tzs_rate')->delete();
        $destination = CatalogItem::where('slug', 'serengeti')->firstOrFail();
        $food = CatalogItem::where('slug', 'zanzibar-lunch')->firstOrFail();
        $food->update(['region_id' => $destination->region_id, 'currency' => 'USD', 'price' => '12.00']);
        $d = $this->tourData();
        unset($d['tour_id']);
        $d['region_id'] = $destination->region_id;
        $d['service_ids'] = [$destination->id, $food->id];

        $response = $this->postJson(route('estimate', 'tour'), $d)
            ->assertOk()
            ->assertJsonPath('pending', true);

        $foodLine = collect($response->json('lines'))->firstWhere('catalog_item_id', $food->id);
        $this->assertNull($foodLine['amount']);
        $this->assertNull($foodLine['unit_price']);
    }

    public function test_transport_estimate_keeps_unconfigured_currency_prices_pending_instead_of_rejecting_request(): void
    {
        SiteSetting::where('key', 'usd_tzs_rate')->delete();
        $d = $this->transportData();
        $fare = TransportFare::findOrFail($d['fare_id']);
        $fare->update(['currency' => 'USD']);

        $this->postJson(route('estimate', 'transport'), $d)
            ->assertOk()
            ->assertJsonPath('pending', true)
            ->assertJsonPath('lines.0.amount', null)
            ->assertJsonPath('lines.0.unit_price', null);
    }

    public function test_guide_access_is_limited_to_assigned_bookings_and_explicit_finance_permissions(): void
    {
        $b = $this->booking();
        $guide = $this->admin(['role' => 'guide', 'can_invoice' => false]);
        $this->actingAs($guide)->get(route('admin.bookings.show', $b))->assertForbidden();
        $b->update(['assigned_to' => $guide->id]);
        $this->get(route('admin.bookings.show', $b))->assertOk();
        $this->post(route('admin.invoices.create', $b))->assertForbidden();
        $this->get(route('admin.settings'))->assertForbidden();
        $guide->update(['can_invoice' => true]);
        $this->post(route('admin.invoices.create', $b))->assertRedirect();
    }

    public function test_admin_pages_and_catalogue_forms_render(): void
    {
        $a = $this->admin();
        $this->actingAs($a);
        foreach (['/admin', '/admin/bookings', '/admin/settings', '/admin/media', '/admin/staff', '/admin/deliveries'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['destinations', 'tours', 'activities', 'food', 'guides', 'vehicles', 'routes', 'fares', 'preferences', 'pages'] as $type) {
            $this->get('/admin/catalog/'.$type)->assertOk();
            $this->get('/admin/catalog/'.$type.'/create')->assertOk();
        }
        $this->post('/admin/catalog/vehicles', ['name' => 'New configured van', 'mode' => 'road', 'capacity' => 10, 'active' => 1])->assertRedirect();
        $this->assertDatabaseHas('vehicle_types', ['name' => 'New configured van']);
        $this->get('/admin/catalog/bookings')->assertNotFound();
    }

    public function test_catalogue_region_is_clearly_required_and_validation_is_shown_at_the_field(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/catalog/destinations/create')
            ->assertOk()
            ->assertSee('Region', false)
            ->assertSee('Select a region', false)
            ->assertSee('required', false);

        $this->followingRedirects()->post('/admin/catalog/destinations', [
            'name' => 'Destination without a region',
            'currency' => 'TZS',
            'pricing_basis' => 'per_person',
            'duration_days' => 1,
        ])->assertOk()
            ->assertSee('Please select a region.')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('id="error-region_id"', false);
    }

    public function test_food_and_guide_forms_have_service_specific_fields_and_save_without_tour_duration(): void
    {
        $this->actingAs($this->admin());
        $region = Region::where('name', 'Zanzibar')->firstOrFail();

        $this->get('/admin/catalog/food/create')
            ->assertOk()
            ->assertSee('Food or meal name')
            ->assertSee('Short menu description')
            ->assertSee('Meal price basis')
            ->assertSee('Dietary and allergy information')
            ->assertDontSee('Tour duration in days')
            ->assertDontSee('Guide languages');

        $this->get('/admin/catalog/guides/create')
            ->assertOk()
            ->assertSee('Guide service name')
            ->assertSee('Guide rate basis')
            ->assertSee('Languages spoken')
            ->assertSee('Experience and qualifications')
            ->assertDontSee('Tour duration in days')
            ->assertDontSee('Dietary and allergy information');

        $this->post('/admin/catalog/food', [
            'name' => 'Example local lunch',
            'region_id' => $region->id,
            'summary' => 'A seasonal local meal.',
            'price' => '18000.00',
            'currency' => 'TZS',
            'pricing_basis' => 'per_person_meal',
            'requirements' => 'Ask about allergies when booking.',
            'published' => 1,
        ])->assertRedirect(route('admin.catalog.index', 'food'));

        $this->post('/admin/catalog/guides', [
            'name' => 'Example heritage guide',
            'region_id' => $region->id,
            'summary' => 'A local heritage guide.',
            'price' => '90000.00',
            'currency' => 'TZS',
            'pricing_basis' => 'per_day',
            'languages' => 'Kiswahili, English',
            'requirements' => 'Licensed local guide.',
            'published' => 1,
        ])->assertRedirect(route('admin.catalog.index', 'guides'));

        $food = CatalogItem::where('name', 'Example local lunch')->firstOrFail();
        $guide = CatalogItem::where('name', 'Example heritage guide')->firstOrFail();
        $this->assertSame('food', $food->kind);
        $this->assertSame('per_person_meal', $food->pricing_basis);
        $this->assertSame('guide', $guide->kind);
        $this->assertSame('per_day', $guide->pricing_basis);
        $this->get(route('catalog.detail', $food->slug))->assertOk()->assertDontSee('1 day(s)');
        $this->get(route('catalog.detail', $guide->slug))->assertOk()->assertDontSee('1 day(s)');
    }

    public function test_food_photo_upload_does_not_require_alt_text_and_uses_food_name_as_fallback(): void
    {
        $this->actingAs($this->admin());
        $region = Region::where('name', 'Zanzibar')->firstOrFail();
        $uploadedPath = null;

        try {
            $this->get('/admin/catalog/food/create')
                ->assertOk()
                ->assertSee('Photo description (optional)')
                ->assertSee('Leave blank to use the record name');

            $this->post('/admin/catalog/food', [
                'name' => 'Fresh coconut breakfast',
                'region_id' => $region->id,
                'currency' => 'TZS',
                'pricing_basis' => 'per_person_meal',
                'image' => UploadedFile::fake()->image('coconut-breakfast.png'),
            ])->assertRedirect(route('admin.catalog.index', 'food'))->assertSessionHasNoErrors();

            $food = CatalogItem::where('name', 'Fresh coconut breakfast')->firstOrFail();
            $asset = MediaAsset::findOrFail($food->media_asset_id);
            $uploadedPath = $asset->path;
            $this->assertSame('Fresh coconut breakfast', $asset->alt);
            $this->assertFileExists(public_path($uploadedPath));
        } finally {
            if ($uploadedPath && str_starts_with($uploadedPath, 'images/uploads/')) {
                @unlink(public_path($uploadedPath));
            }
        }
    }

    public function test_tour_form_hides_requested_fields_without_clearing_saved_details_or_photograph(): void
    {
        $this->actingAs($this->admin());
        $tour = CatalogItem::where('kind', 'tour')->firstOrFail();
        $includedActivity = CatalogItem::where('kind', 'activity')->firstOrFail();
        $media = MediaAsset::firstOrFail();
        $tour->update([
            'itinerary' => 'Day 1: A sample existing itinerary',
            'included_item_ids' => [$includedActivity->id],
            'media_asset_id' => $media->id,
        ]);
        $tour = $tour->fresh();

        $this->get(route('admin.catalog.edit', ['tours', $tour->id]))
            ->assertOk()
            ->assertDontSee('Photograph')
            ->assertDontSee('media_asset_id', false)
            ->assertDontSee('Day-by-day itinerary')
            ->assertDontSee('Services already included in this package')
            ->assertSee('Upload or replace photograph');

        $this->patch(route('admin.catalog.update', ['tours', $tour->id]), [
            'name' => $tour->name,
            'region_id' => $tour->region_id,
            'currency' => $tour->currency,
            'pricing_basis' => $tour->pricing_basis,
            'duration_days' => $tour->duration_days,
        ])->assertRedirect(route('admin.catalog.index', 'tours'));

        $tour->refresh();
        $this->assertSame('Day 1: A sample existing itinerary', $tour->itinerary);
        $this->assertSame([$includedActivity->id], $tour->included_item_ids);
        $this->assertSame($media->id, $tour->media_asset_id);
    }

    public function test_admin_can_delete_unused_catalog_records_and_block_records_used_by_bookings(): void
    {
        $this->actingAs($this->admin());
        $region = Region::where('name', 'Zanzibar')->firstOrFail();
        $unused = CatalogItem::create([
            'region_id' => $region->id,
            'kind' => 'activity',
            'name' => 'Unused removable activity',
            'slug' => 'unused-removable-activity',
            'currency' => 'TZS',
            'pricing_basis' => 'per_person',
            'duration_days' => 1,
        ]);

        $this->get('/admin/catalog/activities')
            ->assertOk()
            ->assertSee('Delete')
            ->assertSee(route('admin.catalog.destroy', ['activities', $unused->id]));
        $this->get(route('admin.catalog.edit', ['activities', $unused->id]))
            ->assertOk()
            ->assertSee('Delete this record');

        $this->delete(route('admin.catalog.destroy', ['activities', $unused->id]))
            ->assertRedirect(route('admin.catalog.index', 'activities'))
            ->assertSessionHas('status', 'Record deleted successfully.');
        $this->assertDatabaseMissing('catalog_items', ['id' => $unused->id]);

        $booking = $this->booking();
        $bookedItemId = DB::table('booking_items')
            ->where('booking_id', $booking->id)
            ->whereNotNull('catalog_item_id')
            ->value('catalog_item_id');
        $this->assertNotNull($bookedItemId);

        $this->from(route('admin.catalog.index', 'tours'))
            ->delete(route('admin.catalog.destroy', ['tours', $bookedItemId]))
            ->assertRedirect(route('admin.catalog.index', 'tours'))
            ->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('catalog_items', ['id' => $bookedItemId]);
    }

    public function test_admin_can_delete_a_route_and_its_unused_fares_together(): void
    {
        $this->actingAs($this->admin());
        $region = Region::where('name', 'Zanzibar')->firstOrFail();
        $vehicle = VehicleType::firstOrFail();
        $route = TransportRoute::create([
            'region_id' => $region->id,
            'name' => 'Unused removable route',
            'slug' => 'unused-removable-route',
            'origin' => 'Town',
            'destination' => 'Harbour',
            'duration_minutes' => 30,
        ]);
        $fare = TransportFare::create([
            'transport_route_id' => $route->id,
            'vehicle_type_id' => $vehicle->id,
            'price' => '1000.00',
            'currency' => 'TZS',
            'pricing_basis' => 'per_vehicle',
            'capacity' => 4,
            'minimum_passengers' => 1,
            'maximum_passengers' => 4,
            'maximum_vehicles' => 1,
        ]);

        $this->delete(route('admin.routes.destroy', $route))
            ->assertRedirect(route('admin.routes.index'));

        $this->assertDatabaseMissing('transport_routes', ['id' => $route->id]);
        $this->assertDatabaseMissing('transport_fares', ['id' => $fare->id]);
    }

    public function test_hotel_selection_invoice_deposit_partial_payment_and_final_payment_workflow(): void
    {
        $a = $this->admin();
        $this->actingAs($a);
        $d = $this->tourData();
        $d['needs_accommodation'] = 1;
        $d['accommodation'] = ['check_in' => today()->addDays(19)->toDateString(), 'check_out' => today()->addDays(21)->toDateString(), 'rooms' => 1, 'adults' => 2, 'children' => 0, 'budget_currency' => 'TZS'];
        $b = $this->booking($d);
        $p = $b->accommodation->proposals()->create(['hotel_name' => 'Example agreed hotel', 'location' => 'Zanzibar', 'check_in' => $d['accommodation']['check_in'], 'check_out' => $d['accommodation']['check_out'], 'rooms' => 1, 'room_type' => 'Double', 'meal_plan' => 'Breakfast', 'nightly_rate' => '100000.00', 'currency' => 'TZS', 'terms' => 'Example agreed cancellation terms', 'reservation_status' => 'confirmed', 'supplier_reference' => 'SUPPLIER-DEMO-1', 'availability_checked_at' => now()]);
        $this->post(route('admin.hotels.select', [$b, $p]), ['customer_agreed' => '1'])->assertRedirect();
        SiteSetting::setValue('payment_instructions', 'DEMO ONLY: configure real business payment details before use.');
        $s = app(InvoiceService::class);
        $i = $s->draft($b->fresh());
        $this->assertSame('520000.00', $i->grand_total);
        $i = $s->issue($i);
        $this->assertSame('156000.00', $i->deposit_required);
        $this->get(route('admin.invoices.edit', $i))->assertOk();
        $this->get(route('admin.invoices.pdf', $i))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $base = ['kind' => 'payment', 'amount' => '156000.00', 'currency' => 'TZS', 'paid_on' => today()->toDateString(), 'method' => 'bank', 'transaction_reference' => 'EXAMPLE-TXN-1', 'verified' => '1'];
        $this->post(route('admin.invoices.payment', $i), $base)->assertRedirect();
        $i = $i->fresh();
        $this->assertSame('partially paid', $i->settlement());
        $this->assertSame(0, $i->dueNowMinor());
        $this->assertSame(36400000, $i->balanceMinor());
        $this->post(route('admin.invoices.payment', $i), $base)->assertSessionHasErrors('invoice');
        $base['transaction_reference'] = 'EXAMPLE-TXN-2';
        $base['amount'] = '364000.00';
        $this->post(route('admin.invoices.payment', $i), $base)->assertRedirect();
        $this->assertSame('paid', $i->fresh()->settlement());
        $this->assertSame('submitted', $b->fresh()->status);
    }

    public function test_private_invoice_links_expire_revoke_and_do_not_expose_internal_notes(): void
    {
        $b = $this->booking();
        $b->update(['internal_notes' => 'PRIVATE-SUPPLIER-NOTE']);
        SiteSetting::setValue('payment_instructions', 'Demo payment instructions');
        $s = app(InvoiceService::class);
        $i = $s->issue($s->draft($b));
        $url = $s->send($i);
        $this->get($url)->assertOk()->assertDontSee('PRIVATE-SUPPLIER-NOTE')->assertHeader('Cache-Control', 'no-store, private');
        $this->get($url.'/pdf')->assertOk();
        $this->get(str_replace(substr($url, -64), str_repeat('a', 64), $url))->assertNotFound();
        $i->tokens()->update(['expires_at' => now()->subMinute()]);
        $this->get($url)->assertNotFound();
        $url = $s->send($i);
        $s->void($i, 'Customer changed travel dates');
        $this->get($url)->assertNotFound();
    }

    public function test_issued_invoice_cannot_be_edited_and_refunds_are_bounded(): void
    {
        $a = $this->admin();
        $b = $this->booking();
        SiteSetting::setValue('payment_instructions', 'Example payment method');
        $s = app(InvoiceService::class);
        $i = $s->issue($s->draft($b));
        $p = $s->recordPayment($i, ['kind' => 'payment', 'amount' => '50000.00', 'currency' => 'TZS', 'paid_on' => today()->toDateString(), 'method' => 'bank', 'transaction_reference' => 'REFUND-SOURCE'], $a->id);
        $s->recordPayment($i, ['kind' => 'refund', 'amount' => '10000.00', 'currency' => 'TZS', 'paid_on' => today()->toDateString(), 'method' => 'bank', 'transaction_reference' => 'REFUND-1', 'source_payment_id' => $p->id], $a->id);
        $this->assertSame(4000000, $i->fresh()->netPaidMinor());
        $this->expectException(ValidationException::class);
        $s->save($i, []);
    }

    public function test_captcha_rejects_forged_expired_replayed_wrong_action_and_wrong_hostname_tokens(): void
    {
        config(['tourism.captcha.enabled' => true, 'tourism.captcha.secret' => 'test-private', 'tourism.captcha.hostnames' => ['tour.example']]);
        $responses = [['success' => false, 'error-codes' => ['invalid-input-response']], ['success' => false, 'error-codes' => ['timeout-or-duplicate']], ['success' => true, 'hostname' => 'tour.example', 'action' => 'contact'], ['success' => true, 'hostname' => 'evil.example', 'action' => 'tour_booking']];
        foreach ($responses as $body) {
            Http::swap(new Factory);
            Http::fake(['challenges.cloudflare.com/*' => Http::response($body)]);
            try {
                app(CaptchaVerifier::class)->verify('dummy', 'tour_booking');
                $this->fail('Invalid response accepted');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('captcha', $e->errors());
            }
        }
        Http::swap(new Factory);
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => true, 'hostname' => 'tour.example', 'action' => 'tour_booking'])->push(['success' => false, 'error-codes' => ['timeout-or-duplicate']])]);
        app(CaptchaVerifier::class)->verify('single-use-token', 'tour_booking');
        $this->expectException(ValidationException::class);
        app(CaptchaVerifier::class)->verify('single-use-token', 'tour_booking');
    }

    public function test_mail_failure_retains_booking_and_failure_can_be_retried(): void
    {
        $b = $this->booking();
        $message = OutboxMessage::first();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Simulated SMTP outage'));
        try {
            (new SendOutboxMessage($message->id))->handle();
            $this->fail('Expected mail failure');
        } catch (\RuntimeException $e) {
            $this->assertDatabaseHas('outbox_messages', ['id' => $message->id, 'status' => 'failed']);
        }
        $this->assertDatabaseHas('bookings', ['id' => $b->id]);
    }

    public function test_database_failure_rolls_back_booking_items_and_outbox(): void
    {
        $this->expectException(QueryException::class);
        try {
            DB::transaction(function () {
                $b = $this->booking();
                DB::table('not_a_real_table')->insert(['value' => 1]);
            });
        } finally {
            $this->assertDatabaseCount('bookings', 0);
            $this->assertDatabaseCount('booking_items', 0);
            $this->assertDatabaseCount('outbox_messages', 0);
        }
    }

    public function test_login_throttle_and_csrf_are_enforced(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'none@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }$this->post('/login', ['email' => 'none@example.com', 'password' => 'wrong'])->assertStatus(429);
        app()->instance('env', 'local');
        $this->post('/contact', ['name' => 'Test', 'email' => 't@example.com', 'message' => 'A legitimate message', 'terms' => 1])->assertStatus(419);
    }

    public function test_login_goes_directly_to_dashboard_even_for_accounts_with_legacy_two_factor_data(): void
    {
        $u = $this->admin();
        $u->forceFill(['two_factor_secret' => 'LEGACY_SECRET', 'two_factor_confirmed_at' => now()])->save();
        $this->post('/login', ['email' => $u->email, 'password' => 'Test-only-secure-passphrase!'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($u);
        $this->get('/security/setup')->assertNotFound();
    }

    public function test_staff_dashboard_is_available_without_authenticator_enrollment(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_image_upload_rejects_executable_payload_and_guide_cannot_manage_staff(): void
    {
        $this->actingAs($this->admin());
        $file = UploadedFile::fake()->createWithContent('payload.php', '<?php echo "unsafe";');
        $this->post('/admin/media', ['image' => $file, 'alt' => 'Invalid', 'sort_order' => 0])->assertSessionHasErrors('image');
        $this->actingAs($this->admin(['role' => 'guide']));
        $this->get('/admin/staff')->assertForbidden();
    }

    public function test_admin_can_upload_catalog_images_and_replace_a_shared_library_photo(): void
    {
        $this->actingAs($this->admin());
        $uploadedPaths = [];
        try {
            $this->post('/admin/catalog/vehicles', [
                'name' => 'Photo transfer',
                'mode' => 'road',
                'capacity' => 4,
                'active' => 1,
                'image' => UploadedFile::fake()->image('vehicle.png')->size(6 * 1024),
                'image_alt' => 'A vehicle on a coastal road',
            ])->assertRedirect();

            $vehicle = VehicleType::where('name', 'Photo transfer')->firstOrFail();
            $this->assertSame('A vehicle on a coastal road', $vehicle->media->alt);
            $uploadedPaths[] = $vehicle->media->path;
            $this->assertFileExists(public_path($vehicle->media->path));
            $destination = CatalogItem::where('slug', 'stone-town')->firstOrFail();
            $destination->update(['media_asset_id' => $vehicle->media_asset_id]);
            $this->get(route('catalog.detail', $destination->slug))
                ->assertOk()
                ->assertSee($vehicle->media->url(), false);

            $photo = MediaAsset::where('path', 'images/zanzibar-dhow.jpg')->firstOrFail();
            $originalPath = $photo->path;
            SiteSetting::setValue('hero_path', $originalPath);
            $this->patch(route('admin.media.update', $photo), [
                'image' => UploadedFile::fake()->image('replacement.png')->size(6 * 1024),
                'alt' => 'Updated dhow photograph',
                'gallery' => 1,
                'sort_order' => 0,
            ])->assertRedirect();
            $this->assertNotSame($originalPath, $photo->fresh()->path);
            $this->assertSame('Updated dhow photograph', $photo->fresh()->alt);
            $this->assertSame($photo->fresh()->path, SiteSetting::valueFor('hero_path'));
            $uploadedPaths[] = $photo->fresh()->path;
        } finally {
            foreach ($uploadedPaths as $path) {
                if (str_starts_with($path, 'images/uploads/')) {
                    @unlink(public_path($path));
                }
            }
        }
    }

    public function test_admin_can_upload_homepage_photographs_and_choose_photo_credit_images(): void
    {
        $this->actingAs($this->admin());
        $uploadedPath = null;
        try {
            $this->post('/admin/settings', [
                'company_name' => 'Example Tours',
                'email' => 'hello@example.test',
                'hero_heading' => 'Welcome',
                'hero_text' => 'Explore Tanzania.',
                'about_text' => 'Local journeys.',
                'why_text' => 'Thoughtfully arranged.',
                'hero_image' => UploadedFile::fake()->image('hero.png'),
                'hero_alt' => 'An island coastline',
            ])->assertRedirect();
            $uploadedPath = SiteSetting::valueFor('hero_path');
            $this->assertStringStartsWith('images/uploads/', $uploadedPath);
            $this->assertFileExists(public_path($uploadedPath));

            $photo = MediaAsset::where('path', 'images/zanzibar-dhow.jpg')->firstOrFail();
            $credits = json_decode(file_get_contents(public_path('images/credits.json')), true, flags: JSON_THROW_ON_ERROR);
            $fields = ['title', 'caption', 'author', 'author_url', 'source_url', 'license', 'license_url', 'changes', 'conditions'];
            $metadata = [];
            foreach ($credits as $index => $credit) {
                $metadata[$index] = array_intersect_key($credit, array_flip($fields));
            }
            $this->post(route('admin.media.credits'), ['credit_media_ids' => [1 => $photo->id], 'credit_metadata' => $metadata])
                ->assertSessionHasNoErrors();
        } finally {
            if ($uploadedPath && str_starts_with($uploadedPath, 'images/uploads/')) {
                @unlink(public_path($uploadedPath));
            }
        }
    }

    public function test_admin_can_upload_logo_without_alt_text_using_company_name_as_description(): void
    {
        $this->actingAs($this->admin());
        $uploadedPath = null;

        try {
            $this->get('/admin/settings')
                ->assertOk()
                ->assertSee('Optional; company name will be used if blank');

            $this->post('/admin/settings', [
                'company_name' => 'Example Safaris',
                'email' => 'hello@example.test',
                'hero_heading' => 'Welcome',
                'hero_text' => 'Explore Tanzania.',
                'about_text' => 'Local journeys.',
                'why_text' => 'Thoughtfully arranged.',
                'whatsapp' => '+255 712 345 678',
                'social_twitter' => 'https://twitter.com/example',
                'social_facebook' => 'https://facebook.com/example',
                'social_instagram' => 'https://instagram.com/example',
                'logo_image' => UploadedFile::fake()->image('logo.png'),
            ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status', 'Logo uploaded successfully.');

            $uploadedPath = SiteSetting::valueFor('logo_path');
            $this->assertStringStartsWith('images/uploads/', $uploadedPath);
            $this->assertDatabaseHas('media_assets', ['path' => $uploadedPath, 'alt' => 'Example Safaris']);

            $this->get('/admin/settings')
                ->assertOk()
                ->assertSee('class="public-site-logo mb-3" src="'.asset($uploadedPath).'"', false)
                ->assertSee('admin-sidebar-logo', false);
            $this->get('/')
                ->assertOk()
                ->assertSee('public-site-logo', false)
                ->assertSee(asset($uploadedPath))
                ->assertSee('mailto:hello@example.test', false)
                ->assertSee('href="https://wa.me/255712345678"', false)
                ->assertSee('href="https://twitter.com/example"', false)
                ->assertSee('href="https://facebook.com/example"', false)
                ->assertSee('href="https://instagram.com/example"', false)
                ->assertSee('aria-label="WhatsApp"', false)
                ->assertSee('aria-label="Twitter"', false)
                ->assertSee('aria-label="Facebook"', false)
                ->assertSee('aria-label="Instagram"', false);
            $this->get('/admin')
                ->assertOk()
                ->assertSee('admin-sidebar-logo', false)
                ->assertSee(asset($uploadedPath))
                ->assertSee('Example Safaris');
        } finally {
            if ($uploadedPath && str_starts_with($uploadedPath, 'images/uploads/')) {
                @unlink(public_path($uploadedPath));
            }
        }
    }

    public function test_social_icons_remain_visible_without_configured_links(): void
    {
        foreach (['whatsapp', 'social_twitter', 'social_facebook', 'social_instagram'] as $key) {
            SiteSetting::setValue($key, '');
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('aria-label="WhatsApp unavailable"', false)
            ->assertSee('aria-label="Twitter unavailable"', false)
            ->assertSee('aria-label="Facebook unavailable"', false)
            ->assertSee('aria-label="Instagram unavailable"', false)
            ->assertDontSee('href="https://wa.me/', false)
            ->assertDontSee('social_twitter', false)
            ->assertDontSee('social_facebook', false)
            ->assertDontSee('social_instagram', false);
    }

    public function test_failed_captcha_returns_to_a_reloadable_review_and_edit_preserves_fields(): void
    {
        $review = $this->reviewed('tour', $this->tourData());
        $id = $review->viewData('draftId');
        config(['tourism.captcha.enabled' => true]);
        $this->post(route('bookings.submit', 'tour'), ['draft_id' => $id])->assertRedirect(route('bookings.review', $id))->assertSessionHasErrors('captcha');
        $this->get(route('bookings.review', $id))->assertOk()->assertSee('Example Traveler');
        $edit = $this->get(route('bookings.edit', $id))->assertRedirect();
        $this->get($edit->headers->get('Location'))->assertOk()->assertSee('traveler@example.com');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_new_admin_vehicle_route_and_fare_can_be_booked_without_code_changes(): void
    {
        $this->actingAs($this->admin());
        $z = Region::where('slug', 'zanzibar')->first();
        $this->post('/admin/catalog/vehicles', ['name' => 'Configured shuttle', 'mode' => 'road', 'capacity' => 10, 'luggage_allowance' => 10, 'active' => 1])->assertRedirect();
        $vehicle = VehicleType::where('name', 'Configured shuttle')->first();
        $this->post('/admin/catalog/routes', ['name' => 'New route', 'region_id' => $z->id, 'origin' => 'Area A', 'destination' => 'Area B', 'duration_minutes' => 35, 'cutoff_hours' => 2, 'published' => 0])->assertRedirect();
        $route = TransportRoute::where('name', 'New route')->first();
        $this->get(route('admin.catalog.create', 'fares'))
            ->assertOk()
            ->assertSee('name="route_name"', false)
            ->assertSee('name="vehicle_type_name"', false)
            ->assertDontSee('name="transport_route_id"', false)
            ->assertDontSee('name="vehicle_type_id"', false);
        $this->from(route('admin.catalog.create', 'fares'))
            ->post('/admin/catalog/fares', ['route_name' => 'Unknown route', 'vehicle_type_name' => $vehicle->name, 'price' => '100000', 'currency' => 'TZS', 'pricing_basis' => 'per_vehicle', 'capacity' => 10, 'minimum_passengers' => 1, 'maximum_passengers' => 10, 'maximum_vehicles' => 1, 'multiple_vehicles' => 0, 'active' => 1])
            ->assertRedirect(route('admin.catalog.create', 'fares'))
            ->assertSessionHasErrors('route_name');
        $this->post('/admin/catalog/fares', ['route_name' => $route->name, 'vehicle_type_name' => $vehicle->name, 'price' => '100000', 'currency' => 'TZS', 'pricing_basis' => 'per_vehicle', 'capacity' => 10, 'minimum_passengers' => 1, 'maximum_passengers' => 10, 'maximum_vehicles' => 1, 'multiple_vehicles' => 0, 'active' => 1])->assertRedirect();
        $fare = $route->fares()->firstOrFail();
        $this->get(route('admin.catalog.edit', ['fares', $fare->id]))
            ->assertOk()
            ->assertSee('name="route_name" type="text" value="New route"', false)
            ->assertSee('name="vehicle_type_name" type="text" value="Configured shuttle"', false);
        $this->patch('/admin/catalog/routes/'.$route->id, ['name' => 'New route', 'region_id' => $z->id, 'origin' => 'Area A', 'destination' => 'Area B', 'duration_minutes' => 35, 'cutoff_hours' => 2, 'published' => 1])->assertRedirect();
        $this->get(route('transport.book', $route->slug))->assertOk()->assertSee('Configured shuttle');
        $d = $this->transportData();
        $d['route_id'] = $route->id;
        $d['fare_id'] = $route->fares()->first()->id;
        $this->assertSame(10000000, app(TransportPricingService::class)->quote($d)['total_minor']);
    }

    public function test_transport_admin_manages_route_and_fares_from_single_routes_section(): void
    {
        $admin = $this->admin();
        $region = Region::where('slug', 'zanzibar')->firstOrFail();
        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.routes.index'), false)
            ->assertDontSee(route('admin.catalog.index', 'vehicles'), false)
            ->assertDontSee(route('admin.catalog.index', 'fares'), false);

        $this->get(route('admin.routes.create'))
            ->assertOk()
            ->assertSee('Origin area')
            ->assertSee('Destination area')
            ->assertSee('Pickup / drop-off instructions')
            ->assertSee('Included services')
            ->assertSee('Vehicle type name')
            ->assertSee('One-way price')
            ->assertSee('Route photo');

        $this->post(route('admin.routes.store'), [
            'name' => 'Admin-created airport route',
            'region_id' => $region->id,
            'origin' => 'Airport',
            'destination' => 'Stone Town',
            'duration_minutes' => 45,
            'description' => 'A direct airport transfer.',
            'instructions' => 'Meet at the arrivals area.',
            'inclusions' => "Air conditioning\nLuggage assistance",
            'exclusions' => 'Meals',
            'cutoff_hours' => 2,
            'active_from' => '',
            'active_until' => '',
            'published' => 1,
            'fares' => [[
                'vehicle_type_name' => 'Admin-created minivan',
                'mode' => 'road',
                'vehicle_description' => 'Air-conditioned minivan',
                'price' => '85000',
                'return_price' => '160000',
                'currency' => 'TZS',
                'pricing_basis' => 'per_vehicle',
                'capacity' => 7,
                'luggage_allowance' => 7,
                'minimum_passengers' => 1,
                'maximum_passengers' => 7,
                'multiple_vehicles' => 0,
                'maximum_vehicles' => 1,
                'active' => 1,
            ]],
        ])->assertRedirect(route('admin.routes.index'));

        $route = TransportRoute::where('name', 'Admin-created airport route')->firstOrFail();
        $fare = $route->fares()->with('vehicleType')->firstOrFail();
        $this->assertSame('Admin-created minivan', $fare->vehicleType->name);
        $this->assertSame('85000.00', $fare->price);
        $this->assertSame('Meet at the arrivals area.', $route->instructions);
        $this->get(route('admin.routes.index'))->assertOk()
            ->assertSee('Admin-created airport route')
            ->assertSee('Admin-created minivan')
            ->assertSee('Edit')
            ->assertSee('Delete');
        $this->get(route('transport.index'))->assertOk()->assertSee('Airport')->assertSee('Stone Town')->assertSee('Admin-created minivan');
    }

    public function test_payment_only_staff_can_read_and_record_payments_but_cannot_issue_invoices(): void
    {
        $b = $this->booking();
        $u = $this->admin(['role' => 'guide', 'can_verify_payments' => true]);
        $b->update(['assigned_to' => $u->id]);
        SiteSetting::setValue('payment_instructions', 'Demo configured payment instructions');
        $s = app(InvoiceService::class);
        $i = $s->issue($s->draft($b));
        $this->actingAs($u)->get(route('admin.invoices.edit', $i))->assertOk();
        $this->post(route('admin.invoices.issue', $i), ['agreement_confirmed' => 1])->assertForbidden();
        $this->post(route('admin.invoices.payment', $i), ['kind' => 'payment', 'amount' => '10000', 'currency' => 'TZS', 'paid_on' => today()->toDateString(), 'method' => 'cash', 'transaction_reference' => 'VERIFIED-CASH-1', 'verified' => 1])->assertRedirect();
        $url = $s->send($i);
        $payment = $i->payments()->first();
        $this->get($url.'/receipt/'.$payment->id)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get($url.'/receipt/999999')->assertNotFound();
    }

    public function test_overpayment_and_wrong_currency_are_rejected_without_posting_ledger_entries(): void
    {
        $a = $this->admin();
        $b = $this->booking();
        SiteSetting::setValue('payment_instructions', 'Demo configured method');
        $s = app(InvoiceService::class);
        $i = $s->issue($s->draft($b));
        $this->actingAs($a);
        $d = ['kind' => 'payment', 'amount' => '9999999', 'currency' => 'TZS', 'paid_on' => today()->toDateString(), 'method' => 'bank', 'transaction_reference' => 'OVERSIZED', 'verified' => 1];
        $this->post(route('admin.invoices.payment', $i), $d)->assertSessionHasErrors('invoice');
        $d['amount'] = '100';
        $d['currency'] = 'USD';
        $this->post(route('admin.invoices.payment', $i), $d)->assertSessionHasErrors('invoice');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_production_cannot_start_with_development_captcha_bypass(): void
    {
        app()->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        (new AppServiceProvider(app()))->boot();
    }

    public function test_optional_demo_requests_are_repeatable_and_do_not_queue_fictitious_email(): void
    {
        $this->seed(DemoBookingSeeder::class);
        $this->seed(DemoBookingSeeder::class);
        $this->assertDatabaseCount('bookings', 3);
        $this->assertDatabaseCount('accommodation_requests', 1);
        $this->assertDatabaseCount('transport_booking_details', 1);
        $this->assertDatabaseCount('outbox_messages', 0);
    }

    public function test_unresolved_accommodation_cannot_be_silently_omitted_from_an_issued_invoice(): void
    {
        $b = $this->booking();
        $b->accommodation()->create(['preferences' => ['notes' => 'Find a hotel'], 'status' => 'requested']);
        SiteSetting::setValue('payment_instructions', 'Demonstration method');
        $s = app(InvoiceService::class);
        $draft = $s->draft($b->fresh());
        $this->expectException(ValidationException::class);
        $s->issue($draft);
    }
}
