import { expect, test } from '@playwright/test';

const users = {
    admin: {
        email: 'admin@itapevi.sp.leg.br',
        password: 'password',
        panel: '/gestao/login',
        expectedPath: '/gestao',
        expectedText: 'Revisão de cursos',
    },
    teacher: {
        email: 'professor@itapevi.sp.leg.br',
        password: 'password',
        panel: '/professor/login',
        expectedPath: '/professor',
        expectedText: 'Meus cursos',
    },
    student: {
        email: 'aluno@itapevi.sp.leg.br',
        password: 'password',
        panel: '/aluno/login',
        expectedPath: '/aluno',
        expectedText: 'Meus cursos',
    },
};

async function login(page, user) {
    await page.goto(user.panel);
    await expect(page.getByRole('heading', { name: 'Bem-vindo de volta!' })).toBeVisible();
    await page.getByLabel('E-mail').fill(user.email);
    await page.locator('#password').fill(user.password);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await page.waitForURL((url) => url.pathname === user.expectedPath, { timeout: 30_000 });
}

test.describe('autenticação e painéis', () => {
    for (const [role, user] of Object.entries(users)) {
        test(`${role} acessa o painel correto`, async ({ page }) => {
            await login(page, user);
            await expect(page.getByText('Painel').first()).toBeVisible();
            await expect(page.getByText(user.expectedText).first()).toBeVisible();
        });
    }
});
