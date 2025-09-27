<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'TelconGH' }}</title>
    
    <link rel="icon" type="image/png" href="{{ asset('logo/telcongh_main.png') }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#3B82F6', // Blue
                        'secondary': '#F97316', // Orange
                        'primary-dark': '#1E40AF',
                        'secondary-dark': '#EA580C',
                    }
                }
            }
        }
    </script>
    @livewireStyles
</head>
