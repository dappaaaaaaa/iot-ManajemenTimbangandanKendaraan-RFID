<!DOCTYPE html>
<html lang="en">

<head>
    @livewireStyles
</head>

<body>

    @yield('content')

    @livewireScripts
    <script src="{{ asset('js/rfid-poller.js') }}"></script>

</body>

</html>
