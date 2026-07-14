<?php

namespace App\Livewire\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Support\MoneyFormatter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProductGrid extends Component
{
    private const INITIAL_VISIBLE = 24;

    private const LOAD_MORE_STEP = 24;

    private const MAX_VISIBLE = 200;

    #[Url(as: 'category')]
    public ?string $categorySlug = null;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'newest';

    /** Nur Produkte mit reduziertem Preis (sale_price gesetzt und unter regulärem Preis). */
    #[Url(as: 'sale')]
    public bool $saleOnly = false;

    /** Wachsendes Limit statt vieler kleiner Paginierungs-Requests — skaliert bis 500+ Zeilen sauber. */
    public int $visibleLimit = self::INITIAL_VISIBLE;

    public function updatedCategorySlug(): void
    {
        $this->visibleLimit = self::INITIAL_VISIBLE;
    }

    public function updatedSearch(): void
    {
        $this->visibleLimit = self::INITIAL_VISIBLE;
    }

    public function updatedSortBy(): void
    {
        $this->visibleLimit = self::INITIAL_VISIBLE;
    }

    public function updatedSaleOnly(): void
    {
        $this->visibleLimit = self::INITIAL_VISIBLE;
    }

    public function loadMore(): void
    {
        if (! $this->products->hasMorePages()) {
            return;
        }

        $this->visibleLimit = min($this->visibleLimit + self::LOAD_MORE_STEP, self::MAX_VISIBLE);
    }

    /**
     * Get active categories for filter
     */
    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        $cached = Cache::get(Category::CACHE_KEY_NAV_ROOT);

        if (is_array($cached) && array_key_exists('ids', $cached)) {
            $ids = array_values(array_filter($cached['ids'], fn ($id): bool => is_int($id) || ctype_digit((string) $id)));

            if ($ids === []) {
                return Category::newCollection();
            }

            return Category::query()
                ->whereIn('id', $ids)
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('sort_order')
                ->get();
        }

        if ($cached !== null) {
            Cache::forget(Category::CACHE_KEY_NAV_ROOT);
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        Cache::put(
            Category::CACHE_KEY_NAV_ROOT,
            ['ids' => $categories->pluck('id')->map(fn ($id) => (int) $id)->all()],
            now()->addMinutes(30),
        );

        return $categories;
    }

    /**
     * Anzahl aktiver Produkte (eine Abfrage statt inline im Blade).
     */
    #[Computed]
    public function totalActiveProductCount(): int
    {
        return Product::query()->where('is_active', true)->count();
    }

    /**
     * Get current category
     */
    #[Computed]
    public function currentCategory(): ?Category
    {
        if (! $this->categorySlug) {
            return null;
        }

        return Category::query()
            ->where('slug', $this->categorySlug)
            ->where('is_active', true)
            ->with(['parent.parent.parent.parent'])
            ->first();
    }

    /**
     * Aktive Kategorie existiert, hat aber keine aktiven Produkte (ohne Suche/Sale-Filter).
     */
    #[Computed]
    public function categoryIsEmpty(): bool
    {
        if ($this->categorySlug === null || $this->categorySlug === '' || $this->search !== '' || $this->saleOnly) {
            return false;
        }

        if ($this->currentCategory === null) {
            return false;
        }

        return Product::query()
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('slug', $this->categorySlug))
            ->doesntExist();
    }

    /**
     * Gefilterte Liste mit wachsendem Limit (Load-more / schneller erster Paint).
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = $this->filteredProductQuery();

        // Für 500+ Produkte ist die Count-Query der häufigste Hotspot.
        // Cache hilft massiv gegen wiederholtes Re-Rendering bei Livewire-Filterwechseln.
        $total = Cache::remember(
            $this->totalCountCacheKey(),
            now()->addSeconds(20),
            fn () => (clone $query)->count(),
        );

        $items = (clone $query)->limit($this->visibleLimit)->get();

        return new LengthAwarePaginator(
            $items,
            $total,
            $this->visibleLimit,
            1,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page'],
        );
    }

    private function totalCountCacheKey(): string
    {
        return 'shop.product-grid.total.'
            . md5(json_encode([
                'category' => $this->categorySlug,
                'search' => $this->search,
                'sortBy' => $this->sortBy,
                'saleOnly' => $this->saleOnly,
            ]));
    }

    protected function filteredProductQuery(): Builder
    {
        $query = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.price',
                'products.sale_price',
                'products.sku',
                'products.images',
                'products.category_id',
                'products.stock',
                'products.has_variants',
                'products.is_active',
                'products.created_at',
            ])
            ->where('products.is_active', true)
            ->with(['category:id,name,slug,game_type'])
            ->withCount('variants');

        if ($this->categorySlug) {
            // Statt `whereHas(category.slug=...)` nutzen wir die bekannte category_id,
            // um JOINs zu vermeiden und Indizes auf `products.category_id` zu nutzen.
            $categoryId = $this->currentCategory?->id;
            if ($categoryId) {
                $query->where('products.category_id', $categoryId);
            } else {
                // Kategorie existiert nicht/ist inaktiv: schnell auf „leer“ springen.
                $query->whereRaw('1 = 0');
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%");
            });
        }

        if ($this->saleOnly) {
            $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price');
        }

        return match ($this->sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): void
    {
        $this->categorySlug = null;
        $this->search = '';
        $this->sortBy = 'newest';
        $this->saleOnly = false;
        $this->visibleLimit = self::INITIAL_VISIBLE;
    }

    /**
     * Format price for display
     */
    public function formatPrice(float $price): string
    {
        return MoneyFormatter::format($price);
    }

    public function render()
    {
        return view('livewire.shop.product-grid');
    }
}
