<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\ImageOptimizer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class SettingsManager extends Component
{
    use WithFileUploads;

    public string $siteName = 'Radar Redaksi';

    public string $tagline = 'Mengabarkan fakta dengan jernih.';

    public string $contactEmail = '';

    public string $facebook = '';

    public string $instagram = '';

    public string $youtube = '';

    public string $seoTitle = 'Radar Redaksi — Kabar Terverifikasi';

    public string $seoDescription = 'Berita Indonesia terbaru, tepercaya, dan mudah dipahami.';

    public string $siteLogo = '';

    public string $siteFavicon = '';

    public ?TemporaryUploadedFile $siteLogoUpload = null;

    public ?TemporaryUploadedFile $siteFaviconUpload = null;

    public function mount(): void
    {
        foreach ($this->settingMap() as $property => $key) {
            $this->{$property} = Setting::query()->where('key', $key)->first()?->value ?? $this->{$property};
        }
        $this->siteLogo = Setting::query()->where('key', 'site_logo')->first()?->value ?? '';
        $this->siteFavicon = Setting::query()->where('key', 'site_favicon')->first()?->value ?? '';
    }

    public function save(ImageOptimizer $imageOptimizer): void
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);
        $data = $this->validate([
            'siteName' => ['required', 'string', 'max:100'], 'tagline' => ['required', 'string', 'max:255'],
            'contactEmail' => ['nullable', 'email'], 'facebook' => ['nullable', 'url'], 'instagram' => ['nullable', 'url'],
            'youtube' => ['nullable', 'url'], 'seoTitle' => ['required', 'string', 'max:255'],
            'seoDescription' => ['required', 'string', 'max:320'],
            'siteLogoUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'siteFaviconUpload' => ['nullable', 'image', 'mimes:png,webp', 'max:512', 'dimensions:max_width=512,max_height=512'],
        ]);
        foreach ($this->settingMap() as $property => $key) {
            $setting = Setting::query()->updateOrCreate(['key' => $key], ['group' => 'site', 'value' => $data[$property] ?: null, 'is_public' => true]);
            AuditLog::record('setting.updated', $setting);
        }
        $this->storeIdentityAsset('site_logo', 'siteLogo', 'siteLogoUpload', 'branding/logos', $imageOptimizer);
        $this->storeIdentityAsset('site_favicon', 'siteFavicon', 'siteFaviconUpload', 'branding/favicons', $imageOptimizer);
        Cache::forget('public.site-settings');
        session()->flash('success', 'Pengaturan berhasil disimpan.');
    }

    public function render(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        return view('livewire.admin.settings-manager');
    }

    /** @return array<string, string> */
    private function settingMap(): array
    {
        return ['siteName' => 'site_name', 'tagline' => 'tagline', 'contactEmail' => 'contact_email', 'facebook' => 'facebook', 'instagram' => 'instagram', 'youtube' => 'youtube', 'seoTitle' => 'seo_title', 'seoDescription' => 'seo_description'];
    }

    private function storeIdentityAsset(string $key, string $valueProperty, string $uploadProperty, string $directory, ImageOptimizer $imageOptimizer): void
    {
        $upload = $this->{$uploadProperty};
        if (! $upload) {
            return;
        }

        $oldUrl = $this->{$valueProperty};
        $path = $imageOptimizer->store($upload, $directory)['path'];
        $url = Storage::disk('public')->url($path);
        $setting = Setting::query()->updateOrCreate(['key' => $key], ['group' => 'site', 'value' => $url, 'is_public' => true]);
        AuditLog::record('setting.updated', $setting);
        $imageOptimizer->deletePublicUrl($oldUrl, $directory);
        $this->{$valueProperty} = $url;
        $this->reset($uploadProperty);
    }
}
