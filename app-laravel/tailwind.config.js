/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        // Neutral colors (95% de l'UI)
        neutral: {
          50: '#fafafa',
          100: '#f5f5f5',
          200: '#e5e5e5',
          300: '#d4d4d4',
          400: '#a3a3a3',
          500: '#737373',
          600: '#525252',
          700: '#404040',
          900: '#171717',
        },
        // Brand colors (Actions primaires)
        brand: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
        },
        // Success colors (Statut actif)
        success: {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          600: '#16a34a',
          700: '#15803d',
          900: '#14532d',
        },
        // Danger colors (Alertes critiques)
        danger: {
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          600: '#dc2626',
          700: '#b91c1c',
          900: '#7f1d1d',
        },
      },
    },
  },
  plugins: [],
  safelist: [
    // Classes utilisées dynamiquement dans Alpine.js (:class) non détectables par le scanner JIT
    'rotate-90',
    'rotate-180',
    // Classes générées dynamiquement en PHP (ex: $daColor, $spamColor, $tc variables)
    'text-emerald-600', 'text-emerald-700',
    'text-amber-500', 'text-amber-600',
    'text-red-500', 'text-red-600',
    'text-blue-600',
    'text-neutral-400', 'text-neutral-800',
    'bg-red-50', 'bg-emerald-50', 'bg-amber-50', 'bg-blue-50', 'bg-neutral-50', 'bg-purple-50',
    'border-red-200', 'border-emerald-200', 'border-amber-200', 'border-blue-200', 'border-neutral-200', 'border-purple-200',
    'bg-purple-50', 'text-purple-700', 'border-purple-200',
    'text-amber-500',
  ],
}
