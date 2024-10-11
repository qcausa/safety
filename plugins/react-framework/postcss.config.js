// postcss.config.js
module.exports = {
  plugins: {
    "@tailwindcss/nesting": {}, // Tailwind's nesting plugin
    "postcss-nested": {}, // PostCSS nested plugin
    tailwindcss: {},
    autoprefixer: {},
  },
};
