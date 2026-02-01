import React, { useState, useEffect } from 'react';
import ReactMarkdown from 'react-markdown';

export default function MyAnnonces() {
    const [annonces, setAnnonces] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        fetchAnnonces();
    }, []);

    const fetchAnnonces = async () => {
        try {
            const response = await fetch('/api/my/annonces');
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des annonces');
            }
            const data = await response.json();
            setAnnonces(data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const getStatusBadgeClass = (status) => {
        const classes = {
            'PENDING_REVIEW': 'badge bg-warning text-dark',
            'PUBLISHED': 'badge bg-success',
            'REJECTED': 'badge bg-danger',
            'COMPLETED': 'badge bg-secondary',
            'DRAFT': 'badge bg-secondary',
            'ARCHIVED': 'badge bg-dark'
        };
        return classes[status] || 'badge bg-secondary';
    };

    const getStatusLabel = (status) => {
        const labels = {
            'PENDING_REVIEW': '⏳ En attente',
            'PUBLISHED': '✅ En ligne',
            'REJECTED': '❌ Refusée',
            'COMPLETED': '🏁 Terminée',
            'DRAFT': '📝 Brouillon',
            'ARCHIVED': '📦 Archivée'
        };
        return labels[status] || status;
    };

    if (loading) {
        return (
            <div className="container py-5">
                <div className="text-center">
                    <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="container py-5">
            <h1 className="mb-4">
                <i className="bi bi-file-earmark-text me-2"></i>
                Mes Annonces
            </h1>

            {error && (
                <div className="alert alert-danger" role="alert">
                    <i className="bi bi-exclamation-triangle me-2"></i>
                    {error}
                </div>
            )}

            {annonces.length === 0 ? (
                <div className="alert alert-info" role="alert">
                    <h5 className="alert-heading">📭 Aucune annonce</h5>
                    <p className="mb-0">Vous n'avez pas encore créé d'annonce. <a href="/annonce/new" className="alert-link">En créer une maintenant</a> ?</p>
                </div>
            ) : (
                <div className="row">
                    {annonces.map((annonce) => (
                        <div key={annonce.id} className="col-md-6 col-lg-4 mb-4">
                            <div className="card h-100 shadow-sm border-0">
                                {annonce.image && (
                                    <img
                                        src={annonce.image}
                                        className="card-img-top"
                                        alt={annonce.title}
                                        style={{ height: '200px', objectFit: 'cover' }}
                                    />
                                )}
                                <div className="card-body d-flex flex-column">
                                    <div className="mb-2">
                                        <span className={getStatusBadgeClass(annonce.status)}>
                                            {getStatusLabel(annonce.status)}
                                        </span>
                                    </div>
                                    <h5 className="card-title">{annonce.title}</h5>
                                    <p className="card-text text-muted small">
                                        <i className="bi bi-calendar me-1"></i>
                                        {annonce.date}
                                    </p>
                                    
                                    {/* Affichage du motif de refus */}
                                    {annonce.status === 'REJECTED' && annonce.refusalReason && (
                                        <div className="alert alert-danger mb-3" role="alert">
                                            <h6 className="alert-heading mb-2">
                                                <i className="bi bi-exclamation-triangle me-1"></i>
                                                Motif du refus
                                            </h6>
                                            <div className="mb-0 small markdown-content markdown-content--compact">
                                                <ReactMarkdown>{annonce.refusalReason}</ReactMarkdown>
                                            </div>
                                        </div>
                                    )}
                                    
                                    <div className="mt-auto d-flex gap-2">
                                        {/* Bouton Modifier : visible uniquement pour DRAFT, PUBLISHED, REJECTED, PENDING_REVIEW */}
                                        {['DRAFT', 'PUBLISHED', 'REJECTED', 'PENDING_REVIEW'].includes(annonce.status) && (
                                            <a href={`/annonce/${annonce.id}/edit`} className="btn btn-sm btn-warning flex-fill">
                                                <i className="bi bi-pencil me-1"></i>
                                                Modifier
                                            </a>
                                        )}
                                        
                                        {/* Bouton Voir détails */}
                                        <a href={`/annonce/${annonce.id}`} className="btn btn-sm btn-outline-primary flex-fill">
                                            <i className="bi bi-eye me-1"></i>
                                            Voir
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <div className="mt-4">
                <a href="/home" className="btn btn-outline-secondary">
                    <i className="bi bi-arrow-left me-2"></i>
                    Retour au catalogue
                </a>
            </div>
        </div>
    );
}
