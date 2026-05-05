<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mestre de Harmonia - Player Ritual</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/erp_design_system.css">
    <link rel="stylesheet" href="/assets/css/mestre_harmonia_player.css">
</head>
<body>
    <?php echo $content; ?>
    <script>
        window.harmoniaPayload = <?php echo json_encode($payload ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.harmoniaSelectedPath = <?php echo json_encode($selectedSessionPath ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_QUOT); ?>;
        window.harmoniaOperator = <?php echo json_encode($operadorEmExercicio ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_QUOT); ?>;
    </script>
    <script src="/assets/js/mestre_harmonia_player.js"></script>
</body>
</html>
