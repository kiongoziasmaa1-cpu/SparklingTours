<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Inquiry;
use App\Models\MediaAsset;
use App\Models\OutboxMessage;
use App\Models\Region;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Audit;
use App\Services\MediaLibrary;
use App\Services\OutboxService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    private function admin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function settings()
    {
        $this->admin();

        return view('admin.settings', ['settings' => SiteSetting::pluck('value', 'key')->all(), 'media' => MediaAsset::all()]);
    }

    public function saveSettings(Request $r)
    {
        $this->admin();
        $imageRules = 'nullable|file|image|mimes:jpg,jpeg,png,gif,bmp,webp,avif|max:10240|dimensions:max_width=6000,max_height=6000';
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'The image exceeds the web PHP upload_max_filesize limit.',
            UPLOAD_ERR_FORM_SIZE => 'The image exceeds the upload size limit specified by the form.',
            UPLOAD_ERR_PARTIAL => 'The image upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'PHP could not find its upload temporary folder. Ask the server administrator to check upload_tmp_dir.',
            UPLOAD_ERR_CANT_WRITE => 'PHP could not write the uploaded image to its temporary folder. Check folder permissions and available disk space.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the image upload. Check the PHP and Apache error logs.',
        ];
        $genericUploadError = 'PHP rejected the image upload. Check the web PHP upload limits, temporary-folder permissions, and the PHP/Apache error logs.';
        foreach (['hero_image', 'about_image', 'fallback_image', 'logo_image'] as $imageField) {
            $uploadedFile = $r->file($imageField);
            if ($uploadedFile && ! $uploadedFile->isValid()) {
                $errorCode = $uploadedFile->getError();
                $message = $uploadErrors[$errorCode] ?? "PHP reported an image upload error (code {$errorCode}). Check the PHP and Apache error logs.";

                return back()->withErrors([$imageField => $message])->withInput();
            }
        }
        $d = $r->validate(['company_name' => 'required|string|max:150', 'email' => 'required_without:notification_email|nullable|email|max:190', 'notification_email' => 'required_without:email|nullable|email|max:190', 'phone' => 'nullable|string|max:40', 'whatsapp' => 'nullable|string|max:40', 'address' => 'nullable|string|max:300', 'hero_heading' => 'required|string|max:200', 'hero_text' => 'required|string|max:600', 'about_text' => 'required|string|max:4000', 'why_text' => 'required|string|max:3000', 'payment_instructions' => 'nullable|string|max:3000', 'invoice_terms' => 'nullable|string|max:3000', 'usd_tzs_rate' => 'nullable|decimal:0,6|min:0.000001|max:99999999', 'exchange_rate_date' => 'nullable|date', 'hero_media_id' => 'nullable|exists:media_assets,id', 'hero_image' => $imageRules, 'hero_alt' => 'required_with:hero_image|nullable|string|max:190', 'about_media_id' => 'nullable|exists:media_assets,id', 'about_image' => $imageRules, 'about_alt' => 'required_with:about_image|nullable|string|max:190', 'fallback_media_id' => 'nullable|exists:media_assets,id', 'fallback_image' => $imageRules, 'fallback_alt' => 'required_with:fallback_image|nullable|string|max:190', 'hero_button_primary' => 'nullable|string|max:50', 'hero_button_secondary' => 'nullable|string|max:50', 'social_twitter' => 'nullable|url:http,https|max:300', 'social_instagram' => 'nullable|url:http,https|max:300', 'social_facebook' => 'nullable|url:http,https|max:300', 'logo_id' => 'nullable|exists:media_assets,id', 'logo_image' => $imageRules, 'logo_alt' => 'nullable|string|max:190', 'demo_mode' => 'boolean'], [
            'hero_image.uploaded' => $genericUploadError,
            'about_image.uploaded' => $genericUploadError,
            'fallback_image.uploaded' => $genericUploadError,
            'logo_image.uploaded' => $genericUploadError,
            'logo_image.image' => 'Choose a valid raster image: JPEG, PNG, GIF, BMP, WebP or AVIF. SVG and other files cannot be displayed as a safe logo.',
            'logo_image.mimes' => 'Choose a JPEG, PNG, GIF, BMP, WebP or AVIF image for the logo.',
            'logo_image.max' => 'The logo image must be 10 MB or smaller.',
            'logo_image.dimensions' => 'The logo image must not exceed 6000 pixels in either dimension.',
        ]);
        $logoUploaded = $r->hasFile('logo_image');
        if (! empty($d['usd_tzs_rate']) && empty($d['exchange_rate_date'])) {
            return back()->withErrors(['exchange_rate_date' => 'Record the effective exchange-rate date.'])->withInput();
        }
        $library = app(MediaLibrary::class);
        $currentPaths = SiteSetting::whereIn('key', ['hero_path', 'about_path', 'fallback_path', 'logo_path'])->pluck('value', 'key');
        foreach ([
            'hero' => ['hero_media_id', 'hero_image', 'hero_alt', 'A dhow on the Zanzibar coast'],
            'about' => ['about_media_id', 'about_image', 'about_alt', 'Safari landscape in Tanzania'],
            'fallback' => ['fallback_media_id', 'fallback_image', 'fallback_alt', 'Tanzania landscape'],
            'logo' => ['logo_id', 'logo_image', 'logo_alt', 'Company logo'],
        ] as $slot => [$mediaField, $imageField, $altField, $defaultAlt]) {
            $pathKey = $slot === 'logo' ? 'logo_path' : $slot.'_path';
            $alt = $slot === 'logo' ? (($d[$altField] ?? '') ?: $d['company_name']) : ($d[$altField] ?? $defaultAlt);
            $asset = null;
            if ($r->hasFile($imageField)) {
                $asset = MediaAsset::create([
                    'path' => $library->store($r->file($imageField)),
                    'alt' => $alt,
                    'gallery' => false,
                    'sort_order' => 0,
                ]);
            } elseif (! empty($d[$mediaField] ?? null)) {
                $asset = MediaAsset::findOrFail($d[$mediaField]);
            } else {
                $asset = MediaAsset::where('path', $currentPaths->get($pathKey))->first();
            }
            $d[$pathKey] = $asset?->path ?? $currentPaths->get($pathKey, '');
            if ($slot !== 'logo') {
                $d[$slot.'_alt'] = $asset?->alt ?? $defaultAlt;
            }
            unset($d[$mediaField], $d[$imageField], $d[$altField]);
        }
        $d['demo_mode'] = $r->boolean('demo_mode') ? '1' : '0';
        foreach ($d as $key => $value) {
            SiteSetting::setValue($key, $value);
        }app()->forgetInstance('site.context');
        Audit::record('settings.updated', $r->user(), ['fields' => array_keys($d)]);

        return back()->with('status', $logoUploaded ? 'Logo uploaded successfully.' : 'Settings updated.');
    }

    public function media()
    {
        $this->admin();
        $credits = json_decode(file_get_contents(public_path('images/credits.json')), true, flags: JSON_THROW_ON_ERROR);
        $settings = SiteSetting::pluck('value', 'key');
        $media = MediaAsset::orderBy('sort_order')->get();
        $creditPhotoIds = [];
        $creditMetadata = [];
        foreach ($credits as $index => $photo) {
            $creditPhotoIds[$index] = $settings->get('credit_photo_'.$photo['filename'])
                ?: $media->firstWhere('path', 'images/'.$photo['filename'])?->id;
            foreach (['title', 'caption', 'author', 'author_url', 'source_url', 'license', 'license_url', 'changes', 'conditions'] as $field) {
                $creditMetadata[$index][$field] = $settings->get('credit_meta_'.$photo['filename'].'_'.$field, $photo[$field]);
            }
        }

        return view('admin.media', ['media' => $media, 'regions' => Region::all(), 'credits' => $credits, 'creditPhotoIds' => $creditPhotoIds, 'creditMetadata' => $creditMetadata]);
    }

    public function upload(Request $r)
    {
        $this->admin();
        $d = $r->validate(
            ['image' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=6000,max_height=6000', 'alt' => 'required|string|max:190', 'region_id' => 'nullable|exists:regions,id', 'gallery' => 'boolean', 'sort_order' => 'required|integer|min:0|max:10000'],
            ['image.uploaded' => 'The image did not reach the server. Choose an image under 10 MB and make sure PHP upload_max_filesize is at least 10M and post_max_size is at least 14M.']
        );
        $asset = MediaAsset::create(['path' => app(MediaLibrary::class)->store($r->file('image')), 'alt' => $d['alt'], 'region_id' => $d['region_id'] ?? null, 'gallery' => $r->boolean('gallery'), 'sort_order' => $d['sort_order']]);
        Audit::record('media.uploaded', $asset);

        return back()->with('status', 'Photograph uploaded.');
    }

    public function updateMedia(Request $r, MediaAsset $asset)
    {
        $this->admin();
        $d = $r->validate(
            ['image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240|dimensions:max_width=6000,max_height=6000', 'alt' => 'required|string|max:190', 'gallery' => 'required|boolean', 'sort_order' => 'required|integer|min:0|max:10000'],
            ['image.uploaded' => 'The image did not reach the server. Choose an image under 10 MB and make sure PHP upload_max_filesize is at least 10M and post_max_size is at least 14M.']
        );
        $oldPath = null;
        if ($r->hasFile('image')) {
            $oldPath = $asset->path;
            $d['path'] = app(MediaLibrary::class)->store($r->file('image'));
        }
        $asset->update($d);
        if ($oldPath !== null) {
            SiteSetting::whereIn('key', ['hero_path', 'about_path', 'fallback_path', 'logo_path'])
                ->where('value', $oldPath)
                ->update(['value' => $asset->path]);
        }
        Audit::record('media.updated', $asset);

        return back()->with('status', 'Photo settings saved.');
    }

    public function saveCreditPhotos(Request $r)
    {
        $this->admin();
        $credits = json_decode(file_get_contents(public_path('images/credits.json')), true, flags: JSON_THROW_ON_ERROR);
        $rules = ['credit_media_ids' => 'required|array'];
        foreach ($credits as $index => $photo) {
            $rules['credit_media_ids.'.$index] = 'nullable|integer|exists:media_assets,id';
            $rules['credit_metadata.'.$index.'.title'] = 'required|string|max:190';
            $rules['credit_metadata.'.$index.'.caption'] = 'required|string|max:500';
            $rules['credit_metadata.'.$index.'.author'] = 'required|string|max:190';
            $rules['credit_metadata.'.$index.'.author_url'] = 'required|url:http,https|max:300';
            $rules['credit_metadata.'.$index.'.source_url'] = 'required|url:http,https|max:500';
            $rules['credit_metadata.'.$index.'.license'] = 'required|string|max:190';
            $rules['credit_metadata.'.$index.'.license_url'] = 'required|url:http,https|max:500';
            $rules['credit_metadata.'.$index.'.changes'] = 'required|string|max:500';
            $rules['credit_metadata.'.$index.'.conditions'] = 'required|string|max:1000';
        }
        $d = $r->validate($rules);
        foreach ($credits as $index => $photo) {
            SiteSetting::setValue('credit_photo_'.$photo['filename'], $d['credit_media_ids'][$index] ?? '');
            foreach ($d['credit_metadata'][$index] as $field => $value) {
                SiteSetting::setValue('credit_meta_'.$photo['filename'].'_'.$field, $value);
            }
        }

        return back()->with('status', 'Photo credit images updated.');
    }

    public function staff()
    {
        $this->admin();

        return view('admin.staff', ['staff' => User::all()]);
    }

    public function saveStaff(Request $r, ?User $user = null)
    {
        $this->admin();
        $d = $r->validate(['name' => 'required|string|max:150', 'email' => ['required', 'email', Rule::unique('users')->ignore($user?->id)], 'role' => 'required|in:admin,guide', 'active' => 'required|boolean', 'can_invoice' => 'required|boolean', 'can_verify_payments' => 'required|boolean', 'password' => [$user ? 'nullable' : 'required', 'string', 'min:12', 'max:200', 'confirmed']]);
        if ($user && $user->isAdmin() && (! $d['active'] || $d['role'] !== 'admin') && User::where('role', 'admin')->where('active', true)->count() <= 1) {
            return back()->withErrors(['role' => 'Keep at least one active administrator.']);
        }
        if (empty($d['password'])) {
            unset($d['password']);
        }$staff = $user ?? new User;
        $staff->fill($d)->save();
        Audit::record('staff.updated', $staff);

        return back()->with('status', 'Staff account saved.');
    }

    public function deliveries()
    {
        $this->admin();

        return view('admin.deliveries', ['messages' => OutboxMessage::latest()->paginate(30), 'inquiries' => Inquiry::latest()->limit(20)->get(), 'audits' => AuditLog::latest()->limit(30)->get()]);
    }

    public function retry(OutboxMessage $message)
    {
        $this->admin();
        abort_if($message->status === 'sent', 422);
        $message->update(['status' => 'pending', 'attempts' => 0, 'processing_at' => null]);
        app(OutboxService::class)->dispatchPending();

        return back()->with('status', 'Delivery retry requested.');
    }
}
