module.exports = {
  darkMode: 'class',
  content: [
    './public/**/*.php',
    './src/**/*.php'
  ],
  theme: {
    extend: {
      colors: {
        'erp-navy':      'var(--erp-navy)',
        'erp-navy-deep': 'var(--erp-navy-deep)',
        'erp-gold':      'var(--erp-gold)',
        'erp-gold-subtle':'var(--erp-gold-subtle)',
        'erp-accent':    'var(--erp-accent)',
        'erp-brand':     'var(--erp-brand)',
        'erp-brand-vibrant': 'var(--erp-brand-vibrant)',
        'erp-accent-strong': 'var(--erp-accent-strong)',
        'erp-bg':        'var(--erp-bg)',
        'erp-surface':   'var(--erp-surface)',
        'erp-surface-2': 'var(--erp-surface-2)',
        'erp-border':    'var(--erp-border)',
        'erp-text':      'var(--erp-text)',
        'erp-muted':     'var(--erp-muted)',
        'erp-success':   'var(--erp-success)',
        'erp-warning':   'var(--erp-warning)',
        'erp-danger':    'var(--erp-danger)',
        'erp-info':      'var(--erp-info)',
        'erp-focus':     'var(--erp-focus)',
        /* Aliases for PWA compatibility */
        erpNavy:     '#1B3A5C',
        erpNavyDeep: '#0E2640',
        erpGold:     '#C9A227',
        erpBg:       '#F6F8FB',
        erpSurface:  '#FFFFFF',
        erpSurface2: '#EDF1F7',
        erpBorder:   '#D1DAE6',
        erpText:     '#1A2B3D',
        erpMuted:    '#6B7F94',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
      borderRadius: {
        'card': '12px',
        'input': '8px',
      },
    }
  },
  plugins: []
};
