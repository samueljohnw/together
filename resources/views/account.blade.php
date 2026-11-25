<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f7f7f4">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="push-public-key" content="{{ config('services.together.vapid_public_key') }}">
        <link rel="manifest" href="/manifest.webmanifest">

        <title>Together — Account</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f7f4] font-sans text-[#252923] antialiased">
        <script type="application/json" data-initial-closed-items>@json($closedItems)</script>
        <div class="mx-auto min-h-screen max-w-[1440px]">
            <header class="mx-auto flex h-[68px] max-w-[1180px] items-center justify-between border-b border-[#e8e9e3] px-5 sm:px-8 lg:px-10">
                <a href="{{ route('home') }}" class="text-[21px] font-semibold tracking-[-0.06em] text-[#53684d]" aria-label="Together home">together</a>
                <div class="flex items-center gap-2">
                    <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 text-[14px] font-medium text-[#697463] transition hover:bg-white hover:text-[#53684d]">Back to home</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-[14px] font-medium text-[#92988d] transition hover:bg-white hover:text-[#53684d]">Sign out</button>
                    </form>
                </div>
            </header>

            <main class="mx-auto max-w-[1180px] px-5 pb-10 pt-8 sm:px-8 sm:pt-10 lg:px-10">
                <div class="mb-7">
                    <h1 class="text-[30px] font-semibold tracking-[-0.05em]">Account</h1>
                    <p class="mt-1.5 text-[15px] text-[#858b80]">Your household and the things you’ve finished together.</p>
                </div>

                <nav class="flex gap-1 border-b border-[#e5e8e0]" role="tablist" aria-label="Account sections">
                    <button type="button" id="closed-items-tab" data-account-tab="closed-items" role="tab" aria-selected="true" aria-controls="closed-items-panel" class="border-b-2 border-[#6f8f62] px-4 py-3 text-[14px] font-medium text-[#53684d]">Closed Items</button>
                    <button type="button" id="members-tab" data-account-tab="members" role="tab" aria-selected="false" aria-controls="members-panel" class="border-b-2 border-transparent px-4 py-3 text-[14px] font-medium text-[#92988d] hover:text-[#53684d]">Members</button>
                </nav>

                <div data-closed-search-container class="relative mt-5">
                    <label for="closed-items-search" class="sr-only">Search closed items</label>
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#a0a49b]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6"/><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <input id="closed-items-search" data-closed-items-search type="search" autocomplete="off" placeholder="Search closed threads and to-dos…" class="h-11 w-full rounded-xl border border-[#e3e6de] bg-white pl-10 pr-4 text-[14px] text-[#343a32] shadow-sm shadow-black/[0.02] outline-none placeholder:text-[#a0a49b] focus:border-[#9eaf95] focus:ring-2 focus:ring-[#e6ece2]">
                </div>

                <div class="mt-5">
                    <section id="members-panel" data-account-panel="members" role="tabpanel" aria-labelledby="members-tab" hidden class="overflow-hidden rounded-2xl border border-[#e8e9e3] bg-white shadow-[0_2px_10px_rgba(34,40,30,0.025)]">
                        <div class="border-b border-[#eff0eb] px-5 py-4 sm:px-6">
                            <h2 class="text-[17px] font-semibold tracking-[-0.02em]">Members</h2>
                            <p class="mt-1 text-[13px] text-[#a0a49b]">People in your shared space</p>
                        </div>
                        <div class="flex flex-col gap-3 border-b border-[#eff0eb] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <p class="text-[14px] font-medium text-[#343a32]">Push notifications</p>
                                <p data-push-status class="mt-1 text-[12px] text-[#959b91]">Get an alert on this device when someone else adds or replies.</p>
                            </div>
                            <button type="button" data-push-toggle class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-[#dfe4da] bg-white px-3.5 text-[13px] font-medium text-[#53684d] transition hover:bg-[#f5f7f2]">Enable notifications</button>
                        </div>
                        <div class="divide-y divide-[#f0f1ec]">
                            @foreach ($members as $member)
                                <div class="flex items-center gap-3.5 px-5 py-5 sm:px-6">
                                    <span @class([
                                        'flex h-10 w-10 items-center justify-center rounded-full text-[13px] font-semibold',
                                        'bg-[#e5d1bf] text-[#715139]' => $member->is(auth()->user()),
                                        'bg-[#e5d9e8] text-[#694e70]' => ! $member->is(auth()->user()),
                                    ])>{{ $member->initials() }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[14px] font-medium text-[#343a32]">{{ $member->name }}@if ($member->is(auth()->user())) <span class="text-[#959b91]">(you)</span>@endif</p>
                                        <p class="mt-0.5 text-[12px] text-[#959b91]">{{ $member->email }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section id="closed-items-panel" data-account-panel="closed-items" role="tabpanel" aria-labelledby="closed-items-tab" class="overflow-hidden rounded-2xl border border-[#e8e9e3] bg-white shadow-[0_2px_10px_rgba(34,40,30,0.025)]">
                        <div class="border-b border-[#eff0eb] px-5 py-4 sm:px-6">
                            <div class="flex items-center gap-2.5">
                                <h2 class="text-[17px] font-semibold tracking-[-0.02em]">Closed Threads and To-Dos</h2>
                                <span data-closed-count class="rounded-full bg-[#f1f3ee] px-2 py-0.5 text-[12px] font-medium text-[#75806e]">{{ count($closedItems) }}</span>
                            </div>
                            <p class="mt-1 text-[13px] text-[#a0a49b]">Threads and to-dos you’ve closed together</p>
                        </div>
                        <div data-closed-list class="divide-y divide-[#f0f1ec]">
                            <p data-closed-empty hidden class="px-5 py-5 text-[13px] text-[#858b80] sm:px-6">No closed items match your search.</p>
                        </div>
                    </section>
                </div>

                <div data-closed-detail hidden class="fixed inset-0 z-50 items-center justify-center bg-[#252923]/25 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" aria-labelledby="closed-detail-title">
                    <section class="w-full max-w-[620px] overflow-hidden rounded-2xl border border-[#e8e9e3] bg-white shadow-2xl">
                        <header class="flex items-start justify-between border-b border-[#eff0eb] px-5 py-4 sm:px-6">
                            <div>
                                <p data-closed-detail-kind class="text-[12px] font-medium uppercase tracking-[0.1em] text-[#92988d]"></p>
                                <h2 id="closed-detail-title" data-closed-detail-title class="mt-1 text-[20px] font-semibold tracking-[-0.04em] text-[#343a32]"></h2>
                                <p data-closed-detail-assignee class="mt-1 text-[12px] text-[#92988d]"></p>
                            </div>
                            <button type="button" data-closed-detail-close class="flex h-8 w-8 items-center justify-center rounded-lg text-[#92988d] hover:bg-[#f5f6f2]" aria-label="Close item details">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            </button>
                        </header>
                        <div class="px-5 py-5 sm:px-6">
                            <div>
                                <h3 data-closed-detail-history-label class="text-[13px] font-semibold text-[#545a51]">Conversation</h3>
                                <div data-closed-detail-history class="mt-3 max-h-[48vh] space-y-4 overflow-y-auto pr-1" aria-live="polite"></div>
                            </div>
                            <p data-closed-error hidden role="alert" class="mt-4 text-[12px] text-[#a8564e]"></p>
                            <button type="button" data-closed-reopen class="mt-5 inline-flex h-9 items-center rounded-lg bg-[#53684d] px-4 text-[13px] font-medium text-white hover:bg-[#455a40]">Re-Open</button>
                        </div>
                    </section>
                </div>
            </main>
        </div>
        <x-media-viewer />
    </body>
</html>
