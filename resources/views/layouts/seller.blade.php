<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Toko Saya') – SITERNAK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800">
<nav class="bg-green-700 text-white">
    <div class="max-w-6xl mx-auto px-4 py-3 text-sm">
        <span class="font-bold">SITERNAK Seller</span>
    </div>
</nav>
<main class="max-w-6xl mx-auto px-4 py-6">
    @if (session('success'))<div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="mb-4 rounded bg-red-100 text-red-800 px-4 py-2">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="mb-4 rounded bg-red-50 text-red-700 px-4 py-2 text-sm">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>