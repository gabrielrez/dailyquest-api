<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Removido da coleção</title>
</head>

<body>
    <h2>Você foi removido da coleção: {{ $collection->name }}</h2>
    <p>
        Caso ache que foi um engano, entre em contato com o dono da coleção
        ({{ $collection->owner->email ?? 'administrador' }}).
    </p>
</body>

</html>