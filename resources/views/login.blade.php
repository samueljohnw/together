<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f7f7f4">
        <link rel="manifest" href="/manifest.webmanifest">
        <title>Together — Sign in</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-[#f7f7f4] px-5 font-sans text-[#252923] antialiased">
        <main class="w-full max-w-[400px] rounded-2xl border border-[#e8e9e3] bg-white p-7 shadow-[0_2px_10px_rgba(34,40,30,0.025)] sm:p-8">
            <a href="{{ route('login') }}" class="text-[24px] font-semibold tracking-[-0.06em] text-[#53684d]">together</a>
            <h1 class="mt-7 text-[22px] font-semibold tracking-[-0.04em]">Sign in</h1>
            <p class="mt-2 text-[14px] leading-relaxed text-[#858b80]">Open your shared household space.</p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-[13px] font-medium text-[#545a51]">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="mt-1.5 h-11 w-full rounded-xl border border-[#e3e6de] px-3.5 text-[14px] outline-none focus:border-[#9eaf95] focus:ring-2 focus:ring-[#e6ece2]">
                </div>
                <div>
                    <label for="password" class="block text-[13px] font-medium text-[#545a51]">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="mt-1.5 h-11 w-full rounded-xl border border-[#e3e6de] px-3.5 text-[14px] outline-none focus:border-[#9eaf95] focus:ring-2 focus:ring-[#e6ece2]">
                </div>
                @error('email')
                    <p class="text-[13px] text-[#a8564e]">{{ $message }}</p>
                @enderror
                <label class="flex items-center gap-2 text-[13px] text-[#697463]">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-[#d7dbd2] accent-[#53684d]">
                    Keep me signed in on this device
                </label>
                <button type="submit" class="h-11 w-full rounded-xl bg-[#53684d] text-[14px] font-medium text-white transition hover:bg-[#455a40]">Sign in</button>
            </form>
        </main>
    </body>
</html>
