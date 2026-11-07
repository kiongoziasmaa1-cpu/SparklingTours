<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Inquiry;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Region;
use App\Models\SiteSetting;
use App\Models\TransportRoute;
use App\Services\CaptchaVerifier;
use App\Services\OutboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function home()
    {
        $destinationRegions = Region::with(['items' => fn ($query) => $query
            ->published()
            ->where('kind', 'destination')
            ->with('media')
            ->orderBy('name')])
            ->whereIn('slug', ['zanzibar', 'mainland'])
            ->orderByRaw("CASE slug WHEN 'zanzibar' THEN 0 ELSE 1 END")
            ->get();

        return view('public.home', [
            'galleries' => MediaAsset::where('gallery', true)->orderBy('sort_order')->get(),
            'destinationRegions' => $destinationRegions,
        ]);
    }

    public function catalog(Request $r, ?string $region = null, ?string $category = null)
    {
        $regionModel = $region ? Region::where('slug', $region)->firstOrFail() : null;
        $map = ['destinations' => 'destination', 'tours' => 'tour', 'activities' => 'activity', 'food' => 'food', 'travel-guide' => 'guide'];
        abort_unless(isset($map[$category ?? 'tours']), 404);
        $q = CatalogItem::published()->where('kind', $map[$category ?? 'tours'])->with(['media', 'region']);
        if ($regionModel) {
            $q->where('region_id', $regionModel->id);
        }

        return view('public.catalog', ['items' => $q->orderBy('name')->paginate(9), 'region' => $regionModel, 'category' => $category ?? 'tours']);
    }

    public function detail(string $slug)
    {
        return view('public.detail', ['item' => CatalogItem::published()->with('media', 'region')->where('slug', $slug)->firstOrFail()]);
    }

    public function transport(Request $r)
    {
        $filters = $r->validate(['region' => 'nullable|integer|exists:regions,id', 'q' => 'nullable|string|max:100']);
        $q = TransportRoute::where('published', true)->whereHas('activeFares')->with(['region', 'media', 'activeFares.vehicleType.media']);
        if (! empty($filters['region'])) {
            $q->where('region_id', $filters['region']);
        }
        if (! empty($filters['q'])) {
            $q->where(fn ($q) => $q->where('origin', 'like', '%'.$filters['q'].'%')->orWhere('destination', 'like', '%'.$filters['q'].'%'));
        }

        return view('public.transport', ['routes' => $q->orderBy('name')->paginate(9), 'regions' => Region::all()]);
    }

    public function page(string $slug)
    {
        return view('public.page', ['page' => Page::where('slug', $slug)->where('published', true)->firstOrFail()]);
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function sendContact(Request $r, CaptchaVerifier $captcha)
    {
        $d = $r->validate(['name' => 'required|string|max:150', 'email' => 'required|email:rfc|max:190', 'message' => 'required|string|min:10|max:3000', 'terms' => 'accepted', 'website' => 'nullable|string|max:0']);
        $captcha->verify($r->input('cf-turnstile-response'), 'contact', $r->ip());
        DB::transaction(function () use ($d) {
            Inquiry::create(collect($d)->only(['name', 'email', 'message'])->all());
            if ($email = SiteSetting::valueFor('notification_email')) {
                app(OutboxService::class)->create($email, 'New website enquiry', ['type' => 'contact', 'name' => $d['name'], 'email' => $d['email'], 'message' => $d['message']]);
            }
        });
        app(OutboxService::class)->dispatchPending();

        return back()->with('status', 'Your message has been received.');
    }

    public function credits()
    {
        $credits = json_decode(file_get_contents(public_path('images/credits.json')), true, flags: JSON_THROW_ON_ERROR);
        $settings = SiteSetting::where('key', 'like', 'credit_photo_%')->pluck('value', 'key');
        $metadata = SiteSetting::where('key', 'like', 'credit_meta_%')->pluck('value', 'key');
        $assets = MediaAsset::whereIn('id', $settings->filter()->values())->get()->keyBy('id');
        foreach ($credits as &$photo) {
            $asset = $assets->get($settings->get('credit_photo_'.$photo['filename']));
            $photo['image_url'] = $asset?->url() ?? asset('images/'.$photo['filename']);
            foreach (['title', 'caption', 'author', 'author_url', 'source_url', 'license', 'license_url', 'changes', 'conditions'] as $field) {
                $photo[$field] = $metadata->get('credit_meta_'.$photo['filename'].'_'.$field, $photo[$field]);
            }
        }
        unset($photo);

        return view('public.credits', ['credits' => $credits]);
    }

    public function sitemap()
    {
        $urls = [url('/'), route('transport.index'), route('tours.index')];
        foreach (CatalogItem::published()->get() as $item) {
            $urls[] = route('catalog.detail', $item->slug);
        } foreach (Page::where('published', true)->get() as $p) {
            $urls[] = route('pages.show', $p->slug);
        }

return response()->view('public.sitemap',['urls' => $urls])->header('Content-Type','application/xml');
    }
}
