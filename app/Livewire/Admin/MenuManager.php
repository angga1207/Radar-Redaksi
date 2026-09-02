<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class MenuManager extends Component
{
    public ?int $menuId = null;

    public ?int $itemId = null;

    public string $name = '';

    public string $location = 'header';

    public bool $menuActive = true;

    public string $label = '';

    public string $url = '';

    public string $target = '_self';

    public string $parentId = '';

    public int $order = 0;

    public bool $itemActive = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('menus.manage'), 403);
        $menuId = Menu::query()->value('id');

        if ($menuId) {
            $this->selectMenu($menuId);
        }
    }

    public function selectMenu(int $id): void
    {
        $menu = Menu::query()->findOrFail($id);
        $this->menuId = $menu->id;
        $this->name = $menu->name;
        $this->location = $menu->location;
        $this->menuActive = $menu->is_active;
        $this->resetItem();
        $this->dispatch('tom-select:set', property: 'location', value: $this->location);
    }

    public function saveMenu(): void
    {
        abort_unless(auth()->user()->hasPermission('menus.manage'), 403);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'location' => ['required', Rule::in(['header', 'footer']), Rule::unique('menus', 'location')->ignore($this->menuId)],
            'menuActive' => ['boolean'],
        ]);
        $menu = Menu::query()->updateOrCreate(['id' => $this->menuId], ['name' => $data['name'], 'location' => $data['location'], 'is_active' => $data['menuActive']]);
        AuditLog::record($this->menuId ? 'menu.updated' : 'menu.created', $menu);
        $this->menuId = $menu->id;
        Cache::forget('public.menus');
        session()->flash('success', 'Menu berhasil disimpan.');
    }

    public function editItem(int $id): void
    {
        $item = MenuItem::query()->where('menu_id', $this->menuId)->findOrFail($id);
        $this->itemId = $item->id;
        $this->label = $item->label;
        $this->url = $item->url;
        $this->target = $item->target;
        $this->parentId = (string) ($item->parent_id ?? '');
        $this->order = $item->order;
        $this->itemActive = $item->is_active;
        $this->dispatch('tom-select:set', property: 'target', value: $this->target);
        $this->dispatch('tom-select:set', property: 'parentId', value: $this->parentId);
    }

    public function saveItem(): void
    {
        abort_unless(auth()->user()->hasPermission('menus.manage') && $this->menuId, 403);
        $data = $this->validate([
            'label' => ['required', 'string', 'max:100'], 'url' => ['required', 'string', 'max:2048'],
            'target' => ['required', Rule::in(['_self', '_blank'])], 'order' => ['integer', 'min:0', 'max:999'], 'itemActive' => ['boolean'],
            'parentId' => ['nullable', 'integer', Rule::exists('menu_items', 'id')->where('menu_id', $this->menuId), Rule::notIn(array_filter([$this->itemId]))],
        ]);
        $item = MenuItem::query()->updateOrCreate(['id' => $this->itemId, 'menu_id' => $this->menuId], [
            'parent_id' => $data['parentId'] ?: null, 'label' => $data['label'], 'url' => $data['url'], 'target' => $data['target'], 'order' => $data['order'], 'is_active' => $data['itemActive'],
        ]);
        AuditLog::record($this->itemId ? 'menu-item.updated' : 'menu-item.created', $item);
        Cache::forget('public.menus');
        $this->resetItem();
    }

    public function deleteItem(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('menus.manage'), 403);
        $item = MenuItem::query()->where('menu_id', $this->menuId)->findOrFail($id);
        AuditLog::record('menu-item.deleted', $item);
        $item->delete();
        Cache::forget('public.menus');
    }

    public function deleteMenu(): void
    {
        abort_unless(auth()->user()->hasPermission('menus.manage') && $this->menuId, 403);
        $menu = Menu::query()->findOrFail($this->menuId);
        AuditLog::record('menu.deleted', $menu);
        $menu->delete();
        Cache::forget('public.menus');
        $this->resetMenu();
        session()->flash('success', 'Menu berhasil dihapus.');
    }

    public function resetMenu(): void
    {
        $this->reset(['menuId', 'name']);
        $this->location = 'header';
        $this->menuActive = true;
        $this->resetItem();
        $this->dispatch('tom-select:set', property: 'location', value: 'header');
    }

    public function resetItem(): void
    {
        $this->reset(['itemId', 'label', 'url', 'parentId', 'order']);
        $this->target = '_self';
        $this->itemActive = true;
        $this->dispatch('tom-select:set', property: 'target', value: '_self');
        $this->dispatch('tom-select:set', property: 'parentId', value: '');
    }

    public function render(): View
    {
        abort_unless(auth()->user()->hasPermission('menus.manage'), 403);

        return view('livewire.admin.menu-manager', [
            'menus' => Menu::query()->withCount('items')->orderBy('name')->get(),
            'items' => $this->menuId ? MenuItem::query()->with('parent')->where('menu_id', $this->menuId)->orderBy('order')->get() : collect(),
        ]);
    }
}
