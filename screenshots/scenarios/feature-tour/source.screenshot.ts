import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedManyToManyFixture } from '../../support/fixtures';

let ingredientEditRoute = '/admin/entries';

export default defineScreenshotScenario({
    id: 'many-to-many-feature-tour-source',
    output: 'feature-tour/many-to-many-source.png',
    route: () => ingredientEditRoute,
    viewport: { width: 1180, height: 720, deviceScaleFactor: 2 },
    async setup(context) {
        const fixture = await seedManyToManyFixture(context);
        ingredientEditRoute = fixture.ingredientEditRoute;
    },
    waitFor: [
        { type: 'selector', selector: '.field:has([name^="fields[relatedRecipes]"])', state: 'visible', timeout: 30000 },
        { type: 'text', text: 'Chicken noodle soup' },
        { type: 'text', text: 'Chicken enchiladas' },
    ],
    preSteps: [{ type: 'wait', waitFor: { type: 'timeout', ms: 250 } }],
    target: {
        type: 'anchoredClip',
        selector: '.field:has([name^="fields[relatedRecipes]"])',
        x: 0,
        y: -12,
        width: 760,
        height: 132,
    },
    caption: 'A Craft entry relating one ingredient to multiple recipes.',
    intent: 'Show the source side of a real Craft relationship using Craft’s native element selector.',
});
