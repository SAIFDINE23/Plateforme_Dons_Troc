import React, { useState, useEffect } from 'react';
import ReactMarkdown from 'react-markdown';
import toast, { Toaster } from 'react-hot-toast';

export default function ModerationDashboard() {
    const [annonces, setAnnonces] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [processingId, setProcessingId] = useState(null);
    
    // État pour la modale de refus
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [selectedAnnonce, setSelectedAnnonce] = useState(null);
    const [refusalReason, setRefusalReason] = useState('');
    const [reasonError, setReasonError] = useState('');

    useEffect(() => {
        fetchPendingAnnonces();
    }, []);

    const fetchPendingAnnonces = async () => {
        try {
            const response = await fetch('/api/admin/pending');
            if (!response.ok) {
                throw new Error('Erreur lors du chargement des annonces à modérer');
            }
            const data = await response.json();
            setAnnonces(data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleValidate = async (annonceId) => {
        setProcessingId(annonceId);
        try {
            const response = await fetch(`/api/admin/annonce/${annonceId}/decide`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'validate' }),
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Erreur lors de la validation');
            }

            // Retirer l'annonce de la liste
            setAnnonces(annonces.filter(a => a.id !== annonceId));
            toast.success('Annonce validée et mise en ligne !');
        } catch (err) {
            setError(err.message);
        } finally {
            setProcessingId(null);
        }
    };

    const openRejectModal = (annonce) => {
        setSelectedAnnonce(annonce);
        setRefusalReason('');
        setReasonError('');
        setShowRejectModal(true);
    };

    const closeRejectModal = () => {
        setShowRejectModal(false);
        setSelectedAnnonce(null);
        setRefusalReason('');
        setReasonError('');
    };

    const handleRejectConfirm = async () => {
        // Validation côté client
        if (!refusalReason.trim()) {
            setReasonError('Le motif du refus est obligatoire');
            return;
        }

        if (refusalReason.trim().length < 10) {
            setReasonError('Le motif doit contenir au moins 10 caractères');
            return;
        }

        setProcessingId(selectedAnnonce.id);
        try {
            const response = await fetch(`/api/admin/annonce/${selectedAnnonce.id}/decide`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'reject',
                    reason: refusalReason.trim()
                }),
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Erreur lors du refus');
            }

            // Retirer l'annonce de la liste
            setAnnonces(annonces.filter(a => a.id !== selectedAnnonce.id));
            toast.error('Annonce refusée.');
            closeRejectModal();
        } catch (err) {
            setError(err.message);
        } finally {
            setProcessingId(null);
        }
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
            <Toaster position="top-right" />
            <h1 className="mb-4">
                <i className="bi bi-shield-check me-2 text-warning"></i>
                Espace de Modération
            </h1>

            {error && (
                <div className="alert alert-danger alert-dismissible fade show" role="alert">
                    <i className="bi bi-exclamation-triangle me-2"></i>
                    {error}
                    <button type="button" className="btn-close" onClick={() => setError('')}></button>
                </div>
            )}

            {annonces.length === 0 ? (
                <div className="alert alert-success" role="alert">
                    <h5 className="alert-heading">✅ Aucune annonce à modérer</h5>
                    <p className="mb-0">Bravo ! Toutes les annonces en attente ont été traitées.</p>
                </div>
            ) : (
                <div className="card shadow-sm border-0">
                    <div className="card-header bg-light border-bottom">
                        <h5 className="mb-0">
                            📋 {annonces.length} annonce{annonces.length > 1 ? 's' : ''} à valider
                        </h5>
                    </div>
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-hover mb-0">
                                <thead className="table-light">
                                    <tr>
                                        <th style={{ width: '15%' }}>Date</th>
                                        <th style={{ width: '15%' }}>Campus</th>
                                        <th style={{ width: '30%' }}>Titre</th>
                                        <th style={{ width: '15%' }}>Auteur</th>
                                        <th style={{ width: '25%' }} className="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {annonces.map((annonce) => (
                                        <tr key={annonce.id} className={processingId === annonce.id ? 'opacity-50' : ''}>
                                            <td>
                                                <small className="text-muted">
                                                    <i className="bi bi-calendar me-1"></i>
                                                    {annonce.date}
                                                </small>
                                            </td>
                                            <td>
                                                {annonce.campuses && annonce.campuses.length > 0 ? (
                                                    annonce.campuses.map((campus, idx) => {
                                                        const labels = { CALAIS: 'Calais', DUNKERQUE: 'Dunkerque', BOULOGNE: 'Boulogne', SAINT_OMER: 'Saint-Omer' };
                                                        return (
                                                            <span key={idx} className="badge bg-info me-1 mb-1">
                                                                📍 {labels[campus] || campus}
                                                            </span>
                                                        );
                                                    })
                                                ) : (
                                                    <span className="badge bg-secondary">Non spécifié</span>
                                                )}
                                            </td>
                                            <td>
                                                <strong>{annonce.title}</strong>
                                                <div className="text-muted small mb-0 mt-1">
                                                    <div className="markdown-content markdown-content--compact">
                                                        <ReactMarkdown>{annonce.description}</ReactMarkdown>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <small>
                                                    <i className="bi bi-person me-1"></i>
                                                    {annonce.owner}
                                                </small>
                                            </td>
                                            <td className="text-center">
                                                <a
                                                    className="btn btn-sm btn-primary"
                                                    href={`/admin/moderation/annonce/${annonce.id}`}
                                                    title="Voir les détails et modérer"
                                                >
                                                    <i className="bi bi-eye me-1"></i>
                                                    Voir détails
                                                </a>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}

            {/* Modale de Refus */}
            {showRejectModal && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }} tabIndex="-1">
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content">
                            <div className="modal-header bg-danger text-white">
                                <h5 className="modal-title">
                                    <i className="bi bi-x-circle me-2"></i>
                                    Refuser l'annonce
                                </h5>
                                <button type="button" className="btn-close btn-close-white" onClick={closeRejectModal}></button>
                            </div>
                            <div className="modal-body">
                                <div className="alert alert-warning">
                                    <i className="bi bi-info-circle me-2"></i>
                                    <strong>Annonce :</strong> {selectedAnnonce?.title}
                                </div>
                                <p className="mb-3">
                                    Veuillez indiquer le motif du refus. Ce message sera visible par l'auteur de l'annonce.
                                </p>
                                <div className="mb-3">
                                    <label htmlFor="refusalReason" className="form-label">
                                        Motif du refus <span className="text-danger">*</span>
                                    </label>
                                    <textarea
                                        id="refusalReason"
                                        className={`form-control ${reasonError ? 'is-invalid' : ''}`}
                                        rows="5"
                                        value={refusalReason}
                                        onChange={(e) => {
                                            setRefusalReason(e.target.value);
                                            setReasonError('');
                                        }}
                                        placeholder="Exemple : Photo non conforme, description insuffisante, contenu inapproprié..."
                                        maxLength="500"
                                    ></textarea>
                                    {reasonError && (
                                        <div className="invalid-feedback">{reasonError}</div>
                                    )}
                                    <div className="form-text">
                                        {refusalReason.length}/500 caractères (minimum 10)
                                    </div>
                                </div>
                                <div className="alert alert-info small mb-0">
                                    <i className="bi bi-lightbulb me-1"></i>
                                    <strong>Conseil :</strong> Soyez précis et constructif pour aider l'utilisateur à corriger son annonce.
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button 
                                    type="button" 
                                    className="btn btn-secondary" 
                                    onClick={closeRejectModal}
                                    disabled={processingId}
                                >
                                    Annuler
                                </button>
                                <button 
                                    type="button" 
                                    className="btn btn-danger"
                                    onClick={handleRejectConfirm}
                                    disabled={processingId}
                                >
                                    {processingId ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2"></span>
                                            Refus en cours...
                                        </>
                                    ) : (
                                        <>
                                            <i className="bi bi-x-circle me-2"></i>
                                            Confirmer le refus
                                        </>
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <div className="mt-4">
                <a href="/home" className="btn btn-outline-secondary">
                    <i className="bi bi-arrow-left me-2"></i>
                    Retour
                </a>
            </div>
        </div>
    );
}
