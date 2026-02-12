// eslint.config.js
import js from "@eslint/js";

export default [
  js.configs.recommended,
  {
    files: ["assets/**/*.js"],
    rules: {
      "no-console": "warn",
      "no-unused-vars": "warn",
    },
  },
  {
    ignores: ["node_modules/", "vendor/", "public/", "var/"],
  },
];