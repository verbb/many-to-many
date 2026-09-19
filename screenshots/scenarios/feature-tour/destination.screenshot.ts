import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedManyToManyFixture } from '../../support/fixtures';

let recipeEditRoute = '/admin/entries';

export default defineScreenshotScenario({
    id: 'many-to-many-feature-tour-destination',
    output: 'feature-tour/many-to-many-destination.png',
    route: () => recipeEditRoute,
    viewport: { width: 1180, height: 720, deviceScaleFactor: 2 },
    async setup(context) {
        const fixture = await seedManyToManyFixture(context);
        recipeEditRoute = fixture.recipeEditRoute;
    },
    waitFor: [
        { type: 'selector', selector: '.js-mtm-field', state: 'visible', timeout: 30000 },
        { type: 'text', text: '1 lb chopped chicken' },
        { type: 'text', text: 'Chicken broth' },
        { type: 'text', text: '1 lb chopped carrots' },
    ],
    preSteps: [{ type: 'wait', waitFor: { type: 'timeout', ms: 250 } }],
    // The inverse field has no view-mode setting, so use Craft's native inline
    // chip presentation for this compact feature-page composition.
    steps: [{
        type: 'locatorEvaluate',
        selector: '.js-mtm-field .elements.chips',
        expression: 'elements.forEach((element) => element.classList.add("inline-chips"))',
    }],
    target: {
        type: 'anchoredClip',
        selector: '.field:has(.js-mtm-field)',
        x: 0,
        y: -12,
        width: 760,
        height: 132,
    },
    caption: 'The same Craft relationship editable from its inverse recipe entry.',
    intent: 'Show the real Many to Many field resolving the related ingredient entries on the other side.',
});
