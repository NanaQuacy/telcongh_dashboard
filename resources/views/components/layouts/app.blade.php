<x-head :title="$title ?? 'TelconGH'" />

<body class="bg-gray-50 min-h-screen">
    {{ $slot }}
    @livewireScripts
</body>
</html>
