describe('Pages légales', () => {
    ['/politique-de-confidentialite', '/conditions-generales-de-vente', '/conditions-generales-utilisation'].forEach((path) => {
        it(`charge ${path}`, () => {
            cy.visit(path);
            cy.get('body').should('be.visible');
        });
    });
});
