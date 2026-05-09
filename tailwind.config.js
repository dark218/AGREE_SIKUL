/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.{js,jsx,ts,tsx,vue}",
    "./resources/**/*.blade.php",
  ],
  theme: {
    extend: {
      colors: {
        // SIKUL Brand Colors
        sikul: {
          primary: '#0B5697',      // Deep Navy Blue
          secondary: '#E5590C',    // Vibrant Orange
          accent: '#0FBCAF',       // Modern Teal
          light: '#F5F7FA',        // Light background
          dark: '#1a1f2e',         // Dark background
          gray: '#CCCCCC',         // Neutral gray
          success: '#0FBCAF',      // Turquoise for success
          warning: '#E5590C',      // Orange for warning
          error: '#DC3545',        // Red for errors
          info: '#0B5697',         // Blue for info
        }
      },
      fontFamily: {
        sans: ['Outfit', 'system-ui', 'sans-serif'],
        display: ['Poppins', 'system-ui', 'sans-serif'],
      },
      fontSize: {
        xs: ['12px', '16px'],
        sm: ['14px', '20px'],
        base: ['16px', '24px'],
        lg: ['18px', '28px'],
        xl: ['20px', '28px'],
        '2xl': ['24px', '32px'],
        '3xl': ['30px', '36px'],
        '4xl': ['36px', '44px'],
      },
      boxShadow: {
        xs: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
        sm: '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
        md: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
        lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
        xl: '0 20px 25px -5px rgba(0, 0, 0, 0.1)',
        premium: '0 10px 30px rgba(11, 86, 151, 0.2)',
        sikul: '0 8px 24px rgba(11, 86, 151, 0.15)',
      },
      borderRadius: {
        none: '0',
        xs: '4px',
        sm: '6px',
        md: '8px',
        lg: '12px',
        xl: '16px',
        '2xl': '24px',
        full: '9999px',
      },
      animation: {
        fadeIn: 'fadeIn 0.6s ease-in-out',
        slideUp: 'slideUp 0.6s ease-out',
        slideDown: 'slideDown 0.6s ease-out',
        pulse: 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        shimmer: 'shimmer 2s infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(20px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        slideDown: {
          '0%': { transform: 'translateY(-20px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        shimmer: {
          '0%': { backgroundPosition: '-1000px 0' },
          '100%': { backgroundPosition: '1000px 0' },
        },
      },
      backgroundImage: {
        'gradient-sikul': 'linear-gradient(135deg, #0B5697 0%, #0FBCAF 100%)',
        'gradient-sikul-reverse': 'linear-gradient(135deg, #E5590C 0%, #0B5697 100%)',
        'gradient-warm': 'linear-gradient(135deg, #E5590C 0%, #F97316 100%)',
      },
      spacing: {
        gutter: '1rem',
      },
    },
  },
  plugins: [],
}
