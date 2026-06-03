const page = await browser.newPage('yakro-dd');
await page.goto('https://esbtp-yakro.klassci.com/login');
await page.waitForLoadState('networkidle');
await page.fill('input[name="login"]', 'superadmin');
await page.fill('input[name="password"]', 'Bonjour@123');
await page.click('button[type="submit"]');
await page.waitForLoadState('networkidle');
await page.goto('https://esbtp-yakro.klassci.com/esbtp/classes');
await page.waitForLoadState('networkidle');
await page.waitForTimeout(2000);

// Find first card kebab
const kebab = await page.$('.ci-card-kebab');
if (!kebab) {
    console.log('No kebab found');
} else {
    const beforeRect = await kebab.evaluate(el => {
        const card = el.closest('.ci-card');
        const cs = card ? getComputedStyle(card) : null;
        return {
            kebabRect: el.getBoundingClientRect(),
            cardTransform: cs ? cs.transform : 'no card',
        };
    });
    console.log('Before click:', JSON.stringify(beforeRect, null, 2));
    
    // Click
    await kebab.click();
    await page.waitForTimeout(500);
    
    // Find the menu
    const menuInfo = await page.evaluate(() => {
        const menu = document.querySelector('.dropdown-menu.show');
        if (!menu) return { found: false };
        const cs = getComputedStyle(menu);
        return {
            found: true,
            parentTag: menu.parentElement.tagName,
            parentClass: menu.parentElement.className,
            teleported: menu.dataset.klassciTeleported,
            position: cs.position,
            top: cs.top,
            left: cs.left,
            menuRect: menu.getBoundingClientRect(),
        };
    });
    console.log('After click:', JSON.stringify(menuInfo, null, 2));
    
    const buf = await page.screenshot({ fullPage: false });
    await saveScreenshot(buf, 'yakro-ddtest.png');
}
