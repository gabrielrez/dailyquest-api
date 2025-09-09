<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Convite</title>
</head>

<body>
    <h2>Você foi convidado para a coleção: {{ $collection->name }}</h2>
    <p>
        @if($is_new_user)
        Você ainda não possui conta. Clique no link abaixo para se registrar e já entrar na coleção:
        @else
        Clique no link abaixo para aceitar o convite e entrar na coleção:
        @endif
    </p>
    <p>
        <a href="{{ $url }}">{{ $url }}</a>
    </p>
</body>

</html>