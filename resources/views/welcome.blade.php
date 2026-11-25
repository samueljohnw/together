<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f7f7f4">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="push-public-key" content="{{ config('services.together.vapid_public_key') }}">
        <link rel="manifest" href="/manifest.webmanifest">

        <title>Together — Home</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f7f4] font-sans text-[#252923] antialiased">
        <script type="application/json" data-initial-items>@json($openItems)</script>
        <div class="mx-auto min-h-screen max-w-[1440px]">
            <main class="min-w-0 flex-1">
                <header class="mx-auto flex h-[68px] max-w-[1180px] items-center justify-between border-b border-[#e8e9e3] px-5 sm:px-8 lg:px-10">
                    <a href="{{ route('home') }}" class="text-[21px] font-semibold tracking-[-0.06em] text-[#53684d]" aria-label="Together home">together</a>
                    <a href="{{ route('account') }}" class="rounded-lg px-3 py-2 text-[14px] font-medium text-[#697463] transition hover:bg-white hover:text-[#53684d]">Account</a>
                </header>

                <div class="mx-auto max-w-[1180px] px-5 pb-10 pt-8 sm:px-8 sm:pt-10 lg:px-10">
                    <div data-search-container class="relative z-10 mb-5">
                        <label for="workspace-search" class="sr-only">Search threads and to-dos</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#a0a49b]" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.6"/><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            <input id="workspace-search" data-workspace-search type="search" autocomplete="off" placeholder="Search threads and to-dos…" class="h-11 w-full rounded-xl border border-[#e3e6de] bg-white pl-10 pr-4 text-[14px] text-[#343a32] shadow-sm shadow-black/[0.02] outline-none placeholder:text-[#a0a49b] focus:border-[#9eaf95] focus:ring-2 focus:ring-[#e6ece2]">
                        </div>
                        <div data-search-results hidden class="absolute left-0 right-0 top-full mt-2 max-h-[360px] overflow-y-auto rounded-xl border border-[#e5e8e0] bg-white p-2 shadow-xl shadow-black/[0.08]" aria-label="Search results"></div>
                    </div>

                    <div class="grid items-stretch gap-6 xl:grid-cols-2">
                        <section id="threads" class="flex h-full flex-col overflow-hidden rounded-2xl border border-[#e8e9e3] bg-white shadow-[0_2px_10px_rgba(34,40,30,0.025)]">
                            <div class="flex items-center justify-between border-b border-[#eff0eb] px-5 py-4">
                                <button type="button" data-mobile-accordion-toggle aria-controls="threads-content" aria-expanded="false" class="flex w-full items-center justify-between text-left md:cursor-default">
                                    <span class="flex items-center gap-2.5">
                                        <h3 class="text-[17px] font-semibold tracking-[-0.02em]">Threads</h3>
                                    </span>
                                    <svg class="h-4 w-4 text-[#858b81] transition-transform md:hidden" data-mobile-accordion-chevron viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>

                            <div id="threads-content" data-mobile-accordion-content class="hidden md:flex md:flex-1 md:flex-col">
                            <div class="border-b border-[#eff0eb] p-4">
                                <form data-new-thread-form class="flex items-center justify-between gap-2 rounded-lg border border-[#e7e9e2] px-3 py-2.5">
                                    <label for="new-thread-title" class="sr-only">New thread title</label>
                                    <input id="new-thread-title" name="title" type="text" maxlength="80" required placeholder="Start a new thread…" class="min-w-0 flex-1 bg-transparent text-[13px] text-[#343a32] outline-none placeholder:text-[#b1b6ad]">
                                    <button type="submit" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#53684d] text-white" aria-label="Create thread">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4.5 12 15-7-4.5 14-3.1-5.2L4.5 12Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m11.9 13.8 3.4-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                    </button>
                                </form>
                            </div>

                            <div data-thread-list class="flex-1 divide-y divide-[#f0f1ec]" role="list" aria-label="Conversation threads">
                                <p data-thread-empty hidden class="px-5 py-8 text-center text-[13px] text-[#969b91]">No open threads. Start one above.</p>
                            </div>

                            </div>
                        </section>

                        <section id="open-items" class="h-full overflow-hidden rounded-2xl border border-[#e8e9e3] bg-white shadow-[0_2px_10px_rgba(34,40,30,0.025)]">
                            <div class="flex items-center justify-between border-b border-[#eff0eb] px-5 py-4 sm:px-6">
                                <button type="button" data-mobile-accordion-toggle aria-controls="todos-content" aria-expanded="false" class="flex w-full items-center justify-between text-left md:cursor-default">
                                    <span class="flex items-center gap-2.5">
                                        <h3 class="text-[17px] font-semibold tracking-[-0.02em]">To-Do</h3>
                                    </span>
                                    <svg class="h-4 w-4 text-[#858b81] transition-transform md:hidden" data-mobile-accordion-chevron viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>

                            <div id="todos-content" data-mobile-accordion-content class="hidden md:block">
                            <form data-new-todo-form class="border-b border-[#eff0eb] p-4 sm:px-5">
                                <label for="new-todo-title" class="sr-only">New to-do title</label>
                                <div class="flex items-center justify-between gap-2 rounded-lg border border-[#e7e9e2] px-3 py-2.5">
                                    <input id="new-todo-title" name="title" type="text" maxlength="120" required placeholder="Add a to-do…" class="min-w-0 flex-1 bg-transparent text-[13px] text-[#343a32] outline-none placeholder:text-[#b1b6ad]">
                                    <button type="submit" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#53684d] text-white" aria-label="Add to-do">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4.5 12 15-7-4.5 14-3.1-5.2L4.5 12Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m11.9 13.8 3.4-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                    </button>
                                </div>
                            </form>

                            <div data-todo-list class="divide-y divide-[#f0f1ec]" role="list" aria-label="Open to-do items">
                                <p data-todo-empty hidden class="px-5 py-8 text-center text-[13px] text-[#969b91]">Nothing to do right now.</p>
                            </div>
                            </div>

                            <div data-todo-detail hidden class="fixed inset-0 z-40 items-center justify-center bg-[#252923]/25 p-4 backdrop-blur-[2px]">
                                <section class="flex max-h-[min(820px,92vh)] w-full max-w-[720px] flex-col overflow-hidden rounded-2xl border border-[#e8e9e3] bg-white shadow-2xl">
                                <div class="flex items-start justify-between border-b border-[#eff0eb] px-5 py-4 sm:px-6">
                                    <div class="min-w-0">
                                        <p class="text-[12px] font-medium uppercase tracking-[0.1em] text-[#92988d]">TODO</p>
                                        <h3 data-detail-title class="mt-1 text-[20px] font-semibold tracking-[-0.04em] text-[#343a32]"></h3>
                                        <button type="button" data-todo-close-item class="mt-3 inline-flex h-8 items-center rounded-lg border border-[#e3e5dd] bg-white px-3 text-[12px] font-medium text-[#697463] hover:bg-[#f5f6f2]">
                                            Close
                                        </button>
                                    </div>
                                    <button type="button" data-todo-close class="flex h-8 w-8 items-center justify-center rounded-lg text-[#92988d] hover:bg-[#f5f6f2]" aria-label="Close to-do details">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                    </button>
                                </div>

                                <div class="flex flex-1 flex-col px-5 py-5 sm:px-6">
                                    <div data-detail-messages aria-live="polite" class="min-h-[300px] flex-1 space-y-4 overflow-y-auto"></div>
                                    <form data-detail-form class="mt-6 rounded-xl border border-[#e7e9e2] bg-white p-2.5 shadow-sm shadow-black/[0.02]">
                                        <label for="todo-message" class="sr-only">Write a message about this to-do</label>
                                        <div class="flex items-center gap-2">
                                            <input data-todo-file-input type="file" multiple class="sr-only" aria-label="Choose files to attach to this to-do">
                                            <button type="button" data-todo-attach class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[#92988d] hover:bg-[#f5f6f2] hover:text-[#53684d]" aria-label="Attach files">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m8.5 12.5 6.7-6.7a3 3 0 0 1 4.2 4.2l-8.9 8.9a5 5 0 0 1-7.1-7.1l8.5-8.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                            <input id="todo-message" name="message" type="text" autocomplete="off" placeholder="Write a message about this to-do…" class="min-w-0 flex-1 bg-transparent px-1 text-[14px] text-[#343a32] outline-none placeholder:text-[#b1b6ad]">
                                            <button type="submit" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#53684d] text-white hover:bg-[#455a40]" aria-label="Send message">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4.5 12 15-7-4.5 14-3.1-5.2L4.5 12Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m11.9 13.8 3.4-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                            </button>
                                        </div>
                                    </form>
                                    <p data-detail-error hidden role="alert" class="mt-2 text-[12px] text-[#a8564e]"></p>
                                </div>
                                </section>
                            </div>


                        </section>
                    </div>
                    <p class="mt-5 text-center text-[12px] text-[#b1b5ac]">Threads stay close. To-dos stay easy to find.</p>

                    <div data-thread-window hidden class="fixed inset-0 z-50 items-center justify-center bg-[#252923]/25 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" aria-labelledby="thread-window-title">
                        <section class="flex max-h-[min(820px,92vh)] w-full max-w-[720px] flex-col overflow-hidden rounded-2xl border border-[#e8e9e3] bg-white shadow-2xl">
                            <header class="flex items-start justify-between border-b border-[#eff0eb] px-5 py-4 sm:px-6">
                                <div>
                                    <p class="text-[12px] font-medium uppercase tracking-[0.1em] text-[#92988d]">Chat</p>
                                    <h2 id="thread-window-title" data-thread-title class="mt-1 text-[20px] font-semibold tracking-[-0.04em] text-[#343a32]"></h2>
                                    <button type="button" data-thread-close-item class="mt-3 inline-flex h-8 items-center rounded-lg border border-[#e3e5dd] bg-white px-3 text-[12px] font-medium text-[#697463] hover:bg-[#f5f6f2]">Close</button>
                                </div>
                                <button type="button" data-thread-close class="flex h-8 w-8 items-center justify-center rounded-lg text-[#92988d] hover:bg-[#f5f6f2]" aria-label="Close chat">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                </button>
                            </header>
                            <div data-thread-messages aria-live="polite" class="min-h-[420px] flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6"></div>
                            <form data-thread-form class="border-t border-[#eff0eb] p-4 sm:px-5">
                                <label for="thread-message" class="sr-only">Write a reply in this thread</label>
                                <div class="flex items-center gap-2 rounded-xl border border-[#e7e9e2] bg-white p-2.5 shadow-sm shadow-black/[0.02]">
                                    <input data-thread-file-input type="file" multiple class="sr-only" aria-label="Choose files to attach">
                                    <button type="button" data-thread-attach class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[#92988d] hover:bg-[#f5f6f2] hover:text-[#53684d]" aria-label="Attach files">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m8.5 12.5 6.7-6.7a3 3 0 0 1 4.2 4.2l-8.9 8.9a5 5 0 0 1-7.1-7.1l8.5-8.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <input id="thread-message" name="message" type="text" autocomplete="off" placeholder="Reply in this thread…" class="min-w-0 flex-1 bg-transparent px-1 text-[14px] text-[#343a32] outline-none placeholder:text-[#b1b6ad]">
                                    <button type="submit" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#53684d] text-white hover:bg-[#455a40]" aria-label="Send reply">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4.5 12 15-7-4.5 14-3.1-5.2L4.5 12Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m11.9 13.8 3.4-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                                    </button>
                                </div>
                                <p data-thread-error hidden role="alert" class="mt-2 text-[12px] text-[#a8564e]"></p>
                            </form>
                        </section>
                    </div>
                </div>
            </main>
        </div>
        <div data-confirm-dialog hidden class="fixed inset-0 z-[55] items-center justify-center bg-[#252923]/30 p-4 backdrop-blur-[2px]" role="alertdialog" aria-modal="true" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message">
            <section class="w-full max-w-[400px] overflow-hidden rounded-2xl border border-[#e8e9e3] bg-white p-6 shadow-2xl">
                <h2 id="confirm-dialog-title" data-confirm-title class="text-[18px] font-semibold tracking-[-0.03em] text-[#343a32]"></h2>
                <p id="confirm-dialog-message" data-confirm-message class="mt-2 text-[14px] leading-relaxed text-[#747b71]"></p>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" data-confirm-cancel class="inline-flex h-9 items-center rounded-lg border border-[#e3e5dd] bg-white px-4 text-[13px] font-medium text-[#697463] hover:bg-[#f5f6f2]">Cancel</button>
                    <button type="button" data-confirm-accept class="inline-flex h-9 items-center rounded-lg bg-[#53684d] px-4 text-[13px] font-medium text-white hover:bg-[#455a40]"></button>
                </div>
            </section>
        </div>
        <x-media-viewer />
    </body>
</html>
