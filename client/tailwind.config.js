/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        rpg: {
          dark: "#0f172a",
          gold: "#fbbf24",
          blood: "#991b1b",
          mana: "#1e40af",
        }
      }
    },
  },
  plugins: [],
}
