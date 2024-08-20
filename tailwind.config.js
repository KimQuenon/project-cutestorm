/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/**/*.html.twig',
    './assets/**/*.js',  
    './assets/**/*.scss',  
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#E189FF',
          dark: '#BD00FF',
          hover: '#A005D6',
        },
        secondary: {
          DEFAULT: '#FFD600',
          dark: '#FFA500',
          hover: '#E8C305'
        },
        tertiary: {
          light: '#CEC8D5',
          DEFAULT: '#888888',
          dark: '#474747',
        },
        background: {
          DEFAULT: '#F3EBFF',
          hover: '#E8DCF9',
        },
        success: {
          DEFAULT: '#32CD32',
        },
        danger: {
          DEFAULT: '#FF00B8',
        },
        highlight: {
          DEFAULT: '#00BFFF',
        },
        white: {
          DEFAULT: '#FBF9FF',
        },
      },
      fontFamily: {
        sans: ['Quicksand', 'sans-serif'],
        gliker: ['Gliker Black Condensed', 'sans-serif'],
      },
    },
  },
  fontFace: [
    {
      fontFamily: 'Gliker Black Condensed',
      src: 'url(../../public/fonts/Gliker-BlackCondensed.ttf)',
    },
  ],
  plugins: [],
}
