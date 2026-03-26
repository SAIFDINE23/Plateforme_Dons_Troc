import React, { useEffect, useState } from 'react';

export default function MessagesNavBadge() {
    const [count, setCount] = useState(0);

    const loadMessageNotifications = async () => {
        try {
            const response = await fetch('/api/notifications');
            if (!response.ok) return;

            const data = await response.json();
            const notifArray = Array.isArray(data) ? data : [];
            const messageCount = notifArray.filter((notification) => notification.type === 'NEW_MESSAGE').length;
            setCount(messageCount);
        } catch {
            // ignore
        }
    };

    useEffect(() => {
        loadMessageNotifications();
        const interval = setInterval(loadMessageNotifications, 20000);
        return () => clearInterval(interval);
    }, []);

    if (count <= 0) {
        return null;
    }

    return <span className="badge bg-danger ms-1">{count}</span>;
}
