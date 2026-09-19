import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

type ManyToManyFixture = {
    ingredientEditRoute: string;
    recipeEditRoute: string;
};

const supportDir = dirname(fileURLToPath(import.meta.url));
const seedScript = readFileSync(join(supportDir, 'seed', 'seed-many-to-many.php'), 'utf8');

/** Seed paired recipe and ingredient sections with a genuine inverse relationship. */
export async function seedManyToManyFixture(context: ScreenshotSetupContext): Promise<ManyToManyFixture> {
    const output = await context.runCraftScript(seedScript, { label: 'seed-many-to-many' });
    const fixture = JSON.parse(output.trim()) as ManyToManyFixture;

    if (!fixture.ingredientEditRoute || !fixture.recipeEditRoute) {
        throw new Error(`Invalid Many to Many fixture payload: ${output}`);
    }

    return fixture;
}
