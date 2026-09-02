<?php

namespace App\Providers;

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        View::composer('layouts.public', function ($view): void {
            $view->with('navCategories', Cache::remember('public.nav-categories', now()->addHour(), fn (): array => Category::query()
                ->where('is_active', true)
                ->orderBy('order')
                ->get(['name', 'slug'])
                ->map(fn (Category $category): array => $category->only(['name', 'slug']))
                ->all()));
            $view->with('publicMenus', Cache::remember('public.menus', now()->addHour(), fn (): array => Menu::query()
                ->where('is_active', true)
                ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('order')])
                ->get(['id', 'location'])
                ->groupBy('location')
                ->map(fn ($menus): array => $menus->flatMap(fn (Menu $menu) => $menu->items)
                    ->groupBy(fn (MenuItem $item): int => $item->parent_id ?? 0)
                    ->pipe(function ($grouped): array {
                        return $grouped->get(0, collect())->map(function (MenuItem $item) use ($grouped): array {
                            return [...$item->only(['label', 'url', 'target']), 'children' => $grouped->get($item->id, collect())
                                ->map(fn (MenuItem $child): array => $child->only(['label', 'url', 'target']))->values()->all()];
                        })->values()->all();
                    }))
                ->all()));
            $view->with('siteSettings', Cache::remember('public.site-settings', now()->addHour(), fn (): array => Setting::query()
                ->where('is_public', true)
                ->get(['key', 'value'])
                ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $setting->value])
                ->all()));
        });
        View::composer('public.home', function ($view): void {
            $view->with('advertisements', Cache::remember('public.advertisements', now()->addMinutes(15), fn (): array => Advertisement::query()
                ->activeNow()
                ->orderByDesc('starts_at')
                ->get(['id', 'placement', 'title', 'image_url', 'destination_url'])
                ->groupBy('placement')
                ->map(fn ($advertisements): array => $advertisements->map(fn (Advertisement $advertisement): array => $advertisement->only(['id', 'title', 'image_url', 'destination_url']))->all())
                ->all()));
        });
    }
}
