<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1">

    <title>{{ env('APP_NAME') }}</title>

    @yield('scripts', '')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if (env('COOKIE_EXPIRE'))
        <meta name="cookie-expire" content="{{ env('COOKIE_EXPIRE') }}">
    @endif

    <!-- Fonts -->
    <!-- <link rel="stylesheet" type="text/css" href="/vendor/webchat/fonts/fonts.min.css" /> -->
    <link rel="stylesheet" type="text/css" href="/vendor/webchat/fonts/fonts.css" />
    <link rel="stylesheet" type="text/css" href="/vendor/webchat/css/main.css?{{env("CSS_VERSION", "v1")}}">
</head>

<body>

<div id="app">
    <opendialog-chat></opendialog-chat>
</div>

<script>
      window.openDialogSettings = {
        url: "{{ URL::to('/') }}",
        user: {
          custom: {
            selected_scenario: "{{request()->get('selected_scenario')}}"
          }
        },
      };

      if ( "{{ request()->get('key') }}" ) {
        window.openDialogSettings.appKey = "{{  request()->get('key') }}"
      }
</script>

<script src="/vendor/webchat/js/app.js?{{env("JS_VERSION", "v1")}}"></script>

<script src="/vendor/webchat/js/opendialog-bot-full.js?{{env("JS_VERSION", "v1")}}"></script>

</body>

</html>
