describe('Carte Blanche - Smoke Test', () => {
    it('La page d\'accueil se charge correctement', () => {
        cy.visit('/');
        cy.get('body').should('be.visible');
    });

    it('La page retourne un status 200', () => {
        cy.request('/').then((response) => {
            expect(response.status).to.eq(200);
        });
    });
});