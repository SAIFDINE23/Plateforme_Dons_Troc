import React, { useEffect, useMemo, useRef, useState, useCallback } from 'react';
import { Send } from 'lucide-react';

export default function Messenger({ conversationId = null, mercureUrl = '', currentUserId = null }) {
    const [conversations, setConversations] = useState([]);
    const [selectedId, setSelectedId] = useState(conversationId);
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);
    const messagesEndRef = useRef(null);
    const eventSourceRef = useRef(null);

    const fallbackConversationId = useMemo(() => {
        const params = new URLSearchParams(window.location.search);
        return params.get('conversation');
    }, []);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    const loadConversations = useCallback(async () => {
        try {
            const response = await fetch('/api/mes-conversations');
            if (!response.ok) return;
            const data = await response.json();
            setConversations(Array.isArray(data) ? data : []);
        } catch (err) {
            console.error('Erreur chargement conversations:', err);
        } finally {
            setLoading(false);
        }
    }, []);

    const loadMessages = useCallback(async (conversationIdToLoad) => {
        if (!conversationIdToLoad) return;
        try {
            const response = await fetch(`/api/conversations/${conversationIdToLoad}/messages`);
            if (!response.ok) return;
            const data = await response.json();
            setMessages(Array.isArray(data) ? data : []);
        } catch (err) {
            console.error('Erreur chargement messages:', err);
        }
    }, []);

    useEffect(() => {
        loadConversations();
    }, [loadConversations]);

    useEffect(() => {
        if (selectedId) {
            loadMessages(selectedId);
        }
    }, [selectedId, loadMessages]);

    useEffect(() => {
        if (!selectedId && conversationId) {
            setSelectedId(conversationId);
        }
    }, [conversationId, selectedId]);

    useEffect(() => {
        if (!selectedId && fallbackConversationId) {
            setSelectedId(fallbackConversationId);
        }
    }, [fallbackConversationId, selectedId]);

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    // Mercure SSE avec reconnexion automatique
    useEffect(() => {
        if (!mercureUrl || !currentUserId) return undefined;

        let retryTimeout = null;
        let isCancelled = false;

        const connect = () => {
            if (isCancelled) return;

            // Fermer la connexion précédente
            if (eventSourceRef.current) {
                eventSourceRef.current.close();
            }

            const url = new URL(mercureUrl);
            url.searchParams.append('topic', `/users/${currentUserId}/conversations`);
            if (selectedId) {
                url.searchParams.append('topic', `/conversations/${selectedId}`);
            }

            const es = new EventSource(url.toString());
            eventSourceRef.current = es;

            es.onmessage = (event) => {
                try {
                    const payload = JSON.parse(event.data);
                    if (payload?.type === 'message' && payload?.conversationId === selectedId) {
                        loadMessages(selectedId);
                    }
                    if (payload?.type === 'conversation_updated') {
                        loadConversations();
                    }
                } catch (error) {
                    console.error('Mercure payload error', error);
                }
            };

            es.onerror = () => {
                es.close();
                eventSourceRef.current = null;
                // Reconnexion automatique après 3 secondes
                if (!isCancelled) {
                    retryTimeout = setTimeout(connect, 3000);
                }
            };
        };

        connect();

        return () => {
            isCancelled = true;
            if (retryTimeout) clearTimeout(retryTimeout);
            if (eventSourceRef.current) {
                eventSourceRef.current.close();
                eventSourceRef.current = null;
            }
        };
    }, [mercureUrl, currentUserId, selectedId, loadMessages, loadConversations]);

    // Polling de secours toutes les 15s (si Mercure est indisponible)
    useEffect(() => {
        const interval = setInterval(() => {
            if (selectedId) loadMessages(selectedId);
            loadConversations();
        }, 15000);
        return () => clearInterval(interval);
    }, [selectedId, loadMessages, loadConversations]);

    const handleSelect = (conversationIdToSelect) => {
        setSelectedId(conversationIdToSelect);
    };

    const handleSend = async (e) => {
        e.preventDefault();
        if (!selectedId || !newMessage.trim() || sending) return;

        setSending(true);
        try {
            const response = await fetch(`/api/conversations/${selectedId}/messages`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content: newMessage.trim() }),
            });

            if (response.ok) {
                setNewMessage('');
                await loadMessages(selectedId);
                await loadConversations();
            }
        } catch (err) {
            console.error('Erreur envoi message:', err);
        } finally {
            setSending(false);
        }
    };

    const selectedConversation = conversations.find((c) => c.id === selectedId);

    const formatDate = (value) => {
        if (!value) return '';
        const parsed = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return value;
        return parsed.toLocaleString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    if (loading) {
        return (
            <div className="container py-5">
                <div className="text-center">
                    <div className="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid py-4">
            <div className="row g-3" style={{ minHeight: '70vh' }}>
                <div className="col-lg-4">
                    <div className="card shadow-sm h-100">
                        <div className="card-header bg-light">
                            <strong>Mes conversations</strong>
                        </div>
                        <div className="list-group list-group-flush">
                            {conversations.length === 0 && (
                                <div className="p-3 text-muted">Aucune conversation</div>
                            )}
                            {conversations.map((conv) => (
                                <button
                                    key={conv.id}
                                    className={`list-group-item list-group-item-action ${selectedId === conv.id ? 'active' : ''}`}
                                    onClick={() => handleSelect(conv.id)}
                                >
                                    <div className="d-flex gap-3 align-items-center">
                                        <img
                                            src={conv.annonce?.image || 'https://placehold.co/64x64'}
                                            alt="annonce"
                                            width="48"
                                            height="48"
                                            className="rounded object-fit-cover"
                                        />
                                        <div className="flex-grow-1 text-start">
                                            <div className="fw-bold text-truncate">
                                                {conv.annonce?.title}
                                            </div>
                                            <div
                                                className="small text-muted text-truncate d-block w-100"
                                                title={conv.otherUser?.name || 'Utilisateur'}
                                            >
                                                @{conv.otherUser?.name || 'utilisateur'}
                                            </div>
                                            <div className="small text-truncate">
                                                {conv.lastMessage?.content || 'Aucun message'}
                                            </div>
                                        </div>
                                        <div className="small text-muted text-end" style={{ minWidth: '80px' }}>
                                            {formatDate(conv.lastMessage?.createdAt || conv.updatedAt)}
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="col-lg-8">
                    <div className="card shadow-sm h-100">
                        <div className="card-header bg-light">
                            <strong>
                                {selectedConversation?.annonce?.title || 'Sélectionnez une conversation'}
                            </strong>
                        </div>
                        <div className="card-body d-flex flex-column" style={{ minHeight: '60vh' }}>
                            <div
                                className="flex-grow-1 overflow-auto mb-3"
                                style={{ maxHeight: '55vh' }}
                            >
                                {messages.length === 0 && (
                                    <div className="text-muted">Aucun message</div>
                                )}
                                {messages.map((msg) => (
                                    <div
                                        key={msg.id}
                                        className={`d-flex mb-2 ${msg.isMine ? 'justify-content-end' : 'justify-content-start'}`}
                                    >
                                        <div
                                            className={`px-3 py-2 rounded ${msg.isMine ? 'bg-primary text-white' : 'bg-light'}`}
                                            style={{ maxWidth: '75%' }}
                                        >
                                            <div className="small">{msg.content}</div>
                                            <div
                                                className={`small mt-1 ${msg.isMine ? 'text-white-50' : 'text-muted'}`}
                                                style={{ fontSize: '0.75rem' }}
                                            >
                                                {formatDate(msg.createdAt)}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                <div ref={messagesEndRef} />
                            </div>

                            <form onSubmit={handleSend} className="d-flex gap-2">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Écrire un message..."
                                    value={newMessage}
                                    onChange={(e) => setNewMessage(e.target.value)}
                                    disabled={!selectedId || sending}
                                    maxLength={5000}
                                />
                                <button
                                    className="btn btn-primary d-flex align-items-center gap-2"
                                    type="submit"
                                    disabled={!selectedId || sending || !newMessage.trim()}
                                >
                                    <Send size={18} />
                                    {sending ? 'Envoi...' : 'Envoyer'}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}