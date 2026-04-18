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
                            text: '#111827',
                            muted: '#3B4A5C'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    },
                    fontSize: {
                        xs: ['0.84rem', { lineHeight: '1.25rem' }],
                        sm: ['0.98rem', { lineHeight: '1.45rem' }],
                        base: ['1.08rem', { lineHeight: '1.65rem' }],
                        lg: ['1.2rem', { lineHeight: '1.75rem' }],
                        xl: ['1.34rem', { lineHeight: '1.9rem' }],
                        '2xl': ['1.62rem', { lineHeight: '2.1rem' }],
                        '3xl': ['2rem', { lineHeight: '2.4rem' }],
                        '4xl': ['2.36rem', { lineHeight: '2.7rem' }],
                        '5xl': ['3rem', { lineHeight: '1' }],
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
            font-size: 17px !important;
        }

        @media (min-width: 1536px) {
            html {
                font-size: 18px !important;
            }
        }

        body {
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.08rem;
            }

            .erp-readable .text-xs {
                font-size: 0.9rem !important;
                line-height: 1.35rem !important;
            }

            .erp-readable .text-sm {
                font-size: 1.02rem !important;
                line-height: 1.55rem !important;
            }

            .erp-readable .text-base {
                font-size: 1.12rem !important;
                line-height: 1.7rem !important;
            }

            .erp-readable .text-\[11px\] {
                font-size: 0.9rem !important;
                line-height: 1.35rem !important;
            }
        }

        @media (min-width: 1800px) {
            .erp-readable {
                font-size: 1.16rem;
            }

            .erp-readable .text-xs {
                font-size: 0.96rem !important;
                line-height: 1.45rem !important;
            }

            .erp-readable .text-sm {
                font-size: 1.08rem !important;
                line-height: 1.65rem !important;
            }

            .erp-readable .text-\[11px\] {
                font-size: 0.98rem !important;
                line-height: 1.5rem !important;
            }
        }
    </style>
</head>
