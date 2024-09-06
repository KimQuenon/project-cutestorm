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
          light: '#FBEC9E',
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
          hover: '#EBDDFF',
        },
        success: {
          DEFAULT: '#32CD32',
        },
        danger: {
          light: '#FF84DC',
          DEFAULT: '#FF00B8',
          dark:'#FF004D',
          hover: '#EB043B',
        },
        highlight: {
          DEFAULT: '#00BFFF',
          dark: '#0075FF',
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
