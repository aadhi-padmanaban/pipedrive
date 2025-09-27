<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Pipedrive Integration')</title>
    <!-- Bootstrap CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@pipedrive/app-extensions-sdk@0/dist/index.umd.js"></script>
</head>
<body class="p-3">
    <div class="container">
        @yield('content')
    </div>
    <!-- Bootstrap JS (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (async function() {
      const sdk = await new AppExtensionsSDK().initialize();
    })();
  </script>
</body>
</html>
