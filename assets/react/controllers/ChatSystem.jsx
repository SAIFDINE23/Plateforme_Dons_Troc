import React, { useEffect, useMemo, useState } from 'react';

export default function ChatSystem() {
    const [conversations, setConversations] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [loading, setLoading] = useState(true);

    const queryConversationId = useMemo(() => {
        const params = new URLSearchParams(window.location.search);
        return params.get('conversation');
    }, []);

    const loadConversations = async () => {
        try {
            const response = await fetch('/api/conversations');
            if (!response.ok) return;
            const data = await response.json();
            setConversations(Array.isArray(data) ? data : []);
        } finally {
            setLoading(false);
        }
    };

    const loadMessages = async (conversationId) => {
        if (!conversationId) return;
        const response = await fetch(`/api/conversations/${conversationId}/messages`);
        if (!response.ok) return;
        const data = await response.json();
        setMessages(Array.isArray(data) ? data : []);
    };

    useEffect(() => {
        loadConversations();
        const interval = setInterval(() => {
            loadConversations();
            if (selectedId) {
                loadMessages(selectedId);
            }
        }, 5000);
        return () => clearInterval(interval);
    }, [selectedId]);

    useEffect(() => {
        if (queryConversationId) {
            setSelectedId(queryConversationId);
            loadMessages(queryConversationId);
        }
    }, [queryConversationId]);

    const handleSelect = (conversationId) => {
        setSelectedId(conversationId);
        loadMessages(conversationId);
    };

    const handleSend = async (e) => {
        e.preventDefault();
        if (!selectedId || !newMessage.trim()) return;

        const response = await fetch(`/api/conversations/${selectedId}/send`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ content: newMessage.trim() }),
        });

        if (response.ok) {
            setNewMessage('');
            await loadMessages(selectedId);
            await loadConversations();
        }
    };

    const selectedConversation = conversations.find((c) => c.id === selectedId);

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
                                            <div className="small text-muted text-truncate">
                                                {conv.otherUser?.name}
                                            </div>
                                            <div className="small text-truncate">
                                                {conv.lastMessage?.content || 'Aucun message'}
                                            </div>
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
                            <div className="flex-grow-1 overflow-auto mb-3">
                                {messages.length === 0 && (
                                    <div className="text-muted">Aucun message</div>
                                )}
                                {messages.map((msg) => (
                                    <div
                                        key={msg.id}
                                        className={`d-flex mb-2 ${msg.isMine ? 'justify-content-end' : 'justify-content-start'}`}
                                    >
                                        <div className={`px-3 py-2 rounded ${msg.isMine ? 'bg-primary text-white' : 'bg-light'}`}>
                                            <div className="small">{msg.content}</div>
                                            <div className={`small mt-1 ${msg.isMine ? 'text-white-50' : 'text-muted'}`} style={{ fontSize: '0.75rem' }}>
                                                {msg.createdAt}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <form onSubmit={handleSend} className="d-flex gap-2">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Écrire un message..."
                                    value={newMessage}
                                    onChange={(e) => setNewMessage(e.target.value)}
                                    disabled={!selectedId}
                                />
                                <button className="btn btn-primary" type="submit" disabled={!selectedId}>
                                    Envoyer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
