<div>
    @isset($jsPath)
        <script>{!! file_get_contents($jsPath) !!}</script>
    @endisset
    @isset($cssPath)
        <style>{!! file_get_contents($cssPath) !!}</style>
    @endisset

    <div
            x-data="LivewireUIModal()"
            x-on:close.stop="setShowPropertyTo(false)"
            x-on:keydown.escape.window="show && closeModalOnEscape()"
            x-show="show"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
    >
        <div class="flex min-h-dvh items-center justify-center p-4">
            <div
                    x-show="show"
                    x-on:click="closeModalOnClickAway()"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 backdrop-blur-none"
                    x-transition:enter-end="opacity-100 backdrop-blur-sm"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 backdrop-blur-sm"
                    x-transition:leave-end="opacity-0 backdrop-blur-none"
                    class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-[opacity,backdrop-filter]"
            >
            </div>

            <div
                    x-show="show && showActiveComponent"
                    x-transition:enter="ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    x-bind:class="modalWidth"
                    class="relative w-full max-w-xl rounded-xl bg-white shadow-2xl ring-1 ring-neutral-200 transition-all dark:bg-neutral-900 dark:ring-neutral-800"
                    id="modal-container"
                    x-trap.noscroll.inert="show && showActiveComponent"
                    aria-modal="true"
            >
                @forelse($components as $id => $component)
                    <div x-show.immediate="activeComponent == '{{ $id }}'" x-ref="{{ $id }}" wire:key="{{ $id }}">
                        @livewire($component['name'], $component['arguments'], key($id))
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>
</div>
