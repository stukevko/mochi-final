<div
    class="w-full"
    x-on:mochi-turnstile.window="$wire.set('turnstileToken', $event.detail.token)"
>
    @if ($sent)
        <div
            class="rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-5 py-8 text-center backdrop-blur-md"
            x-data="{ show: false }"
            x-init="setTimeout(() => show = true, 40)"
            x-show="show"
            x-transition:enter="transition ease-out duration-400"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            role="status"
        >
            <p class="font-display text-lg font-extrabold text-emerald-100">Deine Nachricht ist auf dem Weg zum Mochi-Team! 🚀</p>
            <p class="mt-2 text-sm text-emerald-100/80">Wir melden uns per E-Mail bei dir, sobald es passt.</p>
        </div>
    @else
        <form wire:submit="submit" class="relative space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="c-name" class="block text-xs font-semibold uppercase tracking-wide text-mochi-muted">Name</label>
                    <input
                        id="c-name"
                        type="text"
                        wire:model.blur="name"
                        autocomplete="name"
                        class="mochi-input mt-1.5 block w-full rounded-xl px-3 py-2.5 text-sm"
                    >
                    @error('name') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="c-email" class="block text-xs font-semibold uppercase tracking-wide text-mochi-muted">E-Mail</label>
                    <input
                        id="c-email"
                        type="email"
                        wire:model.blur="email"
                        autocomplete="email"
                        class="mochi-input mt-1.5 block w-full rounded-xl px-3 py-2.5 text-sm"
                    >
                    @error('email') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="sr-only" aria-hidden="true">
                <label for="c-company-website">Nicht ausfüllen</label>
                <input id="c-company-website" type="text" wire:model="company_website" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label for="c-subject" class="block text-xs font-semibold uppercase tracking-wide text-mochi-muted">Betreff</label>
                <select
                    id="c-subject"
                    wire:model.blur="subject"
                    class="mochi-input mt-1.5 block w-full rounded-xl px-3 py-2.5 text-sm"
                >
                    @foreach (\App\Enums\ContactSubject::cases() as $sub)
                        <option value="{{ $sub->value }}">{{ $sub->label() }}</option>
                    @endforeach
                </select>
                @error('subject') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="c-message" class="block text-xs font-semibold uppercase tracking-wide text-mochi-muted">Nachricht</label>
                <textarea
                    id="c-message"
                    wire:model.blur="message"
                    rows="6"
                    class="mochi-input mt-1.5 block w-full resize-y rounded-xl px-3 py-2.5 text-sm"
                ></textarea>
                @error('message') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
            </div>

            @if (\App\Services\TurnstileVerifier::siteKey())
                <div
                    wire:ignore
                    class="cf-turnstile flex min-h-[65px] justify-start"
                    data-sitekey="{{ \App\Services\TurnstileVerifier::siteKey() }}"
                    data-callback="mochiTurnstileCallback"
                ></div>
                @error('turnstileToken') <p class="text-xs text-rose-300">{{ $message }}</p> @enderror
            @endif

            <button
                type="submit"
                class="mochi-cta w-full !min-h-12 !text-sm disabled:cursor-not-allowed disabled:opacity-60"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Nachricht senden</span>
                <span wire:loading>Wird gesendet…</span>
            </button>
        </form>
    @endif
</div>
