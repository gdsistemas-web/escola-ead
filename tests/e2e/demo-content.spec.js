import { expect, test } from '@playwright/test';

const credentials = {
    admin: {
        email: 'admin@itapevi.sp.leg.br',
        password: 'password',
        panel: '/gestao/login',
        expectedPath: '/gestao',
    },
    teacher: {
        email: 'professor@itapevi.sp.leg.br',
        password: 'password',
        panel: '/professor/login',
        expectedPath: '/professor',
    },
    student: {
        email: 'aluno@itapevi.sp.leg.br',
        password: 'password',
        panel: '/aluno/login',
        expectedPath: '/aluno',
    },
};

async function login(page, user) {
    await page.goto(user.panel);
    await page.getByLabel('E-mail').fill(user.email);
    await page.locator('#password').fill(user.password);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await page.waitForURL((url) => url.pathname === user.expectedPath, { timeout: 30_000 });
}

test.describe('conteúdo demo nos painéis', () => {
    test('aluno encontra curso matriculado e conversa com professor', async ({ page }) => {
        await login(page, credentials.student);

        await page.goto('/aluno/cursos');
        await expect(page.getByText('Poder Legislativo Municipal').first()).toBeVisible();
        await expect(page.getByText('100%').first()).toBeVisible();

        await page.goto('/aluno/comunicacao');
        await page.getByRole('button', { name: /Poder Legislativo Municipal/ }).click();
        await expect(page.getByText('Professor, posso usar audiência pública').first()).toBeVisible();
    });

    test('professor encontra curso e chat ativo', async ({ page }) => {
        await login(page, credentials.teacher);

        await page.goto('/professor/cursos');
        await expect(page.getByText('Poder Legislativo Municipal').first()).toBeVisible();
        await expect(page.getByText('Ver curso').first()).toBeVisible();

        await page.goto('/professor/comunicacao');
        await expect(page.getByRole('button', { name: /Aluno EPI/ }).first()).toBeVisible();
        await page.getByRole('button', { name: /Aluno EPI/ }).first().click();
        await expect(page.getByText('Pode sim. Relacione o exemplo').first()).toBeVisible();
    });

    test('gestor encontra curso aguardando revisão', async ({ page }) => {
        await login(page, credentials.admin);

        await page.goto('/gestao/revisao-cursos');
        await expect(page.getByText('Ética e Transparência Pública').first()).toBeVisible();
        await expect(page.getByText('Professor EPI').first()).toBeVisible();
    });
});
