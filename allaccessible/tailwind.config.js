/** @type {import('tailwindcss').Config} */
module.exports = {
  // Prefix all classes with 'aacx-' to avoid conflicts with WordPress and other plugins
  prefix: 'aacx-',

  // Scope all styles to .allaccessible-admin wrapper
  important: '.allaccessible-admin',

  // Scan these files for Tailwind classes
  content: [
    './inc/**/*.php',
    './src/**/*.js',
    './src/**/*.css',
  ],

  theme: {
    extend: {
      colors: {
        // AllAccessible brand colors (updated 2025 - matching www.allaccessible.org)
        'aacx-primary': {
          DEFAULT: '#1d4ed8', // Primary blue from new website
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#1d4ed8', // Main primary color
          700: '#1e3a8a', // Hover state
          800: '#1e40af',
          900: '#1e3a8a',
        },
        'aacx-secondary': {
          DEFAULT: '#16a34a', // Green from new website
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a', // Main secondary color
          700: '#15803d', // Hover state
          800: '#166534',
          900: '#14532d',
        },
        'aacx-cyan': {
          DEFAULT: '#0891b2', // Cyan/teal accent from new website
          50: '#ecfeff',
          100: '#cffafe',
          200: '#a5f3fc',
          300: '#67e8f9',
          400: '#22d3ee',
          500: '#06b6d4',
          600: '#0891b2',
          700: '#0e7490',
          800: '#155e75',
          900: '#164e63',
        },
        'aacx-slate': {
          DEFAULT: '#1e293b', // Dark slate from new website
          50: '#f8fafc',
          100: '#f1f5f9',
          200: '#e2e8f0',
          300: '#cbd5e1',
          400: '#94a3b8',
          500: '#64748b',
          600: '#475569',
          700: '#334155',
          800: '#1e293b',
          900: '#0f172a', // Darkest slate
        },
        'aacx-success': {
          DEFAULT: '#16a34a', // Green (matching secondary)
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
        },
        'aacx-warning': {
          DEFAULT: '#f59e0b',
          50: '#fef3e7',
          100: '#fde0c2',
          200: '#fbcd9d',
          300: '#f9ba78',
          400: '#f7a753',
          500: '#f59e0b',
          600: '#d98809',
          700: '#bd7207',
          800: '#a15c05',
          900: '#854603',
        },
        'aacx-danger': {
          DEFAULT: '#ef4444',
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#ef4444',
          600: '#dc2626',
          700: '#b91c1c',
          800: '#991b1b',
          900: '#7f1d1d',
        },
        'aacx-gray': {
          50: '#f9fafb',
          100: '#f3f4f6',
          200: '#e5e7eb',
          300: '#d1d5db',
          400: '#9ca3af',
          500: '#6b7280',
          600: '#4b5563',
          700: '#374151',
          800: '#1f2937',
          900: '#111827',
        },
      },
      fontFamily: {
        sans: [
          '-apple-system',
          'BlinkMacSystemFont',
          '"Segoe UI"',
          'Roboto',
          '"Helvetica Neue"',
          'Arial',
          'sans-serif',
        ],
      },
      boxShadow: {
        'aacx-sm': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
        'aacx': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
        'aacx-md': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
        'aacx-lg': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        'aacx-xl': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
      },
      borderRadius: {
        'aacx-sm': '0.25rem',
        'aacx': '0.375rem',
        'aacx-md': '0.5rem',
        'aacx-lg': '0.75rem',
        'aacx-xl': '1rem',
      },
    },
  },

  corePlugins: {
    // CRITICAL: Disable Preflight to avoid resetting WordPress admin styles
    preflight: false,
  },

  plugins: [],
}
