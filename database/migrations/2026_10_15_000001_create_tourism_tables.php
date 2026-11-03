<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('guide');
            $t->boolean('active')->default(true);
            $t->boolean('can_invoice')->default(false);
            $t->boolean('can_verify_payments')->default(false);
            $t->text('two_factor_secret')->nullable();
            $t->timestamp('two_factor_confirmed_at')->nullable();
            $t->unsignedBigInteger('two_factor_last_step')->nullable();
        });
        Schema::create('regions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('media_assets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('region_id')->nullable()->constrained();
            $t->string('path');
            $t->string('alt');
            $t->boolean('gallery')->default(false);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('catalog_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('region_id')->constrained();
            $t->foreignId('media_asset_id')->nullable()->constrained();
            $t->string('kind')->index();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('name_sw')->nullable();
            $t->text('summary')->nullable();
            $t->longText('description')->nullable();
            $t->string('location')->nullable();
            $t->decimal('price', 15, 2)->nullable();
            $t->string('currency', 3)->default('TZS');
            $t->string('pricing_basis')->default('per_person');
            $t->boolean('is_free')->default(false);
            $t->boolean('published')->default(false);
            $t->unsignedInteger('duration_days')->default(1);
            $t->text('itinerary')->nullable();
            $t->text('inclusions')->nullable();
            $t->text('exclusions')->nullable();
            $t->json('included_item_ids')->nullable();
            $t->text('requirements')->nullable();
            $t->string('languages')->nullable();
            $t->timestamps();
            $t->index(['region_id', 'kind', 'published']);
        });
        Schema::create('vehicle_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('mode')->default('road');
            $t->text('description')->nullable();
            $t->foreignId('media_asset_id')->nullable()->constrained();
            $t->unsignedInteger('capacity');
            $t->unsignedInteger('luggage_allowance')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('transport_routes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('region_id')->constrained();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('origin');
            $t->string('destination');
            $t->unsignedInteger('duration_minutes')->default(60);
            $t->text('description')->nullable();
            $t->text('instructions')->nullable();
            $t->text('inclusions')->nullable();
            $t->text('exclusions')->nullable();
            $t->unsignedInteger('cutoff_hours')->default(0);
            $t->date('active_from')->nullable();
            $t->date('active_until')->nullable();
            $t->boolean('published')->default(false);
            $t->foreignId('media_asset_id')->nullable()->constrained();
            $t->timestamps();
        });
        Schema::create('transport_fares', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transport_route_id')->constrained();
            $t->foreignId('vehicle_type_id')->constrained();
            $t->decimal('price', 15, 2);
            $t->decimal('return_price', 15, 2)->nullable();
            $t->string('currency', 3)->default('TZS');
            $t->string('pricing_basis')->default('per_vehicle');
            $t->unsignedInteger('capacity');
            $t->unsignedInteger('luggage_allowance')->nullable();
            $t->unsignedInteger('minimum_passengers')->default(1);
            $t->unsignedInteger('maximum_passengers')->default(100);
            $t->boolean('multiple_vehicles')->default(false);
            $t->unsignedInteger('maximum_vehicles')->default(1);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['transport_route_id', 'vehicle_type_id']);
        });
        Schema::create('site_settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->longText('value')->nullable();
        });
        Schema::create('preference_options', function (Blueprint $t) {
            $t->id();
            $t->string('group');
            $t->string('label');
            $t->foreignId('region_id')->nullable()->constrained();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->longText('body');
            $t->string('meta_description')->nullable();
            $t->boolean('published')->default(true);
            $t->timestamps();
        });
        Schema::create('document_sequences', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->unsignedBigInteger('value')->default(0);
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->string('reference')->unique();
            $t->uuid('idempotency_key')->unique();
            $t->string('guest_hash', 64);
            $t->string('payload_hash', 64);
            $t->string('kind')->index();
            $t->string('source');
            $t->foreignId('region_id')->constrained();
            $t->foreignId('assigned_to')->nullable()->constrained('users');
            $t->string('name');
            $t->string('email');
            $t->string('phone');
            $t->string('country');
            $t->string('preferred_contact')->default('email');
            $t->text('special_requests')->nullable();
            $t->date('start_date');
            $t->date('end_date');
            $t->unsignedInteger('adults');
            $t->unsignedInteger('children')->default(0);
            $t->string('currency', 3);
            $t->decimal('known_subtotal', 15, 2);
            $t->boolean('quote_required')->default(false);
            $t->string('status')->default('submitted')->index();
            $t->boolean('availability_confirmed')->default(false);
            $t->text('internal_notes')->nullable();
            $t->timestamp('privacy_accepted_at');
            $t->timestamps();
            $t->index(['kind', 'start_date']);
        });
        Schema::create('booking_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->constrained();
            $t->foreignId('catalog_item_id')->nullable()->constrained();
            $t->text('description');
            $t->unsignedInteger('quantity');
            $t->string('unit');
            $t->decimal('unit_price', 15, 2)->nullable();
            $t->decimal('amount', 15, 2)->nullable();
            $t->json('snapshot');
            $t->timestamps();
        });
        Schema::create('transport_booking_details', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->unique()->constrained();
            $t->foreignId('transport_fare_id')->constrained();
            $t->timestamp('pickup_at');
            $t->timestamp('return_at')->nullable();
            $t->unsignedInteger('passengers');
            $t->unsignedInteger('vehicles');
            $t->unsignedInteger('luggage')->default(0);
            $t->string('pickup_details');
            $t->string('dropoff_details');
            $t->string('arrival_number')->nullable();
            $t->json('snapshot');
            $t->timestamps();
        });
        Schema::create('accommodation_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->unique()->constrained();
            $t->json('preferences');
            $t->string('status')->default('requested');
            $t->unsignedBigInteger('selected_proposal_id')->nullable();
            $t->timestamps();
        });
        Schema::create('accommodation_proposals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('accommodation_request_id')->constrained();
            $t->string('hotel_name');
            $t->string('location');
            $t->date('check_in');
            $t->date('check_out');
            $t->unsignedInteger('rooms');
            $t->string('room_type');
            $t->string('meal_plan');
            $t->decimal('nightly_rate', 15, 2);
            $t->string('currency', 3);
            $t->text('inclusions')->nullable();
            $t->text('terms');
            $t->string('reservation_status')->default('proposed');
            $t->string('supplier_reference')->nullable();
            $t->string('supplier_contact')->nullable();
            $t->timestamp('availability_checked_at');
            $t->timestamp('hold_expires_at')->nullable();
            $t->text('internal_notes')->nullable();
            $t->timestamps();
        });
        Schema::table('accommodation_requests', fn (Blueprint $t) => $t->foreign('selected_proposal_id')->references('id')->on('accommodation_proposals'));
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->string('number')->nullable()->unique();
            $t->foreignId('booking_id')->constrained();
            $t->foreignId('supersedes_id')->nullable()->constrained('invoices');
            $t->string('status')->default('draft');
            $t->string('currency', 3);
            $t->date('issue_date')->nullable();
            $t->date('due_date');
            $t->string('payment_mode')->default('deposit');
            $t->string('deposit_type')->default('percent');
            $t->decimal('deposit_value', 15, 2)->default(30);
            $t->decimal('discount', 15, 2)->default(0);
            $t->decimal('tax_percent', 5, 2)->default(0);
            $t->decimal('subtotal', 15, 2)->default(0);
            $t->decimal('tax_amount', 15, 2)->default(0);
            $t->decimal('grand_total', 15, 2)->default(0);
            $t->decimal('deposit_required', 15, 2)->default(0);
            $t->json('company_snapshot')->nullable();
            $t->json('customer_snapshot')->nullable();
            $t->text('terms')->nullable();
            $t->text('void_reason')->nullable();
            $t->timestamp('issued_at')->nullable();
            $t->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained();
            $t->text('description');
            $t->string('unit');
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 15, 2)->nullable();
            $t->decimal('amount', 15, 2)->nullable();
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained();
            $t->foreignId('recorded_by')->constrained('users');
            $t->foreignId('source_payment_id')->nullable()->constrained('payments');
            $t->string('kind');
            $t->decimal('amount', 15, 2);
            $t->string('currency', 3);
            $t->date('paid_on');
            $t->string('method');
            $t->string('transaction_reference');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['method', 'transaction_reference']);
        });
        Schema::create('document_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained();
            $t->string('token_hash', 64)->unique();
            $t->timestamp('expires_at');
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
        });
        Schema::create('outbox_messages', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->string('recipient');
            $t->string('subject');
            $t->text('payload');
            $t->string('status')->default('pending');
            $t->unsignedInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('processing_at')->nullable();
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('action');
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->json('details')->nullable();
            $t->timestamps();
        });
        Schema::create('inquiries', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->text('message');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_requests', fn (Blueprint $t) => $t->dropForeign(['selected_proposal_id']));
        foreach (['inquiries', 'audit_logs', 'outbox_messages', 'document_tokens', 'payments', 'invoice_items', 'invoices', 'accommodation_proposals', 'accommodation_requests', 'transport_booking_details', 'booking_items', 'bookings', 'document_sequences', 'pages', 'preference_options', 'site_settings', 'transport_fares', 'transport_routes', 'vehicle_types', 'catalog_items', 'media_assets', 'regions'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users',fn (Blueprint $t) => $t->dropColumn(['role', 'active', 'can_invoice', 'can_verify_payments', 'two_factor_secret', 'two_factor_confirmed_at', 'two_factor_last_step']));
    }
};
