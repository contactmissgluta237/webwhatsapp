/** @type {import('jest').Config} */
const config = {
    // L'environnement de test par défaut est node, ce qui est correct pour notre cas
    testEnvironment: 'node',

    // On dit à Jest d'ignorer ces répertoires lors de la recherche de tests
    testPathIgnorePatterns: [
        '/node_modules/',
        '/vendor/' 
    ],

    // On configure les fake timers globalement pour mieux contrôler les promesses et timeouts
    fakeTimers: {
        "enableGlobally": true
    }
};

module.exports = config;
