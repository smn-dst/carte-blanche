import js from "@eslint/js";
import globals from "globals";
import cypress from "eslint-plugin-cypress/flat";

export default [
  js.configs.recommended,
  {
    files: ["assets/**/*.js"],
    languageOptions: {
      globals: {
        ...globals.browser,
      },
    },
    rules: {
      "no-console": "warn",
      "no-unused-vars": "warn",
    },
  },
  {
    files: ["commitlint.config.js", "cypress.config.js"],
    languageOptions: {
      globals: {
        ...globals.node,
      },
    },
  },
  {
    files: ["tests/e2e/**/*.js", "cypress/**/*.js"],
    ...cypress.configs.recommended,
  },
  {
    ignores: ["node_modules/", "vendor/", "public/", "var/"],
  },
];