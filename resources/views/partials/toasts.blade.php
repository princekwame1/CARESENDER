{{-- Global toasts (top right, 5 s). Server flashes are handed to resources/js/modules/toast.js. --}}
@php
    $flashes = [];
    // Error pages (404, 500) render without the session, so guard everything.
    $hasSession = request()->hasSession();
    if ($hasSession && session('status')) {
        $flashes[] = ['type' => 'success', 'message' => session('status')];
    }
    if ($hasSession && session('error')) {
        $flashes[] = ['type' => 'error', 'message' => session('error')];
    }
    if (isset($errors) && $errors->any()) {
        $count = $errors->count();
        $flashes[] = ['type' => 'error', 'message' => $count === 1 ? $errors->first() : $errors->first().' (and '.($count - 1).' more '.Str::plural('problem', $count - 1).' to fix)'];
    }
@endphp
<div id="toasts" class="pointer-events-none fixed right-4 top-4 z-[110] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2.5" aria-live="polite" aria-atomic="false"></div>
<script type="application/json" id="flash-data">@json($flashes)</script>
