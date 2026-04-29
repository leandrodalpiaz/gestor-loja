<?php
$erpPageTitle = $erpPageTitle ?? 'Gestor-Loja';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($erpPageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/erp_design_system.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'erp-navy': 'var(--erp-navy)',
                        'erp-gold': 'var(--erp-gold)',
                        'erp-accent': 'var(--erp-accent)',
                        'erp-bg': 'var(--erp-bg)',
                        'erp-surface': 'var(--erp-surface)',
                        'erp-border': 'var(--erp-border)',
                        'erp-text': 'var(--erp-text)',
                        'erp-muted': 'var(--erp-muted)',
                        'erp-success': 'var(--erp-success)',
                        'erp-warning': 'var(--erp-warning)',
                        'erp-danger': 'var(--erp-danger)',
                        'erp-info': 'var(--erp-info)',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
