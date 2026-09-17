import { expect, test } from '@playwright/test';

/**
 * Smoke coverage for the home page. Assumes the demo seed (or a few real
 * projects) exist in the WordPress instance CI boots via wp-env.
 */
test.describe('Home page', () => {
  test('renders the heading and at least one project', async ({ page }) => {
    await page.goto('/');

    await expect(page.getByRole('heading', { level: 1, name: 'Selected work' })).toBeVisible();

    const cards = page.getByRole('article');
    await expect(cards.first()).toBeVisible();
  });

  test('navigates from a card to the project detail page', async ({ page }) => {
    await page.goto('/');

    const firstProjectLink = page.getByRole('article').first().getByRole('link').first();
    const title = await firstProjectLink.textContent();
    await firstProjectLink.click();

    await expect(page).toHaveURL(/\/projects\/.+/);
    if (title) {
      await expect(page.getByRole('heading', { level: 1 })).toHaveText(title.trim());
    }
  });
});
