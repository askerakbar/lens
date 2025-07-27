/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'telescope-bg': '#f8fafc',
        'telescope-card': '#ffffff',
        'telescope-border': '#e2e8f0',
        'telescope-text': '#1a202c',
        'telescope-success': '#48bb78',
        'telescope-error': '#f56565',
        'telescope-code-bg': '#2d3748',
        'telescope-code-text': '#e2e8f0',
      },
      fontFamily: {
        mono: ['Monaco', 'Menlo', 'Consolas', 'monospace'],
      },
    },
  },
  plugins: [],
}