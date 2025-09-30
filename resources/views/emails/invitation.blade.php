<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Convite</title>
</head>

<body>
    <h2>🎉 You have been invited to the collection: {{ $collection->name }}</h2>
    <p>
        @if($is_new_user)
        You don't have an account yet. Click the link below to register and join the collection:
        @else
        Click the link below to accept the invitation and join the collection:
        @endif
    </p>
    <p>
        <a href="{{ $url }}">{{ $url }}</a>
    </p>
</body>

</html>