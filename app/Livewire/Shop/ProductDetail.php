<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Support\MoneyFormatter;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProductDetail extends Component
{
    #[Locked]
    public Product $product;

    /** @var array<int, int|string> */
    public array $selectedAttributes = [];

    public ?int $selectedVariantId = null;

    public string $message = '';

    public function mount(string $slug): void
    {
        $this->product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'category.parent.parent.parent',
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with('attributeValues.attribute'),
            ])
            ->firstOrFail();

        $this->initializeSelection();
    }

    /**
     * @param  array<string, int|string|null>  $uiSelectedAttributes
     */
    public function addToCart(CartService $cartService, array $uiSelectedAttributes = []): void
    {
        $this->syncUiSelectedAttributes($uiSelectedAttributes);

        if (!$this->canAddToCart) {
            $this->message = 'Bitte wähle zuerst alle Varianten aus.';
            return;
        }

        $variant = $this->selectedVariant;
        if ($this->product->has_variants && !$variant) {
            $variant = $this->firstAvailableVariant();
        }

        if ($variant && $variant->stock < 1) {
            $this->message = 'Diese Variante ist aktuell nicht auf Lager.';
            return;
        }

        if (!$variant && $this->product->stock < 1) {
            $this->message = 'Dieses Produkt ist aktuell nicht auf Lager.';
            return;
        }

        $cartService->add(
            (int) $this->product->id,
            $variant?->id,
            1,
        );

        $this->dispatch('cartUpdated');
        $this->dispatch('shop-toast', message: 'In den Warenkorb gelegt.', type: 'success');
        $this->message = 'Zum Warenkorb hinzugefügt.';
    }

    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        if (!$this->selectedVariantId) {
            return null;
        }

        return $this->product->variants->firstWhere('id', $this->selectedVariantId);
    }

    #[Computed]
    public function displayPrice(): float
    {
        return $this->selectedVariant?->current_price ?? $this->product->current_price;
    }

    #[Computed]
    public function compareAtPrice(): ?float
    {
        if ($this->selectedVariant) {
            if ($this->selectedVariant->sale_price !== null && $this->selectedVariant->price !== null) {
                return (float) $this->selectedVariant->price;
            }

            return null;
        }

        if ($this->product->is_on_sale) {
            return (float) $this->product->price;
        }

        return null;
    }

    #[Computed]
    public function displayStock(): int
    {
        if ($this->selectedVariant) {
            return (int) $this->selectedVariant->stock;
        }

        if ($this->product->has_variants) {
            return (int) $this->product->variants
                ->where('stock', '>', 0)
                ->sum('stock');
        }

        return (int) $this->product->stock;
    }

    #[Computed]
    public function groupedAttributes(): Collection
    {
        return $this->product->variants
            ->flatMap(fn (ProductVariant $variant) => $variant->attributeValues)
            ->groupBy('product_attribute_id')
            ->map(function (Collection $values): array {
                $sortedValues = $values
                    ->unique('id')
                    ->sortBy('sort_order')
                    ->values();

                $firstValue = $sortedValues->first();
                $attribute = $firstValue?->attribute;

                return [
                    'id' => (int) $firstValue->product_attribute_id,
                    'name' => $attribute?->name ?? 'Option',
                    'values' => $sortedValues,
                ];
            })
            ->values();
    }

    #[Computed]
    public function canAddToCart(): bool
    {
        if (!$this->product->has_variants) {
            return $this->displayStock > 0;
        }

        if ($this->groupedAttributes->isEmpty()) {
            return (bool) $this->firstAvailableVariant();
        }

        return $this->selectedVariant?->stock > 0;
    }

    public function formatPrice(float $price): string
    {
        return MoneyFormatter::format($price);
    }

    protected function resolveVariant(): void
    {
        if (!$this->product->has_variants) {
            $this->selectedVariantId = null;
            return;
        }

        $selectedValueIds = collect($this->selectedAttributes)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->values();

        $requiredAttributesCount = $this->groupedAttributes->count();
        if ($selectedValueIds->count() !== $requiredAttributesCount || $requiredAttributesCount === 0) {
            $this->selectedVariantId = null;
            return;
        }

        $variant = $this->product->variants->first(function (ProductVariant $variant) use ($selectedValueIds, $requiredAttributesCount): bool {
            $variantValueIds = $variant->attributeValues->pluck('id')->map(fn ($id) => (int) $id);

            return $variantValueIds->count() === $requiredAttributesCount
                && $selectedValueIds->diff($variantValueIds)->isEmpty()
                && $variantValueIds->diff($selectedValueIds)->isEmpty();
        });

        $this->selectedVariantId = $variant?->id;
    }

    protected function initializeSelection(): void
    {
        if (!$this->product->has_variants) {
            return;
        }

        if ($this->product->variants->count() === 1) {
            $this->selectedVariantId = (int) $this->product->variants->first()->id;
            return;
        }

        if ($this->groupedAttributes->isEmpty()) {
            $this->selectedVariantId = $this->firstAvailableVariant()?->id;
        }
    }

    /**
     * @param  array<string, int|string|null>  $uiSelectedAttributes
     */
    protected function syncUiSelectedAttributes(array $uiSelectedAttributes): void
    {
        if (!$this->product->has_variants || $uiSelectedAttributes === []) {
            return;
        }

        $allowed = [];
        foreach ($this->groupedAttributes as $group) {
            $attrId = (int) ($group['id'] ?? 0);
            if ($attrId < 1) {
                continue;
            }
            $allowed[$attrId] = collect($group['values'])
                ->map(fn ($value): int => (int) $value->id)
                ->values()
                ->all();
        }

        $normalized = [];
        foreach ($uiSelectedAttributes as $attrIdRaw => $valueIdRaw) {
            $attrId = (int) $attrIdRaw;
            $valueId = (int) $valueIdRaw;
            if ($attrId < 1 || $valueId < 1) {
                continue;
            }
            if (!isset($allowed[$attrId])) {
                continue;
            }
            if (!in_array($valueId, $allowed[$attrId], true)) {
                continue;
            }

            $normalized[$attrId] = $valueId;
        }

        $this->selectedAttributes = $normalized;
        $this->resolveVariant();
        $this->message = '';
    }

    protected function firstAvailableVariant(): ?ProductVariant
    {
        return $this->product->variants
            ->first(fn (ProductVariant $variant): bool => $variant->stock > 0)
            ?? $this->product->variants->first();
    }

    public function render()
    {
        return view('livewire.shop.product-detail');
    }
}
