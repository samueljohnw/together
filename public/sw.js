self.addEventListener('push', (event) => {
    event.waitUntil(self.registration.showNotification('Together', {
        body: 'There’s new activity in your shared space.',
        icon: '/icons/together.svg',
        badge: '/icons/together.svg',
        tag: 'together-new-activity',
        data: { url: '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const existingWindow = windows.find((client) => 'focus' in client);

        if (existingWindow) {
            await existingWindow.focus();
            existingWindow.navigate(event.notification.data?.url ?? '/');
            return;
        }

        await self.clients.openWindow(event.notification.data?.url ?? '/');
    })());
});
