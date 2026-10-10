<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('invitation.ended_title', [], $lang) }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; text-align: center;
               background: #5b0f1f; color: #fff4dc; font-family: 'Noto Sans Tamil', 'Nirmala UI', 'Latha', system-ui, sans-serif; line-height: 1.6; padding: 24px; box-sizing: border-box; }
        .mark { font-size: 3rem; color: #e9b949; }
        h1 { font-size: 1.4rem; margin: .5rem 0; }
        p { margin: 0; opacity: .8; }
    </style>
</head>
<body>
    <div>
        <div class="mark" aria-hidden="true">உ</div>
        @foreach ($languages as $l)
            <h1 lang="{{ $l }}">{{ __('invitation.ended_title', [], $l) }}</h1>
        @endforeach
        @foreach ($languages as $l)
            <p lang="{{ $l }}">{{ __('invitation.ended_body', [], $l) }}</p>
        @endforeach
    </div>
</body>
</html>
