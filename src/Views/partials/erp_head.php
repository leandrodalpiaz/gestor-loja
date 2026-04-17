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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        erp: {
                            navy: '#1E3A5F',
                            gold: '#B8960C',
                            app: '#F7F8FA',
                            card: '#FFFFFF',
                            success: '#15803d',
                            warning: '#b45309',
                            danger: '#b91c1c',
                            border: '#D9DEE7',
                            text: '#1F2937',
                            muted: '#64748B'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    },
                    boxShadow: {
                        erp: '0 18px 45px rgba(30, 58, 95, 0.08)'
                    },
                    borderRadius: {
                        'erp-xl': '24px',
                        'erp-lg': '18px',
                        'erp-md': '14px'
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html {
            font-size: 17px;
        }

        @media (min-width: 1536px) {
            html {
                font-size: 18px;
            }
        }

        body {
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }
    </style>
</head>
