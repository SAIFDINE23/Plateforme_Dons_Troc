import React, { useEffect, useState, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Bell, BellRing, CheckCircle, XCircle, MessageCircle } from 'lucide-react';

export default function NotificationBell() {
    const [notifications, setNotifications] = useState([]);
    const [open, setOpen] = useState(false);
    const [hasNewNotification, setHasNewNotification] = useState(false);
    const prevCountRef = useRef(0);

    const loadNotifications = async () => {
        try {
            const response = await fetch('/api/notifications');
            if (!response.ok) return;
            const data = await response.json();
            const notifArray = Array.isArray(data) ? data : [];
            
            // Détecter nouvelle notification pour animation
            if (notifArray.length > prevCountRef.current) {
                setHasNewNotification(true);
                setTimeout(() => setHasNewNotification(false), 2000);
            }
            
            prevCountRef.current = notifArray.length;
            setNotifications(notifArray);
        } catch {
            // ignore
        }
    };

    useEffect(() => {
        loadNotifications();
        const interval = setInterval(loadNotifications, 20000);
        return () => clearInterval(interval);
    }, []);

    const normalizeLink = (link) => {
        if (!link) return '';
        const trimmed = String(link).trim();
        if (!trimmed) return '';

        let path = trimmed;
        try {
            const url = new URL(trimmed, window.location.origin);
            path = `${url.pathname}${url.search}${url.hash}`;
        } catch {
            path = trimmed;
        }

        path = path.replace(/^\/annonces\//, '/annonce/');
        path = path.replace(/(^|\/)annonces\//, '$1annonce/');
        if (!path.startsWith('/')) {
            path = `/${path}`;
        }
        return path;
    };

    const handleNotificationClick = async (notification) => {
        try {
            await fetch(`/api/notifications/${notification.id}/read`, {
                method: 'PATCH',
                keepalive: true,
            });
        } finally {
            setNotifications((prev) => prev.filter((n) => n.id !== notification.id));
            setOpen(false);
        }
    };

    // Icône selon le type de notification
    const getNotificationIcon = (type) => {
        switch (type) {
            case 'VALIDATION':
                return <CheckCircle size={18} className="text-success" />;
            case 'REFUSAL':
                return <XCircle size={18} className="text-danger" />;
            case 'NEW_MESSAGE':
                return <MessageCircle size={18} className="text-info" />;
            default:
                return <Bell size={18} />;
        }
    };

    // Animation de sonnerie
    const bellAnimation = hasNewNotification ? {
        rotate: [0, -15, 15, -15, 15, 0],
        transition: { duration: 0.5 }
    } : {};

    return (
        <div className="dropdown notification-bell">
            <motion.button
                className="btn btn-light position-relative"
                type="button"
                onClick={() => setOpen(!open)}
                animate={bellAnimation}
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
            >
                {hasNewNotification ? (
                    <BellRing size={20} style={{ color: '#F07D00' }} />
                ) : (
                    <Bell size={20} style={{ color: '#004E86' }} />
                )}
                
                <AnimatePresence>
                    {notifications.length > 0 && (
                        <motion.span
                            initial={{ scale: 0 }}
                            animate={{ scale: 1 }}
                            exit={{ scale: 0 }}
                            className="badge-notification"
                        >
                            {notifications.length}
                        </motion.span>
                    )}
                </AnimatePresence>
            </motion.button>

            <AnimatePresence>
                {open && (
                    <motion.div
                        initial={{ opacity: 0, y: -10, scale: 0.95 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: -10, scale: 0.95 }}
                        transition={{ duration: 0.2 }}
                        className="dropdown-menu dropdown-menu-end show shadow"
                        style={{ minWidth: '340px', maxHeight: '400px', overflowY: 'auto', zIndex: 1050 }}
                    >
                        <h6 className="dropdown-header d-flex align-items-center gap-2" style={{ color: '#004E86' }}>
                            <Bell size={18} />
                            <span>Notifications</span>
                            {notifications.length > 0 && (
                                <span className="badge bg-danger ms-auto">{notifications.length}</span>
                            )}
                        </h6>
                        
                        {notifications.length === 0 ? (
                            <div className="dropdown-item-text text-center text-muted py-4">
                                <Bell size={32} className="mb-2 opacity-25" />
                                <p className="mb-0 small">Aucune notification</p>
                            </div>
                        ) : (
                            notifications.map((notification, index) => {
                                const normalizedLink = normalizeLink(notification.link) || '/';
                                return (
                                <motion.a
                                    key={notification.id}
                                    initial={{ opacity: 0, x: -20 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    transition={{ delay: index * 0.05 }}
                                    className="dropdown-item text-wrap border-bottom py-3"
                                    href={normalizedLink}
                                    onClick={() => handleNotificationClick(notification)}
                                    style={{ 
                                        transition: 'background-color 0.2s',
                                        cursor: 'pointer'
                                    }}
                                    onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#F4F7FA'}
                                    onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
                                >
                                    <div className="d-flex align-items-start gap-2">
                                        <div className="mt-1">
                                            {getNotificationIcon(notification.type)}
                                        </div>
                                        <div className="flex-grow-1">
                                            <div className="fw-medium mb-1" style={{ fontSize: '0.9rem' }}>
                                                {notification.message}
                                            </div>
                                            <div className="small text-muted">
                                                {(() => {
                                                    if (!notification.createdAt) return 'Date inconnue';
                                                    const date = new Date(notification.createdAt);
                                                    if (isNaN(date.getTime())) return 'Date invalide';
                                                    return date.toLocaleDateString('fr-FR', {
                                                        day: 'numeric',
                                                        month: 'short',
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    });
                                                })()}
                                            </div>
                                        </div>
                                    </div>
                                </motion.a>
                                );
                            })
                        )}
                    </motion.div>
                )}
            </AnimatePresence>

            {/* Fermeture au clic extérieur */}
            {open && (
                <div
                    className="position-fixed top-0 start-0 w-100 h-100"
                    style={{ zIndex: 1040 }}
                    onClick={() => setOpen(false)}
                />
            )}
        </div>
    );
}
