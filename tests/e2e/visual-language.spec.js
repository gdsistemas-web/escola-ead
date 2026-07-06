import { expect, test } from '@playwright/test';

const forbiddenFragments = [
    'View ',
    'Edit ',
    'Create ',
    'Dashboard',
    'Toggle password visibility',
    'Ãƒ',
    'Ã‚',
    'ï¿½',
];

test.describe('idioma e polimento visual', () => {
    test('login do gestor não exibe ações falsas ou textos quebrados', async ({ page }) => {
        await page.goto('/gestao/login');

        await expect(page.getByRole('heading', { name: 'Bem-vindo de volta!' })).toBeVisible();
        await expect(page.getByText('Conhecimento que transforma vidas e constrói o futuro.')).toBeVisible();
        await expect(page.getByText('Google')).toHaveCount(0);
        await expect(page.getByText('Microsoft')).toHaveCount(0);

        const body = await page.locator('body').innerText();
        for (const fragment of forbiddenFragments) {
            expect(body).not.toContain(fragment);
        }
    });

    test('login público não exibe botões sociais sem integração', async ({ page }) => {
        await page.goto('/login');

        await expect(page.getByRole('heading', { name: 'Bem-vindo de volta!' })).toBeVisible();
        await expect(page.getByText('Google')).toHaveCount(0);
        await expect(page.getByText('Microsoft')).toHaveCount(0);

        const body = await page.locator('body').innerText();
        for (const fragment of forbiddenFragments) {
            expect(body).not.toContain(fragment);
        }
    });
});
