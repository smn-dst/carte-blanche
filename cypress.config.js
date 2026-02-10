// cypress.config.js
const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://127.0.0.1:8888',
    supportFile: false,
    specPattern: 'tests/e2e/**/*.cy.{js,jsx,ts,tsx}',
  },
});